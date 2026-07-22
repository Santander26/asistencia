<?php
require_once __DIR__ . "/../lib/fpdf.php";

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, utf8_decode('Reporte de Asistencia - SIBCA'), 0, 1, 'C');
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, utf8_decode('Generado: ' . date('d/m/Y H:i:s')), 0, 1, 'C');
        $this->Ln(4);
    }

    function Footer()
    {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 7);
        $this->Cell(0, 8, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function TableHeader()
    {
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor(2, 132, 199);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(8, 7, '#', 1, 0, 'C', true);
        $this->Cell(48, 7, utf8_decode('Empleado'), 1, 0, 'C', true);
        $this->Cell(22, 7, utf8_decode('Documento'), 1, 0, 'C', true);
        $this->Cell(30, 7, utf8_decode('Cargo'), 1, 0, 'C', true);
        $this->Cell(22, 7, utf8_decode('Fecha'), 1, 0, 'C', true);
        $this->Cell(16, 7, utf8_decode('Entrada'), 1, 0, 'C', true);
        $this->Cell(16, 7, utf8_decode('Salida'), 1, 0, 'C', true);
        $this->Cell(14, 7, utf8_decode('Horas'), 1, 0, 'C', true);
        $this->Cell(18, 7, utf8_decode('Estado'), 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
    }
}

class ControladorPlantilla
{
    static private function verificarTimeout()
    {
        if (isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] == "ok") {
            require_once "helpers/AccessTimeHelper.php";
            $timeout_min = AccessTimeHelper::getConfig()['tiempo_inactividad'];
            $timeout_seg = max(60, $timeout_min * 60);
            $inactivo = time() - ($_SESSION["ultima_actividad"] ?? 0);
            if ($inactivo > $timeout_seg) {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION["timeout"] = true;
                header('Location: index.php?ruta=login');
                exit;
            }
            $_SESSION["ultima_actividad"] = time();
        }
    }

    static private function restaurarSesionPorCookie($token)
    {
        require_once "models/SesionModel.php";
        $sesion = SesionModel::mdlVerificarToken($token);
        if (!$sesion) return;

        require_once "models/UsuarioModel.php";
        $usuario = UsuarioModel::mdlMostrarUsuario("personal", "id", $sesion["id_usuario"]);
        if (!$usuario || $usuario["id_estado"] != 1) {
            SesionModel::mdlEliminarToken($token);
            setcookie("recordar_token", "", time() - 3600, "/");
            return;
        }

        $_SESSION["iniciarSesion"] = "ok";
        $_SESSION["id"] = $usuario["id"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["apellido"] = $usuario["apellido"];
        $_SESSION["rol"] = $usuario["rol_nombre"];
        $_SESSION["id_rol"] = $usuario["id_rol"];
        $_SESSION["ultima_actividad"] = time();
        $_SESSION["recordar"] = true;

        SesionModel::mdlActualizarActividad($sesion["id"]);
    }

    static public function ctrPlantilla()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION["iniciarSesion"]) && isset($_COOKIE["recordar_token"])) {
            self::restaurarSesionPorCookie($_COOKIE["recordar_token"]);
        }

        self::verificarTimeout();

        if (isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] == "ok") {
            require_once "helpers/AccessTimeHelper.php";
            AccessTimeHelper::denegar();
        }

        if (isset($_GET['ruta']) && $_GET['ruta'] === 'asignar_rol_ajax') {
            require_once "helpers/RbacHelper.php";
            if (!RbacHelper::soloAdmin()) {
                echo json_encode(["success" => false, "message" => "Acceso denegado"]);
                exit;
            }
            require_once "controllers/RolController.php";
            RolController::ctrAsignarRol();
            return;
        }

        // API para actualizaciones en tiempo real
        if (isset($_GET['ruta']) && $_GET['ruta'] === 'api_realtime') {
            header('Content-Type: application/json; charset=utf-8');
            if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
                echo json_encode(["error" => "Sesión expirada"]);
                return;
            }
            require_once "controllers/AsistenciaController.php";
            $type = $_GET["type"] ?? "dashboard";
            $result = [];
            switch ($type) {
                case "dashboard":
                    $result = AsistenciaController::ctrApiDashboard();
                    break;
                case "reportes":
                    $fecha_desde = $_GET["fecha_desde"] ?? date('Y-m-01');
                    $fecha_hasta = $_GET["fecha_hasta"] ?? date('Y-m-d');
                    $id_cargo = $_GET["id_cargo"] ?? null;
                    if ($id_cargo === "" || $id_cargo === "0") $id_cargo = null;
                    $result = AsistenciaController::ctrApiReportes($fecha_desde, $fecha_hasta, $id_cargo);
                    break;
                case "personal":
                    require_once "models/PersonalModel.php";
                    $result = ["tabla" => PersonalModel::mdlListarPersonal()];
                    break;
                case "historial_bajas":
                    $fecha = $_GET["fecha"] ?? date('Y-m-d');
                    $result = AsistenciaController::ctrApiHistorialBajas($fecha);
                    break;
            }
            echo json_encode($result);
            return;
        }

        // API pública para marcación por huella (ESP32)
        if (isset($_GET['ruta']) && $_GET['ruta'] === 'api_marcar_huella') {
            header('Content-Type: application/json; charset=utf-8');
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                echo json_encode(["success" => false, "mensaje" => "Método no permitido"]);
                return;
            }
            require_once "controllers/AsistenciaController.php";
            echo json_encode(AsistenciaController::ctrMarcarPorHuella());
            return;
        }

        // API para enrolamiento y sincronización de huellas (ESP32)
        if (isset($_GET['ruta']) && strpos($_GET['ruta'], 'api_huella_') === 0) {
            header('Content-Type: application/json; charset=utf-8');
            require_once "controllers/HuellaController.php";
            $action = str_replace('api_huella_', '', $_GET['ruta']);
            switch ($action) {
                case 'solicitar':
                    HuellaController::ctrSolicitarEnrolamiento();
                    break;
                case 'consultar':
                    HuellaController::ctrConsultarEnrolamiento();
                    break;
                case 'confirmar':
                    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                        echo json_encode(["success" => false, "mensaje" => "Método no permitido"]);
                        return;
                    }
                    HuellaController::ctrConfirmarEnrolamiento();
                    break;
                case 'mapeo':
                    HuellaController::ctrObtenerMapeo();
                    break;
                case 'sync':
                    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                        echo json_encode(["success" => false, "mensaje" => "Método no permitido"]);
                        return;
                    }
                    HuellaController::ctrSyncOffline();
                    break;
                case 'heartbeat':
                    HuellaController::ctrHeartbeat();
                    break;
            }
            return;
        }

        if (isset($_GET['ruta']) && $_GET['ruta'] === 'importar_justificaciones') {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") return;
            require_once "helpers/RbacHelper.php";
            if (!RbacHelper::soloAdminODirectorOSecretaria()) {
                header('Location: index.php?ruta=inicio');
                exit;
            }
            require_once "controllers/JustificacionController.php";
            JustificacionController::ctrImportar();
            return;
        }

        if (isset($_GET['ruta']) && $_GET['ruta'] === 'exportar_justificacion_pdf') {
            if (!isset($_GET["id"])) return;
            require_once "controllers/JustificacionController.php";
            JustificacionController::ctrExportarPDF();
            return;
        }

        if (isset($_GET['ruta']) && $_GET['ruta'] === 'exportar_reporte') {
            if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
                header('Location: index.php?ruta=login');
                exit;
            }
            require_once "helpers/RbacHelper.php";
            if (!RbacHelper::soloAdminODirectorOSecretaria()) {
                header('Location: index.php?ruta=inicio');
                exit;
            }
            require_once "controllers/AsistenciaController.php";
            require_once "helpers/AuditoriaHelper.php";
            $formato = $_GET["formato"] ?? "xls";
            AuditoriaHelper::log('exportar', 'reporte', null, 'Exportó reporte en formato ' . strtoupper($formato));
            $fecha_inicio = $_GET["fecha_inicio"] ?? date('Y-m-01');
            $fecha_fin = $_GET["fecha_fin"] ?? date('Y-m-d');
            $id_cargo = $_GET["id_cargo"] ?? "";
            if ($id_cargo === "" || $id_cargo === "0") $id_cargo = null;
            $_GET["fecha_inicio"] = $fecha_inicio;
            $_GET["fecha_fin"] = $fecha_fin;
            $_GET["id_cargo"] = $id_cargo;
            $asistencias = AsistenciaController::ctrListarAsistenciasReporte();
            $formato = $_GET["formato"] ?? "xls";

            $dir = __DIR__ . "/../backups_reportes";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $timestamp = date("Y-m-d_H-i-s");
            $ext = $formato === "csv" ? "csv" : ($formato === "pdf" ? "pdf" : "xls");
            $filename = "reporte_{$fecha_inicio}_{$fecha_fin}_{$timestamp}.{$ext}";
            $path = "$dir/$filename";

            if ($formato === "pdf") {
                $pdf = new PDF('L', 'mm', 'A4');
                $pdf->AliasNbPages();
                $pdf->SetMargins(8, 8, 8);
                $pdf->AddPage();
                $pdf->SetFont('Arial', '', 7);

                $pdf->TableHeader();

                $fill = false;
                $i = 1;
                foreach ($asistencias as $a) {
                    $nombre = utf8_decode($a["nombre"] . ' ' . $a["apellido"]);
                    $cargo = utf8_decode($a["nombre_cargo"] ?? '');
                    $horas = $a["horas_trabajadas"]
                        ? sprintf('%02d:%02d', floor($a["horas_trabajadas"]), round(($a["horas_trabajadas"] - floor($a["horas_trabajadas"])) * 60))
                        : '--';
                    $entrada = $a["hora_entrada"] ? substr($a["hora_entrada"], 0, 5) : '--:--';
                    $salida = $a["hora_salida"] ? substr($a["hora_salida"], 0, 5) : '--:--';

                    if ($fill) $pdf->SetFillColor(235, 245, 255);
                    else $pdf->SetFillColor(255, 255, 255);

                    $pdf->Cell(8, 6, $i++, 1, 0, 'C', $fill);
                    $pdf->Cell(48, 6, $nombre, 1, 0, 'L', $fill);
                    $pdf->Cell(22, 6, $a["documento_identidad"], 1, 0, 'C', $fill);
                    $pdf->Cell(30, 6, $cargo, 1, 0, 'L', $fill);
                    $pdf->Cell(22, 6, $a["fecha"], 1, 0, 'C', $fill);
                    $pdf->Cell(16, 6, $entrada, 1, 0, 'C', $fill);
                    $pdf->Cell(16, 6, $salida, 1, 0, 'C', $fill);
                    $pdf->Cell(14, 6, $horas, 1, 0, 'C', $fill);
                    $pdf->Cell(18, 6, utf8_decode($a["estado_entrada"]), 1, 1, 'C', $fill);
                    $fill = !$fill;
                }

                $pdf->Output('F', $path);
                header("Content-Type: application/pdf");
                header("Content-Disposition: attachment; filename=\"$filename\"");
                readfile($path);
                exit;
            } else if ($formato === "csv") {
                $fp = fopen($path, "w");
                fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($fp, ["#","Empleado","Documento","Cargo","Fecha","Entrada","Salida","Horas","Estado"]);
                $i = 1;
                foreach ($asistencias as $a) {
                    fputcsv($fp, [
                        $i++,
                        $a["nombre"] . " " . $a["apellido"],
                        $a["documento_identidad"],
                        $a["nombre_cargo"] ?? "",
                        $a["fecha"],
                        $a["hora_entrada"] ? substr($a["hora_entrada"], 0, 5) : "--:--",
                        $a["hora_salida"] ? substr($a["hora_salida"], 0, 5) : "--:--",
                        $a["horas_trabajadas"] ? number_format($a["horas_trabajadas"], 2) . " h" : "--",
                        $a["estado_entrada"]
                    ]);
                }
                fclose($fp);
                header("Content-Type: text/csv; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"$filename\"");
                readfile($path);
                exit;
            } else {
                $html = '<html><meta charset="UTF-8"><table>';
                $html .= '<tr><th>#</th><th>Empleado</th><th>Documento</th><th>Cargo</th><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Horas</th><th>Estado</th></tr>';
                $i = 1;
                foreach ($asistencias as $a) {
                    $html .= '<tr>';
                    $html .= '<td>' . $i++ . '</td>';
                    $html .= '<td>' . htmlspecialchars($a["nombre"] . " " . $a["apellido"], ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td>' . htmlspecialchars($a["documento_identidad"], ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td>' . htmlspecialchars($a["nombre_cargo"] ?? "", ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td>' . $a["fecha"] . '</td>';
                    $html .= '<td>' . ($a["hora_entrada"] ? substr($a["hora_entrada"], 0, 5) : "--:--") . '</td>';
                    $html .= '<td>' . ($a["hora_salida"] ? substr($a["hora_salida"], 0, 5) : "--:--") . '</td>';
                    $html .= '<td>' . ($a["horas_trabajadas"] ? number_format($a["horas_trabajadas"], 2) . " h" : "--") . '</td>';
                    $html .= '<td>' . $a["estado_entrada"] . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</table></html>';
                file_put_contents($path, $html);
                header("Content-Type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"$filename\"");
                readfile($path);
                exit;
            }
        }

        if (isset($_GET["logout"]) && $_GET["logout"] == "true") {
            require_once "controllers/LoginController.php";
            LoginController::ctrLogout();
            return;
        }

        $rutaActual = isset($_GET["ruta"]) ? $_GET["ruta"] : "inicio";

        if ($rutaActual === "error_404") {
            include "views/modules/error_404.php";
            return;
        }
        if ($rutaActual === "error_403") {
            include "views/modules/error_403.php";
            return;
        }
        if ($rutaActual === "olvide_password") {
            include "views/modules/olvide_password.php";
            return;
        }
        if ($rutaActual === "reset_password") {
            if (!isset($_GET["token"])) {
                header('Location: index.php?ruta=login');
                exit;
            }
            require_once "models/PasswordResetModel.php";
            $reset_user = PasswordResetModel::mdlVerificarToken($_GET["token"]);
            if (!$reset_user) {
                header('Location: index.php?ruta=olvide_password&error=El enlace de restablecimiento no es válido o ha expirado');
                exit;
            }
            include "views/modules/reset_password.php";
            return;
        }
        if ($rutaActual === "fuera_horario") {
            include "views/modules/fuera_horario.php";
            return;
        }

        if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
            if ($rutaActual !== "login") {
                header('Location: index.php?ruta=login');
                exit;
            }
        }

        if ($rutaActual === "login") {
            include "views/modules/login.php";
        }
        else {
            include "views/layouts/header.php";

            echo '<div id="dashboard-view" class="view-container layout-dashboard" style="display:flex;">';
            include "views/layouts/menu.php";
            echo '<div class="main-wrapper">';
            include "views/layouts/topbar.php";

            require_once "helpers/RbacHelper.php";
            $id_rol = RbacHelper::getRolId();
            $esAdmin = ($id_rol === 4);
            $esDirector = ($id_rol === 1);
            $esSecretaria = ($id_rol === 2);
            $esPersonal = ($id_rol === 3);
            $adminODirector = $esAdmin || $esDirector;
            $adminODirectorOSecretaria = $esAdmin || $esDirector || $esSecretaria;

            if ($rutaActual == "inicio") {
                if ($adminODirectorOSecretaria) include "views/modules/dashboard.php";
                else RbacHelper::denegar('perfil');
            }
            else if ($rutaActual == "asistencia") {
                if ($adminODirector) include "views/modules/asistencia.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "turnos") {
                if ($esAdmin || $esDirector) include "views/modules/turnos.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "configuracion") {
                if ($adminODirectorOSecretaria) include "views/modules/configuracion.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "personal") {
                if ($adminODirectorOSecretaria) include "views/modules/personal.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "cargos") {
                if ($esAdmin || $esDirector) include "views/modules/cargos.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "departamentos") {
                if ($esAdmin || $esDirector) include "views/modules/departamentos.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "reportes") {
                if ($adminODirectorOSecretaria) include "views/modules/reportes.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "asignar_rol") {
                if ($esAdmin) include "views/modules/asignar_rol.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "auditoria") {
                if ($esAdmin || $esDirector) include "views/modules/auditoria.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "justificaciones") {
                if ($adminODirectorOSecretaria) include "views/modules/justificaciones.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "gestion_calendario") {
                if ($adminODirectorOSecretaria) include "views/modules/calendario_escolar.php";
                else if ($esPersonal) include "views/modules/calendario_personal.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "calendario_personal") {
                if ($esPersonal) include "views/modules/calendario_personal.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "mis_justificaciones") {
                if ($esPersonal) include "views/modules/mis_justificaciones.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "perfil") {
                include "views/modules/perfil.php";
            }
            else if ($rutaActual == "historial_bajas") {
                if ($esAdmin || $esDirector) include "views/modules/historial_bajas.php";
                else RbacHelper::denegar();
            }
            else if ($rutaActual == "mis_asistencias") {
                include "views/modules/mis_asistencias.php";
            }
            else {
                header('Location: index.php?ruta=error_404');
                exit;
            }

            echo '</div>';
            echo '</div>';
        }

        include "views/layouts/footer.php";
    }
}
