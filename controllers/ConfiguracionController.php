<?php

class ConfiguracionController
{

    static private function getDbCreds()
    {
        return [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'user' => getenv('DB_USER') ?: 'root',
            'pass' => getenv('DB_PASSWORD') ?: '',
            'name' => getenv('DB_NAME') ?: 'asistencia_db',
        ];
    }

    static public function ctrCrearBackup()
    {
        if (isset($_POST["btnCrearBackup"])) {
            require_once "helpers/CsrfHelper.php";
            if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
                echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=configuracion";</script>';
                return;
            }

            $db = self::getDbCreds();
            $fecha = date("Y-m-d_H-i-s");
            $nombreArchivo = "backup_" . $db['name'] . "_" . $fecha . ".sql";
            $rutaDirectorio = $_SERVER["DOCUMENT_ROOT"] . "/backups/";
            if (!is_dir($rutaDirectorio)) mkdir($rutaDirectorio, 0755, true);
            $rutaArchivo = $rutaDirectorio . $nombreArchivo;

            require_once "config/Rutas.php";
            $rutas = Rutas::getRutas();
            $mysqldumpPath = $rutas['mysqldump'];

            $passOption = ($db['pass'] == "") ? "" : "-p" . escapeshellarg($db['pass']);
            $comando = "$mysqldumpPath -h " . escapeshellarg($db['host']) . " -u " . escapeshellarg($db['user']) . " $passOption " . escapeshellarg($db['name']) . " > " . escapeshellarg($rutaArchivo);

            $salida = null;
            $codigoSalida = null;

            exec($comando, $salida, $codigoSalida);

            if ($codigoSalida === 0) {
                if (file_exists($rutaArchivo)) {
                    require_once "helpers/AuditoriaHelper.php";
                    AuditoriaHelper::log('backup', 'configuracion', null, 'Creó backup: ' . $nombreArchivo);
                    echo '<script>
                        alert("¡Respaldo Exitoso! \n\nEl archivo ' . $nombreArchivo . ' se ha generado correctamente en el servidor.");
                        window.location = "index.php?ruta=configuracion";
                    </script>';
                }
            }
            else {
                echo '<script>
                    alert("Ocurrió un error al intentar generar el backup. Código: ' . $codigoSalida . '");
                </script>';
            }
        }
    }

    static public function ctrRestaurarBackup()
    {
        if (isset($_POST["btnRestaurarBackup"])) {
            require_once "helpers/CsrfHelper.php";
            if (!CsrfHelper::validate($_POST['csrf_token'] ?? '')) {
                echo '<script>alert("Token de seguridad inválido"); window.location = "index.php?ruta=configuracion";</script>';
                return;
            }

            if (isset($_FILES["archivoBackup"]["tmp_name"]) && !empty($_FILES["archivoBackup"]["tmp_name"])) {

                $archivoSubido = $_FILES["archivoBackup"]["tmp_name"];
                $nombreOriginal = $_FILES["archivoBackup"]["name"];
                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

                if ($extension !== "sql") {
                    echo '<script>
                        alert("Error: El archivo debe tener extensión .sql");
                    </script>';
                    return;
                }

                $db = self::getDbCreds();

                require_once "config/Rutas.php";
                $rutas = Rutas::getRutas();
                $mysqlPath = $rutas['mysql'];

                $passOption = ($db['pass'] == "") ? "" : "-p" . escapeshellarg($db['pass']);
                $comando = "$mysqlPath -h " . escapeshellarg($db['host']) . " -u " . escapeshellarg($db['user']) . " $passOption " . escapeshellarg($db['name']) . " < " . escapeshellarg($archivoSubido);

                $salida = null;
                $codigoSalida = null;

                exec($comando, $salida, $codigoSalida);

                if ($codigoSalida === 0) {
                    require_once "helpers/AuditoriaHelper.php";
                    AuditoriaHelper::log('restaurar', 'configuracion', null, 'Restauró backup: ' . $nombreOriginal);
                    echo '<script>
                        alert("¡Misión Cumplida! \n\nLa Base de Datos ha sido limpiada y restaurada exitosamente con el backup: ' . $nombreOriginal . '.");
                        window.location = "index.php?ruta=configuracion";
                    </script>';
                }
                else {
                    echo '<script>
                        alert("¡Ocurrió un error catastrófico al intentar inyectar el backup! Código: ' . $codigoSalida . '");
                    </script>';
                }
            }
            else {
                echo '<script>
                    alert("Por favor, seleccione un archivo válido.");
                </script>';
            }
        }
    }

    static public function ctrMostrarTurnos()
    {
        $tabla = "turnos";
        return TurnoModel::mdlMostrarTurnos($tabla);
    }

    static public function ctrAgregarTurno($datos)
    {
        $tabla = "turnos";
        return TurnoModel::mdlAgregarTurno($tabla, $datos);
    }

    static public function ctrModificarTurno($datos)
    {
        $tabla = "turnos";
        return TurnoModel::mdlModificarTurno($tabla, $datos);
    }

    static public function ctrCambiarEstadoTurno($id, $estado)
    {
        $tabla = "turnos";
        return TurnoModel::mdlCambiarEstadoTurno($tabla, $id, $estado);
    }

    static public function ctrAjaxHandler()
    {
        if (isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action === 'mostrarTurnos') {
                $turnos = self::ctrMostrarTurnos();
                echo json_encode($turnos);
                return;
            }

            if ($action === 'cambiarEstadoTurno' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'];
                $estado = $data['estado'];
                $resultado = self::ctrCambiarEstadoTurno($id, $estado);
                echo json_encode(['success' => $resultado]);
                return;
            }
        }

        echo json_encode(['error' => 'Acción no válida']);
    }
}