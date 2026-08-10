<?php

function avatar_default_url() {
    return URLROOT . '/img/default-avatar.svg';
}

/**
 * URL pública del avatar del usuario, o imagen por defecto si falta el archivo.
 */
function avatar_url($filename = '') {
    $default = avatar_default_url();
    $filename = trim((string)$filename);
    if ($filename === '' || $filename === 'default.png') {
        return $default;
    }
    $safe = basename($filename);
    $path = dirname(APPROOT) . '/public/uploads/avatars/' . $safe;
    if (is_file($path)) {
        return URLROOT . '/uploads/avatars/' . rawurlencode($safe);
    }
    return $default;
}

/** Actualiza la foto en sesión desde la base (evita avatar desactualizado tras login). */
function avatar_sync_session_from_db($userId) {
    $userId = (int)$userId;
    if ($userId <= 0 || !class_exists('User')) {
        return;
    }
    $pic = trim((new User())->getProfilePictureById($userId));
    if ($pic !== '') {
        $_SESSION['user_profile_picture'] = $pic;
    }
}

/** onerror: intenta SVG por defecto y luego la inicial. */
function avatar_img_onerror_attr() {
    $def = htmlspecialchars(avatar_default_url(), ENT_QUOTES, 'UTF-8');
    return "if(!this.dataset.fb){this.dataset.fb='1';this.src='{$def}';}"
        . "else{this.style.display='none';var n=this.nextElementSibling;if(n)n.style.display='inline-flex';}";
}

/** Solo dígitos; para Argentina agrega 54 si falta código país. */
function phone_digits_normalized($raw) {
    $digits = preg_replace('/\D+/', '', trim((string)$raw));
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) <= 11 && strpos($digits, '54') !== 0) {
        $digits = '54' . ltrim($digits, '0');
    }
    return $digits;
}

function phone_has_valid($raw) {
    $d = phone_digits_normalized($raw);
    return strlen($d) >= 10;
}

function phone_tel_href($raw) {
    $d = phone_digits_normalized($raw);
    return $d !== '' ? 'tel:+' . $d : '';
}

function phone_whatsapp_href($raw) {
    $d = phone_digits_normalized($raw);
    return $d !== '' ? 'https://wa.me/' . $d : '';
}
