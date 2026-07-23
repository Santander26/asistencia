<?php
require_once "helpers/CsrfHelper.php";
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Configuración del Sistema</h1>
            <p class="current-date">Ajustes Generales y Mantenimiento</p>
        </div>
    </div>

    <div class="dashboard-widgets" style="grid-template-columns: 1fr; max-width: 800px; gap: 20px;">

        <?php
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guardar_smtp"])) {
            if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
                echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=configuracion";</script>';
                exit;
            }
            require_once "helpers/MailHelper.php";
            MailHelper::guardarConfig([
                'smtp_host'     => $_POST["smtp_host"],
                'smtp_port'     => (int)$_POST["smtp_port"],
                'smtp_secure'   => $_POST["smtp_secure"],
                'smtp_username' => $_POST["smtp_username"],
                'smtp_password' => $_POST["smtp_password"],
                'brevo_api_key' => $_POST["brevo_api_key"] ?? '',
                'resend_api_key' => $_POST["resend_api_key"] ?? '',
                'from_email'    => $_POST["from_email"],
                'from_name'     => $_POST["from_name"],
            ]);
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('configurar', 'sistema', null, 'Configuración SMTP actualizada');
            echo '<script>alert("Configuración SMTP guardada correctamente"); window.location = "index.php?ruta=configuracion";</script>';
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["probar_email"])) {
            if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
                echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=configuracion";</script>';
                exit;
            }
            require_once "helpers/MailHelper.php";
            $config = MailHelper::getConfig();
            $destino = $_POST["email_prueba"] ?? $config["from_email"];
            $ok = MailHelper::enviar($destino, 'Test', 'Prueba de configuración SMTP - SIBCA', '<h2>Correo de prueba</h2><p>Si recibes esto, la configuración SMTP funciona correctamente.</p>');
            echo '<script>alert("' . ($ok ? 'Correo de prueba enviado correctamente' : 'Error al enviar correo de prueba') . '"); window.location = "index.php?ruta=configuracion";</script>';
        }

        $smtp_config = [];
        $smtp_json = __DIR__ . '/../../config/smtp.json';
        if (file_exists($smtp_json)) {
            $smtp_config = json_decode(file_get_contents($smtp_json), true) ?: [];
        }
        ?>

        <div class="widget">
            <div class="widget-header">
                <h2 style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-envelope text-blue" style="font-size: 1.5rem;"></i>
                    Configuración de Correo (SMTP)
                </h2>
            </div>
            <div class="widget-content">
                <p style="margin-bottom: 20px; color: var(--clr-text-title);">
                    Configura el servidor SMTP para el envío de correos (restablecimiento de contraseña, notificaciones, etc.).
                </p>
                <form method="post">
                    <?php echo CsrfHelper::field(); ?>
                    <input type="hidden" name="guardar_smtp" value="1">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="input-group">
                            <label>Servidor SMTP</label>
                            <div class="input-wrapper">
                                <i class="ph ph-server"></i>
                                <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtp_config['smtp_host'] ?? 'smtp.gmail.com', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Puerto</label>
                            <div class="input-wrapper">
                                <i class="ph ph-plug"></i>
                                <input type="number" name="smtp_port" value="<?php echo $smtp_config['smtp_port'] ?? 587; ?>" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Seguridad</label>
                            <div class="input-wrapper">
                                <i class="ph ph-shield"></i>
                                <select name="smtp_secure">
                                    <option value="tls" <?php echo ($smtp_config['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($smtp_config['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="" <?php echo empty($smtp_config['smtp_secure'] ?? '') ? 'selected' : ''; ?>>Sin seguridad</option>
                                </select>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Usuario SMTP</label>
                            <div class="input-wrapper">
                                <i class="ph ph-user"></i>
                                <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($smtp_config['smtp_username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Contraseña SMTP</label>
                            <div class="input-wrapper">
                                <i class="ph ph-lock"></i>
                                <input type="password" name="smtp_password" value="<?php echo htmlspecialchars($smtp_config['smtp_password'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="input-group" style="grid-column: 1 / -1;">
                            <label>Resend API Key <span style="font-size:0.75rem;color:var(--clr-green);font-weight:normal;">(Recomendado - HTTPS puerto 443, 100 emails/día gratis)</span></label>
                            <div class="input-wrapper">
                                <i class="ph ph-key"></i>
                                <input type="password" name="resend_api_key" placeholder="re_..." value="<?php echo htmlspecialchars($smtp_config['resend_api_key'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="input-group" style="grid-column: 1 / -1;">
                            <label>Brevo API Key <span style="font-size:0.75rem;color:var(--clr-text-muted);font-weight:normal;">(Alternativa sin SMTP - usa HTTPS puerto 443)</span></label>
                            <div class="input-wrapper">
                                <i class="ph ph-key"></i>
                                <input type="password" name="brevo_api_key" placeholder="xkeysib-..." value="<?php echo htmlspecialchars($smtp_config['brevo_api_key'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Correo From</label>
                            <div class="input-wrapper">
                                <i class="ph ph-at"></i>
                                <input type="email" name="from_email" value="<?php echo htmlspecialchars($smtp_config['from_email'] ?? 'noreply@sibca.edu', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Nombre From</label>
                            <div class="input-wrapper">
                                <i class="ph ph-tag"></i>
                                <input type="text" name="from_name" value="<?php echo htmlspecialchars($smtp_config['from_name'] ?? 'SIBCA - Sistema de Asistencia', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; gap:0.5rem; margin-top:1rem;">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Guardar Configuración</button>
                    </div>
                </form>
                <form method="post" style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid var(--border-color);">
                    <?php echo CsrfHelper::field(); ?>
                    <input type="hidden" name="probar_email" value="1">
                    <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                        <label style="font-size:0.85rem; white-space:nowrap;">Enviar prueba a:</label>
                        <div class="input-wrapper" style="flex:1; min-width:200px;">
                            <i class="ph ph-envelope"></i>
                            <input type="email" name="email_prueba" placeholder="correo@ejemplo.com" value="<?php echo htmlspecialchars($smtp_config['from_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <button type="submit" class="btn btn-outline"><i class="ph ph-paper-plane-tilt"></i> Enviar Prueba</button>
                    </div>
                </form>
            </div>
        </div>

        <?php
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guardar_access_time"])) {
            if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
                echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=configuracion";</script>';
                exit;
            }
            require_once "helpers/AccessTimeHelper.php";
            $config = AccessTimeHelper::getConfig();
            if (isset($_POST["dias"])) {
                $dias = array_map("intval", (array)$_POST["dias"]);
                sort($dias);
                $config['dias'] = $dias;
            }
            if (isset($_POST["hora_inicio"])) {
                $config['hora_inicio'] = (int)$_POST["hora_inicio"];
            }
            if (isset($_POST["hora_fin"])) {
                $config['hora_fin'] = (int)$_POST["hora_fin"];
            }
            if (isset($_POST["habilitado"])) {
                $config['habilitado'] = $_POST["habilitado"] === "1";
            }
            if (isset($_POST["tiempo_inactividad"])) {
                $config['tiempo_inactividad'] = max(1, (int)$_POST["tiempo_inactividad"]);
            }
            AccessTimeHelper::guardarConfig($config);
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('configurar', 'sistema', null, 'Configuración de sistema actualizada');
            echo '<script>alert("Configuración guardada correctamente"); window.location = "index.php?ruta=configuracion";</script>';
        }

        $access_config = [];
        $access_json = __DIR__ . '/../../config/access_time.json';
        if (file_exists($access_json)) {
            $access_config = json_decode(file_get_contents($access_json), true) ?: [];
        }
        $access_dias = $access_config['dias'] ?? [1, 2, 3, 4, 5];
        $access_hora_inicio = $access_config['hora_inicio'] ?? 8;
        $access_hora_fin = $access_config['hora_fin'] ?? 13;
        $access_habilitado = $access_config['habilitado'] ?? false;
        $diasNombres = [1 => 'Lun', 2 => 'Mar', 3 => 'Mie', 4 => 'Jue', 5 => 'Vie', 6 => 'Sab', 7 => 'Dom'];
        ?>

        <div class="widget">
            <div class="widget-header">
                <h2 style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-clock text-blue" style="font-size: 1.5rem;"></i>
                    Horario de Acceso
                </h2>
            </div>
            <div class="widget-content">
                <p style="margin-bottom: 20px; color: var(--clr-text-title);">
                    Restringe el acceso al sistema por rol y horario. Admin y Director no tienen restricción.
                </p>
                <form method="post">
                    <?php echo CsrfHelper::field(); ?>
                    <input type="hidden" name="guardar_access_time" value="1">
                    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem;">
                        <label style="font-weight:500;">Habilitar restricci&oacute;n:</label>
                        <label style="display:flex; align-items:center; gap:0.4rem; cursor:pointer;">
                            <input type="hidden" name="habilitado" value="0">
                            <input type="checkbox" name="habilitado" value="1" <?php echo $access_habilitado ? 'checked' : ''; ?>>
                            Activado
                        </label>
                    </div>
                    <div class="input-group" style="margin-bottom:1.25rem;">
                        <label>D&iacute;as permitidos</label>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; padding-top:0.3rem;">
                            <?php foreach ($diasNombres as $num => $nom): ?>
                            <label style="display:flex; align-items:center; gap:0.3rem; cursor:pointer;">
                                <input type="checkbox" name="dias[]" value="<?php echo $num; ?>" <?php echo in_array($num, $access_dias) ? 'checked' : ''; ?>>
                                <?php echo $nom; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="input-group">
                            <label>Hora inicio</label>
                            <div class="input-wrapper">
                                <i class="ph ph-sun"></i>
                                <input type="time" name="hora_inicio" value="<?php echo str_pad($access_hora_inicio, 2, '0', STR_PAD_LEFT) . ':00'; ?>" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Hora fin</label>
                            <div class="input-wrapper">
                                <i class="ph ph-moon"></i>
                                <input type="time" name="hora_fin" value="<?php echo str_pad($access_hora_fin, 2, '0', STR_PAD_LEFT) . ':00'; ?>" required>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; gap:0.5rem;">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Guardar Horario</button>
                    </div>
                </form>
            </div>
        </div>

        <?php $tiempo_inactividad = $access_config['tiempo_inactividad'] ?? 5; ?>

        <div class="widget">
            <div class="widget-header">
                <h2 style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-timer text-blue" style="font-size: 1.5rem;"></i>
                    Tiempo de Inactividad
                </h2>
            </div>
            <div class="widget-content">
                <p style="margin-bottom: 20px; color: var(--clr-text-title);">
                    Define despu&eacute;s de cu&aacute;ntos minutos de inactividad se cerrar&aacute; la sesi&oacute;n autom&aacute;ticamente.
                </p>
                <form method="post">
                    <?php echo CsrfHelper::field(); ?>
                    <input type="hidden" name="guardar_access_time" value="1">
                    <div style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
                        <div class="input-group" style="flex:1; min-width:200px; margin-bottom:0;">
                            <label>Minutos de inactividad</label>
                            <div class="input-wrapper">
                                <i class="ph ph-clock-countdown"></i>
                                <input type="number" name="tiempo_inactividad" min="1" max="120" value="<?php echo $tiempo_inactividad; ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Guardar</button>
                    </div>
                    <small style="color:var(--text-muted); display:block; margin-top:0.75rem;">M&iacute;nimo 1 minuto. Valor actual: <strong><?php echo $tiempo_inactividad; ?> minuto(s)</strong> (<?php echo $tiempo_inactividad * 60; ?> segundos).</small>
                </form>
            </div>
        </div>

        <div class="widget">
            <div class="widget-header">
                <h2 style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-database text-blue" style="font-size: 1.5rem;"></i>
                    Copias de Seguridad (Backups)
                </h2>
            </div>

            <div class="widget-content">
                <p style="margin-bottom: 20px; color: var(--clr-text-title);">
                    Genera un respaldo completo de la base de datos (estructura y todos los registros de personal,
                    cargos, departamentos y asistencias). El archivo <strong>.sql</strong> generado se guardará
                    automáticamente en el servidor.
                </p>

                <div class="alert alert-warning"
                    style="background-color: var(--clr-yellow-light); color: var(--clr-yellow-dark); padding: 15px; border-radius: var(--border-radius-md); margin-bottom: 25px;">
                    <i class="ph ph-warning-circle"></i> <strong>Importante:</strong> Se recomienda realizar esta acción
                    semanalmente o antes de hacer limpiezas mayores de personal.
                </div>

                <form method="post">
                    <?php echo CsrfHelper::field(); ?>
                    <button type="submit" name="btnCrearBackup" class="btn btn-primary"
                        style="font-size: 1.1rem; padding: 12px 24px;">
                        <i class="ph ph-download-simple"></i> Generar Respaldo Ahora
                    </button>
                    <?php
require_once "controllers/ConfiguracionController.php";
$backup = new ConfiguracionController();
$backup->ctrCrearBackup();
?>
                </form>
            </div>
        </div>

        <!-- Lista de Backups -->
        <div class="widget">
            <div class="widget-header">
                <h2 style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-folder-open text-yellow" style="font-size: 1.5rem;"></i>
                    Backups Disponibles
                </h2>
            </div>
            <div class="widget-content">
                <?php
                $backupDir = $_SERVER["DOCUMENT_ROOT"] . "/backups/";
                $archivos = glob($backupDir . "*.sql");
                if (empty($archivos)):
                ?>
                <p style="color: var(--clr-text-muted);">No hay backups disponibles. Genera uno primero.</p>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th>Tamaño</th>
                            <th>Fecha</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archivos as $archivo):
                            $nombre = basename($archivo);
                            $tamano = filesize($archivo);
                            $fecha = date("d/m/Y H:i:s", filemtime($archivo));
                            $tamanoStr = $tamano > 1048576 ? round($tamano/1048576,1)." MB" : round($tamano/1024,1)." KB";
                        ?>
                        <tr>
                            <td><i class="ph ph-file-sql" style="margin-right:6px;"></i><?php echo htmlspecialchars($nombre); ?></td>
                            <td><?php echo $tamanoStr; ?></td>
                            <td><?php echo $fecha; ?></td>
                            <td>
                                <a href="index.php?ruta=descargar_backup&archivo=<?php echo urlencode($nombre); ?>" class="btn btn-sm btn-outline" style="color:var(--clr-blue);border-color:var(--clr-blue);">
                                    <i class="ph ph-download-simple"></i> Descargar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel de Restauración -->
        <div class="widget">
            <div class="widget-header">
                <h2 style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-upload-simple text-green" style="font-size: 1.5rem;"></i>
                    Restaurar Sistema
                </h2>
            </div>

            <div class="widget-content">
                <p style="margin-bottom: 20px; color: var(--clr-text-title);">
                    Sube un archivo <strong>.sql</strong> previamente generado por este sistema para restaurar toda la
                    información a ese punto exacto en el tiempo.
                </p>

                <div class="alert alert-danger"
                    style="background-color: var(--clr-red-light); color: var(--clr-red-dark); padding: 15px; border-radius: var(--border-radius-md); margin-bottom: 25px;">
                    <i class="ph ph-warning-octagon"></i> <strong>Atención:</strong> Esta acción borrará los registros
                    actuales e instalará la información del archivo de respaldo. No se puede deshacer.
                </div>

                <form method="post" enctype="multipart/form-data">
                    <?php echo CsrfHelper::field(); ?>
                    <div class="input-group">
                        <label>Archivo de respaldo</label>
                        <div class="input-wrapper">
                            <i class="ph ph-file-sql"></i>
                            <input type="file" name="archivoBackup" accept=".sql" required>
                        </div>
                    </div>

                    <button type="submit" name="btnRestaurarBackup" class="btn"
                        style="background-color: var(--clr-green); color: white; font-size: 1.1rem; padding: 12px 24px;">
                        <i class="ph ph-clock-counter-clockwise"></i> Iniciar Restauración Mágica
                    </button>
                    <?php
$restore = new ConfiguracionController();
$restore->ctrRestaurarBackup();
?>
                </form>
            </div>
        </div>

    </div>
</main>