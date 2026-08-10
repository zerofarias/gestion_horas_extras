<?php

class Survey {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function isReady() {
        return surveys_is_ready();
    }

    public function getByCompany($companyId, $status = null) {
        $sql = 'SELECT s.*, u.full_name AS creator_name
                FROM surveys s
                LEFT JOIN users u ON u.id = s.created_by
                WHERE s.company_id = :cid';
        if ($status) {
            $sql .= ' AND s.status = :st';
        }
        $sql .= ' ORDER BY s.updated_at DESC';
        $this->db->query($sql);
        $this->db->bind(':cid', (int)$companyId);
        if ($status) {
            $this->db->bind(':st', $status);
        }
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query('SELECT * FROM surveys WHERE id = :id');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function create($data) {
        $this->db->query('INSERT INTO surveys (company_id, title, description, is_anonymous, status, open_at, close_at, created_by)
            VALUES (:cid, :title, :desc, :anon, :st, :open_at, :close_at, :by)');
        $this->db->bind(':cid', (int)$data['company_id']);
        $this->db->bind(':title', trim($data['title']));
        $this->db->bind(':desc', $data['description'] ?? null);
        $this->db->bind(':anon', !empty($data['is_anonymous']) ? 1 : 0);
        $this->db->bind(':st', $data['status'] ?? 'draft');
        $this->db->bind(':open_at', $data['open_at'] ?? null);
        $this->db->bind(':close_at', $data['close_at'] ?? null);
        $this->db->bind(':by', (int)($data['created_by'] ?? 0) ?: null);
        if ($this->db->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return 0;
    }

    public function update($id, $data) {
        $this->db->query('UPDATE surveys SET title = :title, description = :desc, is_anonymous = :anon,
            open_at = :open_at, close_at = :close_at WHERE id = :id');
        $this->db->bind(':title', trim($data['title']));
        $this->db->bind(':desc', $data['description'] ?? null);
        $this->db->bind(':anon', !empty($data['is_anonymous']) ? 1 : 0);
        $this->db->bind(':open_at', $data['open_at'] ?? null);
        $this->db->bind(':close_at', $data['close_at'] ?? null);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function setStatus($id, $status) {
        $this->db->query('UPDATE surveys SET status = :st WHERE id = :id');
        $this->db->bind(':st', $status);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function deleteAssignments($surveyId) {
        $this->db->query('DELETE FROM survey_assignments WHERE survey_id = :sid');
        $this->db->bind(':sid', (int)$surveyId);
        return $this->db->execute();
    }

    public function addAssignment($surveyId, $targetType, $targetId) {
        $this->db->query('INSERT INTO survey_assignments (survey_id, target_type, target_id) VALUES (:sid, :tt, :tid)');
        $this->db->bind(':sid', (int)$surveyId);
        $this->db->bind(':tt', $targetType);
        $this->db->bind(':tid', (int)$targetId);
        return $this->db->execute();
    }

    public function getAssignments($surveyId) {
        $this->db->query('SELECT * FROM survey_assignments WHERE survey_id = :sid');
        $this->db->bind(':sid', (int)$surveyId);
        return $this->db->resultSet();
    }

    public function assignmentsToTargetOptions(array $rows) {
        $opts = ['target_all' => false, 'company_ids' => [], 'area_ids' => [], 'user_ids' => []];
        foreach ($rows as $r) {
            if ($r->target_type === 'company' && (int)$r->target_id === 0) {
                $opts['target_all'] = true;
            } elseif ($r->target_type === 'company') {
                $opts['company_ids'][] = (int)$r->target_id;
            } elseif ($r->target_type === 'area') {
                $opts['area_ids'][] = (int)$r->target_id;
            } elseif ($r->target_type === 'user') {
                $opts['user_ids'][] = (int)$r->target_id;
            }
        }
        return $opts;
    }

    public function saveAssignmentsFromPost($surveyId, array $post) {
        $survey = $this->getById($surveyId);
        $companyId = $survey ? (int)$survey->company_id : 0;
        $this->deleteAssignments($surveyId);
        if (!empty($post['target_all'])) {
            $this->addAssignment($surveyId, 'company', 0);
            return;
        }
        foreach ((array)($post['company_ids'] ?? []) as $cid) {
            $cid = (int)$cid;
            if ($cid > 0 && ($companyId <= 0 || $cid === $companyId)) {
                $this->addAssignment($surveyId, 'company', $cid);
            }
        }
        foreach ((array)($post['area_ids'] ?? []) as $aid) {
            $aid = (int)$aid;
            if ($aid > 0) {
                $area = (new Area())->getById($aid);
                if ($area && ($companyId <= 0 || (int)$area->company_id === $companyId || $area->company_id === null)) {
                    $this->addAssignment($surveyId, 'area', $aid);
                }
            }
        }
        foreach ((array)($post['user_ids'] ?? []) as $uid) {
            $uid = (int)$uid;
            if ($uid <= 0) {
                continue;
            }
            $u = (new User())->getUserById($uid);
            if ($u && (int)$u->company_id === $companyId) {
                $this->addAssignment($surveyId, 'user', $uid);
            }
        }
    }

    public function resolveAudienceUserIds($surveyId) {
        $rows = $this->getAssignments($surveyId);
        $opts = $this->assignmentsToTargetOptions($rows);
        if (empty($rows)) {
            $survey = $this->getById($surveyId);
            if ($survey) {
                return (new NotificationTargetService())->resolveUserIds([
                    'target_all' => false,
                    'company_ids' => [(int)$survey->company_id],
                    'area_ids' => [],
                    'user_ids' => [],
                ]);
            }
        }
        return (new NotificationTargetService())->resolveUserIds($opts);
    }

    public function getQuestions($surveyId) {
        $this->db->query('SELECT * FROM survey_questions WHERE survey_id = :sid ORDER BY sort_order ASC, id ASC');
        $this->db->bind(':sid', (int)$surveyId);
        return $this->db->resultSet();
    }

    public function deleteQuestions($surveyId) {
        $this->db->query('DELETE FROM survey_questions WHERE survey_id = :sid');
        $this->db->bind(':sid', (int)$surveyId);
        return $this->db->execute();
    }

    public function hasExplicitAssignments($surveyId) {
        return count($this->getAssignments($surveyId)) > 0;
    }

    public function userBelongsToSurveyCompany($survey, $userId) {
        if (!$survey) {
            return false;
        }
        $user = (new User())->getUserById((int)$userId);
        return $user && (int)$survey->company_id === (int)$user->company_id;
    }

    public function saveQuestionsFromPost($surveyId, array $questionsPost) {
        $types = array_keys(survey_question_types());
        $toInsert = [];
        foreach ($questionsPost as $q) {
            if (!is_array($q)) {
                continue;
            }
            $label = trim($q['label'] ?? '');
            if ($label === '') {
                continue;
            }
            $type = $q['question_type'] ?? 'short_text';
            if (!in_array($type, $types, true)) {
                $type = 'short_text';
            }
            $config = [];
            if (in_array($type, ['single_choice', 'multiple_choice'], true)) {
                $opts = preg_split('/\r\n|\r|\n/', $q['options'] ?? '');
                $config['options'] = array_values(array_filter(array_map('trim', $opts)));
            }
            if ($type === 'scale') {
                $config['min'] = (int)($q['scale_min'] ?? 1);
                $config['max'] = (int)($q['scale_max'] ?? 5);
            }
            $toInsert[] = [
                'type' => $type,
                'label' => $label,
                'required' => !empty($q['is_required']) ? 1 : 0,
                'config' => $config ? json_encode($config, JSON_UNESCAPED_UNICODE) : null,
            ];
        }
        if (empty($toInsert)) {
            return false;
        }

        $this->deleteQuestions($surveyId);
        $order = 0;
        foreach ($toInsert as $row) {
            $this->db->query('INSERT INTO survey_questions (survey_id, sort_order, question_type, label, is_required, config_json)
                VALUES (:sid, :ord, :qt, :lbl, :req, :cfg)');
            $this->db->bind(':sid', (int)$surveyId);
            $this->db->bind(':ord', $order++);
            $this->db->bind(':qt', $row['type']);
            $this->db->bind(':lbl', $row['label']);
            $this->db->bind(':req', $row['required']);
            $this->db->bind(':cfg', $row['config']);
            $this->db->execute();
        }
        return true;
    }

    public function isOpenForSubmission($survey) {
        if (!$survey || $survey->status !== 'published') {
            return false;
        }
        $now = time();
        if (!empty($survey->open_at) && strtotime($survey->open_at) > $now) {
            return false;
        }
        if (!empty($survey->close_at) && strtotime($survey->close_at) < $now) {
            return false;
        }
        return true;
    }

    public function userCanAccess($surveyId, $userId) {
        $userId = (int)$userId;
        $ids = $this->resolveAudienceUserIds($surveyId);
        return in_array($userId, $ids, true);
    }

    public function userHasResponded($surveyId, $userId, $isAnonymous) {
        if ($isAnonymous) {
            $this->db->query('SELECT 1 FROM survey_completion_tokens WHERE survey_id = :sid AND user_id = :uid LIMIT 1');
            $this->db->bind(':sid', (int)$surveyId);
            $this->db->bind(':uid', (int)$userId);
            $this->db->single();
            return $this->db->rowCount() > 0;
        }
        $this->db->query('SELECT 1 FROM survey_responses WHERE survey_id = :sid AND user_id = :uid LIMIT 1');
        $this->db->bind(':sid', (int)$surveyId);
        $this->db->bind(':uid', (int)$userId);
        $this->db->single();
        return $this->db->rowCount() > 0;
    }

    public function submitResponse($survey, $userId, array $answersPost) {
        $surveyId = (int)$survey->id;
        $userId = (int)$userId;
        if (!$this->userBelongsToSurveyCompany($survey, $userId)) {
            return ['ok' => false, 'message' => 'No tenés acceso a esta encuesta.'];
        }
        if ($this->userHasResponded($surveyId, $userId, (int)$survey->is_anonymous)) {
            return ['ok' => false, 'message' => 'Ya enviaste esta encuesta.'];
        }
        $questions = $this->getQuestions($surveyId);
        if (empty($questions)) {
            return ['ok' => false, 'message' => 'La encuesta no tiene preguntas.'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->query('INSERT INTO survey_responses (survey_id, user_id) VALUES (:sid, :uid)');
            $this->db->bind(':sid', $surveyId);
            if ((int)$survey->is_anonymous) {
                $this->db->bind(':uid', null, PDO::PARAM_NULL);
            } else {
                $this->db->bind(':uid', $userId);
            }
            $this->db->execute();
            $responseId = (int)$this->db->lastInsertId();

            foreach ($questions as $q) {
                $qid = (int)$q->id;
                $raw = $answersPost['q_' . $qid] ?? $answersPost[$qid] ?? null;
                $text = '';
                if (is_array($raw)) {
                    $text = json_encode(array_map('trim', $raw), JSON_UNESCAPED_UNICODE);
                } else {
                    $text = trim((string)$raw);
                }
                if ((int)$q->is_required && $text === '') {
                    throw new RuntimeException('Completá todas las preguntas obligatorias.');
                }
                if ($text === '') {
                    continue;
                }
                $this->db->query('INSERT INTO survey_answers (response_id, question_id, answer_text) VALUES (:rid, :qid, :txt)');
                $this->db->bind(':rid', $responseId);
                $this->db->bind(':qid', $qid);
                $this->db->bind(':txt', $text);
                $this->db->execute();
            }

            if ((int)$survey->is_anonymous) {
                $hash = hash('sha256', $surveyId . '|' . $userId . '|' . bin2hex(random_bytes(8)));
                $this->db->query('INSERT INTO survey_completion_tokens (survey_id, user_id, token_hash) VALUES (:sid, :uid, :h)
                    ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash)');
                $this->db->bind(':sid', $surveyId);
                $this->db->bind(':uid', $userId);
                $this->db->bind(':h', $hash);
                $this->db->execute();
            }

            $this->db->commit();
            return ['ok' => true, 'message' => 'Gracias. Tu respuesta fue registrada.'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            if ((int)$e->getCode() === 23000) {
                return ['ok' => false, 'message' => 'Ya enviaste esta encuesta.'];
            }
            throw $e;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function notifyAudience($survey) {
        if (!notifications_is_ready()) {
            return 0;
        }
        $userIds = $this->resolveAudienceUserIds((int)$survey->id);
        $notif = new UserNotification();
        $link = URLROOT . '/survey/fill/' . (int)$survey->id;
        $count = 0;
        foreach ($userIds as $uid) {
            if ($notif->create([
                'user_id' => $uid,
                'title' => 'Nueva encuesta: ' . $survey->title,
                'body' => (int)$survey->is_anonymous ? 'Encuesta anónima — tu identidad no se guarda.' : 'Completá la encuesta cuando puedas.',
                'link_url' => $link,
                'type' => 'survey',
                'reference_id' => (int)$survey->id,
            ])) {
                $count++;
            }
        }
        return $count;
    }

    public function getPendingForUser($userId) {
        $user = (new User())->getUserById((int)$userId);
        $companyId = $user ? (int)$user->company_id : 0;
        $this->db->query("SELECT s.* FROM surveys s WHERE s.status = 'published' AND s.company_id = :cid");
        $this->db->bind(':cid', $companyId);
        $all = $this->db->resultSet();
        $pending = [];
        foreach ($all as $s) {
            if (!$this->isOpenForSubmission($s)) {
                continue;
            }
            if (!$this->userCanAccess((int)$s->id, $userId)) {
                continue;
            }
            if ($this->userHasResponded((int)$s->id, $userId, (int)$s->is_anonymous)) {
                continue;
            }
            $pending[] = $s;
        }
        return $pending;
    }

    public function countResponses($surveyId) {
        $this->db->query('SELECT COUNT(*) AS c FROM survey_responses WHERE survey_id = :sid');
        $this->db->bind(':sid', (int)$surveyId);
        $row = $this->db->single();
        return $row ? (int)$row->c : 0;
    }

    public function getResponsesWithUsers($surveyId) {
        $this->db->query('SELECT sr.*, u.full_name
            FROM survey_responses sr
            LEFT JOIN users u ON u.id = sr.user_id
            WHERE sr.survey_id = :sid
            ORDER BY sr.submitted_at DESC');
        $this->db->bind(':sid', (int)$surveyId);
        return $this->db->resultSet();
    }

    public function getAnswersForResponse($responseId) {
        $this->db->query('SELECT sa.*, sq.label, sq.question_type
            FROM survey_answers sa
            JOIN survey_questions sq ON sq.id = sa.question_id
            WHERE sa.response_id = :rid');
        $this->db->bind(':rid', (int)$responseId);
        return $this->db->resultSet();
    }

    public function aggregateChoiceAnswers($surveyId, $questionId) {
        $this->db->query('SELECT sa.answer_text FROM survey_answers sa
            JOIN survey_responses sr ON sr.id = sa.response_id
            WHERE sr.survey_id = :sid AND sa.question_id = :qid');
        $this->db->bind(':sid', (int)$surveyId);
        $this->db->bind(':qid', (int)$questionId);
        $rows = $this->db->resultSet();
        $counts = [];
        foreach ($rows as $r) {
            $val = $r->answer_text;
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                foreach ($decoded as $v) {
                    $counts[$v] = ($counts[$v] ?? 0) + 1;
                }
            } else {
                $counts[$val] = ($counts[$val] ?? 0) + 1;
            }
        }
        return $counts;
    }
}
