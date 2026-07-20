<?php
require_once "config/Conexion.php";

class PersonalModel
{

    static public function mdlContarPersonalActivo($tabla)
    {
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) as total FROM $tabla WHERE id_estado = 1 AND id != 1 AND email != 'admin'");
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>