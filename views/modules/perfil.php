<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$id_usuario = (int)($_SESSION["id"] ?? 0);
require_once "models/UsuarioModel.php";
require_once "models/AsistenciaModel.php";

$usuario = UsuarioModel::mdlMostrarUsuario("personal", "id", $id_usuario);
if (!$usuario) {
    echo '<script>alert("Usuario no encontrado"); window.location = "index.php?ruta=login";</script>';
    exit;
}

if (isset($_POST["btnActualizarPerfil"])) {
    require_once "helpers/CsrfHelper.php";
    if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
        echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=perfil";</script>';
        exit;
    }
    $nombre = trim($_POST["perfil_nombre"] ?? '');
    $apellido = trim($_POST["perfil_apellido"] ?? '');
    $email = trim($_POST["perfil_email"] ?? '');
    $telefono = trim($_POST["perfil_telefono"] ?? '');
    $direccion = trim($_POST["perfil_direccion"] ?? '');
    $fecha_nac = $_POST["perfil_fecha_nac"] ?? '';

    $errors = [];
    if (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', $nombre)) $errors[] = 'El nombre solo debe contener letras.';
    if (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', $apellido)) $errors[] = 'El apellido solo debe contener letras.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email no válido.';
    if ($telefono && !preg_match('/^[0-9+\-\s()]+$/', $telefono)) $errors[] = 'Teléfono no válido.';

    if (empty($errors)) {
        $stmt = Conexion::conectar()->prepare("UPDATE personal SET nombre = :nombre, apellido = :apellido, email = :email, telefono = :telefono, direccion = :direccion, fecha_nacimiento = :fecha_nac WHERE id = :id");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":apellido", $apellido, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $direccion, PDO::PARAM_STR);
        $stmt->bindParam(":fecha_nac", $fecha_nac, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id_usuario, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION["nombre"] = $nombre;
            $_SESSION["apellido"] = $apellido;
            echo '<script>alert("Perfil actualizado correctamente"); window.location = "index.php?ruta=perfil";</script>';
            exit;
        } else {
            echo '<script>alert("Error al actualizar el perfil");</script>';
        }
    } else {
        echo '<script>alert("' . implode('\\n', $errors) . '");</script>';
    }
}

if (isset($_POST["btnSubirFoto"]) && isset($_FILES["foto_perfil"])) {
    require_once "helpers/CsrfHelper.php";
    if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
        echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=perfil";</script>';
        exit;
    }
    $file = $_FILES["foto_perfil"];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($mimeType, $allowed)) {
        $uploadDir = "foto_perfil/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $id_usuario . "." . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            $stmt = Conexion::conectar()->prepare("UPDATE personal SET foto = :foto WHERE id = :id");
            $stmt->bindParam(":foto", $fileName, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id_usuario, PDO::PARAM_INT);
            $stmt->execute();
            echo '<script>alert("Foto actualizada"); window.location = "index.php?ruta=perfil";</script>';
            exit;
        }
    } else {
        echo '<script>alert("Formato no válido. Usa JPG, PNG o GIF.");</script>';
    }
}

if (isset($_POST["btnCambiarPassword"])) {
    require_once "helpers/CsrfHelper.php";
    if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
        echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=perfil";</script>';
        exit;
    }
    if (isset($_POST["password_actual"]) && isset($_POST["password_nueva"]) && strlen($_POST["password_nueva"]) >= 4) {
        if (password_verify($_POST["password_actual"], $usuario["password"])) {
            $hash = password_hash($_POST["password_nueva"], PASSWORD_DEFAULT);
            $stmt = Conexion::conectar()->prepare("UPDATE personal SET password = :password WHERE id = :id");
            $stmt->bindParam(":password", $hash, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id_usuario, PDO::PARAM_INT);
            if ($stmt->execute()) {
                echo '<script>alert("Contraseña actualizada con éxito");</script>';
            }
        } else {
            echo '<script>alert("La contraseña actual no es correcta");</script>';
        }
    } else {
        echo '<script>alert("La nueva contraseña debe tener al menos 4 caracteres");</script>';
    }
}

$usuario = UsuarioModel::mdlMostrarUsuario("personal", "id", $id_usuario);

$asistencias = AsistenciaModel::mdlListarAsistenciasReporte(
    date('Y-m-01'), date('Y-m-d'), null, $id_usuario
);

