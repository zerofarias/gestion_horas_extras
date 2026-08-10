<?php

class CourseEnrollment {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getOrCreate($courseId, $userId) {
        $row = $this->get($courseId, $userId);
        if ($row) {
            return $row;
        }
        $this->db->query('INSERT INTO course_enrollments (course_id, user_id, status, current_lesson_position)
            VALUES (:course_id, :user_id, \'not_started\', 1)');
        $this->db->bind(':course_id', (int)$courseId);
        $this->db->bind(':user_id', (int)$userId);
        $this->db->execute();
        return $this->get($courseId, $userId);
    }

    public function get($courseId, $userId) {
        $this->db->query('SELECT * FROM course_enrollments WHERE course_id = :cid AND user_id = :uid');
        $this->db->bind(':cid', (int)$courseId);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->single();
    }

    public function getById($id) {
        $this->db->query('SELECT e.*, c.title AS course_title, c.passing_score, c.stars_on_complete,
            c.first_finisher_bonus, c.require_quiz, c.max_quiz_attempts
            FROM course_enrollments e
            JOIN courses c ON c.id = e.course_id
            WHERE e.id = :id');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    /** Semilla única por inscripción para orden de preguntas distinto por empleado. */
    public function ensureQuizSeed($enrollmentId) {
        if (!learning_quiz_columns_ready()) {
            return null;
        }
        $enrollment = $this->getById($enrollmentId);
        if (!$enrollment) {
            return null;
        }
        if (!empty($enrollment->quiz_order_seed)) {
            return (int)$enrollment->quiz_order_seed;
        }
        $seed = abs(crc32('quiz-' . (int)$enrollment->user_id . '-' . (int)$enrollment->course_id . '-' . (int)$enrollmentId));
        if ($seed < 1) {
            $seed = random_int(1, 2147483646);
        }
        $this->db->query('UPDATE course_enrollments SET quiz_order_seed = :seed WHERE id = :id');
        $this->db->bind(':seed', $seed);
        $this->db->bind(':id', (int)$enrollmentId);
        $this->db->execute();
        return $seed;
    }

    public function countCompletedBefore($courseId, $excludeEnrollmentId) {
        $this->db->query('SELECT COUNT(*) AS c FROM course_enrollments
            WHERE course_id = :cid AND status = \'completed\' AND completed_at IS NOT NULL AND id != :eid');
        $this->db->bind(':cid', (int)$courseId);
        $this->db->bind(':eid', (int)$excludeEnrollmentId);
        $row = $this->db->single();
        return $row ? (int)$row->c : 0;
    }

    public function markLessonComplete($enrollmentId, $lessonId) {
        $this->db->query('INSERT IGNORE INTO lesson_completions (enrollment_id, lesson_id) VALUES (:eid, :lid)');
        $this->db->bind(':eid', (int)$enrollmentId);
        $this->db->bind(':lid', (int)$lessonId);
        $this->db->execute();

        $enrollment = $this->getById($enrollmentId);
        if (!$enrollment) {
            return false;
        }
        $courseModel = new Course();
        $lessons = $courseModel->getLessons($enrollment->course_id);
        $completed = $this->getCompletedLessonIds($enrollmentId);
        $required = 0;
        $doneRequired = 0;
        $maxPos = 1;
        foreach ($lessons as $l) {
            if ($l->is_required) {
                $required++;
                if (in_array((int)$l->id, $completed, true)) {
                    $doneRequired++;
                }
            }
            if ((int)$l->position > $maxPos) {
                $maxPos = (int)$l->position;
            }
        }
        $lesson = $courseModel->getLessonById($lessonId);
        $nextPos = $lesson ? min((int)$lesson->position + 1, $maxPos) : 1;
        $pct = $required > 0 ? (int)round(($doneRequired / $required) * 100) : 100;
        $allRequiredDone = ($required === 0) || ($doneRequired >= $required);

        $enrollmentFull = $this->getById($enrollmentId);
        $requireQuiz = $enrollmentFull ? (int)$enrollmentFull->require_quiz : 1;
        $status = 'in_progress';
        if ($allRequiredDone && !$requireQuiz) {
            $status = 'completed';
        }

        $this->db->query('UPDATE course_enrollments SET
            status = :status, current_lesson_position = :pos, progress_percent = :pct,
            started_at = COALESCE(started_at, NOW()), updated_at = NOW()
            WHERE id = :id');
        $this->db->bind(':status', $status);
        $this->db->bind(':pos', $nextPos);
        $this->db->bind(':pct', $pct);
        $this->db->bind(':id', (int)$enrollmentId);
        if (!$this->db->execute()) {
            return false;
        }

        if ($status === 'completed' && $enrollmentFull) {
            $this->awardCourseStars($enrollmentFull);
        }
        return true;
    }

    /** Estrellas al completar curso sin cuestionario (una sola vez). */
    public function awardCourseStars($enrollment) {
        if (!$enrollment || (int)($enrollment->stars_awarded ?? 0) > 0) {
            return 0;
        }
        $courseId = (int)$enrollment->course_id;
        $course = (new Course())->getById($courseId);
        if (!$course) {
            return 0;
        }
        $stars = (int)$course->stars_on_complete;
        if ($stars <= 0) {
            return 0;
        }
        $wallet = new StarWallet();
        $wallet->addStars((int)$enrollment->user_id, $stars, 'course', $courseId, 'Curso: ' . $course->title);
        $sql = 'UPDATE course_enrollments SET stars_awarded = :stars, completed_at = COALESCE(completed_at, NOW())';
        if (learning_quiz_columns_ready()) {
            $sql .= ', bonus_stars = 0';
        }
        $sql .= ' WHERE id = :eid';
        $this->db->query($sql);
        $this->db->bind(':stars', $stars);
        $this->db->bind(':eid', (int)$enrollment->id);
        $this->db->execute();
        return $stars;
    }

    public function allRequiredLessonsDone($enrollmentId, $courseId) {
        $courseModel = new Course();
        $lessons = $courseModel->getLessons($courseId);
        $completed = $this->getCompletedLessonIds($enrollmentId);
        foreach ($lessons as $l) {
            if ($l->is_required && !in_array((int)$l->id, $completed, true)) {
                return false;
            }
        }
        return true;
    }

    public function getCompletedLessonIds($enrollmentId) {
        $this->db->query('SELECT lesson_id FROM lesson_completions WHERE enrollment_id = :eid');
        $this->db->bind(':eid', (int)$enrollmentId);
        $rows = $this->db->resultSet();
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = (int)$r->lesson_id;
        }
        return $ids;
    }

    public function isLessonCompleted($enrollmentId, $lessonId) {
        $this->db->query('SELECT id FROM lesson_completions WHERE enrollment_id = :eid AND lesson_id = :lid');
        $this->db->bind(':eid', (int)$enrollmentId);
        $this->db->bind(':lid', (int)$lessonId);
        return (bool)$this->db->single();
    }

    public function submitQuiz($enrollmentId, $answers, $courseId) {
        $enrollment = $this->getById($enrollmentId);
        $course = (new Course())->getById($courseId);
        if (!$enrollment || !$course) {
            return ['ok' => false, 'message' => 'Inscripción no encontrada.'];
        }
        if ((int)$enrollment->quiz_attempts >= (int)$course->max_quiz_attempts) {
            return ['ok' => false, 'message' => 'Agotaste los intentos del cuestionario.'];
        }
        $courseModel = new Course();
        $questions = $courseModel->getQuizQuestions($courseId);
        if (count($questions) < 5) {
            return ['ok' => false, 'message' => 'El curso no tiene cuestionario configurado.'];
        }
        $seed = $this->ensureQuizSeed($enrollmentId);
        if ($seed) {
            $questions = learning_shuffle_quiz_questions($questions, $seed);
        }
        $correct = 0;
        foreach ($questions as $q) {
            $selected = isset($answers[$q->id]) ? (int)$answers[$q->id] : 0;
            foreach ($q->options as $opt) {
                if ((int)$opt->id === $selected && $opt->is_correct) {
                    $correct++;
                    break;
                }
            }
        }
        $score = (int)round(($correct / count($questions)) * 100);
        $passed = $score >= (int)$course->passing_score;
        $attempts = (int)$enrollment->quiz_attempts + 1;

        $completionRank = null;
        $bonusStars = 0;
        $isFirstFinisher = false;
        if ($passed) {
            $completedBefore = $this->countCompletedBefore($courseId, $enrollmentId);
            $completionRank = $completedBefore + 1;
            $isFirstFinisher = ($completedBefore === 0);
            $bonusVal = (int)($course->first_finisher_bonus ?? 0);
            if ($isFirstFinisher && $bonusVal > 0) {
                $bonusStars = $bonusVal;
            }
        }

        $progressPct = $passed ? 100 : min(99, (int)($enrollment->progress_percent ?? 0));
        $sql = 'UPDATE course_enrollments SET quiz_score = :score, quiz_attempts = :attempts,
            quiz_passed_at = :passed_at, status = :status, progress_percent = :pct,
            completed_at = :completed_at, updated_at = NOW()';
        if (learning_quiz_columns_ready()) {
            $sql .= ', completion_rank = :rank, bonus_stars = :bonus';
        }
        $sql .= ' WHERE id = :id';
        $this->db->query($sql);
        $this->db->bind(':score', $score);
        $this->db->bind(':attempts', $attempts);
        $this->db->bind(':passed_at', $passed ? date('Y-m-d H:i:s') : null);
        $this->db->bind(':status', $passed ? 'completed' : 'failed_quiz');
        $this->db->bind(':pct', $progressPct);
        $this->db->bind(':completed_at', $passed ? date('Y-m-d H:i:s') : null);
        if (learning_quiz_columns_ready()) {
            $this->db->bind(':rank', $completionRank);
            $this->db->bind(':bonus', $bonusStars);
        }
        $this->db->bind(':id', (int)$enrollmentId);
        $this->db->execute();

        $stars = 0;
        if ($passed && (int)$enrollment->stars_awarded === 0) {
            $wallet = new StarWallet();
            $baseStars = (int)$course->stars_on_complete;
            $wallet->addStars((int)$enrollment->user_id, $baseStars, 'course', (int)$courseId, 'Curso: ' . $course->title);
            $stars = $baseStars;
            if ($bonusStars > 0) {
                $wallet->addStars(
                    (int)$enrollment->user_id,
                    $bonusStars,
                    'course',
                    (int)$courseId,
                    'Bonus 1° en completar: ' . $course->title
                );
                $stars += $bonusStars;
            }
            $sqlStars = 'UPDATE course_enrollments SET stars_awarded = :stars';
            if (learning_quiz_columns_ready()) {
                $sqlStars .= ', bonus_stars = :bonus';
            }
            $sqlStars .= ' WHERE id = :id';
            $this->db->query($sqlStars);
            $this->db->bind(':stars', $baseStars);
            if (learning_quiz_columns_ready()) {
                $this->db->bind(':bonus', $bonusStars);
            }
            $this->db->bind(':id', (int)$enrollmentId);
            $this->db->execute();
        }

        return [
            'ok' => true,
            'passed' => $passed,
            'score' => $score,
            'correct' => $correct,
            'total' => count($questions),
            'stars' => $stars,
            'base_stars' => $passed ? (int)$course->stars_on_complete : 0,
            'bonus_stars' => $bonusStars,
            'is_first_finisher' => $isFirstFinisher,
            'completion_rank' => $completionRank,
            'attempts_left' => max(0, (int)$course->max_quiz_attempts - $attempts),
        ];
    }

    public function getReportByCompany($companyId) {
        $this->db->query('SELECT e.*, u.full_name, u.username, c.title AS course_title,
            a.name AS area_name
            FROM course_enrollments e
            JOIN users u ON u.id = e.user_id
            JOIN courses c ON c.id = e.course_id
            LEFT JOIN areas a ON a.id = u.area_id
            WHERE c.company_id = :company_id
            ORDER BY c.title, u.full_name');
        $this->db->bind(':company_id', (int)$companyId);
        return $this->db->resultSet();
    }
}
