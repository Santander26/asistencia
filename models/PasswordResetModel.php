<?php
require_once "config/Conexion.php";

class PasswordResetModel
{
    static public function mdlBuscarPorDocumento($documento)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT id, nombre, apellido, email FROM personal WHERE documento_identidad = :doc AND id_estado = 1 AND id != 1"
        );
        $stmt->bindParam(":doc", $documento, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlCrearToken($id_personal, $token, $expires_at)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO password_resets (id_personal, token, expires_at) VALUES (:id_personal, :token, :expires_at)"
        );
        $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        $stmt->bindParam(":expires_at", $expires_at, PDO::PARAM_STR);
        return $stmt->execute();
    }

    static public function mdlVerificarToken($token)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT pr.*, p.nombre, p.apellido, p.email
             FROM password_resets pr
             JOIN personal p ON pr.id_personal = p.id
             WHERE pr.token = :token AND pr.used = 0 AND pr.expires_at > NOW()
             ORDER BY pr.created_at DESC LIMIT 1"
        );
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlMarcarUsado($id)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE password_resets SET used = 1 WHERE id = :id"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlActualizarPassword($id_personal, $password_hash)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE personal SET password = :password, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id"
        );
        $stmt->bindParam(":password", $password_hash, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id_personal, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlLimpiarTokensExpirados()
    {
        $stmt = Conexion::conectar()->prepare(
            "DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1"
        );
        return $stmt->execute();
    }
}
