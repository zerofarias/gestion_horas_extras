<?php

class SurveyAdminController {
    private $surveyModel;
    private $companyModel;

    public function __construct() {
        if (!hasRole('admin')) {
            redirect('login');
        }
        if (!surveys_is_ready()) {
            $_SESSION['flash_error'] = 'Ejecutá migration_surveys.sql en MySQL (ver MIGRATIONS.md).';
            redirect('notificationsAdmin/index');
        }
        ensureAdminCompanySession();
        $this->surveyModel = new Survey();
        $this->companyModel = new Company();
    }

    public function index() {
        $companyId = requireAdminCompany('admin/dashboard');
        $this->view('admin/surveys/list', [
            'surveys' => $this->surveyModel->getByCompany($companyId),
            'company_id' => $companyId,
        ]);
    }

    public function edit($id = 0) {
        $companyId = requireAdminCompany('surveyAdmin/index');
        $id = (int)$id;
        $survey = $id > 0 ? $this->surveyModel->getById($id) : null;
        if ($survey && (int)$survey->company_id !== $companyId) {
            redirect('surveyAdmin/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $title = trim($_POST['title'] ?? '');
            if ($title === '') {
                $_SESSION['flash_error'] = 'El título es obligatorio.';
                redirect('surveyAdmin/edit/' . $id);
            }
            $data = [
                'title' => $title,
                'description' => trim($_POST['description'] ?? ''),
                'is_anonymous' => !empty($_POST['is_anonymous']),
                'open_at' => trim($_POST['open_at'] ?? '') ?: null,
                'close_at' => trim($_POST['close_at'] ?? '') ?: null,
            ];
            if ($id <= 0) {
                $data['company_id'] = $companyId;
                $data['created_by'] = (int)$_SESSION['user_id'];
                $data['status'] = 'draft';
                $newId = $this->surveyModel->create($data);
                if ($newId > 0) {
                    $this->surveyModel->saveAssignmentsFromPost($newId, $_POST);
                    if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
                        if (!$this->surveyModel->saveQuestionsFromPost($newId, $_POST['questions'])) {
                            $_SESSION['flash_error'] = 'Agregá al menos una pregunta con enunciado.';
                            redirect('surveyAdmin/edit/' . $newId);
                        }
                    }
                    $_SESSION['flash_success'] = 'Encuesta creada.';
                    redirect('surveyAdmin/edit/' . $newId);
                }
            } else {
                $this->surveyModel->update($id, $data);
                $isPublished = ($survey->status === 'published');
                if (!$isPublished) {
                    $this->surveyModel->saveAssignmentsFromPost($id, $_POST);
                    if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                        if (!$this->surveyModel->saveQuestionsFromPost($id, $_POST['questions'])) {
                            $_SESSION['flash_error'] = 'Debe haber al menos una pregunta con enunciado.';
                            redirect('surveyAdmin/edit/' . $id);
                        }
                    }
                    $_SESSION['flash_success'] = 'Encuesta guardada.';
                } else {
                    $_SESSION['flash_success'] = 'Datos generales actualizados. La encuesta publicada no permite cambiar preguntas ni destinatarios.';
                }
                redirect('surveyAdmin/edit/' . $id);
            }
        }

        $assignments = $id > 0 ? $this->surveyModel->getAssignments($id) : [];
        $selected = $this->surveyModel->assignmentsToTargetOptions($assignments);
        $this->view('admin/surveys/edit', [
            'survey' => $survey,
            'questions' => $id > 0 ? $this->surveyModel->getQuestions($id) : [],
            'companies' => $this->companyModel->getAllCompanies(),
            'areas' => (new Area())->getAvailableForCompany($companyId),
            'users' => (new User())->getUsersByCompany($companyId),
            'selected' => $selected,
            'question_types' => survey_question_types(),
            'is_new' => $id <= 0,
        ]);
    }

    public function publish($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('surveyAdmin/index');
        }
        csrf_verify();
        $id = (int)$id;
        $companyId = requireAdminCompany('surveyAdmin/index');
        $survey = $this->surveyModel->getById($id);
        if (!$survey || (int)$survey->company_id !== $companyId) {
            redirect('surveyAdmin/index');
        }
        if (empty($this->surveyModel->getQuestions($id))) {
            $_SESSION['flash_error'] = 'Agregá al menos una pregunta antes de publicar.';
            redirect('surveyAdmin/edit/' . $id);
        }
        if (!$this->surveyModel->hasExplicitAssignments($id)) {
            $_SESSION['flash_error'] = 'Definí destinatarios (empresa, área o empleados) antes de publicar.';
            redirect('surveyAdmin/edit/' . $id);
        }
        $audience = $this->surveyModel->resolveAudienceUserIds($id);
        if (empty($audience)) {
            $_SESSION['flash_error'] = 'No hay empleados en la audiencia seleccionada.';
            redirect('surveyAdmin/edit/' . $id);
        }
        $this->surveyModel->setStatus($id, 'published');
        $n = $this->surveyModel->notifyAudience($this->surveyModel->getById($id));
        $_SESSION['flash_success'] = 'Encuesta publicada. Notificaciones enviadas a ' . $n . ' empleado(s).';
        redirect('surveyAdmin/index');
    }

    public function close($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('surveyAdmin/index');
        }
        csrf_verify();
        $id = (int)$id;
        $companyId = requireAdminCompany('surveyAdmin/index');
        $survey = $this->surveyModel->getById($id);
        if ($survey && (int)$survey->company_id === $companyId) {
            $this->surveyModel->setStatus($id, 'closed');
            $_SESSION['flash_success'] = 'Encuesta cerrada.';
        }
        redirect('surveyAdmin/index');
    }

    public function results($id) {
        $id = (int)$id;
        $companyId = requireAdminCompany('surveyAdmin/index');
        $survey = $this->surveyModel->getById($id);
        if (!$survey || (int)$survey->company_id !== $companyId) {
            redirect('surveyAdmin/index');
        }
        $questions = $this->surveyModel->getQuestions($id);
        $aggregates = [];
        foreach ($questions as $q) {
            if (in_array($q->question_type, ['single_choice', 'multiple_choice', 'scale'], true)) {
                $aggregates[$q->id] = $this->surveyModel->aggregateChoiceAnswers($id, (int)$q->id);
            }
        }
        $this->view('admin/surveys/results', [
            'survey' => $survey,
            'questions' => $questions,
            'aggregates' => $aggregates,
            'responses' => (int)$survey->is_anonymous ? [] : $this->surveyModel->getResponsesWithUsers($id),
            'response_count' => $this->surveyModel->countResponses($id),
        ]);
    }

    private function view($view, $data = []) {
        extract($data, EXTR_SKIP);
        require APPROOT . '/views/' . $view . '.php';
    }
}
