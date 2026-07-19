<?php
require_once "config/Conexion.php";

class SesionModel
{
    static public function mdlGuardarToken($id_usuario, $token)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO sesiones (id_usuario, token, ultima_actividad) VALUES (:id_usuario, :token, NOW())"
        );
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        return $stmt->execute() ? $stmt->lastInsertId() : false;
    }

    static public function mdlVerificarToken($token)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT * FROM sesiones WHERE token = :token AND ultima_actividad > DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    static public function mdlActualizarActividad($id)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE sesiones SET ultima_actividad = NOW() WHERE id = :id"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlEliminarToken($token)
    {
        $stmt = Conexion::conectar()->prepare(
            "DELETE FROM sesiones WHERE token = :token"
        );
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        return $stmt->execute();
    }

    static public function mdlLimpiarSesionesExpiradas()
    {
        $stmt = Conexion::conectar()->prepare(
            "DELETE FROM sesiones WHERE ultima_actividad < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        return $stmt->execute();
    }
}
