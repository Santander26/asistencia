<?php
require_once "models/AsistenciaModel.php";

class AsistenciaController
{
    static public function ctrContarPresentesHoy()
    {
        $tabla = "asistencias";
        $fecha = date('Y-m-d');
        $respuesta = AsistenciaModel::mdlContarPresentesHoy($tabla, $fecha);
        return $respuesta["total"];
    }

    static public function ctrCalcularPorcentajeAsistencia($presentes)
    {
        require_once "models/PersonalModel.php";
        $totalEmpleados = PersonalModel::mdlContarPersonalActivo("personal");
        if ($totalEmpleados["total"] == 0) return 0;
        $porcentaje = ($presentes / $totalEmpleados["total"]) * 100;
        return round($porcentaje, 1);
    }

    static public function ctrMarcarAsistencia()
    {
        if (!isset($_POST["codigoAsistencia"])) return;

        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=asistencia";</script>';
            return;
        }

        require_once "models/UsuarioModel.php";
        require_once "models/TurnoModel.php";
        require_once "helpers/AuditoriaHelper.php";

        $personal = UsuarioModel::mdlMostrarUsuario("personal", "documento_identidad", trim($_POST["codigoAsistencia"]));

        if (!$personal || $personal["id_estado"] != 1) {
            echo '<script>
                alert("Credencial Inválida. El empleado no existe o está inactivo.");
                window.location = "index.php?ruta=asistencia";
            </script>';
            return;
        }

        $horaActual = date('H:i:s');
        $estadoEntrada = 'Presente';

        $turno = TurnoModel::mdlMostrarTurno("horarios_turnos", "id", $personal["id_turno"]);
        if ($turno) {
            $horaEntradaOficial = strtotime($turno["hora_entrada"]);
            $horaRegistro = strtotime($horaActual);
            $limiteTolerancia = $horaEntradaOficial + 900;
            if ($horaRegistro > $limiteTolerancia) $estadoEntrada = "Tarde";
        }

        $resultado = AsistenciaModel::mdlRegistrarMarcacion($personal["id"], $estadoEntrada);
        $saludo = ($resultado['tipo'] == 'salida') ? 'Nos vemos pronto' : 'Bienvenido(a)';

        AuditoriaHelper::log($resultado['tipo'] == 'salida' ? 'marcar_salida' : 'marcar_entrada', 'asistencia', $personal["id"], $personal["nombre"] . ' ' . $personal["apellido"] . ' - ' . $resultado['tipo'] . ' a las ' . $horaActual);

