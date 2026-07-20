<?php
if (!function_exists('utf8_decode')) {
    function utf8_decode($s) { return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8'); }
}
require_once "models/JustificacionModel.php";

class JustificacionController
{
    static public function ctrListar()
    {
        return JustificacionModel::mdlListarJustificaciones();
    }

    static public function ctrListarFiltro($fecha_inicio = null, $fecha_fin = null, $id_personal = null)
    {
        return JustificacionModel::mdlListarJustificacionesFiltro($fecha_inicio, $fecha_fin, $id_personal);
    }

    static public function ctrContarPendientes()
    {
        return JustificacionModel::mdlContarPendientes();
    }

    static public function ctrListarPersonalActivo()
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT p.id, p.nombre, p.apellido, p.documento_identidad, c.nombre as nombre_cargo
            FROM personal p
            LEFT JOIN cargos c ON p.id_cargo = c.id
            WHERE p.id_estado = 1 AND p.id != 1 AND (p.id_rol != 4 OR p.id_rol IS NULL)
            ORDER BY p.apellido ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function ctrCrear()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") return;
        if (!isset($_POST["id_personal"], $_POST["fecha_justificacion"], $_POST["motivo"], $_POST["tipo_justificacion"])) {
            echo '<script>alert("Faltan datos"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }
        require_once "helpers/RbacHelper.php";
        if (!RbacHelper::soloAdminODirectorOSecretaria()) {
            RbacHelper::denegar();
            return;
        }

        $id_personal = (int)$_POST["id_personal"];
        $fecha = $_POST["fecha_justificacion"];

        if (JustificacionModel::mdlVerificarDuplicado($id_personal, $fecha)) {
            echo '<script>alert("Ya existe una justificación para este empleado en la fecha seleccionada"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }

        $doc_adjunto = null;
        if (isset($_FILES["documento_adjunto"])) {
            $dir = __DIR__ . "/../adjuntos_justificaciones";
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $nombres = [];
            $files = $_FILES["documento_adjunto"];
            $total = is_array($files["name"]) ? count($files["name"]) : ($files["error"] === UPLOAD_ERR_OK ? 1 : 0);
            for ($i = 0; $i < $total; $i++) {
                $name = is_array($files["name"]) ? $files["name"][$i] : $files["name"];
                $error = is_array($files["error"]) ? $files["error"][$i] : $files["error"];
                $tmp  = is_array($files["tmp_name"]) ? $files["tmp_name"][$i] : $files["tmp_name"];
                if ($error !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $mime = mime_content_type($tmp);
                $ext_validas = ['jpg', 'jpeg', 'png'];
                $mime_validos = ['image/jpeg', 'image/png'];
                if (!in_array($ext, $ext_validas) || !in_array($mime, $mime_validos)) continue;
                $filename = "just_" . uniqid() . "." . $ext;
                move_uploaded_file($tmp, "$dir/$filename");
                $nombres[] = $filename;
            }
            if (!empty($nombres)) $doc_adjunto = implode(",", $nombres);
        }

        $datos = [
            "id_personal" => $id_personal,
            "fecha" => $fecha,
            "motivo" => trim($_POST["motivo"]),
            "tipo" => $_POST["tipo_justificacion"],
            "documento_adjunto" => $doc_adjunto
        ];

        $resultado = JustificacionModel::mdlCrearJustificacion($datos);
        if ($resultado) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('crear', 'justificacion', $resultado, 'Creó justificación para personal ID: ' . $datos["id_personal"] . ' - ' . $datos["motivo"]);
            echo '<script>alert("Justificación creada correctamente"); window.location = "index.php?ruta=justificaciones";</script>';
        } else {
            echo '<script>alert("Error al crear justificación"); window.location = "index.php?ruta=justificaciones";</script>';
        }
    }

    static public function ctrAprobar()
    {
        if (!isset($_GET["id"], $_GET["action"]) || $_GET["action"] !== "aprobar") return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_GET['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }
        require_once "helpers/RbacHelper.php";
        if (!RbacHelper::soloAdminODirector()) {
            RbacHelper::denegar();
            return;
        }

        $id = (int)$_GET["id"];
        $resultado = JustificacionModel::mdlAprobarJustificacion($id, (int)$_SESSION["id"]);

        if ($resultado) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('aprobar', 'justificacion', $id, 'Aprobó justificación ID: ' . $id);
            echo '<script>alert("Justificación aprobada"); window.location = "index.php?ruta=justificaciones";</script>';
        } else {
            echo '<script>alert("Error o ya fue procesada"); window.location = "index.php?ruta=justificaciones";</script>';
        }
    }

    static public function ctrRechazar()
    {
        if (!isset($_GET["id"], $_GET["action"]) || $_GET["action"] !== "rechazar") return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_GET['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }
        require_once "helpers/RbacHelper.php";
        if (!RbacHelper::soloAdminODirector()) {
            RbacHelper::denegar();
            return;
        }

        $id = (int)$_GET["id"];
        $resultado = JustificacionModel::mdlRechazarJustificacion($id);

        if ($resultado) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('rechazar', 'justificacion', $id, 'Rechazó justificación ID: ' . $id);
            echo '<script>alert("Justificación rechazada"); window.location = "index.php?ruta=justificaciones";</script>';
        } else {
            echo '<script>alert("Error o ya fue procesada"); window.location = "index.php?ruta=justificaciones";</script>';
        }
    }

    static public function ctrEliminar()
    {
        if (!isset($_GET["id"], $_GET["action"]) || $_GET["action"] !== "eliminar") return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_GET['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }
        require_once "helpers/RbacHelper.php";
        if (!RbacHelper::soloAdmin()) {
            RbacHelper::denegar();
            return;
        }

        $id = (int)$_GET["id"];
        $resultado = JustificacionModel::mdlEliminarJustificacion($id);

        if ($resultado) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log('eliminar', 'justificacion', $id, 'Eliminó justificación ID: ' . $id);
            echo '<script>alert("Justificación eliminada"); window.location = "index.php?ruta=justificaciones";</script>';
        } else {
            echo '<script>alert("Error al eliminar"); window.location = "index.php?ruta=justificaciones";</script>';
        }
    }

    static public function ctrListarPorPersonal($id_personal)
    {
        return JustificacionModel::mdlListarJustificacionesFiltro(null, null, $id_personal);
    }

    static public function ctrCrearPersonal()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") return;
        if (!isset($_POST["fecha_justificacion"], $_POST["motivo"], $_POST["tipo_justificacion"])) {
            echo '<script>alert("Faltan datos"); window.location = "index.php?ruta=mis_justificaciones";</script>';
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=mis_justificaciones";</script>';
            return;
        }
        if (!isset($_SESSION["id"])) {
            echo '<script>alert("Debe iniciar sesion"); window.location = "index.php?ruta=login";</script>';
            return;
        }

        $id_personal = (int)$_SESSION["id"];
        $fecha = $_POST["fecha_justificacion"];

        if (JustificacionModel::mdlVerificarDuplicado($id_personal, $fecha)) {
            echo '<script>alert("Ya existe una justificacion para esta fecha"); window.location = "index.php?ruta=mis_justificaciones";</script>';
            return;
        }

        $doc_adjunto = null;
        if (isset($_FILES["documento_adjunto"])) {
            $dir = __DIR__ . "/../adjuntos_justificaciones";
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $nombres = [];
            $files = $_FILES["documento_adjunto"];
            $total = is_array($files["name"]) ? count($files["name"]) : ($files["error"] === UPLOAD_ERR_OK ? 1 : 0);
            for ($i = 0; $i < $total; $i++) {
                $name = is_array($files["name"]) ? $files["name"][$i] : $files["name"];
                $error = is_array($files["error"]) ? $files["error"][$i] : $files["error"];
                $tmp  = is_array($files["tmp_name"]) ? $files["tmp_name"][$i] : $files["tmp_name"];
                if ($error !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $mime = mime_content_type($tmp);
                $ext_validas = ['jpg', 'jpeg', 'png'];
                $mime_validos = ['image/jpeg', 'image/png'];
                if (!in_array($ext, $ext_validas) || !in_array($mime, $mime_validos)) continue;
                $filename = "just_" . uniqid() . "." . $ext;
                move_uploaded_file($tmp, "$dir/$filename");
                $nombres[] = $filename;
            }
            if (!empty($nombres)) $doc_adjunto = implode(",", $nombres);
        }

        $datos = [
            "id_personal" => $id_personal,
            "fecha" => $fecha,
            "motivo" => trim($_POST["motivo"]),
            "tipo" => $_POST["tipo_justificacion"],
            "documento_adjunto" => $doc_adjunto
        ];

        $resultado = JustificacionModel::mdlCrearJustificacion($datos);
        if ($resultado) {
            require_once "helpers/AuditoriaHelper.php";
            AuditoriaHelper::log("crear", "justificacion", $resultado, "Creo justificacion propia: " . $datos["motivo"]);
            echo '<script>alert("Justificacion creada correctamente"); window.location = "index.php?ruta=mis_justificaciones";</script>';
        } else {
            echo '<script>alert("Error al crear justificacion"); window.location = "index.php?ruta=mis_justificaciones";</script>';
        }
    }

    static public function ctrExportarPDF()
    {
        if (!isset($_GET["id"])) return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/RbacHelper.php";

        $id = (int)$_GET["id"];
        $justificacion = JustificacionModel::mdlObtenerJustificacion($id);
        if (!$justificacion) {
            echo '<script>alert("Justificación no encontrada"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }

        $esAdminODirectorOSecretaria = RbacHelper::soloAdminODirectorOSecretaria();
        $esPropietario = isset($_SESSION["id"]) && (int)$_SESSION["id"] === (int)$justificacion["id_personal"];
        if (!$esAdminODirectorOSecretaria && !$esPropietario) {
            RbacHelper::denegar();
            return;
        }

        require_once "lib/fpdf.php";

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetMargins(20, 20, 20);
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, utf8_decode('JUSTIFICACIÓN DE INASISTENCIA'), 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetDrawColor(2, 132, 199);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(6);

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(50, 7, utf8_decode('Fecha de creación:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, utf8_decode(date('d/m/Y', strtotime($justificacion["created_at"]))), 0, 1, 'L');
        $pdf->Ln(4);

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(50, 7, utf8_decode('Empleado:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, utf8_decode($justificacion["personal_nombre"] . ' ' . $justificacion["personal_apellido"]), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(50, 7, utf8_decode('Documento de Identidad:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, utf8_decode($justificacion["documento_identidad"]), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(50, 7, utf8_decode('Cargo:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, utf8_decode($justificacion["nombre_cargo"] ?? 'No asignado'), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(50, 7, utf8_decode('Fecha de inasistencia:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, utf8_decode(date('d/m/Y', strtotime($justificacion["fecha"]))), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(50, 7, utf8_decode('Tipo de justificación:'), 0, 0, 'L');
        $tipos = ['medico' => 'Médico', 'personal' => 'Personal', 'permiso' => 'Permiso', 'otro' => 'Otro'];
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, utf8_decode($tipos[$justificacion["tipo"]] ?? $justificacion["tipo"]), 0, 1, 'L');

        $pdf->Ln(6);

        $pdf->SetFont('Arial', '', 11);
        $cuerpo = sprintf(
            "Yo, %s %s, identificado(a) con Documento de Identidad No. %s, quien labora en el cargo de %s, por medio de la presente me permito JUSTIFICAR mi inasistencia ocurrida el día %s, por motivos de tipo %s.",
            $justificacion["personal_nombre"],
            $justificacion["personal_apellido"],
            $justificacion["documento_identidad"],
            $justificacion["nombre_cargo"] ?? 'No asignado',
            date('d/m/Y', strtotime($justificacion["fecha"])),
            strtolower($tipos[$justificacion["tipo"]] ?? $justificacion["tipo"])
        );
        $pdf->MultiCell(0, 6, utf8_decode($cuerpo));
        $pdf->Ln(4);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, utf8_decode('Motivo:'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 11);
        $pdf->MultiCell(0, 6, utf8_decode($justificacion["motivo"]));
        $pdf->Ln(4);

        $estado = $justificacion["aprobado_por"] === null ? 'PENDIENTE' : ($justificacion["aprobado_por"] == 0 ? 'RECHAZADA' : 'APROBADA');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, utf8_decode('Estado: ' . $estado), 0, 1, 'L');
        if ($justificacion["aprobado_nombre"]) {
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, utf8_decode('Aprobada por: ' . $justificacion["aprobado_nombre"] . ' ' . $justificacion["aprobado_apellido"]), 0, 1, 'L');
        }
        $pdf->Ln(6);

        $pdf->Ln(10);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);

        $y_firma1 = $pdf->GetY();
        $pdf->Line(30, $y_firma1, 80, $y_firma1);
        $pdf->Line(120, $y_firma1, 170, $y_firma1);
        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(80, 6, utf8_decode('Firma del Empleado'), 0, 0, 'C');
        $pdf->Cell(40, 6, '', 0, 0, 'C');
        $pdf->Cell(80, 6, utf8_decode('Firma del Encargado / Autorizante'), 0, 1, 'C');

        $y_firma2 = $pdf->GetY() + 10;
        $pdf->Line(30, $y_firma2, 80, $y_firma2);
        $pdf->Line(120, $y_firma2, 170, $y_firma2);
        $pdf->SetXY(30, $y_firma2 + 5);
        $pdf->Cell(50, 6, utf8_decode('Firma del Director'), 0, 0, 'C');
        $pdf->SetXY(120, $y_firma2 + 5);
        $pdf->Cell(50, 6, utf8_decode('Firma del Supervisor / RR.HH.'), 0, 1, 'C');

        $archivos = $justificacion["documento_adjunto"]
            ? array_filter(array_map("trim", explode(",", $justificacion["documento_adjunto"])))
            : [];

        $dir_adjuntos = __DIR__ . "/../adjuntos_justificaciones";
        $imagenes_validas = [];
        foreach ($archivos as $a) {
            $ruta = "$dir_adjuntos/$a";
            if (file_exists($ruta)) {
                $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $imagenes_validas[] = $ruta;
                }
            }
        }

        if (!empty($imagenes_validas)) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, utf8_decode('Documentos Adjuntos'), 0, 1, 'C');
            $pdf->Ln(4);

            $ancho_max = 80;
            $x_imagen = 20;
            foreach ($imagenes_validas as $idx => $ruta_img) {
                if ($idx > 0) {
                    if ($pdf->GetY() > 220) {
                        $pdf->AddPage();
                    } else {
                        $pdf->Ln(8);
                    }
                }
                $pdf->SetFont('Arial', '', 9);
                $pdf->Cell(0, 5, utf8_decode('Imagen ' . ($idx + 1) . ':'), 0, 1, 'L');
                $img_final = $ruta_img;
                if ($ext === 'png') {
                    $fp = fopen($ruta_img, 'rb');
                    $header = fread($fp, 8);
                    fclose($fp);
                    if ($header === "\x89PNG\r\n\x1a\n") {
                        $fp = fopen($ruta_img, 'rb');
                        fread($fp, 25);
                        $interlace = ord(fread($fp, 1));
                        fclose($fp);
                        if ($interlace === 1) {
                            $im = imagecreatefrompng($ruta_img);
                            if ($im) {
                                $tmp = tempnam(sys_get_temp_dir(), 'fpdf_noint_') . '.png';
                                imagepng($im, $tmp, 9, PNG_NO_FILTER);
                                imagedestroy($im);
                                $img_final = $tmp;
                            }
                        }
                    }
                }
                $pdf->Image($img_final, $x_imagen, null, $ancho_max);
            }
        }

        $pdf->Output('D', 'justificacion_' . $id . '_' . date('Ymd') . '.pdf');
        exit;
    }

    static public function ctrImportar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") return;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_once "helpers/CsrfHelper.php";
        if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
            echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }
        require_once "helpers/RbacHelper.php";
        if (!RbacHelper::soloAdminODirectorOSecretaria()) {
            RbacHelper::denegar();
            return;
        }

        if (!isset($_FILES["archivo_importar"]) || $_FILES["archivo_importar"]["error"] !== UPLOAD_ERR_OK) {
            echo '<script>alert("Error al subir el archivo"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }

        $tmp = $_FILES["archivo_importar"]["tmp_name"];
        $handle = fopen($tmp, "r");
        if (!$handle) {
            echo '<script>alert("No se pudo leer el archivo"); window.location = "index.php?ruta=justificaciones";</script>';
            return;
        }

        $contador = 0;
        $errores = 0;
        $linea = 0;
        $tipos_validos = ['medico', 'personal', 'permiso', 'otro'];

        while (($fila = fgetcsv($handle)) !== false) {
            $linea++;
            if ($linea === 1) continue;

            if (count($fila) < 3) { $errores++; continue; }

            $id_personal = (int)trim($fila[0]);
            $fecha = trim($fila[1]);
            $motivo = trim($fila[2]);
            $tipo = isset($fila[3]) ? trim($fila[3]) : 'otro';

            if (!$id_personal || !$fecha || !$motivo) { $errores++; continue; }
            if (!in_array($tipo, $tipos_validos)) $tipo = 'otro';

            $stmt = Conexion::conectar()->prepare("SELECT id FROM personal WHERE id = :id AND id_estado = 1 AND id != 1");
            $stmt->bindParam(":id", $id_personal, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) { $errores++; continue; }

            if (JustificacionModel::mdlVerificarDuplicado($id_personal, $fecha)) { $errores++; continue; }

            $datos = [
                "id_personal" => $id_personal,
                "fecha" => $fecha,
                "motivo" => $motivo,
                "tipo" => $tipo,
                "documento_adjunto" => null
            ];

            if (JustificacionModel::mdlCrearJustificacion($datos)) {
                $contador++;
            } else {
                $errores++;
            }
        }

        fclose($handle);
        $mensaje = "Importación completada: $contador creadas, $errores con errores";

        require_once "helpers/AuditoriaHelper.php";
        AuditoriaHelper::log('importar', 'justificacion', null, $mensaje);

        echo '<script>alert("' . $mensaje . '"); window.location = "index.php?ruta=justificaciones";</script>';
    }
}
