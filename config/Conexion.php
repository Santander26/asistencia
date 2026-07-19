<?php
class Conexion
{
    static private $instancia = null;

    static public function conectar()
    {
        if (self::$instancia === null) {
            try {
                $host = getenv('DB_HOST') ?: 'localhost';
                $dbname = getenv('DB_NAME') ?: 'asistencia_db';
                $user = getenv('DB_USER') ?: 'root';
                $pass = getenv('DB_PASSWORD') ?: '';
                self::$instancia = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8",
                    $user,
                    $pass
                );
                self::$instancia->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instancia->exec("set names utf8");
            } catch (PDOException $e) {
                die("Error de conexión a la base de datos. Contacte al administrador.");
            }
        }
        return self::$instancia;
    }
}
?>