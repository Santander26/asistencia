<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once "controllers/AsistenciaController.php";
require_once "helpers/RbacHelper.php";
$rolId = RbacHelper::getRolId();
$mostrarNotificaciones = ($rolId !== 3); // Admin, Director, Secretaria
$tardeHoy = AsistenciaController::ctrContarTardeHoy();
$tardeLista = ($tardeHoy > 0 && $mostrarNotificaciones) ? AsistenciaController::ctrListarTardeHoy() : [];
$nombreUsuario = $_SESSION["nombre"] ?? "Usuario";
$apellidoUsuario = $_SESSION["apellido"] ?? "Invitado";
$rolUsuario = $_SESSION["rol"] ?? match ($rolId) { 4 => 'Admin', 1 => 'Director', 2 => 'Secretaria', default => 'Personal' };
?>
<!-- Topbar / Cabecera -->
<header class="topbar">
    <div class="topbar-left">
        <button id="toggle-sidebar-btn" class="toggle-sidebar">
            <i class="ph ph-list"></i>
        </button>
        <div class="search-bar">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" placeholder="Buscar personal, cargos...">
        </div>
    </div>

    <div class="topbar-right">
        <button id="theme-toggle" class="icon-btn" aria-label="Cambiar modo claro/oscuro">
            <i class="ph ph-moon"></i>
        </button>
        <?php if ($mostrarNotificaciones): ?>
        <div class="notifications-wrapper">
            <button class="icon-btn notification-btn" id="notification-btn">
                <i class="ph ph-bell"></i>
                <?php if ($tardeHoy > 0): ?>
                <span class="badge badge-warning"><?php echo $tardeHoy; ?></span>
                <?php endif; ?>
            </button>
            <div class="notification-dropdown" id="notification-dropdown">
                <div class="notification-header">
                    <h4>Notificaciones</h4>
                </div>
                <div class="notification-list">
                    <?php if ($tardeHoy > 0): ?>
                    <div class="notification-item">
                        <i class="ph ph-warning-circle text-yellow"></i>
                        <div>
                            <strong><?php echo $tardeHoy; ?> empleado(s) llegaron tarde hoy</strong>
                            <ul style="margin:4px 0 0 16px; font-size:0.85rem;">
                                <?php foreach ($tardeLista as $t): ?>
                                <li><?php echo htmlspecialchars($t['nombre'] . ' ' . $t['apellido'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo $t['nombre_cargo'] ?? '--'; ?> (<?php echo substr($t['hora_entrada'], 0, 5); ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="notification-item">
                        <i class="ph ph-check-circle text-green"></i>
                        <div>
                            <strong>No hay novedades</strong>
                            <p>Todos los empleados registrados están en horario.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="notification-footer">
                    <a href="index.php?ruta=reportes">Ver reporte completo</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php
        $avatarSrc = '';
        if (isset($_SESSION["id"]) && !empty($_SESSION["id"])) {
            require_once "models/UsuarioModel.php";
            $user = UsuarioModel::mdlMostrarUsuario('personal', 'id', $_SESSION["id"]);
            if ($user && !empty($user['foto'])) {
                $possiblePath = __DIR__ . '/../../foto_perfil/' . $user['foto'];
                if (file_exists($possiblePath)) {
                    $avatarSrc = 'foto_perfil/' . $user['foto'];
                }
            }
        }

        if (empty($avatarSrc)) {
            $fullName = trim($nombreUsuario . ' ' . $apellidoUsuario);
            $avatarSrc = 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=0284c7&color=fff&size=128';
        }
        ?>

        <div class="user-profile" style="cursor: pointer;" data-toggle="modal" data-target="#modalPerfil" title="Ver mi perfil">
            <img src="<?php echo $avatarSrc; ?>" alt="Perfil" class="avatar-sm">
            <div class="user-info">
                <span class="user-name"><?php echo $nombreUsuario . ' ' . $apellidoUsuario; ?></span>
                <span class="user-role"><?php echo $rolUsuario; ?></span>
            </div>
            <i class="ph ph-caret-down"></i>
        </div>
    </div>
</header>
