<?php

class CourseDiscussion {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public static function isReady() {
        return learning_enrich_is_ready();
    }

    public function getByCourse($courseId, $lessonId = null, $limit = 50) {
        $sql = 'SELECT d.*, u.full_name AS author_name, u.profile_picture,
            r.full_name AS replier_name, l.title AS lesson_title
            FROM course_discussions d
            JOIN users u ON u.id = d.user_id
            LEFT JOIN users r ON r.id = d.replied_by
            LEFT JOIN course_lessons l ON l.id = d.lesson_id
            WHERE d.course_id = :cid';
        if ($lessonId !== null) {
            $sql .= ' AND (d.lesson_id IS NULL OR d.lesson_id = :lid)';
        }
        $sql .= ' ORDER BY d.is_resolved ASC, d.created_at DESC LIMIT ' . (int)$limit;
        $this->db->query($sql);
        $this->db->bind(':cid', (int)$courseId);
        if ($lessonId !== null) {
            $this->db->bind(':lid', (int)$lessonId);
        }
        return $this->db->resultSet();
    }

    public function getPendingCount($courseId) {
        $this->db->query('SELECT COUNT(*) AS c FROM course_discussions
            WHERE course_id = :cid AND post_type = \'question\' AND is_resolved = 0 AND admin_reply IS NULL');
        $this->db->bind(':cid', (int)$courseId);
        $row = $this->db->single();
        return $row ? (int)$row->c : 0;
    }

    public function create($courseId, $userId, array $data) {
        $this->db->query('INSERT INTO course_discussions (course_id, lesson_id, user_id, post_type, body)
            VALUES (:cid, :lid, :uid, :type, :body)');
        $this->db->bind(':cid', (int)$courseId);
        $this->db->bind(':lid', !empty($data['lesson_id']) ? (int)$data['lesson_id'] : null);
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':type', $data['post_type'] ?? 'question');
        $this->db->bind(':body', trim($data['body']));
        if ($this->db->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function reply($id, $adminUserId, $reply, $resolve = true) {
        $this->db->query('UPDATE course_discussions SET
            admin_reply = :reply, replied_by = :rid, replied_at = NOW(), is_resolved = :resolved
            WHERE id = :id');
        $this->db->bind(':reply', trim($reply));
        $this->db->bind(':rid', (int)$adminUserId);
        $this->db->bind(':resolved', $resolve ? 1 : 0);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function getById($id) {
        $this->db->query('SELECT d.*, c.company_id FROM course_discussions d
            JOIN courses c ON c.id = d.course_id WHERE d.id = :id');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }
}
