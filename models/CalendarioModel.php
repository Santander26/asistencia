<?php
require_once "config/Conexion.php";

class CalendarioModel
{
    static public function mdlObtenerDiasPorMes($anio, $mes)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT id, dia, tipo, color, descripcion FROM calendario_escolar WHERE anio = :anio AND mes = :mes ORDER BY dia"
        );
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlObtenerDiasPorRango($anio, $mes)
    {
        return self::mdlObtenerDiasPorMes($anio, $mes);
    }

    static public function mdlGuardarDias($datos)
    {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare(
            "INSERT INTO calendario_escolar (anio, mes, dia, tipo, color, descripcion)
             VALUES (:anio, :mes, :dia, :tipo, :color, :descripcion)
             ON DUPLICATE KEY UPDATE tipo = VALUES(tipo), color = VALUES(color), descripcion = VALUES(descripcion)"
        );
        $affected = 0;
        foreach ($datos as $d) {
            $stmt->bindValue(":anio", (int)$d["anio"], PDO::PARAM_INT);
            $stmt->bindValue(":mes", (int)$d["mes"], PDO::PARAM_INT);
            $stmt->bindValue(":dia", (int)$d["dia"], PDO::PARAM_INT);
            $stmt->bindValue(":tipo", $d["tipo"], PDO::PARAM_STR);
            $stmt->bindValue(":color", $d["color"] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(":descripcion", $d["descripcion"] ?? "", PDO::PARAM_STR);
            if ($stmt->execute()) $affected++;
        }
        return $affected;
    }

    static public function mdlEliminarDia($id)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM calendario_escolar WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlEliminarDias($ids)
    {
        if (empty($ids)) return 0;
        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        $stmt = Conexion::conectar()->prepare("DELETE FROM calendario_escolar WHERE id IN ($placeholders)");
        return $stmt->execute($ids);
    }

    static public function mdlLimpiarMes($anio, $mes)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM calendario_escolar WHERE anio = :anio AND mes = :mes");
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlObtenerTareasPorCalendario($id_calendario)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT ct.*, p.nombre as creador_nombre, p.apellido as creador_apellido
             FROM calendario_tareas ct
             LEFT JOIN personal p ON ct.creado_por = p.id
             WHERE ct.id_calendario = :id
             ORDER BY ct.hora"
        );
        $stmt->bindParam(":id", $id_calendario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlListarTareasPorMes($anio, $mes)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT ct.*, c.dia, c.tipo as dia_tipo, c.color as dia_color,
                    p.nombre as creador_nombre, p.apellido as creador_apellido
             FROM calendario_tareas ct
             JOIN calendario_escolar c ON ct.id_calendario = c.id
             LEFT JOIN personal p ON ct.creado_por = p.id
             WHERE c.anio = :anio AND c.mes = :mes
             ORDER BY c.dia, ct.hora"
        );
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlGuardarTarea($id_calendario, $titulo, $descripcion, $hora, $creado_por)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO calendario_tareas (id_calendario, titulo, descripcion, hora, creado_por)
             VALUES (:id_calendario, :titulo, :descripcion, :hora, :creado_por)"
        );
        $stmt->bindParam(":id_calendario", $id_calendario, PDO::PARAM_INT);
        $stmt->bindParam(":titulo", $titulo, PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
        $stmt->bindParam(":hora", $hora, PDO::PARAM_STR);
        $stmt->bindParam(":creado_por", $creado_por, PDO::PARAM_INT);
        return $stmt->execute();
    }

    static public function mdlActualizarTarea($id, $titulo, $descripcion, $hora)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE calendario_tareas SET titulo = :titulo, descripcion = :descripcion, hora = :hora WHERE id = :id"
        );
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":titulo", $titulo, PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
        $stmt->bindParam(":hora", $hora, PDO::PARAM_STR);
        return $stmt->execute();
    }

    static public function mdlEliminarTarea($id)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM calendario_tareas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
