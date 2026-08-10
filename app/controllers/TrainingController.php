<?php

class TrainingController {
    private $courseModel;
    private $enrollmentModel;
    private $assignmentService;
    private $walletModel;
    private $rewardModel;
    private $taskModel;
    private $resourceModel;
    private $discussionModel;
    private $noteModel;
    private $reviewModel;

    public function __construct() {
        if (!isLoggedIn()) {
            redirect('login');
        }
        if (!learning_is_ready()) {
            $_SESSION['flash_error'] = 'El módulo de capacitación no está disponible.';
            redirect(hasRole('admin') ? 'admin/dashboard' : 'employee/index');
        }
        if (hasRole('empleado') && !employee_portal_can('training')) {
            $_SESSION['flash_error'] = 'El módulo de capacitación no está disponible en el portal.';
            redirect('employee/index');
        }
        $this->courseModel = new Course();
        $this->enrollmentModel = new CourseEnrollment();
        $this->assignmentService = new LearningAssignmentService();
        $this->walletModel = new StarWallet();
        $this->rewardModel = new Reward();
        $this->taskModel = new Task();
        $this->resourceModel = new CourseResource();
        $this->discussionModel = new CourseDiscussion();
        $this->noteModel = new UserLessonNote();
        $this->reviewModel = new CourseReview();
    }

    public function index() {
        $userId = (int)$_SESSION['user_id'];
        $courseIds = $this->assignmentService->getCourseIdsForUser($userId);
        $reviewsReady = learning_reviews_is_ready();
        $courses = [];
        foreach ($courseIds as $cid) {
            $c = $this->courseModel->getById($cid);
            if (!$c) {
                continue;
            }
            $enrollment = $this->enrollmentModel->getOrCreate($cid, $userId);
            $c->enrollment = $enrollment;
            $c->lesson_count = count($this->courseModel->getLessons($cid));
            if ($reviewsReady) {
                $c->review_stats = $this->reviewModel->getStats($cid);
            }
            $courses[] = $c;
        }
        $this->view('employee/learning_index', [
            'courses' => $courses,
            'stars' => $this->walletModel->getBalance($userId),
            'reviews_ready' => $reviewsReady,
        ]);
    }

    public function course($id) {
        $userId = (int)$_SESSION['user_id'];
        $id = (int)$id;
        if (!$this->assignmentService->userCanAccessCourse($userId, $id)) {
            $_SESSION['flash_error'] = 'No tenés acceso a este curso.';
            redirect('training/index');
        }
        $enrollment = $this->enrollmentModel->getOrCreate($id, $userId);
        $pos = max(1, (int)$enrollment->current_lesson_position);
        redirect('training/lesson/' . $id . '/' . $pos);
    }

