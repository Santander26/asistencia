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
}
