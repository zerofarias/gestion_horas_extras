<?php

class CourseReview {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public static function isReady() {
        return learning_reviews_is_ready();
    }

    /** @return object{likes:int,dislikes:int,total:int} */
    public function getStats($courseId) {
        $this->db->query('SELECT
            SUM(vote = \'like\') AS likes,
            SUM(vote = \'dislike\') AS dislikes,
            COUNT(*) AS total
            FROM course_reviews WHERE course_id = :cid');
        $this->db->bind(':cid', (int)$courseId);
        $row = $this->db->single();
        return (object)[
            'likes' => (int)($row->likes ?? 0),
            'dislikes' => (int)($row->dislikes ?? 0),
            'total' => (int)($row->total ?? 0),
        ];
    }

    public function getByCourse($courseId, $limit = 30) {
        $this->db->query('SELECT r.*, u.full_name AS author_name
            FROM course_reviews r
            JOIN users u ON u.id = r.user_id
            WHERE r.course_id = :cid
            ORDER BY r.updated_at DESC
            LIMIT ' . (int)$limit);
        $this->db->bind(':cid', (int)$courseId);
        return $this->db->resultSet();
    }

    public function getUserReview($courseId, $userId) {
        $this->db->query('SELECT * FROM course_reviews
            WHERE course_id = :cid AND user_id = :uid LIMIT 1');
        $this->db->bind(':cid', (int)$courseId);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->single();
    }

    public function upsert($courseId, $userId, $vote, $comment = null) {
        $vote = $vote === 'dislike' ? 'dislike' : 'like';
        $comment = trim((string)$comment);
        $comment = $comment === '' ? null : $comment;

        $existing = $this->getUserReview($courseId, $userId);
        if ($existing) {
            $this->db->query('UPDATE course_reviews SET vote = :vote, comment = :comment, updated_at = NOW()
                WHERE id = :id');
            $this->db->bind(':vote', $vote);
            $this->db->bind(':comment', $comment);
            $this->db->bind(':id', (int)$existing->id);
            return $this->db->execute();
        }

        $this->db->query('INSERT INTO course_reviews (course_id, user_id, vote, comment)
            VALUES (:cid, :uid, :vote, :comment)');
        $this->db->bind(':cid', (int)$courseId);
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':vote', $vote);
        $this->db->bind(':comment', $comment);
        return $this->db->execute();
    }
}
