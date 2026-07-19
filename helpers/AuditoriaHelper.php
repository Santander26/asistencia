<?php
require_once "config/Conexion.php";

class AuditoriaHelper
{
    static public function log($accion, $entidad, $id_entidad = null, $detalle = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $id_usuario = $_SESSION["id"] ?? null;
        if (!$id_usuario) return;

        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO auditoria (id_usuario, accion, entidad, id_entidad, detalle) VALUES (:id_usuario, :accion, :entidad, :id_entidad, :detalle)"
        );
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":accion", $accion, PDO::PARAM_STR);
        $stmt->bindParam(":entidad", $entidad, PDO::PARAM_STR);
        $stmt->bindParam(":id_entidad", $id_entidad, PDO::PARAM_INT);
        $stmt->bindParam(":detalle", $detalle, PDO::PARAM_STR);
        $stmt->execute();
    }
}
