<?php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443,
    ]);
    session_start();
}
require_once "controllers/PlantillaController.php";
require_once "controllers/AsistenciaController.php";
require_once "controllers/ConfiguracionController.php";

require_once "models/AsistenciaModel.php";
require_once "models/PersonalModel.php";

$plantilla = new ControladorPlantilla();
$plantilla->ctrPlantilla();
?>