<?php
require_once "models/CargoModel.php";
require_once "models/DepartamentoModel.php";
require_once "config/Conexion.php";

class CargoController
{
    static public function ctrListarCargos()
    {
        return CargoModel::mdlListarCargos();
    }

    static public function ctrListarDepartamentos()
    {
        return DepartamentoModel::mdlMostrarDepartamentos("departamentos");
    }

    static public function ctrCrear()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["nombre_cargo"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=cargos";</script>';
            exit;
        }

        $datos = array(
            "nombre" => trim($_POST["nombre_cargo"]),
            "id_departamento" => (int)$_POST["id_departamento"]
        );

        if (CargoModel::mdlCrearCargo($datos)) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('crear', 'cargo', null, 'Creó cargo: ' . $datos["nombre"]);
            echo '<script>alert("Cargo creado correctamente"); window.location = "index.php?ruta=cargos";</script>';
        } else {
            echo '<script>alert("Error al crear el cargo"); window.location = "index.php?ruta=cargos";</script>';
        }
        exit;
    }

    static public function ctrEditar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["editar_id"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=cargos";</script>';
            exit;
        }

        $datos = array(
            "id" => (int)$_POST["editar_id"],
            "nombre" => trim($_POST["editar_nombre"]),
            "id_departamento" => (int)$_POST["editar_id_departamento"]
        );

        if (CargoModel::mdlEditarCargo($datos)) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('editar', 'cargo', $datos["id"], 'Editó cargo: ' . $datos["nombre"]);
            echo '<script>alert("Cargo actualizado correctamente"); window.location = "index.php?ruta=cargos";</script>';
        } else {
            echo '<script>alert("Error al actualizar el cargo"); window.location = "index.php?ruta=cargos";</script>';
        }
        exit;
    }

    static public function ctrEliminar()
    {
        if (!isset($_GET["id"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_GET['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=cargos";</script>';
            exit;
        }

        $id = (int)$_GET["id"];

        $tienePersonal = CargoModel::mdlTienePersonalAsignado($id);
        $tieneTurnos = CargoModel::mdlTieneTurnosAsignados($id);

        if ($tienePersonal || $tieneTurnos) {
            $mensajes = [];
            if ($tienePersonal) $mensajes[] = "personal asignado";
            if ($tieneTurnos) $mensajes[] = "turnos vinculados";
            echo '<script>
                alert("No se puede eliminar este cargo porque tiene ' . implode(" y ", $mensajes) . '. Reasígnelos primero.");
                window.location = "index.php?ruta=cargos";
            </script>';
            exit;
        }

        $cargo = CargoModel::mdlObtenerCargo($id);
        if (CargoModel::mdlEliminarCargo($id)) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('eliminar', 'cargo', $id, 'Eliminó cargo: ' . ($cargo["nombre"] ?? "ID $id"));
            echo '<script>alert("Cargo eliminado correctamente"); window.location = "index.php?ruta=cargos";</script>';
        } else {
            echo '<script>alert("Error al eliminar el cargo"); window.location = "index.php?ruta=cargos";</script>';
        }
        exit;
    }
}