    public function lesson($courseId, $position = 1) {
        $userId = (int)$_SESSION['user_id'];
        $courseId = (int)$courseId;
        $position = (int)$position;
        if (!$this->assignmentService->userCanAccessCourse($userId, $courseId)) {
            redirect('training/index');
        }
        $course = $this->courseModel->getById($courseId);
        $lessons = $this->courseModel->getLessons($courseId);
        $lesson = $this->courseModel->getLessonByPosition($courseId, $position);
        if (!$lesson && !empty($lessons)) {
            $lesson = $lessons[0];
            $position = (int)$lesson->position;
        }
        $enrollment = $this->enrollmentModel->getOrCreate($courseId, $userId);
        $completedIds = $this->enrollmentModel->getCompletedLessonIds($enrollment->id);
        $allDone = $this->enrollmentModel->allRequiredLessonsDone($enrollment->id, $courseId);
        $lessonId = $lesson ? (int)$lesson->id : 0;
        $resources = [];
        $lessonResources = [];
        $courseResources = [];
        $discussions = [];
        $userNote = null;
        if (learning_resources_is_ready() && $lessonId) {
            foreach ($this->resourceModel->getByCourse($courseId, $lessonId) as $r) {
                if (!empty($r->lesson_id) && (int)$r->lesson_id === $lessonId) {
                    $resources[] = $r;
                } elseif (empty($r->lesson_id)) {
                    $courseResources[] = $r;
                }
            }
        }
        if (learning_discussions_is_ready() && $lessonId) {
            $discussions = $this->discussionModel->getByCourse($courseId, $lessonId);
        }
        if (learning_notes_is_ready() && $lessonId) {
            $userNote = $this->noteModel->get($userId, $lessonId);
        }
        $panel = preg_replace('/[^a-z]/', '', $_GET['panel'] ?? 'content');
        if ($panel === 'materials' && !learning_resources_is_ready()) {
            $panel = 'content';
        } elseif ($panel === 'notes' && !learning_notes_is_ready()) {
            $panel = 'content';
        } elseif ($panel === 'community' && !learning_discussions_is_ready()) {
            $panel = 'content';
        }
        $progressMeta = learning_enrollment_progress_meta($lessons, $completedIds, $enrollment);
        $reviewsReady = learning_reviews_is_ready();
        $reviewStats = null;
        $courseReviews = [];
        $userReview = null;
        if ($reviewsReady) {
            $reviewStats = $this->reviewModel->getStats($courseId);
            $courseReviews = $this->reviewModel->getByCourse($courseId);
            $userReview = $this->reviewModel->getUserReview($courseId, $userId);
        }
        $this->view('employee/course_player', [
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'position' => $position,
            'enrollment' => $enrollment,
            'completedIds' => $completedIds,
            'allLessonsDone' => $allDone,
            'stars' => $this->walletModel->getBalance($userId),
            'resources' => $resources,
            'course_resources' => $courseResources,
            'discussions' => $discussions,
            'user_note' => $userNote,
            'enrich_ready' => learning_enrich_is_ready(),
            'resources_ready' => learning_resources_is_ready(),
            'discussions_ready' => learning_discussions_is_ready(),
            'notes_ready' => learning_notes_is_ready(),
            'panel' => $panel,
            'reviews_ready' => $reviewsReady,
            'review_stats' => $reviewStats,
            'course_reviews' => $courseReviews,
            'user_review' => $userReview,
            'progress_meta' => $progressMeta,
        ]);
    }

    public function submitReview($courseId) {
        csrf_verify();
        $userId = (int)$_SESSION['user_id'];
        $courseId = (int)$courseId;
        if (!$this->assignmentService->userCanAccessCourse($userId, $courseId) || !learning_reviews_is_ready()) {
            redirect('training/index');
        }
        $vote = $_POST['vote'] ?? '';
        if ($vote !== 'like' && $vote !== 'dislike') {
            $_SESSION['flash_error'] = 'Elegí Me gusta o No me gusta para tu reseña.';
        } else {
            if ($this->reviewModel->upsert($courseId, $userId, $vote, $_POST['comment'] ?? '')) {
                $_SESSION['flash_success'] = 'Gracias por tu reseña.';
            } else {
                $_SESSION['flash_error'] = 'No se pudo guardar tu reseña. Intentá de nuevo.';
            }
        }
        $pos = (int)($_POST['lesson_position'] ?? 0);
        if ($pos > 0) {
            redirect('training/lesson/' . $courseId . '/' . $pos . '?panel=reviews');
        }
        redirect('training/index');
    }

    public function saveNote($lessonId) {
        csrf_verify();
        $userId = (int)$_SESSION['user_id'];
        $lessonId = (int)$lessonId;
        $lesson = $this->courseModel->getLessonById($lessonId);
        if (!$lesson || !learning_notes_is_ready()) {
            redirect('training/index');
        }
        if (!$this->assignmentService->userCanAccessCourse($userId, (int)$lesson->course_id)) {
            redirect('training/index');
        }
        $this->noteModel->save($userId, $lessonId, $_POST['body'] ?? '');
        $_SESSION['flash_success'] = 'Notas guardadas.';
        redirect('training/lesson/' . (int)$lesson->course_id . '/' . (int)$lesson->position . '?panel=notes');
    }

