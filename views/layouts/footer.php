<!-- Modal Perfil -->
<div id="modalPerfil" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Mi Perfil</h2>
            <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <?php
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $nombreUsuario = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "Usuario";
            $apellidoUsuario = isset($_SESSION["apellido"]) ? $_SESSION["apellido"] : "Invitado";

            $avatarSrc = '';
            if (isset($_SESSION["id"]) && !empty($_SESSION["id"])) {
                require_once __DIR__ . '/../../models/UsuarioModel.php';
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

            <img src="<?php echo $avatarSrc; ?>" alt="Perfil" class="profile-avatar">
            <h3 style="margin-bottom: 5px;">
                <?php echo isset($_SESSION["nombre"]) ? $_SESSION["nombre"] . " " . $_SESSION["apellido"] : "Usuario Invitado"; ?>
            </h3>
            <p style="color: var(--text-muted); margin-bottom: 20px;">
                <?php
                $rolFooter = $_SESSION["rol"] ?? '';
                if (!$rolFooter) {
                    require_once __DIR__ . '/../../helpers/RbacHelper.php';
                    $rolFooter = match (RbacHelper::getRolId()) { 4 => 'Admin', 1 => 'Director', 2 => 'Secretaria', default => 'Personal' };
                }
                echo $rolFooter;
                ?>
            </p>

            <form method="post" style="max-width: 300px; margin: 0 auto; text-align: left;">
                <input type="hidden" name="idPerfil" value="<?php echo isset($_SESSION["id"]) ? $_SESSION["id"] : '' ; ?>">
                <?php require_once "helpers/CsrfHelper.php"; echo CsrfHelper::field(); ?>
                <div class="input-group" style="text-align: left;">
                    <label>Contraseña Actual</label>
                    <div class="input-wrapper">
                        <i class="ph ph-lock"></i>
                        <input type="password" name="passwordActualPerfil" placeholder="Contraseña actual" required>
                    </div>
                </div>
                <div class="input-group" style="text-align: left;">
                    <label>Nueva Contraseña</label>
                    <div class="input-wrapper">
                        <i class="ph ph-lock"></i>
                        <input type="password" name="nuevaPasswordPerfil" placeholder="Nueva contraseña (mín. 4 caracteres)" minlength="4" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 15px;">Guardar
                    Cambios</button>
            </form>
            <?php
if (isset($_POST["idPerfil"]) && isset($_POST["nuevaPasswordPerfil"]) && $_POST["nuevaPasswordPerfil"] != "" && isset($_POST["passwordActualPerfil"])) {
    require_once "helpers/CsrfHelper.php";
    if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
        echo '<script>alert("Token de seguridad inválido. Recargue la página.");</script>';
    } else {
        require_once "config/Conexion.php";
        $userId = (int)$_POST["idPerfil"];
        if ($userId !== (int)($_SESSION["id"] ?? 0)) {
            echo '<script>alert("No autorizado");</script>';
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT password FROM personal WHERE id = :id");
            $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();
            if ($user && password_verify($_POST["passwordActualPerfil"], $user["password"])) {
                $hash = password_hash($_POST["nuevaPasswordPerfil"], PASSWORD_DEFAULT);
                $stmt = Conexion::conectar()->prepare("UPDATE personal SET password = :password WHERE id = :id");
                $stmt->bindParam(":password", $hash, PDO::PARAM_STR);
                $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
                if ($stmt->execute()) {
                    echo '<script>alert("Contraseña actualizada con éxito");</script>';
                }
            } else {
                echo '<script>alert("La contraseña actual no es correcta");</script>';
            }
        }
    }
}
?>
        </div>
    </div>
</div>


<script src="js/script.js"></script>
</body>

</html>