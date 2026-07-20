<?php

function _validarTurnoParaCargo($id_turno, $id_cargo) {
    if (empty($id_turno) || empty($id_cargo)) return false;
    require_once "models/TurnoModel.php";
    $cargosIds = TurnoModel::mdlListarIdsCargos($id_turno);
    if (empty($cargosIds)) return true;
    return in_array((int)$id_cargo, $cargosIds);
}

class PersonalController
{
    static public function ctrCrearPersonal()
    {
        if (isset($_POST["nuevoNombre"])) {
            if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
            require_once "helpers/CsrfHelper.php";
            if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
                $_SESSION['form_errors'] = ['general' => 'Token de seguridad inválido. Recargue la página.'];
                $_SESSION['form_old'] = $_POST;
                header('Location: index.php?ruta=personal');
                exit;
            }

            $errors = array();

            if (!isset($_POST["nuevoNombre"]) || !preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', $_POST["nuevoNombre"])) {
                $errors['nuevoNombre'] = 'El nombre solo debe contener letras y espacios.';
            }
            if (!isset($_POST["nuevoApellido"]) || !preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', $_POST["nuevoApellido"])) {
                $errors['nuevoApellido'] = 'El apellido solo debe contener letras y espacios.';
            }
            if (!isset($_POST["nuevoDocumento"]) || !preg_match('/^[0-9]+$/', $_POST["nuevoDocumento"])) {
                $errors['nuevoDocumento'] = 'El documento debe contener solo números.';
            }
            if (!isset($_POST["nuevoEmail"]) || !filter_var($_POST["nuevoEmail"], FILTER_VALIDATE_EMAIL)) {
                $errors['nuevoEmail'] = 'El correo electrónico no tiene un formato válido.';
            }
            if (!isset($_POST["nuevoCargo"]) || !is_numeric($_POST["nuevoCargo"])) {
                $errors['nuevoCargo'] = 'Selecciona un cargo válido.';
            }
            if (!isset($_POST["nuevoTurno"]) || !is_numeric($_POST["nuevoTurno"])) {
                $errors['nuevoTurno'] = 'Selecciona un turno válido.';
            }

            if (!isset($errors['nuevoCargo']) && !isset($errors['nuevoTurno'])) {
                if (!_validarTurnoParaCargo($_POST["nuevoTurno"], $_POST["nuevoCargo"])) {
                    $errors['nuevoTurno'] = 'El turno seleccionado no corresponde al cargo elegido.';
                }
            }

            if (count($errors) === 0) {
                require_once "models/UsuarioModel.php";
                $duplicados = UsuarioModel::mdlVerificarDuplicado($_POST["nuevoDocumento"], $_POST["nuevoEmail"]);
                foreach ($duplicados as $d) {
                    if ($d["documento_identidad"] === $_POST["nuevoDocumento"]) {
                        $errors['nuevoDocumento'] = 'El documento de identidad ya existe en el sistema.';
                    }
                    if ($d["email"] === $_POST["nuevoEmail"]) {
                        $errors['nuevoEmail'] = 'El correo electrónico ya existe en el sistema.';
                    }
                }
            }

            if (count($errors) === 0) {
                $tabla = "personal";
                $password = password_hash($_POST["nuevoDocumento"], PASSWORD_DEFAULT);

                $datos = array(
                    "nombre" => $_POST["nuevoNombre"],
                    "apellido" => $_POST["nuevoApellido"],
                    "documento_identidad" => $_POST["nuevoDocumento"],
                    "email" => $_POST["nuevoEmail"],
                    "password" => $password,
                    "id_cargo" => $_POST["nuevoCargo"],
                    "id_turno" => $_POST["nuevoTurno"],
                    "id_rol" => 3,
                    "id_estado" => 1
                );

                require_once "models/UsuarioModel.php";
                require_once "helpers/AuditoriaHelper.php";
                $respuesta = UsuarioModel::mdlIngresarUsuario($tabla, $datos);

                if ($respuesta == "ok") {
                    $nuevoId = Conexion::conectar()->lastInsertId();
                    AuditoriaHelper::log('crear', 'personal', $nuevoId, 'Creó empleado: ' . $_POST["nuevoNombre"] . ' ' . $_POST["nuevoApellido"] . ' (Doc: ' . $_POST["nuevoDocumento"] . ')');
                    unset($_SESSION['form_old']);
                    unset($_SESSION['form_errors']);
                    echo '<script>
                        alert("El empleado ha sido guardado correctamente");
                        window.location = "index.php?ruta=personal";
                    </script>';
                }
                else {
                    $_SESSION['form_errors'] = ['general' => 'Error al guardar el empleado.'];
                    $_SESSION['form_old'] = $_POST;
                    header('Location: index.php?ruta=personal');
                    exit;
                }

            } else {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_old'] = $_POST;
                header('Location: index.php?ruta=personal');
                exit;
            }
        }
    }

    static public function ctrEditarPersonal()
    {
        if (isset($_POST["editarNombre"])) {
            if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
            require_once "helpers/CsrfHelper.php";
            if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
                $_SESSION['form_errors_edit'] = ['general' => 'Token de seguridad inválido. Recargue la página.'];
                $_SESSION['form_old_edit'] = $_POST;
                header('Location: index.php?ruta=personal');
                exit;
            }

            $errors = array();
            if (!isset($_POST["editarNombre"]) || !preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', $_POST["editarNombre"])) {
                $errors['editarNombre'] = 'El nombre solo debe contener letras y espacios.';
            }
            if (!isset($_POST["editarApellido"]) || !preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', $_POST["editarApellido"])) {
                $errors['editarApellido'] = 'El apellido solo debe contener letras y espacios.';
            }
            if (!isset($_POST["editarDocumento"]) || !preg_match('/^[0-9]+$/', $_POST["editarDocumento"])) {
                $errors['editarDocumento'] = 'El documento debe contener solo números.';
            }
            if (!isset($_POST["editarEmail"]) || !filter_var($_POST["editarEmail"], FILTER_VALIDATE_EMAIL)) {
                $errors['editarEmail'] = 'El correo electrónico no tiene un formato válido.';
            }

            if (!isset($_POST["editarCargo"]) || !is_numeric($_POST["editarCargo"])) {
                $errors['editarCargo'] = 'Selecciona un cargo válido.';
            }
            if (!isset($_POST["editarTurno"]) || !is_numeric($_POST["editarTurno"])) {
                $errors['editarTurno'] = 'Selecciona un turno válido.';
            }

            if (!isset($errors['editarCargo']) && !isset($errors['editarTurno'])) {
                if (!_validarTurnoParaCargo($_POST["editarTurno"], $_POST["editarCargo"])) {
                    $errors['editarTurno'] = 'El turno seleccionado no corresponde al cargo elegido.';
                }
            }

            if (count($errors) === 0) {
                require_once "models/UsuarioModel.php";
                $duplicados = UsuarioModel::mdlVerificarDuplicado($_POST["editarDocumento"], $_POST["editarEmail"], $_POST["idPersonal"]);
                foreach ($duplicados as $d) {
                    if ($d["documento_identidad"] === $_POST["editarDocumento"]) {
                        $errors['editarDocumento'] = 'El documento de identidad ya existe en el sistema.';
                    }
                    if ($d["email"] === $_POST["editarEmail"]) {
                        $errors['editarEmail'] = 'El correo electrónico ya existe en el sistema.';
                    }
                }
            }

            if (count($errors) === 0) {
                $tabla = "personal";

                if ($_POST["editarPassword"] != "") {
                    $password = password_hash($_POST["editarPassword"], PASSWORD_DEFAULT);
                }
                else {
                    $stmtHash = Conexion::conectar()->prepare("SELECT password FROM personal WHERE id = :id");
                    $stmtHash->bindParam(":id", $_POST["idPersonal"], PDO::PARAM_INT);
                    $stmtHash->execute();
                    $hashRow = $stmtHash->fetch();
                    $password = $hashRow ? $hashRow["password"] : "";
                }

                $datos = array(
                    "id" => $_POST["idPersonal"],
                    "nombre" => $_POST["editarNombre"],
                    "apellido" => $_POST["editarApellido"],
                    "documento_identidad" => $_POST["editarDocumento"],
                    "email" => $_POST["editarEmail"],
                    "password" => $password,
                    "id_cargo" => $_POST["editarCargo"],
                    "id_turno" => $_POST["editarTurno"]
                );

                require_once "models/UsuarioModel.php";
                require_once "helpers/AuditoriaHelper.php";
                $respuesta = UsuarioModel::mdlEditarUsuario($tabla, $datos);

                if ($respuesta == "ok") {
                    AuditoriaHelper::log('editar', 'personal', $_POST["idPersonal"], 'Editó empleado: ' . $_POST["editarNombre"] . ' ' . $_POST["editarApellido"]);
                    unset($_SESSION['form_old_edit']);
                    unset($_SESSION['form_errors_edit']);
                    echo '<script>
                        alert("El empleado ha sido actualizado correctamente");
                        window.location = "index.php?ruta=personal";
                    </script>';
                }
                else {
                    $_SESSION['form_errors_edit'] = ['general' => 'Error al editar el empleado.'];
                    $_SESSION['form_old_edit'] = $_POST;
                    header('Location: index.php?ruta=personal');
                    exit;
                }
            } else {
                $_SESSION['form_errors_edit'] = $errors;
                $_SESSION['form_old_edit'] = $_POST;
                header('Location: index.php?ruta=personal');
                exit;
            }
        }
    }

    static public function ctrInactivarPersonal()
    {
        if (isset($_GET["idEliminar"])) {
            $tabla = "personal";
            $dato = (int)$_GET["idEliminar"];
            $estado = $_GET["status"];

            if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

            require_once "helpers/CsrfHelper.php";
            $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
            if (!CsrfHelper::validate($csrfToken)) {
                echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=personal";</script>';
                return;
            }

            require_once "models/UsuarioModel.php";
            require_once "helpers/AuditoriaHelper.php";

            if ((int)$estado < 2 && $_SERVER["REQUEST_METHOD"] !== "POST") {
                echo '<script>
                    alert("Debe especificar un motivo para dar de baja.");
                    window.location = "index.php?ruta=personal";
                </script>';
                return;
            }

            if ((int)$estado >= 2) {
                require_once "models/HistorialBajaModel.php";
                HistorialBajaModel::mdlReactivar($dato, $_SESSION["id"]);
            }

            if ((int)$estado < 2) {
                require_once "models/HistorialBajaModel.php";
                $datos = array(
                    "id_personal" => $dato,
                    "motivo" => $_POST["motivoBaja"],
                    "descripcion" => $_POST["descripcionBaja"] ?? "",
                    "fecha_baja" => $_POST["fechaBaja"] ?? date("Y-m-d"),
                    "id_usuario_registra" => $_SESSION["id"]
                );
                HistorialBajaModel::mdlInsertar($datos);
            }

            $respuesta = UsuarioModel::mdlInactivarUsuario($tabla, $dato, $estado);

            if ($respuesta == "ok") {
                $accion = ((int)$estado >= 2) ? 'activar' : 'desactivar';
                $detalle = $accion == 'activar'
                    ? 'Reactivó empleado ID: ' . $dato
                    : 'Desactivó empleado ID: ' . $dato . ' - Motivo: ' . $_POST["motivoBaja"];
                AuditoriaHelper::log($accion, 'personal', $dato, $detalle);
                echo '<script>
                    window.location = "index.php?ruta=personal";
                </script>';
            } else {
                echo '<script>
                    alert("Error al cambiar el estado del usuario");
                    window.location = "index.php?ruta=personal";
                </script>';
            }
        }
    }

    static public function ctrSubirFotoPerfil()
    {
        if (isset($_POST["idPersonalFoto"]) && isset($_FILES["fotoPerfil"])) {
            if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
            require_once "helpers/CsrfHelper.php";
            if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
                echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=personal";</script>';
                return;
            }

            $id = $_POST["idPersonalFoto"];
            $file = $_FILES["fotoPerfil"];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($mimeType, $allowedTypes)) {
                echo '<script>
                    alert("Solo se permiten archivos de imagen (JPEG, PNG, GIF)");
                    window.location = "index.php?ruta=personal";
                </script>';
                return;
            }

            $uploadDir = "foto_perfil/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = $id . "." . $extension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                require_once "models/UsuarioModel.php";
                require_once "helpers/AuditoriaHelper.php";
                $respuesta = UsuarioModel::mdlActualizarFoto("personal", $id, $fileName);

                if ($respuesta == "ok") {
                    AuditoriaHelper::log('subir_foto', 'personal', $id, 'Subió foto de perfil a empleado ID: ' . $id);
                    echo '<script>
                        alert("Foto subida correctamente");
                        window.location = "index.php?ruta=personal";
                    </script>';
                } else {
                    echo '<script>
                        alert("Error al actualizar la base de datos");
                        window.location = "index.php?ruta=personal";
                    </script>';
                }
            } else {
                echo '<script>
                    alert("Error al subir el archivo");
                    window.location = "index.php?ruta=personal";
                </script>';
            }
        }
    }
}
