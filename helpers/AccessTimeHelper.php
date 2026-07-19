<?php
class AccessTimeHelper
{
    static public function getConfig()
    {
        $jsonFile = __DIR__ . '/../config/access_time.json';
        $json = [];
        if (file_exists($jsonFile)) {
            $json = json_decode(file_get_contents($jsonFile), true) ?: [];
        }
        return [
            'dias'                => $json['dias'] ?? [1, 2, 3, 4, 5],
            'hora_inicio'         => (int)($json['hora_inicio'] ?? 8),
            'hora_fin'            => (int)($json['hora_fin'] ?? 13),
            'habilitado'          => (bool)($json['habilitado'] ?? false),
            'tiempo_inactividad'  => (int)($json['tiempo_inactividad'] ?? 5),
        ];
    }

    static public function guardarConfig($datos)
    {
        $jsonFile = __DIR__ . '/../config/access_time.json';
        file_put_contents($jsonFile, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    static public function verificar()
    {
        $config = self::getConfig();

        if (!$config['habilitado']) return true;

        $id_rol = (int)($_SESSION["id_rol"] ?? 0);

        if ($id_rol === 4 || $id_rol === 1) return true;

        $dia = (int)date("N");
        $hora = (int)date("G");

        return in_array($dia, $config['dias']) && $hora >= $config['hora_inicio'] && $hora < $config['hora_fin'];
    }

    static public function denegar()
    {
        $id_rol = (int)($_SESSION["id_rol"] ?? 0);

        if ($id_rol === 4 || $id_rol === 1) return;

        if (!self::verificar()) {
            session_unset();
            session_destroy();
            session_start();
            header('Location: index.php?ruta=fuera_horario');
            exit;
        }
    }
}
