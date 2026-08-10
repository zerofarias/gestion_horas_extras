<?php

class MailService {

    public function isAvailable() {
        if (!notifications_is_ready()) {
            return false;
        }
        $settings = (new MailSettings())->get();
        return $settings && (int)$settings->is_enabled === 1 && !empty($settings->smtp_host) && !empty($settings->from_email);
    }

    public function sendToUser($userId, $subject, $htmlBody) {
        $user = (new User())->getUserById((int)$userId);
        if (!$user || empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'El usuario no tiene email válido.'];
        }
        return $this->send($user->email, $subject, $htmlBody);
    }

    public function send($toEmail, $subject, $htmlBody) {
        if (!$this->isAvailable()) {
            return ['ok' => false, 'message' => 'El envío por correo no está configurado o está desactivado.'];
        }

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $autoload = dirname(APPROOT) . '/vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }
        }
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return ['ok' => false, 'message' => 'Ejecutá composer install en la raíz del proyecto para habilitar PHPMailer.'];
        }

        $settings = (new MailSettings())->get();
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $settings->smtp_host;
            $mail->Port = (int)$settings->smtp_port;
            $enc = $settings->smtp_encryption ?? 'tls';
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            if (!empty($settings->smtp_user)) {
                $mail->SMTPAuth = true;
                $mail->Username = $settings->smtp_user;
                $mail->Password = $settings->smtp_password ?? '';
            }
            $fromName = $settings->from_name ?: (function_exists('app_name') ? app_name() : SITENAME);
            $mail->setFrom($settings->from_email, $fromName);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $mail->send();
            return ['ok' => true, 'message' => 'Correo enviado.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Error SMTP: ' . $e->getMessage()];
        }
    }
}
