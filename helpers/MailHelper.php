<?php
require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../lib/phpmailer/SMTP.php';
require_once __DIR__ . '/../lib/phpmailer/Exception.php';

class MailHelper
{
    static public function getConfig()
    {
        $jsonFile = __DIR__ . '/../config/smtp.json';
        $json = [];
        if (file_exists($jsonFile)) {
            $json = json_decode(file_get_contents($jsonFile), true) ?: [];
        }
        return [
            'smtp_host'     => $json['smtp_host'] ?? getenv('SMTP_HOST') ?: 'smtp.gmail.com',
            'smtp_port'     => (int)($json['smtp_port'] ?? getenv('SMTP_PORT') ?: 587),
            'smtp_secure'   => $json['smtp_secure'] ?? getenv('SMTP_SECURE') ?: 'tls',
            'smtp_auth'     => true,
            'smtp_username' => $json['smtp_username'] ?? getenv('SMTP_USER') ?: '',
            'smtp_password' => $json['smtp_password'] ?? getenv('SMTP_PASS') ?: '',
            'from_email'    => $json['from_email'] ?? getenv('MAIL_FROM') ?: 'noreply@sibca.edu',
            'from_name'     => $json['from_name'] ?? getenv('MAIL_FROM_NAME') ?: 'SIBCA - Sistema de Asistencia',
        ];
    }

    static public function guardarConfig($datos)
    {
        $jsonFile = __DIR__ . '/../config/smtp.json';
        file_put_contents($jsonFile, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    static public function enviar($para, $nombre, $asunto, $cuerpoHtml)
    {
        $config = self::getConfig();

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $config['smtp_host'];
            $mail->SMTPAuth   = $config['smtp_auth'];
            $mail->Username   = $config['smtp_username'];
            $mail->Password   = $config['smtp_password'];
            $mail->SMTPSecure = $config['smtp_secure'];
            $mail->Port       = $config['smtp_port'];

            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($para, $nombre);

            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpoHtml;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $cuerpoHtml));

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
            return false;
        }
    }
}
