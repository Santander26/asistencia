<?php
require_once "models/AsistenciaModel.php";

class HuellaController
{
    static public function ctrSolicitarEnrolamiento()
    {
        $id_personal = intval($_POST["id_personal"] ?? 0);
        if (!$id_personal) {
            echo json_encode(["success" => false, "mensaje" => "ID requerido"]);
            return;
        }

        require_once "models/UsuarioModel.php";
        $personal = UsuarioModel::mdlMostrarUsuario("personal", "id", $id_personal);
        if (!$personal) {
            echo json_encode(["success" => false, "mensaje" => "Empleado no encontrado"]);
            return;
        }

        $pdo = Conexion::conectar();

        // Cancelar enrolamientos previos pendientes del mismo empleado
        $stmt = $pdo->prepare("UPDATE enrolamiento_pendiente SET status = 'cancelled' WHERE id_personal = ? AND status = 'pending'");
        $stmt->execute([$id_personal]);

        $stmt = $pdo->prepare("INSERT INTO enrolamiento_pendiente (id_personal, documento, nombre, apellido) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_personal, $personal["documento_identidad"], $personal["nombre"], $personal["apellido"]]);

        echo json_encode(["success" => true, "mensaje" => "Enrolamiento solicitado. Coloque el dedo en el lector."]);
    }

    static public function ctrConsultarEnrolamiento()
    {
        $pdo = Conexion::conectar();
        $stmt = $pdo->query("SELECT id, id_personal, documento, nombre, apellido FROM enrolamiento_pendiente WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Pasar a in_progress para evitar que otro device lo tome
            $upd = $pdo->prepare("UPDATE enrolamiento_pendiente SET status = 'in_progress' WHERE id = ?");
            $upd->execute([$row["id"]]);
            echo json_encode(["pending" => true, "enrollment_id" => $row["id"], "documento" => $row["documento"], "nombre" => $row["nombre"], "apellido" => $row["apellido"]]);
        } else {
            echo json_encode(["pending" => false]);
        }
    }

    static public function ctrConfirmarEnrolamiento()
    {
        $enrollment_id = intval($_POST["enrollment_id"] ?? 0);
        $documento = trim($_POST["documento"] ?? "");
        $fingerprint_id = intval($_POST["fingerprint_id"] ?? -1);

        if (!$enrollment_id || $fingerprint_id < 0) {
            echo json_encode(["success" => false, "mensaje" => "Datos incompletos"]);
            return;
        }

        $pdo = Conexion::conectar();

        // Guardar fingerprint_id en sdk_huella del personal
        $stmt = $pdo->prepare("UPDATE personal p JOIN enrolamiento_pendiente e ON p.id = e.id_personal SET p.sdk_huella = ? WHERE e.id = ?");
        $stmt->execute([(string)$fingerprint_id, $enrollment_id]);

        // Marcar enrolamiento como completado
        $stmt = $pdo->prepare("UPDATE enrolamiento_pendiente SET status = 'completed' WHERE id = ?");
        $stmt->execute([$enrollment_id]);

        echo json_encode(["success" => true, "mensaje" => "Huella registrada correctamente"]);
    }

    static public function ctrObtenerMapeo()
    {
        $pdo = Conexion::conectar();
        $stmt = $pdo->query("SELECT sdk_huella as fingerprint_id, documento_identidad, nombre, apellido FROM personal WHERE sdk_huella IS NOT NULL AND sdk_huella != '' AND id_estado = 1");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapeo = [];
        foreach ($rows as $r) {
            $mapeo[] = [
                "fingerprint_id" => intval($r["fingerprint_id"]),
                "documento" => $r["documento_identidad"],
                "nombre" => $r["nombre"],
                "apellido" => $r["apellido"]
            ];
        }

        echo json_encode(["success" => true, "mapeo" => $mapeo]);
    }

    static public function ctrSyncOffline()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $registros = $input["registros"] ?? [];

        if (empty($registros)) {
            echo json_encode(["success" => false, "mensaje" => "Sin datos"]);
            return;
        }

        require_once "models/UsuarioModel.php";
        require_once "models/TurnoModel.php";
        require_once "helpers/AuditoriaHelper.php";

        $pdo = Conexion::conectar();
        $procesados = 0;
        $errores = [];

        foreach ($registros as $r) {
            $documento = trim($r["documento"] ?? "");

            $personal = UsuarioModel::mdlMostrarUsuario("personal", "documento_identidad", $documento);
            if (!$personal || $personal["id_estado"] != 1) {
                $errores[] = ["documento" => $documento, "error" => "No encontrado o inactivo"];
                continue;
            }

            $horaActual = date('H:i:s');
            $estadoEntrada = 'Presente';
            $turno = TurnoModel::mdlMostrarTurno("horarios_turnos", "id", $personal["id_turno"]);
            if ($turno) {
                $limite = strtotime($turno["hora_entrada"]) + 900;
                if (strtotime($horaActual) > $limite) $estadoEntrada = "Tarde";
            }

            $resultado = AsistenciaModel::mdlRegistrarMarcacion($personal["id"], $estadoEntrada);

            AuditoriaHelper::log(
                $resultado['tipo'] == 'salida' ? 'marcar_salida' : 'marcar_entrada',
                'asistencia',
                $personal["id"],
                $personal["nombre"] . ' ' . $personal["apellido"] . ' - ' . $resultado['tipo'] . ' (offline) a las ' . $horaActual
            );

            $procesados++;
        }

        echo json_encode(["success" => true, "procesados" => $procesados, "errores" => $errores]);
    }
}
