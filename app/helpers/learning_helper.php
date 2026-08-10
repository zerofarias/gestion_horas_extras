<?php

function learning_is_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW TABLES LIKE 'courses'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function learning_slugify($text) {
    $text = mb_strtolower(trim((string)$text), 'UTF-8');
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($t !== false) {
            $text = $t;
        }
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'curso';
}

/** ID de video YouTube si la URL es reconocible. */
function learning_youtube_id($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }
    $patterns = [
        '#youtube\.com/watch\?(?:[^&]*&)*v=([\w-]{6,})#i',
        '#youtube\.com/embed/([\w-]{6,})#i',
        '#youtube\.com/shorts/([\w-]{6,})#i',
        '#youtube\.com/live/([\w-]{6,})#i',
        '#youtu\.be/([\w-]{6,})#i',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) {
            return $m[1];
        }
    }
    return '';
}

/** Parámetros de embed para mantener al usuario en la plataforma. */
function learning_embed_query($provider) {
    if ($provider === 'youtube') {
        return 'rel=0&modestbranding=1&playsinline=1&iv_load_policy=3';
    }
    if ($provider === 'vimeo') {
        return 'title=0&byline=0&portrait=0';
    }
    return '';
}

function learning_append_query($url, $query) {
    if ($query === '') {
        return $url;
    }
    $sep = strpos($url, '?') !== false ? '&' : '?';
    return $url . $sep . $query;
}

/** Convierte URL YouTube/Vimeo a embed (reproduce dentro del sitio). */
function learning_embed_url($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }
    $yt = learning_youtube_id($url);
    if ($yt !== '') {
        $embed = 'https://www.youtube-nocookie.com/embed/' . $yt;
        return learning_append_query($embed, learning_embed_query('youtube'));
    }
    if (preg_match('#player\.vimeo\.com/video/(\d+)#', $url, $m)) {
        return learning_append_query(
            'https://player.vimeo.com/video/' . $m[1],
            learning_embed_query('vimeo')
        );
    }
    if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
        return learning_append_query(
            'https://player.vimeo.com/video/' . $m[1],
            learning_embed_query('vimeo')
        );
    }
    if (preg_match('#youtube-nocookie\.com/embed/[\w-]+#i', $url)
        || preg_match('#youtube\.com/embed/[\w-]+#i', $url)) {
        return learning_append_query($url, learning_embed_query('youtube'));
    }
    return $url;
}

function learning_status_label($status) {
    $map = [
        'not_started'  => 'Pendiente',
        'in_progress'  => 'En curso',
        'completed'    => 'Completado',
        'failed_quiz'  => 'Quiz no aprobado',
    ];
    return $map[$status] ?? $status;
}

function learning_status_badge_class($status) {
    $map = [
        'not_started' => 'lrn-badge-pending',
        'in_progress' => 'lrn-badge-progress',
        'completed'   => 'lrn-badge-done',
        'failed_quiz' => 'lrn-badge-failed',
    ];
    return $map[$status] ?? 'lrn-badge-pending';
}