    public function postDiscussion($courseId) {
        csrf_verify();
        $userId = (int)$_SESSION['user_id'];
        $courseId = (int)$courseId;
        if (!$this->assignmentService->userCanAccessCourse($userId, $courseId) || !learning_discussions_is_ready()) {
            redirect('training/index');
        }
        $body = trim($_POST['body'] ?? '');
        if ($body === '') {
            $_SESSION['flash_error'] = 'Escribí tu pregunta o sugerencia.';
        } else {
            $postType = $_POST['post_type'] ?? 'question';
            if (!in_array($postType, ['question', 'suggestion', 'comment'], true)) {
                $postType = 'question';
            }
            $this->discussionModel->create($courseId, $userId, [
                'lesson_id' => (int)($_POST['lesson_id'] ?? 0) ?: null,
                'post_type' => $postType,
                'body' => $body,
            ]);
            $_SESSION['flash_success'] = 'Mensaje enviado. RRHH o el instructor te responderá pronto.';
        }
        $pos = (int)($_POST['lesson_position'] ?? 1);
        redirect('training/lesson/' . $courseId . '/' . $pos . '?panel=community');
    }

    public function completeLesson($courseId, $lessonId) {
        csrf_verify();
        $userId = (int)$_SESSION['user_id'];
        $courseId = (int)$courseId;
        $lessonId = (int)$lessonId;
        if (!$this->assignmentService->userCanAccessCourse($userId, $courseId)) {
            redirect('training/index');
        }
        $lesson = $this->courseModel->getLessonById($lessonId);
        if (!$lesson || (int)$lesson->course_id !== $courseId) {
            $_SESSION['flash_error'] = 'Lección no válida para este curso.';
            redirect('training/course/' . $courseId);
        }
        $enrollment = $this->enrollmentModel->getOrCreate($courseId, $userId);
        $this->enrollmentModel->markLessonComplete($enrollment->id, $lessonId);

        if ($this->enrollmentModel->allRequiredLessonsDone($enrollment->id, $courseId)) {
            $course = $this->courseModel->getById($courseId);
            if ($course && $course->require_quiz) {
                redirect('training/quiz/' . $courseId);
            }
            $_SESSION['flash_success'] = '¡Felicitaciones! Completaste el curso.';
            redirect('training/index');
        }

        $nextLesson = $this->courseModel->getLessonByPosition($courseId, (int)$lesson->position + 1);
        if ($nextLesson) {
            redirect('training/lesson/' . $courseId . '/' . (int)$nextLesson->position);
        }
        redirect('training/lesson/' . $courseId . '/' . (int)$lesson->position);
    }

    public function quiz($courseId) {
        $userId = (int)$_SESSION['user_id'];
        $courseId = (int)$courseId;
        if (!$this->assignmentService->userCanAccessCourse($userId, $courseId)) {
            redirect('training/index');
        }
        $enrollment = $this->enrollmentModel->getOrCreate($courseId, $userId);
        if (!$this->enrollmentModel->allRequiredLessonsDone($enrollment->id, $courseId)) {
            $_SESSION['flash_error'] = 'Completá todas las lecciones antes del cuestionario.';
            redirect('training/course/' . $courseId);
        }
        $course = $this->courseModel->getById($courseId);
        $questions = $this->courseModel->getQuizQuestionsForEnrollment($courseId, $enrollment->id);
        if (count($questions) < 5) {
            $_SESSION['flash_error'] = 'El cuestionario aún no está listo. Contactá a RRHH.';
            redirect('training/lesson/' . $courseId . '/' . max(1, (int)$enrollment->current_lesson_position));
        }
        $this->view('employee/course_quiz', [
            'course' => $course,
            'questions' => $questions,
            'enrollment' => $enrollment,
            'quiz_shuffled' => learning_quiz_columns_ready(),
        ]);
    }