$avatarSrc = '';
if ($usuario && !empty($usuario['foto'])) {
    $path = 'foto_perfil/' . $usuario['foto'];
    if (file_exists($path)) $avatarSrc = $path;
}
if (empty($avatarSrc)) {
    $fullName = trim(($usuario['nombre'] ?? 'U') . ' ' . ($usuario['apellido'] ?? ''));
    $avatarSrc = 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=0284c7&color=fff&size=128';
}
?>
<main class="dashboard-content">
    <div class="page-header">
        <div>
            <h1>Mi Perfil</h1>
            <p class="current-date">Información personal y registro de asistencia</p>
        </div>
    </div>

    <!-- Avatar + subir foto -->
    <div class="widget" style="margin-bottom:1.5rem;">
        <div style="padding:1.5rem; display:flex; align-items:center; gap:2rem; flex-wrap:wrap;">
            <img src="<?php echo $avatarSrc; ?>" alt="Perfil" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid var(--color-primary);">
            <div>
                <h3 style="margin:0 0 4px 0;"><?php echo htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p style="margin:0 0 8px 0; color:var(--text-muted);">
                    <span class="status-badge" style="background:<?php echo match ((int)($usuario['id_rol'] ?? 3)) { 4 => '#dc2626', 1 => '#0284c7', 2 => '#7c3aed', default => '#6b7280' }; ?>; color:#fff; padding:2px 10px; border-radius:12px; font-size:0.8rem;">
                        <?php echo htmlspecialchars($usuario['rol_nombre'] ?? 'Personal', ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    &nbsp;·&nbsp; <?php echo htmlspecialchars($usuario['documento_identidad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <form method="post" enctype="multipart/form-data" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <?php require_once "helpers/CsrfHelper.php"; echo CsrfHelper::field(); ?>
                    <input type="file" name="foto_perfil" accept="image/jpeg,image/png,image/gif" required style="font-size:0.85rem;">
                    <button type="submit" name="btnSubirFoto" class="btn btn-sm btn-outline">Subir Foto</button>
                </form>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">

        <!-- Editar datos personales -->
        <div class="widget">
            <div class="widget-header">
                <h2>Editar Información</h2>
            </div>
            <div style="padding:1.5rem;">
                <form method="post">
                    <?php echo CsrfHelper::field(); ?>
                    <div class="input-group">
                        <label>Nombre</label>
                        <div class="input-wrapper">
                            <i class="ph ph-user"></i>
                            <input type="text" name="perfil_nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Apellido</label>
                        <div class="input-wrapper">
                            <i class="ph ph-user"></i>
                            <input type="text" name="perfil_apellido" value="<?php echo htmlspecialchars($usuario['apellido'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <i class="ph ph-envelope"></i>
                            <input type="email" name="perfil_email" value="<?php echo htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Teléfono</label>
                        <div class="input-wrapper">
                            <i class="ph ph-phone"></i>
                            <input type="text" name="perfil_telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Dirección</label>
                        <div class="input-wrapper">
                            <i class="ph ph-map-pin"></i>
                            <input type="text" name="perfil_direccion" value="<?php echo htmlspecialchars($usuario['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Fecha de Nacimiento</label>
                        <div class="input-wrapper">
                            <i class="ph ph-cake"></i>
                            <input type="date" name="perfil_fecha_nac" value="<?php echo htmlspecialchars($usuario['fecha_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Documento (no editable)</label>
                        <div class="input-wrapper">
                            <i class="ph ph-identification-card"></i>
                            <input type="text" value="<?php echo htmlspecialchars($usuario['documento_identidad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" disabled>
                        </div>
                    </div>
                    <button type="submit" name="btnActualizarPerfil" class="btn btn-primary btn-block" style="margin-top:1rem;">Guardar Cambios</button>
                </form>
            </div>
        </div>

        <!-- Cambiar Contraseña -->
        <div class="widget">
            <div class="widget-header">
                <h2>Cambiar Contraseña</h2>
            </div>
            <div style="padding:1.5rem;">
                <form method="post">
                    <?php echo CsrfHelper::field(); ?>
                    <div class="input-group">
                        <label>Contraseña Actual</label>
                        <div class="input-wrapper">
                            <i class="ph ph-lock"></i>
                            <input type="password" name="password_actual" placeholder="Ingresa tu contraseña actual" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Nueva Contraseña</label>
                        <div class="input-wrapper">
                            <i class="ph ph-lock"></i>
                            <input type="password" name="password_nueva" placeholder="Mínimo 4 caracteres" required minlength="4">
                        </div>
                    </div>
                    <button type="submit" name="btnCambiarPassword" class="btn btn-primary btn-block" style="margin-top:1rem;">Actualizar Contraseña</button>
                </form>
            </div>

            <div class="widget-header" style="margin-top:1.5rem;">
                <h2>Información del Sistema</h2>
            </div>
            <div style="padding:0 1.5rem 1.5rem;">
                <div class="table-responsive">
                <table style="width:100%; font-size:0.9rem;">
                    <tr><td style="padding:4px 8px; color:var(--text-muted);">Cargo</td><td style="padding:4px 8px;"><strong><?php echo htmlspecialchars($usuario['nombre_cargo'] ?? 'No asignado', ENT_QUOTES, 'UTF-8'); ?></strong></td></tr>
                    <tr><td style="padding:4px 8px; color:var(--text-muted);">Turno</td><td style="padding:4px 8px;"><strong><?php echo htmlspecialchars($usuario['nombre_turno'] ?? 'No asignado', ENT_QUOTES, 'UTF-8'); ?></strong></td></tr>
                    <tr><td style="padding:4px 8px; color:var(--text-muted);">Registrado desde</td><td style="padding:4px 8px;"><strong><?php echo htmlspecialchars($usuario['fecha_registro'] ?? '--', ENT_QUOTES, 'UTF-8'); ?></strong></td></tr>
                </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Asistencia -->
    <div class="widget" style="margin-top:1.5rem;">
        <div class="widget-header">
            <h2>Mi Historial de Asistencia (<?php echo date('M Y'); ?>)</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Horas</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($asistencias)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:2rem;">No hay registros de asistencia este mes.</td></tr>
                    <?php else: $i = 1; ?>
                    <?php foreach ($asistencias as $a): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($a["fecha"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $a["hora_entrada"] ? substr($a["hora_entrada"], 0, 5) : '--:--'; ?></td>
                        <td><?php echo $a["hora_salida"] ? substr($a["hora_salida"], 0, 5) : '--:--'; ?></td>
                        <td><?php echo $a["horas_trabajadas"] ? number_format($a["horas_trabajadas"], 2) . ' h' : '--'; ?></td>
                        <td><span class="status-badge" style="background:<?php echo $a["estado_entrada"] === 'Presente' ? '#16a34a' : ($a["estado_entrada"] === 'Tarde' ? '#f59e0b' : '#6b7280'); ?>; color:#fff; padding:2px 8px; border-radius:8px; font-size:0.8rem;"><?php echo htmlspecialchars($a["estado_entrada"], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
