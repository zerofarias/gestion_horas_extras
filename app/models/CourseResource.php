<?php

class CourseResource {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public static function isReady() {
        return learning_enrich_is_ready();
    }

    public function getByCourse($courseId, $lessonId = null) {
        $sql = 'SELECT r.*, u.full_name AS creator_name, l.title AS lesson_title
            FROM course_resources r
            LEFT JOIN users u ON u.id = r.created_by
            LEFT JOIN course_lessons l ON l.id = r.lesson_id
            WHERE r.course_id = :cid AND r.is_visible = 1';
        if ($lessonId !== null) {
            $sql .= ' AND (r.lesson_id IS NULL OR r.lesson_id = :lid)';
        }
        $sql .= ' ORDER BY r.sort_order ASC, r.title ASC';
        $this->db->query($sql);
        $this->db->bind(':cid', (int)$courseId);
        if ($lessonId !== null) {
            $this->db->bind(':lid', (int)$lessonId);
        }
        return $this->db->resultSet();
    }

    public function getCourseLevel($courseId) {
        $this->db->query('SELECT r.*, u.full_name AS creator_name
            FROM course_resources r
            LEFT JOIN users u ON u.id = r.created_by
            WHERE r.course_id = :cid AND r.lesson_id IS NULL AND r.is_visible = 1
            ORDER BY r.sort_order ASC, r.title ASC');
        $this->db->bind(':cid', (int)$courseId);
        return $this->db->resultSet();
    }

    public function getAllByCourse($courseId) {
        $this->db->query('SELECT r.*, u.full_name AS creator_name, l.title AS lesson_title
            FROM course_resources r
            LEFT JOIN users u ON u.id = r.created_by
            LEFT JOIN course_lessons l ON l.id = r.lesson_id
            WHERE r.course_id = :cid
            ORDER BY r.lesson_id IS NULL DESC, r.sort_order ASC, r.title ASC');
        $this->db->bind(':cid', (int)$courseId);
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query('SELECT * FROM course_resources WHERE id = :id');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function create(array $data) {
        $this->db->query('INSERT INTO course_resources (
            course_id, lesson_id, title, description, resource_type,
            external_url, file_path, file_name, file_size, sort_order, is_visible, created_by
        ) VALUES (
            :course_id, :lesson_id, :title, :description, :resource_type,
            :external_url, :file_path, :file_name, :file_size, :sort_order, :is_visible, :created_by
        )');
        $this->bindFields($data);
        if ($this->db->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function delete($id, $courseId) {
        $this->db->query('DELETE FROM course_resources WHERE id = :id AND course_id = :cid');
        $this->db->bind(':id', (int)$id);
        $this->db->bind(':cid', (int)$courseId);
        return $this->db->execute();
    }

    private function bindFields(array $data) {
        $this->db->bind(':course_id', (int)$data['course_id']);
        $this->db->bind(':lesson_id', !empty($data['lesson_id']) ? (int)$data['lesson_id'] : null);
        $this->db->bind(':title', trim($data['title']));
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':resource_type', $data['resource_type'] ?? 'document');
        $this->db->bind(':external_url', $data['external_url'] ?? null);
        $this->db->bind(':file_path', $data['file_path'] ?? null);
        $this->db->bind(':file_name', $data['file_name'] ?? null);
        $this->db->bind(':file_size', !empty($data['file_size']) ? (int)$data['file_size'] : null);
        $this->db->bind(':sort_order', (int)($data['sort_order'] ?? 0));
        $this->db->bind(':is_visible', !empty($data['is_visible']) ? 1 : 0);
        $this->db->bind(':created_by', !empty($data['created_by']) ? (int)$data['created_by'] : null);
    }
}
