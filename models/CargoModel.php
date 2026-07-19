<?php
require_once "config/Conexion.php";

class CargoModel
{
    static public function mdlListarCargos()
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT c.*, d.nombre as nombre_departamento,
                    (SELECT COUNT(*) FROM personal WHERE id_cargo = c.id) as total_personal,
                    (SELECT COUNT(*) FROM turnos_cargos WHERE id_cargo = c.id) as total_turnos
             FROM cargos c
             LEFT JOIN departamentos d ON c.id_departamento = d.id
             ORDER BY c.nombre"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdlObtenerCargo($id)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM cargos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    static public function mdlCrearCargo($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO cargos (nombre, id_departamento) VALUES (:nombre, :id_departamento)"
        );
        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":id_departamento", $datos["id_departamento"], PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlEditarCargo($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE cargos SET nombre = :nombre, id_departamento = :id_departamento WHERE id = :id"
        );
        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":id_departamento", $datos["id_departamento"], PDO::PARAM_INT);
        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlEliminarCargo($id)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM cargos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlTienePersonalAsignado($id)
    {
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) as total FROM personal WHERE id_cargo = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch()["total"] > 0;
    }

    static public function mdlTieneTurnosAsignados($id)
    {
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) as total FROM turnos_cargos WHERE id_cargo = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch()["total"] > 0;
    }
}
