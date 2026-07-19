<?php

return [
    'smtp_host'     => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_port'     => (int)(getenv('SMTP_PORT') ?: 587),
    'smtp_secure'   => getenv('SMTP_SECURE') ?: 'tls',
    'smtp_auth'     => true,
    'smtp_username' => getenv('SMTP_USER') ?: '',
    'smtp_password' => getenv('SMTP_PASS') ?: '',
    'from_email'    => getenv('MAIL_FROM') ?: 'noreply@sibca.edu',
    'from_name'     => getenv('MAIL_FROM_NAME') ?: 'SIBCA - Sistema de Asistencia',
];
