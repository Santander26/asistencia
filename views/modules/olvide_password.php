<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["documento_identidad"])) {
    require_once "controllers/LoginController.php";
    LoginController::ctrSolicitarReset();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - SIBCA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modals.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body class="light-theme">
<main id="login-view" class="view-container active-view">
    <div class="login-card">
        <div class="login-header">
            <i class="ph ph-graduation-cap logotype-icon"></i>
            <h1>Recuperar Contraseña</h1>
            <p>Ingrese su documento de identidad para generar un enlace de restablecimiento.</p>
        </div>

        <?php if (isset($_GET["enviado"]) && $_GET["enviado"] === "ok"): ?>
        <div class="alert alert-success" style="text-align:center;">
            <p><strong>Correo enviado correctamente.</strong></p>
            <p style="font-size:0.85rem; margin-top:0.5rem;">Revisa tu bandeja de entrada. Si no aparece, revisa la carpeta de spam.</p>
        </div>
        <?php elseif (isset($_GET["link"])):
            $link = htmlspecialchars($_GET["link"], ENT_QUOTES, 'UTF-8');
        ?>
        <div class="alert alert-warning" style="text-align:center; border-left:4px solid #f59e0b;">
            <p><strong>No se pudo enviar el correo.</strong></p>
            <p style="font-size:0.85rem; margin-top:0.5rem;">Usa este enlace directamente para restablecer tu contraseña (vence en 1 hora):</p>
            <p style="word-break:break-all; font-size:0.85rem; margin-top:0.5rem;">
                <a href="<?php echo $link; ?>"><?php echo $link; ?></a>
            </p>
        </div>
        <?php endif; ?>

        <form method="post">
            <?php require_once "helpers/CsrfHelper.php"; echo CsrfHelper::field(); ?>
            <div class="input-group">
                <label>Documento de Identidad</label>
                <div class="input-wrapper">
                    <i class="ph ph-identification-card"></i>
                    <input type="text" name="documento_identidad" placeholder="Ingrese su documento" required autocomplete="off">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Generar Enlace</button>
            <?php if (isset($_GET["error"])): ?>
            <br><div class="alert alert-danger"><?php echo htmlspecialchars($_GET["error"], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </form>
        <div class="login-footer">
            <p><a href="index.php?ruta=login" style="color:var(--color-primary); text-decoration:none;">Volver al inicio de sesión</a></p>
        </div>
    </div>
    <div class="login-illustration">
        <div class="glass-shape shape-1"></div>
        <div class="glass-shape shape-2"></div>
        <div class="illustration-content">
            <h2>Restablecimiento de Contraseña</h2>
            <p>Generaremos un enlace único para que puedas crear una nueva contraseña.</p>
        </div>
    </div>
</main>
</body>
</html>
