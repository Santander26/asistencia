<?php
require_once "models/TurnoModel.php";
require_once "config/Conexion.php";

class TurnoController
{
    static public function ctrListarTurnos()
    {
        return TurnoModel::mdlMostrarTurnos("horarios_turnos");
    }

    static public function ctrListarTurnosActivos()
    {
        return TurnoModel::mdlMostrarTurnos("horarios_turnos", true);
    }

    static public function ctrListarCargos()
    {
        $stmt = Conexion::conectar()->prepare("SELECT id, nombre FROM cargos ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function ctrAgregarTurno()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["nombre_turno"])) return;

        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=turnos";</script>';
            exit;
        }

        $cargos = isset($_POST["cargos"]) && is_array($_POST["cargos"])
            ? array_map('intval', $_POST["cargos"])
            : [];

        $datos = array(
            "nombre_turno" => trim($_POST["nombre_turno"]),
            "hora_entrada" => $_POST["hora_entrada"],
            "hora_salida" => $_POST["hora_salida"],
            "tolerancia_minutos" => (int)($_POST["tolerancia_minutos"] ?? 0),
            "cargos" => $cargos
        );

        if (TurnoModel::mdlAgregarTurno("horarios_turnos", $datos)) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('crear', 'turno', null, 'Creó turno: ' . $datos["nombre_turno"]);
            echo '<script>alert("Turno agregado correctamente"); window.location = "index.php?ruta=turnos";</script>';
        } else {
            echo '<script>alert("Error al agregar el turno"); window.location = "index.php?ruta=turnos";</script>';
        }
        exit;
    }

    static public function ctrEditarTurno()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["editar_id"])) return;

        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=turnos";</script>';
            exit;
        }

        $cargos = isset($_POST["editar_cargos"]) && is_array($_POST["editar_cargos"])
            ? array_map('intval', $_POST["editar_cargos"])
            : [];

        $datos = array(
            "id" => (int)$_POST["editar_id"],
            "nombre_turno" => trim($_POST["editar_nombre_turno"]),
            "hora_entrada" => $_POST["editar_hora_entrada"],
            "hora_salida" => $_POST["editar_hora_salida"],
            "tolerancia_minutos" => (int)($_POST["editar_tolerancia_minutos"] ?? 0),
            "cargos" => $cargos
        );

        if (TurnoModel::mdlModificarTurno("horarios_turnos", $datos)) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('editar', 'turno', $datos["id"], 'Editó turno: ' . $datos["nombre_turno"]);
            echo '<script>alert("Turno actualizado correctamente"); window.location = "index.php?ruta=turnos";</script>';
        } else {
            echo '<script>alert("Error al actualizar el turno"); window.location = "index.php?ruta=turnos";</script>';
        }
        exit;
    }

    static public function ctrCambiarEstadoTurno()
    {
        if (!isset($_GET["id"]) || !isset($_GET["estado"])) return;
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_GET['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=turnos";</script>';
            exit;
        }

        $id = (int)$_GET["id"];
        $nuevoEstado = (int)$_GET["estado"] == 1 ? 2 : 1;

        if ($nuevoEstado == 2) {
            $db = Conexion::conectar();
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM personal WHERE id_turno = :id AND id_estado = 1");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result["total"] > 0) {
                $total = $result["total"];
                echo '<script>
                    alert("No se puede desactivar este turno. Hay ' . $total . ' empleado(s) activo(s) asignados a él. Reasínalos primero.");
                    window.location = "index.php?ruta=turnos";
                </script>';
                exit;
            }
        }

        TurnoModel::mdlCambiarEstadoTurno("horarios_turnos", $id, $nuevoEstado);
        require_once "helpers/AuditoriaHelper.php";
        $accion = $nuevoEstado == 1 ? 'activar' : 'desactivar';
        AuditoriaHelper::log($accion, 'turno', $id, ucfirst($accion) . 'ó turno ID: ' . $id);
        echo '<script>window.location = "index.php?ruta=turnos";</script>';
        exit;
    }
}
