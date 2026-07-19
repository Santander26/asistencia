<?php
require_once "config/Conexion.php";
require_once "models/MovimientoModel.php";

class AsistenciaModel
{
    static public function mdlContarPresentesHoy($tabla, $fecha)
    {
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) as total FROM $tabla WHERE fecha = :fecha AND (estado_entrada = 'Presente' OR estado_entrada = 'Tarde') AND id_personal != 1");
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    static public function mdlComprobarAsistenciaHoy($tabla, $idPersonal, $fecha)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE id_personal = :id_personal AND fecha = :fecha");
        $stmt->bindParam(":id_personal", $idPersonal, PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    static public function mdlRegistrarAsistencia($tabla, $datos)
    {
        $stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(id_personal, fecha, hora_entrada, estado_entrada) VALUES (:id_personal, :fecha, :hora_entrada, :estado_entrada)");
        $stmt->bindParam(":id_personal", $datos["id_personal"], PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
        $stmt->bindParam(":hora_entrada", $datos["hora_entrada"], PDO::PARAM_STR);
        $stmt->bindParam(":estado_entrada", $datos["estado_entrada"], PDO::PARAM_STR);
        if ($stmt->execute()) return "ok";
        return "error";
    }

    static public function mdlActualizarSalida($tabla, $id_personal, $fecha, $hora_salida)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET hora_salida = :hora_salida, horas_trabajadas = ROUND(TIME_TO_SEC(TIMEDIFF(:hora_salida, hora_entrada)) / 3600, 2) WHERE id_personal = :id_personal AND fecha = :fecha");
        $stmt->bindParam(":hora_salida", $hora_salida, PDO::PARAM_STR);
        $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        return $stmt->execute();
    }

    static public function mdlRegistrarMarcacion($id_personal, $estado_entrada = null)
    {
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');

        $ultimo = MovimientoModel::mdlUltimoMovimiento($id_personal, $fecha);

        if (!$ultimo) {
            $tipo = 'entrada';
            $yaExiste = self::mdlComprobarAsistenciaHoy('asistencias', $id_personal, $fecha);
            if (!$yaExiste) {
                $datos = array(
                    'id_personal' => $id_personal,
                    'fecha' => $fecha,
                    'hora_entrada' => $hora,
                    'estado_entrada' => $estado_entrada ?? 'Presente'
                );
                self::mdlRegistrarAsistencia('asistencias', $datos);
            }
            $mensaje = 'Entrada registrada';
        } elseif ($ultimo['tipo'] == 'entrada') {
            $tipo = 'salida';
            self::mdlActualizarSalida('asistencias', $id_personal, $fecha, $hora);
            $mensaje = 'Salida registrada';
        } else {
            $tipo = 'entrada';
            $mensaje = 'Reingreso registrado';
        }

        MovimientoModel::mdlInsertarMovimiento($id_personal, $fecha, $hora, $tipo);
        return ['mensaje' => $mensaje, 'tipo' => $tipo];
    }

    static public function mdlListarAsistenciasReporte($fecha_inicio, $fecha_fin, $id_cargo = null, $id_personal = null)
    {
        $sql = "SELECT a.id, a.fecha, a.hora_entrada, a.hora_salida, a.horas_trabajadas, a.estado_entrada,
                       p.nombre, p.apellido, p.documento_identidad, c.nombre as nombre_cargo
                FROM asistencias a
                INNER JOIN personal p ON a.id_personal = p.id
                LEFT JOIN cargos c ON p.id_cargo = c.id
                WHERE a.fecha BETWEEN :fecha_inicio AND :fecha_fin AND p.id != 1";
        if ($id_cargo) {
            $sql .= " AND p.id_cargo = :id_cargo";
        }
        if ($id_personal) {
            $sql .= " AND p.id = :id_personal";
        }
        $sql .= " ORDER BY a.fecha DESC, a.hora_entrada DESC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":fecha_inicio", $fecha_inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $fecha_fin, PDO::PARAM_STR);
        if ($id_cargo) {
            $stmt->bindParam(":id_cargo", $id_cargo, PDO::PARAM_INT);
        }
        if ($id_personal) {
            $stmt->bindParam(":id_personal", $id_personal, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdlAsistenciaUltimosDias($dias = 7)
    {
        $fecha_fin = date('Y-m-d');
        $fecha_ini = date('Y-m-d', strtotime("-" . ($dias - 1) . " days"));
        $stmt = Conexion::conectar()->prepare("SELECT fecha,
                       SUM(CASE WHEN estado_entrada IN ('Presente','Tarde') THEN 1 ELSE 0 END) as presentes,
                       SUM(CASE WHEN estado_entrada = 'Tarde' THEN 1 ELSE 0 END) as tarde
                FROM asistencias
                WHERE fecha BETWEEN :fecha_ini AND :fecha_fin AND id_personal != 1
                GROUP BY fecha
                ORDER BY fecha ASC");
        $stmt->bindParam(":fecha_ini", $fecha_ini, PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $fecha_fin, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $result = array();
        for ($i = 0; $i < $dias; $i++) {
            $fecha = date('Y-m-d', strtotime("+" . $i . " days", strtotime($fecha_ini)));
            $result[$fecha] = array('presentes' => 0, 'tarde' => 0, 'ausentes' => 0);
        }
        foreach ($rows as $r) {
            if (isset($result[$r['fecha']])) {
                $result[$r['fecha']]['presentes'] = (int)$r['presentes'];
                $result[$r['fecha']]['tarde'] = (int)$r['tarde'];
            }
        }
        return $result;
    }

    static public function mdlEstadoAsistenciaHoy()
    {
        $fecha = date('Y-m-d');
        $stmt = Conexion::conectar()->prepare("SELECT p.id, p.nombre, p.apellido, p.foto, p.id_cargo,
                       c.nombre as nombre_cargo,
                       a.hora_entrada, a.estado_entrada
                FROM personal p
                LEFT JOIN cargos c ON p.id_cargo = c.id
                LEFT JOIN asistencias a ON a.id_personal = p.id AND a.fecha = :fecha
                WHERE p.id_estado = 1 AND p.id != 1
                ORDER BY a.estado_entrada ASC, p.apellido ASC");
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdlContarTardeHoy()
    {
        $fecha = date('Y-m-d');
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) as total FROM asistencias WHERE fecha = :fecha AND estado_entrada = 'Tarde' AND id_personal != 1");
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    static public function mdlListarTardeHoy()
    {
        $fecha = date('Y-m-d');
        $stmt = Conexion::conectar()->prepare("SELECT a.hora_entrada, p.nombre, p.apellido, p.documento_identidad, c.nombre as nombre_cargo
                FROM asistencias a
                INNER JOIN personal p ON a.id_personal = p.id
                LEFT JOIN cargos c ON p.id_cargo = c.id
                WHERE a.fecha = :fecha AND a.estado_entrada = 'Tarde' AND p.id != 1
                ORDER BY a.hora_entrada ASC");
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