        echo '<script>
            alert("' . $resultado['mensaje'] . '\n' . $saludo . ' ' . $personal["nombre"] . ' ' . $personal["apellido"] . '\nHora: ' . $horaActual . '");
            window.location = "index.php?ruta=asistencia";
        </script>';
    }

    static public function ctrListarAsistenciasReporte()
    {
        $fecha_inicio = $_GET["fecha_inicio"] ?? date('Y-m-01');
        $fecha_fin = $_GET["fecha_fin"] ?? date('Y-m-d');
        $id_cargo = $_GET["id_cargo"] ?? null;
        if ($id_cargo === "" || $id_cargo === "0") $id_cargo = null;
        return AsistenciaModel::mdlListarAsistenciasReporte($fecha_inicio, $fecha_fin, $id_cargo);
    }

    static public function ctrAsistenciaUltimosDias($dias = 7)
    {
        return AsistenciaModel::mdlAsistenciaUltimosDias($dias);
    }

    static public function ctrEstadoAsistenciaHoy()
    {
        return AsistenciaModel::mdlEstadoAsistenciaHoy();
    }

    static public function ctrContarTardeHoy()
    {
        $respuesta = AsistenciaModel::mdlContarTardeHoy();
        return $respuesta["total"] ?? 0;
    }

    static public function ctrListarTardeHoy()
    {
        return AsistenciaModel::mdlListarTardeHoy();
    }

    static public function ctrApiDashboard()
    {
        $fecha = date('Y-m-d');
        $tabla = "asistencias";

        require_once "models/PersonalModel.php";
        $totalPersonalActivo = PersonalModel::mdlContarPersonalActivo("personal");
        $total = $totalPersonalActivo["total"] ?? 0;

        $presentesResp = AsistenciaModel::mdlContarPresentesHoy($tabla, $fecha);
        $presentes = $presentesResp["total"] ?? 0;
        $tardeResp = AsistenciaModel::mdlContarTardeHoy();
        $tarde = $tardeResp["total"] ?? 0;
        $presentesPuntuales = max(0, $presentes - $tarde);
        $ausentes = max(0, $total - $presentes);
        $porcentaje = $total > 0 ? round(($presentes / $total) * 100, 1) : 0;
        $porcentajeAusencia = $total > 0 ? round(100 - $porcentaje, 1) : 0;

        $estadoHoy = AsistenciaModel::mdlEstadoAsistenciaHoy();

        $chartSemanal = AsistenciaModel::mdlAsistenciaUltimosDias(7);
        $labels = array();
        $dataPresentes = array();
        foreach ($chartSemanal as $f => $d) {
            $labels[] = date('d/m', strtotime($f));
            $dataPresentes[] = $d['presentes'];
        }

        require_once "controllers/JustificacionController.php";
        $pendientes = JustificacionController::ctrContarPendientes();

        return array(
            "stats" => array(
                "total" => $total,
                "presentes" => $presentes,
                "ausentes" => $ausentes,
                "tarde" => $tarde,
                "porcentaje_asistencia" => $porcentaje,
                "porcentaje_ausencia" => $porcentajeAusencia,
                "justificaciones_pendientes" => $pendientes
            ),
            "tabla" => $estadoHoy,
            "chart_semanal" => array("labels" => $labels, "presentes" => $dataPresentes),
            "chart_hoy" => array(
                "presentes" => $presentesPuntuales,
                "tarde" => $tarde,
                "ausentes" => $ausentes
            )
        );
    }

    static public function ctrApiReportes($fecha_desde, $fecha_hasta, $id_cargo)
    {
        return array(
            "tabla" => AsistenciaModel::mdlListarAsistenciasReporte($fecha_desde, $fecha_hasta, $id_cargo)
        );
    }

    static public function ctrApiHistorialBajas($fecha)
    {
        require_once "models/HistorialBajaModel.php";
        return array("tabla" => HistorialBajaModel::mdlListar());
    }

    // Endpoint para marcación por huella desde ESP32
    static public function ctrMarcarPorHuella()
    {
        $documento = trim($_POST["documento"] ?? "");
        if (empty($documento)) {
            return ["success" => false, "mensaje" => "Documento requerido"];
        }

        require_once "models/UsuarioModel.php";
        require_once "models/TurnoModel.php";
        require_once "helpers/AuditoriaHelper.php";

        $personal = UsuarioModel::mdlMostrarUsuario("personal", "documento_identidad", $documento);
        if (!$personal || $personal["id_estado"] != 1) {
            return ["success" => false, "mensaje" => "Empleado no encontrado o inactivo"];
        }

        $horaActual = date('H:i:s');
        $estadoEntrada = 'Presente';
        $turno = TurnoModel::mdlMostrarTurno("horarios_turnos", "id", $personal["id_turno"]);
        if ($turno) {
            $horaEntradaOficial = strtotime($turno["hora_entrada"]);
            $horaRegistro = strtotime($horaActual);
            $limiteTolerancia = $horaEntradaOficial + 900;
            if ($horaRegistro > $limiteTolerancia) $estadoEntrada = "Tarde";
        }

        $resultado = AsistenciaModel::mdlRegistrarMarcacion($personal["id"], $estadoEntrada);

        AuditoriaHelper::log(
            $resultado['tipo'] == 'salida' ? 'marcar_salida' : 'marcar_entrada',
            'asistencia',
            $personal["id"],
            $personal["nombre"] . ' ' . $personal["apellido"] . ' - ' . $resultado['tipo'] . ' (huella) a las ' . $horaActual
        );

        return [
            "success" => true,
            "nombre" => $personal["nombre"],
            "apellido" => $personal["apellido"],
            "estado" => $estadoEntrada,
            "tipo" => $resultado['tipo'],
            "mensaje" => $resultado['mensaje'],
            "hora" => $horaActual
        ];
    }
}
