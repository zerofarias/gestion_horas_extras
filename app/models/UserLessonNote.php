<?php

class UserLessonNote {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public static function isReady() {
        return learning_enrich_is_ready();
    }

    public function get($userId, $lessonId) {
        $this->db->query('SELECT * FROM user_lesson_notes WHERE user_id = :uid AND lesson_id = :lid');
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':lid', (int)$lessonId);
        return $this->db->single();
    }

    public function save($userId, $lessonId, $body) {
        $existing = $this->get($userId, $lessonId);
        if ($existing) {
            $this->db->query('UPDATE user_lesson_notes SET body = :body WHERE id = :id');
            $this->db->bind(':body', trim($body));
            $this->db->bind(':id', (int)$existing->id);
            return $this->db->execute();
        }
        $this->db->query('INSERT INTO user_lesson_notes (user_id, lesson_id, body) VALUES (:uid, :lid, :body)');
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':lid', (int)$lessonId);
        $this->db->bind(':body', trim($body));
        return $this->db->execute();
    }
}
