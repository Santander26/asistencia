<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nueva_password"])) {
    require_once "controllers/LoginController.php";
    LoginController::ctrEjecutarReset();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - SIBCA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modals.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body class="light-theme">
<main id="login-view" class="view-container active-view">
    <div class="login-card">
        <div class="login-header">
            <i class="ph ph-graduation-cap logotype-icon"></i>
            <h1>Nueva Contraseña</h1>
            <p>Ingrese su nueva contraseña para <?php echo htmlspecialchars($reset_user["nombre"] . ' ' . $reset_user["apellido"], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <form method="post">
            <?php require_once "helpers/CsrfHelper.php"; echo CsrfHelper::field(); ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET["token"] ?? "", ENT_QUOTES, 'UTF-8'); ?>">
            <div class="input-group">
                <label>Nueva Contraseña</label>
                <div class="input-wrapper">
                    <i class="ph ph-lock"></i>
                    <input type="password" name="nueva_password" placeholder="Mínimo 6 caracteres" required minlength="6">
                    <button type="button" class="toggle-password" aria-label="Mostrar contraseña">
                        <i class="ph ph-eye"></i>
                    </button>
                </div>
            </div>
            <div class="input-group">
                <label>Confirmar Contraseña</label>
                <div class="input-wrapper">
                    <i class="ph ph-lock"></i>
                    <input type="password" name="confirmar_password" placeholder="Repita la contraseña" required minlength="6">
                    <button type="button" class="toggle-password" aria-label="Mostrar contraseña">
                        <i class="ph ph-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Cambiar Contraseña</button>
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
            <p>Elija una nueva contraseña segura para acceder al sistema.</p>
        </div>
    </div>
</main>
</body>
</html>
