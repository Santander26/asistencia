<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Asistencia - Login</title>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modals.css">
    <!-- Phosphor Icons para iconos modernos -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body class="light-theme">
<main id="login-view" class="view-container active-view">
    <div class="login-card">
        <div class="login-header">
           <img src="css/escudo.png" class="logotype-icon" alt="Escudo">
            <h1>SIBCA</h1>
            <p>Gestión de Asistencia</p>
        </div>
        <form id="login-form" method="post">
            <?php require_once "helpers/CsrfHelper.php"; echo CsrfHelper::field(); ?>
            <div class="input-group">
                <label for="email">Correo Electrónico o Usuario</label>
                <div class="input-wrapper">
                    <i class="ph ph-user"></i>
                    <input type="text" id="email" name="ingresoEmail" placeholder="admin@escuela.edu" required>
                </div>
            </div>
            <div class="input-group">
                <label for="password">Contraseña</label>
                <div class="input-wrapper">
                    <i class="ph ph-lock"></i>
                    <input type="password" id="password" name="ingresoPassword" placeholder="••••••••" required>
                    <button type="button" class="toggle-password" aria-label="Mostrar contraseña">
                        <i class="ph ph-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-actions">
                <label class="remember-me">
                    <input type="checkbox" name="recordarme"> Recordarme
                </label>
                <a href="index.php?ruta=olvide_password" style="font-size:0.8rem; color:var(--color-primary); text-decoration:none;">¿Olvidó su contraseña?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            <?php
                if (isset($_SESSION["timeout"]) && $_SESSION["timeout"]) {
                    unset($_SESSION["timeout"]);
                    echo '<br><div class="alert alert-warning">Sesión expirada por inactividad. Inicie sesión nuevamente.</div>';
                }
                if(isset($_POST['ingresoEmail'])) {
                    require_once "controllers/LoginController.php";
                    $login = new LoginController();
                    $login->ctrIngreso();
                }
            ?>
        </form>
        <div class="login-footer">
            <p>Acceso seguro para personal autorizado.</p>
        </div>
    </div>
    <div class="login-illustration">
        <div class="glass-shape shape-1"></div>
        <div class="glass-shape shape-2"></div>
        <div class="illustration-content">
            <h2>Control de Asistencia</h2>
            <p>Gestione asistencia, horarios e informes en tiempo real desde cualquier dispositivo.</p>
        </div>
    </div>
</main>
</body>
</html>