    public function submitQuiz($courseId) {
        csrf_verify();
        $userId = (int)$_SESSION['user_id'];
        $courseId = (int)$courseId;
        if (!$this->assignmentService->userCanAccessCourse($userId, $courseId)) {
            redirect('training/index');
        }
        $enrollment = $this->enrollmentModel->getOrCreate($courseId, $userId);
        $result = $this->enrollmentModel->submitQuiz($enrollment->id, $_POST['answers'] ?? [], $courseId);
        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['message'];
            redirect('training/quiz/' . $courseId);
        }
        $this->view('employee/course_quiz_result', [
            'course' => $this->courseModel->getById($courseId),
            'result' => $result,
            'stars' => $this->walletModel->getBalance($userId),
        ]);
    }

    public function stars() {
        $userId = (int)$_SESSION['user_id'];
        $user = (new User())->getUserById($userId);
        $companyId = (int)($user->company_id ?? 0);
        $this->view('employee/my_stars', [
            'balance' => $this->walletModel->getBalance($userId),
            'transactions' => $this->walletModel->getTransactions($userId),
            'rewards' => $companyId ? $this->rewardModel->getByCompany($companyId) : [],
        ]);
    }

    public function redeem($rewardId) {
        csrf_verify();
        $userId = (int)$_SESSION['user_id'];
        $result = $this->rewardModel->redeem($userId, (int)$rewardId);
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        redirect('training/stars');
    }

    public function tasks() {
        $userId = (int)$_SESSION['user_id'];
        $taskIds = $this->assignmentService->getTaskIdsForUser($userId);
        $tasks = [];
        foreach ($taskIds as $tid) {
            $t = $this->taskModel->getById($tid);
            if ($t) {
                $t->completed = $this->taskModel->isCompletedByUser($tid, $userId);
                $tasks[] = $t;
            }
        }
        $this->view('employee/tasks', [
            'tasks' => $tasks,
            'stars' => $this->walletModel->getBalance($userId),
        ]);
    }

    public function completeTask($taskId) {
        csrf_verify();
        $userId = (int)$_SESSION['user_id'];
        $taskId = (int)$taskId;
        if (!$this->assignmentService->userCanAccessTask($userId, $taskId)) {
            redirect('training/tasks');
        }
        $result = $this->taskModel->complete($taskId, $userId, $_POST['note'] ?? null);
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        redirect('training/tasks');
    }

    public function streamResource($id = 0) {
        $resourceId = (int)$id;
        if ($resourceId <= 0) {
            http_response_code(404);
            exit;
        }
        $resource = $this->resourceModel->getById($resourceId);
        if (!$resource || empty($resource->file_path)) {
            http_response_code(404);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];
        if (!$this->assignmentService->userCanAccessCourse($userId, (int)$resource->course_id)) {
            http_response_code(403);
            exit;
        }
        $inline = !preg_match('/\.(xlsx?|csv|docx?|pptx?|zip|rar)$/i', (string)$resource->file_path);
        protected_upload_send($resource->file_path, $inline, basename((string)$resource->file_path));
    }

    public function streamLessonFile($courseId = 0, $lessonId = 0) {
        $userId = (int)$_SESSION['user_id'];
        $courseId = (int)$courseId;
        $lessonId = (int)$lessonId;
        if ($courseId <= 0 || $lessonId <= 0 || !$this->assignmentService->userCanAccessCourse($userId, $courseId)) {
            http_response_code(404);
            exit;
        }
        $lesson = $this->courseModel->getLessonById($lessonId);
        if (!$lesson || (int)$lesson->course_id !== $courseId) {
            http_response_code(404);
            exit;
        }
        $this->sendLessonUpload($lesson);
    }

    private function sendLessonUpload($lesson) {
        $url = trim((string)($lesson->content_url ?? ''));
        if ($url === '' || preg_match('#^https?://#i', $url) || learning_is_embed_video_url($url)) {
            http_response_code(404);
            exit;
        }
        $inline = !preg_match('/\.(xlsx?|csv|docx?|pptx?|zip|rar)$/i', $url);
        protected_upload_send($url, $inline, basename($url));
    }

    private function view($view, $data = []) {
        if (file_exists('../app/views/' . $view . '.php')) {
            require_once '../app/views/' . $view . '.php';
        } else {
            die('Vista no encontrada: ' . $view);
        }
    }
}
