<?php

class SurveyController {
    private $surveyModel;

    public function __construct() {
        requireEmployeeRole();
        require_employee_portal_feature('surveys');
        if (!surveys_is_ready()) {
            redirect('employee/index');
        }
        $this->surveyModel = new Survey();
    }

    public function index() {
        $userId = (int)$_SESSION['user_id'];
        $pending = $this->surveyModel->getPendingForUser($userId);
        $this->view('employee/surveys', ['pending' => $pending]);
    }

    public function fill($id = 0) {
        $id = (int)$id;
        $userId = (int)$_SESSION['user_id'];
        $survey = $this->surveyModel->getById($id);
        if (!$survey || !$this->surveyModel->isOpenForSubmission($survey)) {
            $_SESSION['flash_error'] = 'Esta encuesta no está disponible.';
            redirect('survey/index');
        }
        if (!$this->surveyModel->userBelongsToSurveyCompany($survey, $userId)) {
            $_SESSION['flash_error'] = 'No tenés acceso a esta encuesta.';
            redirect('survey/index');
        }
        if (!$this->surveyModel->userCanAccess($id, $userId)) {
            $_SESSION['flash_error'] = 'No tenés acceso a esta encuesta.';
            redirect('survey/index');
        }
        if ($this->surveyModel->userHasResponded($id, $userId, (int)$survey->is_anonymous)) {
            $_SESSION['flash_error'] = 'Ya completaste esta encuesta.';
            redirect('survey/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $result = $this->surveyModel->submitResponse($survey, $userId, $_POST);
            $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
            redirect('survey/index');
        }

        $this->view('employee/survey_fill', [
            'survey' => $survey,
            'questions' => $this->surveyModel->getQuestions($id),
        ]);
    }

    private function view($view, $data = []) {
        require APPROOT . '/views/' . $view . '.php';
    }
}
