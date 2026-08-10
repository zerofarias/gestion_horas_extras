-- Reseñas de curso (like / dislike + comentario opcional)
-- Ejecutar después de migration_learning.sql

CREATE TABLE IF NOT EXISTS course_reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    course_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    vote ENUM('like', 'dislike') NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_course_user (course_id, user_id),
    KEY idx_course_vote (course_id, vote),
    CONSTRAINT fk_crev_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_crev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
