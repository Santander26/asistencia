<?php
require_once "models/DepartamentoModel.php";
require_once "config/Conexion.php";

class DepartamentoController
{
    static public function ctrListarDepartamentos()
    {
        return DepartamentoModel::mdlMostrarDepartamentos("departamentos");
    }

    static public function ctrCrear()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["nombre_departamento"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=departamentos";</script>';
            exit;
        }

        $datos = array(
            "nombre" => trim($_POST["nombre_departamento"]),
            "descripcion" => trim($_POST["descripcion_departamento"] ?? "")
        );

        if (DepartamentoModel::mdlCrearDepartamento($datos)) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('crear', 'departamento', null, 'Creó departamento: ' . $datos["nombre"]);
            echo '<script>alert("Departamento creado correctamente"); window.location = "index.php?ruta=departamentos";</script>';
        } else {
            echo '<script>alert("Error al crear el departamento"); window.location = "index.php?ruta=departamentos";</script>';
        }
        exit;
    }

    static public function ctrEditar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["editar_id"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=departamentos";</script>';
            exit;
        }

        $datos = array(
            "id" => (int)$_POST["editar_id"],
            "nombre" => trim($_POST["editar_nombre"]),
            "descripcion" => trim($_POST["editar_descripcion"] ?? "")
        );

        if (DepartamentoModel::mdlEditarDepartamento($datos)) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('editar', 'departamento', $datos["id"], 'Editó departamento: ' . $datos["nombre"]);
            echo '<script>alert("Departamento actualizado correctamente"); window.location = "index.php?ruta=departamentos";</script>';
        } else {
            echo '<script>alert("Error al actualizar el departamento"); window.location = "index.php?ruta=departamentos";</script>';
        }
        exit;
    }

    static public function ctrEliminar()
    {
        if (!isset($_GET["id"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_GET['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=departamentos";</script>';
            exit;
        }

        $id = (int)$_GET["id"];

        if (DepartamentoModel::mdlTieneCargosAsignados($id)) {
            echo '<script>
                alert("No se puede eliminar este departamento porque tiene cargos asignados. Reasígnelos primero.");
                window.location = "index.php?ruta=departamentos";
            </script>';
            exit;
        }

        $depto = DepartamentoModel::mdlObtenerDepartamento($id);
        if (DepartamentoModel::mdlEliminarDepartamento($id)) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('eliminar', 'departamento', $id, 'Eliminó departamento: ' . ($depto["nombre"] ?? "ID $id"));
            echo '<script>alert("Departamento eliminado correctamente"); window.location = "index.php?ruta=departamentos";</script>';
        } else {
            echo '<script>alert("Error al eliminar el departamento"); window.location = "index.php?ruta=departamentos";</script>';
        }
        exit;
    }
}