/** @deprecated Usar learning_lesson_stream_url o learning_resource_stream_url */
function learning_media_url($path) {
    $path = trim((string)$path);
    if ($path === '' || preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return '';
}

/** ¿La URL es YouTube o Vimeo? */
function learning_is_embed_video_url($url) {
    $url = trim((string)$url);
    return (bool)preg_match('#(youtube\.com|youtu\.be|vimeo\.com)#i', $url);
}

/**
 * Modo de reproducción de una lección: iframe | video | pdf | text
 * @return array{mode:string,src?:string,body?:string}
 */
function learning_lesson_player($lesson, $forAdmin = false) {
    if (!$lesson) {
        return ['mode' => 'text', 'body' => ''];
    }
    $type = $lesson->content_type ?? 'text';
    $url = trim((string)($lesson->content_url ?? ''));
    $courseId = (int)($lesson->course_id ?? 0);
    $lessonId = (int)($lesson->id ?? 0);
    $localSrc = ($courseId > 0 && $lessonId > 0) ? learning_lesson_stream_url($courseId, $lessonId, $forAdmin) : '';

    if ($type === 'video' && $url !== '') {
        if (learning_is_embed_video_url($url)) {
            return ['mode' => 'iframe', 'src' => learning_embed_url($url)];
        }
        if (preg_match('#^https?://#i', $url)) {
            return ['mode' => 'iframe', 'src' => $url];
        }
        return ['mode' => 'video', 'src' => $localSrc];
    }

    if ($type === 'file' && $url !== '') {
        if (preg_match('/\.(xlsx?|csv|docx?|pptx?|zip|rar)$/i', $url)) {
            return ['mode' => 'download', 'src' => $localSrc, 'filename' => basename($url)];
        }
        return ['mode' => 'pdf', 'src' => $localSrc];
    }

    return ['mode' => 'text', 'body' => (string)($lesson->content_body ?? '')];
}

function learning_lesson_type_icon($type) {
    $icons = [
        'video' => 'fa-play-circle',
        'file'  => 'fa-file-pdf',
        'text'  => 'fa-align-left',
    ];
    return $icons[$type] ?? 'fa-book';
}

function learning_table_exists($table) {
    static $cache = [];
    $table = preg_replace('/[^a-z0-9_]/', '', (string)$table);
    if ($table === '') {
        return false;
    }
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    try {
        $db = new Database();
        $db->query("SHOW TABLES LIKE '{$table}'");
        $cache[$table] = (bool)$db->single();
    } catch (Throwable $e) {
        $cache[$table] = false;
    }
    return $cache[$table];
}

function learning_resources_is_ready() {
    return learning_table_exists('course_resources');
}

function learning_discussions_is_ready() {
    return learning_table_exists('course_discussions');
}

function learning_notes_is_ready() {
    return learning_table_exists('user_lesson_notes');
}

/** Columnas objectives / instructor_notes en course_lessons. */
function learning_lesson_meta_is_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW COLUMNS FROM course_lessons LIKE 'objectives'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/** Enrich completo: materiales + preguntas + notas privadas. */
function learning_enrich_is_ready() {
    return learning_resources_is_ready()
        && learning_discussions_is_ready()
        && learning_notes_is_ready()
        && learning_lesson_meta_is_ready();
}

function learning_reviews_is_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW TABLES LIKE 'course_reviews'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/** Porcentaje de valoraciones positivas (0–100). */
function learning_review_positive_pct($stats) {
    $total = (int)($stats->total ?? 0);
    if ($total === 0) {
        return null;
    }
    return (int)round(((int)($stats->likes ?? 0) / $total) * 100);
}

/** Progreso coherente para el reproductor (obligatorias vs % en BD). */
function learning_enrollment_progress_meta($lessons, $completedIds, $enrollment) {
    $requiredTotal = 0;
    $requiredDone = 0;
    foreach ($lessons as $l) {
        if ($l->is_required) {
            $requiredTotal++;
            if (in_array((int)$l->id, $completedIds, true)) {
                $requiredDone++;
            }
        }
    }
    if ($requiredTotal === 0) {
        $requiredTotal = count($lessons);
        $requiredDone = count($completedIds);
    }
    $status = $enrollment->status ?? 'not_started';
    $pct = (int)($enrollment->progress_percent ?? 0);
    $barPct = min(100, max(0, $pct));
    $label = $pct . '% completado';
    if ($status === 'completed') {
        $label = 'Curso completado';
        $barPct = 100;
    } elseif ($status === 'failed_quiz') {
        $label = 'Lecciones listas · reintentá el cuestionario';
        $barPct = min(99, max($barPct, 85));
    }
    return [
        'required_done' => $requiredDone,
        'required_total' => max(1, $requiredTotal),
        'bar_percent' => $barPct,
        'label' => $label,
    ];
}

/** Texto de progreso en catálogo de cursos. */
function learning_catalog_progress_text($enrollment, $lessonCount) {
    $status = $enrollment->status ?? 'not_started';
    $pct = (int)($enrollment->progress_percent ?? 0);
    $pos = min(max(1, (int)($enrollment->current_lesson_position ?? 1)), max(1, $lessonCount));
    if ($status === 'completed') {
        return 'Curso completado';
    }
    if ($status === 'failed_quiz') {
        return 'Reintentá el cuestionario';
    }
    return 'Lección ' . $pos . ' de ' . max(1, $lessonCount) . ' · ' . $pct . '%';
}

/** Mensaje de estrellas según si el curso exige cuestionario. */
function learning_stars_promise_text($course) {
    $stars = (int)($course->stars_on_complete ?? 0);
    if ($stars <= 0) {
        return '';
    }
    if (!empty($course->require_quiz)) {
        return '+' . $stars . ' estrellas al aprobar el cuestionario';
    }
    return '+' . $stars . ' estrellas al completar';
}

function learning_format_bytes($bytes) {
    $bytes = (int)$bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / 1048576, 1) . ' MB';
}

