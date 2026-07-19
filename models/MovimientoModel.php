<?php
require_once "config/Conexion.php";

class MovimientoModel
{
    static public function mdlInsertarMovimiento($id_personal, $fecha, $hora, $tipo)
    {
        $stmt = Conexion::conectar()->prepare("INSERT INTO movimientos (id_personal, fecha, hora, tipo) VALUES (:id_personal, :fecha, :hora, :tipo)");
        $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->bindParam(":hora", $hora, PDO::PARAM_STR);
        $stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);
        return $stmt->execute();
    }

    static public function mdlUltimoMovimiento($id_personal, $fecha)
    {
        $stmt = Conexion::conectar()->prepare("SELECT tipo FROM movimientos WHERE id_personal = :id_personal AND fecha = :fecha ORDER BY id DESC LIMIT 1");
        $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    static public function mdlMovimientosDelDia($id_personal, $fecha)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM movimientos WHERE id_personal = :id_personal AND fecha = :fecha ORDER BY id ASC");
        $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdlTotalMovimientosHoy()
    {
        $fecha = date('Y-m-d');
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) as total FROM movimientos WHERE fecha = :fecha");
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }
}
