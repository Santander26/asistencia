<?php
class LoginController
{
    static public function ctrIngreso()
    {
        if (isset($_POST["ingresoEmail"])) {
            require_once "helpers/CsrfHelper.php";
            if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
                echo '<br><div class="alert alert-danger">Token de seguridad inválido. Recargue la página.</div>';
                return;
            }
            require_once "models/UsuarioModel.php";
            require_once "helpers/AuditoriaHelper.php";

            $tabla = "personal";
            $email = $_POST["ingresoEmail"];

            $respuesta = UsuarioModel::mdlMostrarUsuario($tabla, "email", $email);

            $ingresoGenerico = '<br><div class="alert alert-danger">Credenciales incorrectas.</div>';

            if ($respuesta) {
                if ($respuesta["id_estado"] != 1) {
                    echo $ingresoGenerico;
                    return;
                }

                $bloqueado_hasta = $respuesta["bloqueado_hasta"] ?? null;
                if ($bloqueado_hasta && strtotime($bloqueado_hasta) > time()) {
                    $restante = ceil((strtotime($bloqueado_hasta) - time()) / 60);
                    echo '<br><div class="alert alert-danger">Demasiados intentos fallidos. Intente de nuevo en ' . $restante . ' minuto(s).</div>';
                    return;
                }

                if (password_verify($_POST["ingresoPassword"], $respuesta["password"])) {
                    UsuarioModel::mdlResetearIntentos($respuesta["id"]);

                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        session_start();
                    }

                    session_regenerate_id(true);

                    $_SESSION["iniciarSesion"] = "ok";
                    $_SESSION["id"] = $respuesta["id"];
                    $_SESSION["nombre"] = $respuesta["nombre"];
                    $_SESSION["apellido"] = $respuesta["apellido"];
                    $_SESSION["rol"] = $respuesta["rol_nombre"];
                    $_SESSION["id_rol"] = (int)$respuesta["id_rol"];
                    $_SESSION["ultima_actividad"] = time();

                    require_once "helpers/AccessTimeHelper.php";
                    if (!AccessTimeHelper::verificar()) {
                        session_unset();
                        session_destroy();
                        echo '<br><div class="alert alert-warning">El sistema solo está disponible de lunes a viernes de 8:00 AM a 1:00 PM para su rol.</div>';
                        return;
                    }

                    if (isset($_POST["recordarme"]) && $_POST["recordarme"] === "on") {
                        $_SESSION["recordar"] = true;
                        $token = bin2hex(random_bytes(32));
                        require_once "models/SesionModel.php";
                        SesionModel::mdlGuardarToken($respuesta["id"], $token);
                        setcookie("recordar_token", $token, time() + 86400 * 30, "/", "", !empty($_SERVER['HTTPS']), true);
                    } else {
                        $_SESSION["recordar"] = false;
                        if (isset($_COOKIE["recordar_token"])) {
                            require_once "models/SesionModel.php";
                            SesionModel::mdlEliminarToken($_COOKIE["recordar_token"]);
                            setcookie("recordar_token", "", time() - 3600, "/");
                        }
                    }

                    require_once "helpers/AuditoriaHelper.php";
                    AuditoriaHelper::log('inicio_sesion', 'personal', $respuesta["id"], 'Inicio de sesión: ' . $respuesta["nombre"] . ' ' . $respuesta["apellido"]);

                    $destino = ((int)$respuesta["id_rol"] === 3) ? 'perfil' : 'inicio';
                    echo '<script>
                        window.location = "index.php?ruta=' . $destino . '";
                    </script>';
                } else {
                    UsuarioModel::mdlIncrementarIntentos($respuesta["id"]);

                    $nuevos_intentos = ($respuesta["intentos_fallidos"] ?? 0) + 1;
                    if ($nuevos_intentos >= 3) {
                        UsuarioModel::mdlBloquearUsuario($respuesta["id"]);
                        echo '<br><div class="alert alert-danger">Demasiados intentos fallidos. Usuario bloqueado por 15 minutos.</div>';
                    } else {
                        $restantes = 3 - $nuevos_intentos;
                        echo $ingresoGenerico;
                    }
                }
            } else {
                echo $ingresoGenerico;
            }
        }
    }

    static public function ctrSolicitarReset()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["documento_identidad"])) return;

        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            header('Location: index.php?ruta=olvide_password&error=Token de seguridad inválido');
            exit;
        }

        require_once "models/PasswordResetModel.php";
        $doc = trim($_POST["documento_identidad"]);

        $usuario = PasswordResetModel::mdlBuscarPorDocumento($doc);
        if (!$usuario) {
            header('Location: index.php?ruta=olvide_password&error=No se encontró un usuario activo con ese documento');
            exit;
        }

        PasswordResetModel::mdlLimpiarTokensExpirados();

        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        PasswordResetModel::mdlCrearToken($usuario["id"], $token, $expires_at);

        $baseUrl = rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']), '/');
        $link = $baseUrl . '/index.php?ruta=reset_password&token=' . $token;

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log('solicitar_reset', 'personal', $usuario["id"], 'Solicitó restablecimiento de contraseña: ' . $usuario["nombre"] . ' ' . $usuario["apellido"]);

        require_once "helpers/MailHelper.php";
        $cuerpo = '
            <h2>Restablecimiento de Contraseña - SIBCA</h2>
            <p>Hola <strong>' . htmlspecialchars($usuario["nombre"] . ' ' . $usuario["apellido"], ENT_QUOTES, 'UTF-8') . '</strong>,</p>
            <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para crear una nueva:</p>
            <p style="text-align:center; margin:20px 0;">
                <a href="' . $link . '"
                   style="display:inline-block; padding:12px 24px; background:#0284c7; color:#fff; text-decoration:none; border-radius:6px; font-size:16px;">
                    Restablecer Contraseña
                </a>
            </p>
            <p>Si no solicitaste este cambio, ignora este mensaje.</p>
            <p>Este enlace vence en 1 hora.</p>
            <hr>
            <p style="font-size:12px; color:#666;">SIBCA - Sistema de Asistencia</p>
        ';

        $enviado = MailHelper::enviar($usuario["email"], $usuario["nombre"] . ' ' . $usuario["apellido"], 'Restablecimiento de Contraseña - SIBCA', $cuerpo);

        if ($enviado) {
            header('Location: index.php?ruta=olvide_password&enviado=ok');
        } else {
            header('Location: index.php?ruta=olvide_password&link=' . urlencode($link));
        }
        exit;
    }

    static public function ctrEjecutarReset()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["token"], $_POST["nueva_password"], $_POST["confirmar_password"])) return;

        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            header('Location: index.php?ruta=reset_password&token=' . urlencode($_POST['token'] ?? '') . '&error=Token de seguridad inválido');
            exit;
        }

        require_once "models/PasswordResetModel.php";
        $token = $_POST["token"];
        $password = $_POST["nueva_password"];
        $confirmar = $_POST["confirmar_password"];

        if (strlen($password) < 6) {
            header('Location: index.php?ruta=reset_password&token=' . urlencode($token) . '&error=La contraseña debe tener al menos 6 caracteres');
            exit;
        }

        if ($password !== $confirmar) {
            header('Location: index.php?ruta=reset_password&token=' . urlencode($token) . '&error=Las contraseñas no coinciden');
            exit;
        }

        $reset = PasswordResetModel::mdlVerificarToken($token);
        if (!$reset) {
            header('Location: index.php?ruta=olvide_password&error=El enlace de restablecimiento no es válido o ha expirado');
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        PasswordResetModel::mdlActualizarPassword($reset["id_personal"], $hash);
        PasswordResetModel::mdlMarcarUsado($reset["id"]);

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log('reset_password', 'personal', $reset["id_personal"], 'Restableció su contraseña: ' . $reset["nombre"] . ' ' . $reset["apellido"]);

        echo '<script>alert("Contraseña restablecida correctamente. Ahora puede iniciar sesión."); window.location = "index.php?ruta=login";</script>';
        exit;
    }

    static public function ctrLogout()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_COOKIE["recordar_token"])) {
            require_once "models/SesionModel.php";
            SesionModel::mdlEliminarToken($_COOKIE["recordar_token"]);
            setcookie("recordar_token", "", time() - 3600, "/");
        }

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log('cierre_sesion', 'personal', $_SESSION["id"] ?? null, 'Cierre de sesión');
        session_destroy();
        header('Location: index.php?ruta=login');
        exit;
    }
}
