<?php
require_once "config/Conexion.php";

class DepartamentoModel
{
    static public function mdlMostrarDepartamentos($tabla)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdlObtenerDepartamento($id)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM departamentos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    static public function mdlCrearDepartamento($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO departamentos (nombre, descripcion) VALUES (:nombre, :descripcion)"
        );
        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
        return $stmt->execute();
    }

    static public function mdlEditarDepartamento($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE departamentos SET nombre = :nombre, descripcion = :descripcion WHERE id = :id"
        );
        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlEliminarDepartamento($id)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM departamentos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlTieneCargosAsignados($id)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT COUNT(*) as total FROM cargos WHERE id_departamento = :id"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch()["total"] > 0;
    }
}
