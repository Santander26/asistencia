<?php
require_once "config/Conexion.php";

class TurnoModel
{
    static public function mdlMostrarTurnos($tabla, $soloActivos = false)
    {
        $sql = "SELECT t.*, GROUP_CONCAT(c.nombre SEPARATOR ', ') as cargos_asociados
                FROM $tabla t
                LEFT JOIN turnos_cargos tc ON t.id = tc.id_turno
                LEFT JOIN cargos c ON tc.id_cargo = c.id";
        if ($soloActivos) {
            $sql .= " WHERE t.estado = 1";
        }
        $sql .= " GROUP BY t.id ORDER BY t.nombre_turno";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdlMostrarTurno($tabla, $item, $valor)
    {
        $sql = "SELECT t.*, GROUP_CONCAT(tc.id_cargo) as cargos_ids
                FROM $tabla t
                LEFT JOIN turnos_cargos tc ON t.id = tc.id_turno
                WHERE t.$item = :$item
                GROUP BY t.id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":" . $item, $valor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    static public function mdlListarIdsCargos($id_turno)
    {
        $stmt = Conexion::conectar()->prepare("SELECT id_cargo FROM turnos_cargos WHERE id_turno = :id_turno");
        $stmt->bindParam(":id_turno", $id_turno, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    static public function mdlMostrarTurnosPorCargo($tabla, $id_cargo)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT t.* FROM $tabla t
             WHERE t.estado = 1
             AND (
                 NOT EXISTS (SELECT 1 FROM turnos_cargos tc WHERE tc.id_turno = t.id)
                 OR EXISTS (SELECT 1 FROM turnos_cargos tc WHERE tc.id_turno = t.id AND tc.id_cargo = :id_cargo)
             )
             ORDER BY t.nombre_turno"
        );
        $stmt->bindParam(":id_cargo", $id_cargo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdlAgregarTurno($tabla, $datos)
    {
        $db = Conexion::conectar();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO $tabla (nombre_turno, hora_entrada, hora_salida, tolerancia_minutos, estado)
                 VALUES (:nombre_turno, :hora_entrada, :hora_salida, :tolerancia_minutos, 1)"
            );
            $stmt->bindParam(":nombre_turno", $datos["nombre_turno"], PDO::PARAM_STR);
            $stmt->bindParam(":hora_entrada", $datos["hora_entrada"], PDO::PARAM_STR);
            $stmt->bindParam(":hora_salida", $datos["hora_salida"], PDO::PARAM_STR);
            $stmt->bindParam(":tolerancia_minutos", $datos["tolerancia_minutos"], PDO::PARAM_INT);
            $stmt->execute();

            $id_turno = $db->lastInsertId();

            if (!empty($datos["cargos"])) {
                $stmt = $db->prepare("INSERT INTO turnos_cargos (id_turno, id_cargo) VALUES (:id_turno, :id_cargo)");
                foreach ($datos["cargos"] as $id_cargo) {
                    $stmt->bindParam(":id_turno", $id_turno, PDO::PARAM_INT);
                    $stmt->bindParam(":id_cargo", $id_cargo, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    static public function mdlModificarTurno($tabla, $datos)
    {
        $db = Conexion::conectar();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "UPDATE $tabla SET nombre_turno = :nombre_turno, hora_entrada = :hora_entrada,
                 hora_salida = :hora_salida, tolerancia_minutos = :tolerancia_minutos
                 WHERE id = :id"
            );
            $stmt->bindParam(":nombre_turno", $datos["nombre_turno"], PDO::PARAM_STR);
            $stmt->bindParam(":hora_entrada", $datos["hora_entrada"], PDO::PARAM_STR);
            $stmt->bindParam(":hora_salida", $datos["hora_salida"], PDO::PARAM_STR);
            $stmt->bindParam(":tolerancia_minutos", $datos["tolerancia_minutos"], PDO::PARAM_INT);
            $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
            $stmt->execute();

            $eliminar = $db->prepare("DELETE FROM turnos_cargos WHERE id_turno = :id_turno");
            $eliminar->bindParam(":id_turno", $datos["id"], PDO::PARAM_INT);
            $eliminar->execute();

            if (!empty($datos["cargos"])) {
                $stmt = $db->prepare("INSERT INTO turnos_cargos (id_turno, id_cargo) VALUES (:id_turno, :id_cargo)");
                foreach ($datos["cargos"] as $id_cargo) {
                    $stmt->bindParam(":id_turno", $datos["id"], PDO::PARAM_INT);
                    $stmt->bindParam(":id_cargo", $id_cargo, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    static public function mdlCambiarEstadoTurno($tabla, $id, $estado)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET estado = :estado WHERE id = :id");
        $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
