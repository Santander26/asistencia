<?php
require_once "models/CalendarioModel.php";

class CalendarioController
{
    static public function ctrListarDias($anio, $mes)
    {
        return CalendarioModel::mdlObtenerDiasPorMes($anio, $mes);
    }

    static public function ctrGuardarDias()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["guardar_dias"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=gestion_calendario";</script>';
            exit;
        }

        $anio = (int)$_POST["anio"];
        $mes = (int)$_POST["mes"];
        $tipo = $_POST["tipo"];
        $color = $_POST["color"] ?? null;
        $dias_raw = $_POST["dias"] ?? "";
        $descripcion = $_POST["descripcion"] ?? "";

        $dias = array_map("intval", array_filter(array_map("trim", explode(",", $dias_raw)), function($v) {
            return $v >= 1 && $v <= 31;
        }));

        if (empty($dias)) {
            echo '<script>alert("No se especificaron dias validos"); window.location = "index.php?ruta=gestion_calendario&anio=' . $anio . '&mes=' . $mes . '";</script>';
            exit;
        }

        $datos = [];
        foreach ($dias as $dia) {
            $datos[] = ["anio" => $anio, "mes" => $mes, "dia" => $dia, "tipo" => $tipo, "color" => $color, "descripcion" => $descripcion];
        }

        $affected = CalendarioModel::mdlGuardarDias($datos);

        require_once "helpers/AuditoriaHelper.php";
        $etiqueta = ["laboral" => "laboral", "feriado" => "feriado", "vacaciones" => "vacaciones", "no_laborable" => "no laborable"];
        AuditoriaHelper::log("crear", "calendario_escolar", null, "Configuro {$affected} dia(s) como {$etiqueta[$tipo]} para {$mes}/{$anio}");

