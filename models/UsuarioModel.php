<?php
require_once "config/Conexion.php";

class UsuarioModel
{

    static public function mdlMostrarUsuario($tabla, $item, $valor)
    {
        if ($item != null) {
            $stmt = Conexion::conectar()->prepare("SELECT p.*, r.nombre as rol_nombre, e.nombre as estado_nombre FROM $tabla p LEFT JOIN roles r ON p.id_rol = r.id LEFT JOIN estados_personal e ON p.id_estado = e.id WHERE p.$item = :$item");
            $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        }
        else {
            $stmt = Conexion::conectar()->prepare("SELECT p.*, c.nombre as nombre_cargo, t.nombre_turno as nombre_turno, r.nombre as rol_nombre, e.nombre as estado_nombre FROM $tabla p LEFT JOIN cargos c ON p.id_cargo = c.id LEFT JOIN horarios_turnos t ON p.id_turno = t.id LEFT JOIN roles r ON p.id_rol = r.id LEFT JOIN estados_personal e ON p.id_estado = e.id WHERE p.id != 1 AND p.email != 'admin'");
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }

    static public function mdlVerificarDuplicado($documento, $email, $excluirId = null)
    {
        $pdo = Conexion::conectar();
        $sql = "SELECT documento_identidad, email FROM personal WHERE (documento_identidad = :doc OR email = :email)";
        if ($excluirId) {
            $sql .= " AND id != :id";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":doc", $documento, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        if ($excluirId) {
            $stmt->bindParam(":id", $excluirId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlIngresarUsuario($tabla, $datos)
    {
        try {
            $stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(nombre, apellido, documento_identidad, email, password, id_cargo, id_turno, id_rol, id_estado) VALUES (:nombre, :apellido, :documento_identidad, :email, :password, :id_cargo, :id_turno, :id_rol, :id_estado)");

            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":apellido", $datos["apellido"], PDO::PARAM_STR);
            $stmt->bindParam(":documento_identidad", $datos["documento_identidad"], PDO::PARAM_STR);
            $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
            $stmt->bindParam(":password", $datos["password"], PDO::PARAM_STR);
            $stmt->bindParam(":id_cargo", $datos["id_cargo"], PDO::PARAM_INT);
            $stmt->bindParam(":id_turno", $datos["id_turno"], PDO::PARAM_INT);
            $stmt->bindParam(":id_rol", $datos["id_rol"], PDO::PARAM_INT);
            $stmt->bindParam(":id_estado", $datos["id_estado"], PDO::PARAM_INT);

            if ($stmt->execute()) {
                return "ok";
            }
            return "error";
        } catch (PDOException $e) {
            return "error";
        }
    }

    static public function mdlEditarUsuario($tabla, $datos)
    {
        try {
            $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET nombre = :nombre, apellido = :apellido, documento_identidad = :documento_identidad, email = :email, password = :password, id_cargo = :id_cargo, id_turno = :id_turno WHERE id = :id");

            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":apellido", $datos["apellido"], PDO::PARAM_STR);
            $stmt->bindParam(":documento_identidad", $datos["documento_identidad"], PDO::PARAM_STR);
            $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
            $stmt->bindParam(":password", $datos["password"], PDO::PARAM_STR);
            $stmt->bindParam(":id_cargo", $datos["id_cargo"], PDO::PARAM_INT);
            $stmt->bindParam(":id_turno", $datos["id_turno"], PDO::PARAM_INT);
            $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);

            if ($stmt->execute()) {
                return "ok";
            }
            return "error";
        } catch (PDOException $e) {
            return "error";
        }
    }

    static public function mdlInactivarUsuario($tabla, $id, $estado)
    {
        $estado = (int)$estado;
        if ($estado >= 2) {
            $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET id_estado = 1 WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        } else {
            $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET id_estado = 2 WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        }

        return $stmt->execute() ? "ok" : "error";
    }

    static public function mdlIncrementarIntentos($id)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE personal SET intentos_fallidos = intentos_fallidos + 1 WHERE id = :id"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlResetearIntentos($id)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE personal SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = :id"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlBloquearUsuario($id)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE personal SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = :id"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlActualizarFoto($tabla, $id, $foto)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET foto = :foto WHERE id = :id");

        $stmt->bindParam(":foto", $foto, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }
    }
}
?>