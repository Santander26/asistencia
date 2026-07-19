<?php if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once "helpers/RbacHelper.php";
$rol = RbacHelper::getRolId();
$esAdmin = ($rol === 4);
$esDirector = ($rol === 1);
$esSecretaria = ($rol === 2);
$esPersonal = ($rol === 3);
$adminODirector = $esAdmin || $esDirector;
$adminODirectorOSecretaria = $esAdmin || $esDirector || $esSecretaria;
?>
<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('show-sidebar');this.classList.remove('show');"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="ph-fill ph-graduation-cap"></i>
        <span class="brand-name">SIBCA</span>
        <button id="close-sidebar-btn" class="close-sidebar lg-hidden">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Menú Principal</div>

        <?php if ($adminODirectorOSecretaria): ?>
        <a href="index.php?ruta=inicio" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'inicio' || !isset($_GET['ruta'])) echo 'active'; ?>">
            <i class="ph ph-squares-four"></i>
            <span>Dashboard</span>
        </a>
        <?php endif; ?>

        <?php if ($adminODirectorOSecretaria): ?>
        <a href="index.php?ruta=personal" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'personal') echo 'active'; ?>">
            <i class="ph ph-users-three"></i>
            <span>Gestión Personal</span>
        </a>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
        <a href="index.php?ruta=asignar_rol" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'asignar_rol') echo 'active'; ?>">
            <i class="ph ph-shield-check"></i>
            <span>Asignar Rol</span>
        </a>
        <?php endif; ?>

        <?php if ($adminODirectorOSecretaria): ?>
        <a href="index.php?ruta=reportes" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'reportes') echo 'active'; ?>">
            <i class="ph ph-chart-bar"></i>
            <span>Informes y Reportes</span>
        </a>
        <a href="index.php?ruta=justificaciones" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'justificaciones') echo 'active'; ?>">
            <i class="ph ph-file-text"></i>
            <span>Justificaciones</span>
        </a>
        <?php endif; ?>

        <a href="index.php?ruta=asistencia" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'asistencia') echo 'active'; ?>">
            <i class="ph ph-clock"></i>
            <span>Asistencia</span>
        </a>

        <?php if ($adminODirectorOSecretaria || $esPersonal): ?>
        <a href="index.php?ruta=<?php echo $esPersonal ? 'calendario_personal' : 'gestion_calendario'; ?>" class="nav-item <?php if (isset($_GET['ruta']) && ($_GET['ruta'] == 'gestion_calendario' || $_GET['ruta'] == 'calendario_personal')) echo 'active'; ?>">
            <i class="ph ph-calendar"></i>
            <span>Calendario Escolar</span>
        </a>
        <?php endif; ?>

        <?php if ($esAdmin || $esDirector): ?>
        <a href="index.php?ruta=turnos" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'turnos') echo 'active'; ?>">
            <i class="ph ph-clock-countdown"></i>
            <span>Gestión de Horarios</span>
        </a>
        <a href="index.php?ruta=departamentos" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'departamentos') echo 'active'; ?>">
            <i class="ph ph-buildings"></i>
            <span>Departamentos</span>
        </a>
        <a href="index.php?ruta=cargos" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'cargos') echo 'active'; ?>">
            <i class="ph ph-briefcase"></i>
            <span>Cargos / Roles</span>
        </a>
        <?php endif; ?>

        <?php if ($adminODirectorOSecretaria): ?>
        <a href="index.php?ruta=configuracion" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'configuracion') echo 'active'; ?>">
            <i class="ph ph-gear"></i>
            <span>Configuración</span>
        </a>
        <?php endif; ?>

        <?php if ($esAdmin || $esDirector): ?>
        <a href="index.php?ruta=auditoria" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'auditoria') echo 'active'; ?>">
            <i class="ph ph-notepad"></i>
            <span>Historial de Movimientos</span>
        </a>
        <a href="index.php?ruta=historial_bajas" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'historial_bajas') echo 'active'; ?>">
            <i class="ph ph-prohibit"></i>
            <span>Historial de Bajas</span>
        </a>
        <?php endif; ?>

        <?php if ($esPersonal): ?>
        <a href="index.php?ruta=mis_asistencias" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'mis_asistencias') echo 'active'; ?>">
            <i class="ph ph-clock-counter-clockwise"></i>
            <span>Mis Asistencias</span>
        </a>
        <a href="index.php?ruta=mis_justificaciones" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'mis_justificaciones') echo 'active'; ?>">
            <i class="ph ph-file-text"></i>
            <span>Mis Justificaciones</span>
        </a>
        <?php endif; ?>

        <a href="index.php?ruta=perfil" class="nav-item <?php if (isset($_GET['ruta']) && $_GET['ruta'] == 'perfil') echo 'active'; ?>">
            <i class="ph ph-user-circle"></i>
            <span>Mi Perfil</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="index.php?logout=true" class="nav-item logout" id="logout-btn">
            <i class="ph ph-sign-out"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>
