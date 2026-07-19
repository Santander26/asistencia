<?php
require_once "config/Conexion.php";

class HistorialBajaModel
{
    static public function mdlInsertar($datos)
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "INSERT INTO historial_bajas (id_personal, motivo, descripcion, fecha_baja, id_usuario_registra)
                 VALUES (:id_personal, :motivo, :descripcion, :fecha_baja, :id_usuario_registra)"
            );
            $stmt->bindParam(":id_personal", $datos["id_personal"], PDO::PARAM_INT);
            $stmt->bindParam(":motivo", $datos["motivo"], PDO::PARAM_STR);
            $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
            $stmt->bindParam(":fecha_baja", $datos["fecha_baja"], PDO::PARAM_STR);
            $stmt->bindParam(":id_usuario_registra", $datos["id_usuario_registra"], PDO::PARAM_INT);
            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            return "error";
        }
    }

    static public function mdlReactivar($id_personal, $id_usuario)
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "UPDATE historial_bajas SET fecha_reactivacion = NOW()
                 WHERE id_personal = :id_personal AND fecha_reactivacion IS NULL
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            return "error";
        }
    }

    static public function mdlListar($id_personal = null)
    {
        try {
            if ($id_personal) {
                $stmt = Conexion::conectar()->prepare(
                    "SELECT h.*, p.nombre, p.apellido, p.documento_identidad,
                            u.nombre as usuario_nombre, u.apellido as usuario_apellido
                     FROM historial_bajas h
                     JOIN personal p ON h.id_personal = p.id
                     JOIN personal u ON h.id_usuario_registra = u.id
                     WHERE h.id_personal = :id_personal
                     ORDER BY h.created_at DESC"
                );
                $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
            } else {
                $stmt = Conexion::conectar()->prepare(
                    "SELECT h.*, p.nombre, p.apellido, p.documento_identidad,
                            u.nombre as usuario_nombre, u.apellido as usuario_apellido,
                            c.nombre as nombre_cargo
                     FROM historial_bajas h
                     JOIN personal p ON h.id_personal = p.id
                     JOIN personal u ON h.id_usuario_registra = u.id
                     LEFT JOIN cargos c ON p.id_cargo = c.id
                     ORDER BY h.created_at DESC"
                );
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    static public function mdlUltimaBaja($id_personal)
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "SELECT * FROM historial_bajas
                 WHERE id_personal = :id_personal
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
}
