<?php
require_once "config/Conexion.php";

class RolController
{
    static public function ctrListarUsuariosConRol()
    {
        $stmt = Conexion::conectar()->prepare("SELECT p.id, p.nombre, p.apellido, p.documento_identidad, c.nombre as nombre_cargo, p.id_rol, r.nombre as rol_nombre
            FROM personal p
            LEFT JOIN cargos c ON p.id_cargo = c.id
            LEFT JOIN roles r ON p.id_rol = r.id
            WHERE p.id != 1 AND (p.id_rol != 4 OR p.id_rol IS NULL)
            ORDER BY p.apellido ASC, p.nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function ctrContarPorRol($id_rol)
    {
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) as total FROM personal WHERE id_rol = :id_rol AND id != 1 AND id_rol != 4");
        $stmt->bindParam(":id_rol", $id_rol, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetch()["total"];
    }

    static public function ctrAsignarRol()
    {
        if (!isset($_POST["id_personal"]) || !isset($_POST["id_rol"])) {
            echo json_encode(["success" => false, "message" => "Faltan datos"]);
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/RbacHelper.php";
        if (!RbacHelper::soloAdmin()) {
            echo json_encode(["success" => false, "message" => "Acceso denegado"]);
            return;
        }

        $id_personal = (int)$_POST["id_personal"];
        $nuevo_rol = (int)$_POST["id_rol"];

        if ($nuevo_rol === 4) {
            echo json_encode(["success" => false, "message" => "El rol Admin no se puede asignar desde aquí"]);
            return;
        }

        if ($id_personal === (int)$_SESSION["id"]) {
            echo json_encode(["success" => false, "message" => "No puedes cambiar tu propio rol desde aquí"]);
            return;
        }

        $stmt = Conexion::conectar()->prepare("SELECT id, nombre FROM roles WHERE id = :id");
        $stmt->bindParam(":id", $nuevo_rol, PDO::PARAM_INT);
        $stmt->execute();
        $rolDestino = $stmt->fetch();
        if (!$rolDestino) {
            echo json_encode(["success" => false, "message" => "El rol no existe"]);
            return;
        }

        $rolesUnicos = [1, 2];
        if (in_array($nuevo_rol, $rolesUnicos)) {
            $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) as total FROM personal WHERE id_rol = :rol AND id != :id AND id != 1");
            $stmt->bindParam(":rol", $nuevo_rol, PDO::PARAM_INT);
            $stmt->bindParam(":id", $id_personal, PDO::PARAM_INT);
            $stmt->execute();
            if ((int)$stmt->fetch()["total"] > 0) {
                echo json_encode(["success" => false, "message" => "Ya existe un " . $rolDestino["nombre"] . ". Debes quitarle el rol primero."]);
                return;
            }
        }

        try {
            $stmt = Conexion::conectar()->prepare("UPDATE personal SET id_rol = :id_rol WHERE id = :id");
            $stmt->bindParam(":id_rol", $nuevo_rol, PDO::PARAM_INT);
            $stmt->bindParam(":id", $id_personal, PDO::PARAM_INT);
            $stmt->execute();

            require_once "helpers/AuditoriaHelper.php";
            $nombreRol = $rolDestino["nombre"];
            AuditoriaHelper::log('asignar_rol', 'personal', $id_personal, 'Asignó rol ' . $nombreRol . ' a personal ID: ' . $id_personal);

            echo json_encode(["success" => true, "message" => "Rol asignado correctamente"]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error al asignar rol: " . $e->getMessage()]);
        }
    }
}
