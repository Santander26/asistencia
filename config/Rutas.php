<?php

class Rutas
{
    const ENTORNO_ACTUAL = 'wamp_windows';

    static public function getRutas()
    {
        $env = getenv('APP_ENV') ?: self::ENTORNO_ACTUAL;
        $rutas = array(
            'xampp_linux' => array(
                'mysqldump' => '/opt/lampp/bin/mysqldump',
                'mysql' => '/opt/lampp/bin/mysql'
            ),
            'wamp_windows' => array(
                'mysqldump' => 'C:\wamp64\bin\mariadb\mariadb11.2.2\bin\mysqldump.exe',
                'mysql' => 'C:\wamp64\bin\mariadb\mariadb11.2.2\bin\mysql.exe'
            ),
            'laragon_windows' => array(
                'mysqldump' => 'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
                'mysql' => 'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysql.exe'
            ),
            'produccion_linux' => array(
                'mysqldump' => '/usr/bin/mysqldump',
                'mysql' => '/usr/bin/mysql'
            ),
            'docker' => array(
                'mysqldump' => '/usr/bin/mysqldump',
                'mysql' => '/usr/bin/mysql'
            )
        );

        return $rutas[$env];
    }
}
?>