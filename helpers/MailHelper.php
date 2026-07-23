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
            'from_email'    => $json['from_email'] ?? getenv('MAIL_FROM') ?: '',
            'from_name'     => $json['from_name'] ?? getenv('MAIL_FROM_NAME') ?: 'SIBCA - Sistema de Asistencia',
            'brevo_api_key' => $json['brevo_api_key'] ?? getenv('BREVO_API_KEY') ?: '',
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

        // Si hay API key de Brevo, usar API HTTP (puerto 443, no bloqueado)
        if (!empty($config['brevo_api_key'])) {
            return self::enviarPorBrevo($config, $para, $nombre, $asunto, $cuerpoHtml);
        }

        return self::enviarPorSMTP($config, $para, $nombre, $asunto, $cuerpoHtml);
    }

    static private function enviarPorBrevo($config, $para, $nombre, $asunto, $cuerpoHtml)
    {
        $data = json_encode([
            'sender' => [
                'name'  => $config['from_name'],
                'email' => $config['from_email'],
            ],
            'to' => [[
                'email' => $para,
                'name'  => $nombre,
            ]],
            'subject'     => $asunto,
            'htmlContent' => $cuerpoHtml,
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . $config['brevo_api_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("Brevo API error: HTTP $httpCode - $response");
        return false;
    }

    static private function enviarPorSMTP($config, $para, $nombre, $asunto, $cuerpoHtml)
    {
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