        echo '<script>alert("' . $affected . ' dia(s) guardado(s) como ' . $etiqueta[$tipo] . '"); window.location = "index.php?ruta=gestion_calendario&anio=' . $anio . '&mes=' . $mes . '";</script>';
        exit;
    }

    static public function ctrGuardarVacaciones()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["guardar_vacaciones"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=gestion_calendario";</script>';
            exit;
        }

        $fecha_inicio = $_POST["fecha_inicio"];
        $fecha_fin = $_POST["fecha_fin"];
        $color = $_POST["color_vac"] ?? "#f59e0b";
        $descripcion = $_POST["desc_vacaciones"] ?? "";

        if (!$fecha_inicio || !$fecha_fin) {
            echo '<script>alert("Debe seleccionar fecha de inicio y fin"); window.history.back();</script>';
            exit;
        }

        $start = new DateTime($fecha_inicio);
        $end = new DateTime($fecha_fin);
        $end->modify("+1 day");

        $datos = [];
        $period = new DatePeriod($start, new DateInterval("P1D"), $end);
        foreach ($period as $date) {
            $datos[] = [
                "anio" => (int)$date->format("Y"),
                "mes" => (int)$date->format("n"),
                "dia" => (int)$date->format("j"),
                "tipo" => "vacaciones",
                "color" => $color,
                "descripcion" => $descripcion
            ];
        }

        $affected = CalendarioModel::mdlGuardarDias($datos);

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log("crear", "calendario_escolar", null, "Registro vacaciones: {$fecha_inicio} al {$fecha_fin}");

        $anio_redirect = (int)$start->format("Y");
        $mes_redirect = (int)$start->format("n");
        echo '<script>alert("' . $affected . ' dia(s) guardado(s) como vacaciones"); window.location = "index.php?ruta=gestion_calendario&anio=' . $anio_redirect . '&mes=' . $mes_redirect . '";</script>';
        exit;
    }

    static public function ctrEliminarDia()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["eliminar_dia"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=gestion_calendario";</script>';
            exit;
        }

        $id = (int)$_POST["id"];
        $anio = (int)$_POST["anio"];
        $mes = (int)$_POST["mes"];

        CalendarioModel::mdlEliminarDia($id);

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log("eliminar", "calendario_escolar", $id, "Elimino dia configurado");

        echo '<script>window.location = "index.php?ruta=gestion_calendario&anio=' . $anio . '&mes=' . $mes . '";</script>';
        exit;
    }

    static public function ctrEliminarDias()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["eliminar_dias"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=gestion_calendario";</script>';
            exit;
        }

        $ids = isset($_POST["ids"]) ? (array)$_POST["ids"] : [];
        $ids = array_map("intval", $ids);
        $ids = array_filter($ids);

        $anio = (int)$_POST["anio"];
        $mes = (int)$_POST["mes"];

        if (empty($ids)) {
            echo '<script>alert("No selecciono ningun dia"); window.location = "index.php?ruta=gestion_calendario&anio=' . $anio . '&mes=' . $mes . '";</script>';
            exit;
        }

        $count = CalendarioModel::mdlEliminarDias($ids);

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log("eliminar", "calendario_escolar", null, "Elimino {$count} dia(s) configurados en bloque");

        echo '<script>alert("' . $count . ' dia(s) eliminados"); window.location = "index.php?ruta=gestion_calendario&anio=' . $anio . '&mes=' . $mes . '";</script>';
        exit;
    }

    static public function ctrGuardarTarea()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["guardar_tarea"]) || $_POST["guardar_tarea"] !== "1") return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=gestion_calendario";</script>';
            exit;
        }

        $id_calendario = (int)$_POST["id_calendario"];
        $titulo = trim($_POST["titulo_tarea"]);
        $descripcion = trim($_POST["desc_tarea"] ?? "");
        $hora = $_POST["hora_tarea"] ?: null;
        $creado_por = $_SESSION["id"] ?? null;

        if (!$titulo) {
            echo '<script>alert("El titulo de la tarea es obligatorio"); window.history.back();</script>';
            exit;
        }

        CalendarioModel::mdlGuardarTarea($id_calendario, $titulo, $descripcion, $hora, $creado_por);

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log("crear", "calendario_tareas", null, "Creo tarea: {$titulo}");

        echo '<script>window.location = "index.php?ruta=gestion_calendario&anio=' . ((int)$_POST["anio"]) . '&mes=' . ((int)$_POST["mes"]) . '";</script>';
        exit;
    }

    static public function ctrEditarTarea()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["editar_tarea"]) || $_POST["editar_tarea"] !== "1") return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=gestion_calendario";</script>';
            exit;
        }

        $id = (int)$_POST["id_tarea"];
        $titulo = trim($_POST["titulo_tarea"]);
        $descripcion = trim($_POST["desc_tarea"] ?? "");
        $hora = $_POST["hora_tarea"] ?: null;

        if (!$titulo) {
            echo '<script>alert("El titulo de la tarea es obligatorio"); window.history.back();</script>';
            exit;
        }

        CalendarioModel::mdlActualizarTarea($id, $titulo, $descripcion, $hora);

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log("editar", "calendario_tareas", $id, "Edito tarea: {$titulo}");

        echo '<script>window.location = "index.php?ruta=gestion_calendario&anio=' . ((int)$_POST["anio"]) . '&mes=' . ((int)$_POST["mes"]) . '";</script>';
        exit;
    }

    static public function ctrEliminarTarea()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["eliminar_tarea"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=gestion_calendario";</script>';
            exit;
        }

        $id = (int)$_POST["id"];
        $anio = (int)$_POST["anio"];
        $mes = (int)$_POST["mes"];

        CalendarioModel::mdlEliminarTarea($id);

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log("eliminar", "calendario_tareas", $id, "Elimino tarea");

        echo '<script>window.location = "index.php?ruta=gestion_calendario&anio=' . $anio . '&mes=' . $mes . '";</script>';
        exit;
    }
}
