<?php
require_once "config/Conexion.php";

class JustificacionModel
{
    static public function mdlListarJustificaciones()
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT j.*, 
                   p.nombre as personal_nombre, p.apellido as personal_apellido, p.documento_identidad,
                   a.nombre as aprobado_nombre, a.apellido as aprobado_apellido,
                   c.nombre as nombre_cargo
            FROM justificaciones j
            INNER JOIN personal p ON j.id_personal = p.id
            LEFT JOIN personal a ON j.aprobado_por = a.id
            LEFT JOIN cargos c ON p.id_cargo = c.id
            WHERE p.id != 1
            ORDER BY j.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdlListarJustificacionesFiltro($fecha_inicio = null, $fecha_fin = null, $id_personal = null)
    {
        $sql = "
            SELECT j.*, 
                   p.nombre as personal_nombre, p.apellido as personal_apellido, p.documento_identidad,
                   a.nombre as aprobado_nombre, a.apellido as aprobado_apellido,
                   c.nombre as nombre_cargo
            FROM justificaciones j
            INNER JOIN personal p ON j.id_personal = p.id
            LEFT JOIN personal a ON j.aprobado_por = a.id
            LEFT JOIN cargos c ON p.id_cargo = c.id
            WHERE p.id != 1
        ";
        $params = [];
        if ($fecha_inicio) {
            $sql .= " AND j.fecha >= :fecha_inicio";
            $params[":fecha_inicio"] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND j.fecha <= :fecha_fin";
            $params[":fecha_fin"] = $fecha_fin;
        }
        if ($id_personal) {
            $sql .= " AND j.id_personal = :id_personal";
            $params[":id_personal"] = (int)$id_personal;
        }
        $sql .= " ORDER BY j.created_at DESC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    static public function mdlContarPendientes()
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) as total FROM justificaciones j
            INNER JOIN personal p ON j.id_personal = p.id
            WHERE j.aprobado_por IS NULL AND p.id != 1
        ");
        $stmt->execute();
        return (int)$stmt->fetch()["total"];
    }

    static public function mdlVerificarDuplicado($id_personal, $fecha)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) as total FROM justificaciones
            WHERE id_personal = :id_personal AND fecha = :fecha
        ");
        $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetch()["total"] > 0;
    }

    static public function mdlObtenerJustificacion($id)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT j.*, 
                   p.nombre as personal_nombre, p.apellido as personal_apellido, p.documento_identidad,
                   p.foto, p.id_cargo,
                   c.nombre as nombre_cargo,
                   a.nombre as aprobado_nombre, a.apellido as aprobado_apellido
            FROM justificaciones j
            INNER JOIN personal p ON j.id_personal = p.id
            LEFT JOIN personal a ON j.aprobado_por = a.id
            LEFT JOIN cargos c ON p.id_cargo = c.id
            WHERE j.id = :id
        ");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    static public function mdlCrearJustificacion($datos)
    {
        $db = Conexion::conectar();
        $stmt = $db->prepare("
            INSERT INTO justificaciones (id_personal, fecha, motivo, tipo, documento_adjunto)
            VALUES (:id_personal, :fecha, :motivo, :tipo, :documento_adjunto)
        ");
        $stmt->bindParam(":id_personal", $datos["id_personal"], PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":motivo", $datos["motivo"], PDO::PARAM_STR);
        $stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
        $stmt->bindParam(":documento_adjunto", $datos["documento_adjunto"], PDO::PARAM_STR);
        return $stmt->execute() ? $db->lastInsertId() : false;
    }

    static public function mdlAprobarJustificacion($id, $aprobado_por)
    {
        $stmt = Conexion::conectar()->prepare("
            UPDATE justificaciones SET aprobado_por = :aprobado_por, fecha_aprobacion = NOW()
            WHERE id = :id AND aprobado_por IS NULL
        ");
        $stmt->bindParam(":aprobado_por", $aprobado_por, PDO::PARAM_INT);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    static public function mdlRechazarJustificacion($id)
    {
        $stmt = Conexion::conectar()->prepare("
            UPDATE justificaciones SET aprobado_por = 0, fecha_aprobacion = NOW()
            WHERE id = :id AND aprobado_por IS NULL
        ");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    static public function mdlEliminarJustificacion($id)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM justificaciones WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