function learning_resource_icon($type) {
    $map = [
        'link' => 'fa-link',
        'video' => 'fa-play-circle',
        'pdf' => 'fa-file-pdf',
        'document' => 'fa-file-word',
        'spreadsheet' => 'fa-file-excel',
        'archive' => 'fa-file-archive',
        'image' => 'fa-file-image',
        'other' => 'fa-file',
    ];
    return $map[$type] ?? 'fa-file';
}

function learning_resource_url($resource, $forAdmin = false) {
    if (!empty($resource->external_url)) {
        return $resource->external_url;
    }
    if (!empty($resource->file_path) && !empty($resource->id)) {
        return learning_resource_stream_url((int)$resource->id, $forAdmin);
    }
    return '';
}

function learning_ext_to_resource_type($ext) {
    $ext = strtolower($ext);
    $map = [
        'pdf' => 'pdf',
        'xls' => 'spreadsheet', 'xlsx' => 'spreadsheet', 'csv' => 'spreadsheet',
        'doc' => 'document', 'docx' => 'document', 'txt' => 'document', 'rtf' => 'document',
        'ppt' => 'document', 'pptx' => 'document',
        'zip' => 'archive', 'rar' => 'archive', '7z' => 'archive',
        'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'webp' => 'image',
        'mp4' => 'video', 'webm' => 'video', 'ogg' => 'video',
    ];
    return $map[$ext] ?? 'other';
}

function learning_discussion_type_label($type) {
    $map = ['question' => 'Pregunta', 'suggestion' => 'Sugerencia', 'comment' => 'Comentario'];
    return $map[$type] ?? $type;
}

/** Tipos de archivo permitidos para lección (content_type=file) y materiales */
function learning_allowed_upload_extensions() {
    return ['pdf', 'xls', 'xlsx', 'doc', 'docx', 'pptx', 'zip', 'mp4', 'webm', 'png', 'jpg', 'jpeg'];
}

/** Mezcla preguntas y opciones con semilla fija por inscripción (mismo orden en reintento). */
function learning_shuffle_quiz_questions(array $questions, $seed) {
    $seed = (int)$seed;
    if ($seed <= 0 || count($questions) < 2) {
        return $questions;
    }
    mt_srand($seed);
    $list = array_values($questions);
    for ($i = count($list) - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        $tmp = $list[$i];
        $list[$i] = $list[$j];
        $list[$j] = $tmp;
    }
    $display = 1;
    foreach ($list as &$q) {
        $q->display_position = $display++;
        if (!empty($q->options) && is_array($q->options)) {
            $opts = array_values($q->options);
            for ($i = count($opts) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                $tmp = $opts[$i];
                $opts[$i] = $opts[$j];
                $opts[$j] = $tmp;
            }
            $q->options = $opts;
        }
    }
    unset($q);
    mt_srand();
    return $list;
}

function learning_quiz_columns_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW COLUMNS FROM course_enrollments LIKE 'quiz_order_seed'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}
