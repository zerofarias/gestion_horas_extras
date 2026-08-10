<?php

class Task {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getByCompany($companyId) {
        $this->db->query('SELECT t.*,
            (SELECT COUNT(*) FROM task_completions tc WHERE tc.task_id = t.id) AS completion_count
            FROM tasks t WHERE t.company_id = :company_id AND t.is_active = 1
            ORDER BY t.due_date ASC, t.title ASC');
        $this->db->bind(':company_id', (int)$companyId);
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query('SELECT * FROM tasks WHERE id = :id');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function create(array $data) {
        $this->db->query('INSERT INTO tasks (company_id, title, description, due_date, stars_on_complete, created_by)
            VALUES (:company_id, :title, :description, :due_date, :stars_on_complete, :created_by)');
        $this->db->bind(':company_id', (int)$data['company_id']);
        $this->db->bind(':title', trim($data['title']));
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':due_date', !empty($data['due_date']) ? $data['due_date'] : null);
        $this->db->bind(':stars_on_complete', (int)($data['stars_on_complete'] ?? 0));
        $this->db->bind(':created_by', !empty($data['created_by']) ? (int)$data['created_by'] : null);
        if ($this->db->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function update($id, array $data) {
        $this->db->query('UPDATE tasks SET title = :title, description = :description,
            due_date = :due_date, stars_on_complete = :stars_on_complete, is_active = :is_active
            WHERE id = :task_id AND company_id = :p_company_id');
        $this->db->bind(':title', trim($data['title']));
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':due_date', !empty($data['due_date']) ? $data['due_date'] : null);
        $this->db->bind(':stars_on_complete', (int)($data['stars_on_complete'] ?? 0));
        $this->db->bind(':is_active', !empty($data['is_active']) ? 1 : 0);
        $this->db->bind(':task_id', (int)$id);
        $this->db->bind(':p_company_id', (int)$data['company_id']);
        return $this->db->execute();
    }

    public function getAssignments($taskId) {
        $this->db->query('SELECT * FROM task_assignments WHERE task_id = :tid');
        $this->db->bind(':tid', (int)$taskId);
        return $this->db->resultSet();
    }

    public function replaceAssignments($taskId, array $rows) {
        $this->db->query('DELETE FROM task_assignments WHERE task_id = :tid');
        $this->db->bind(':tid', (int)$taskId);
        $this->db->execute();
        foreach ($rows as $row) {
            $this->db->query('INSERT INTO task_assignments (task_id, target_type, target_id)
                VALUES (:task_id, :target_type, :target_id)');
            $this->db->bind(':task_id', (int)$taskId);
            $this->db->bind(':target_type', $row['target_type']);
            $this->db->bind(':target_id', (int)$row['target_id']);
            $this->db->execute();
        }
        return true;
    }

    public function isCompletedByUser($taskId, $userId) {
        $this->db->query('SELECT id FROM task_completions WHERE task_id = :tid AND user_id = :uid');
        $this->db->bind(':tid', (int)$taskId);
        $this->db->bind(':uid', (int)$userId);
        return (bool)$this->db->single();
    }

    public function complete($taskId, $userId, $note = null) {
        if ($this->isCompletedByUser($taskId, $userId)) {
            return ['ok' => false, 'message' => 'Ya marcaste esta tarea como hecha.'];
        }
        $task = $this->getById($taskId);
        if (!$task) {
            return ['ok' => false, 'message' => 'Tarea no encontrada.'];
        }
        $this->db->query('INSERT INTO task_completions (task_id, user_id, note) VALUES (:tid, :uid, :note)');
        $this->db->bind(':tid', (int)$taskId);
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':note', $note);
        $this->db->execute();
        $stars = (int)$task->stars_on_complete;
        if ($stars > 0) {
            (new StarWallet())->addStars($userId, $stars, 'task', (int)$taskId, 'Tarea: ' . $task->title);
        }
        return ['ok' => true, 'message' => 'Tarea completada.', 'stars' => $stars];
    }

    public function getCompletions($taskId) {
        $this->db->query('SELECT tc.*, u.full_name FROM task_completions tc
            JOIN users u ON u.id = tc.user_id WHERE tc.task_id = :tid ORDER BY tc.completed_at DESC');
        $this->db->bind(':tid', (int)$taskId);
        return $this->db->resultSet();
    }
}
