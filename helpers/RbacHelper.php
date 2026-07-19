<?php
class RbacHelper
{
    static public function getRolId()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (isset($_SESSION["id_rol"])) return (int)$_SESSION["id_rol"];
        $rolName = $_SESSION["rol"] ?? '';
        if ($rolName === 'Director' || $rolName === 'Administrador') return 1;
        if ($rolName === 'Secretaria' || $rolName === 'Supervisor') return 2;
        return 3;
    }

    static public function tieneRol($rolesPermitidos)
    {
        return in_array(self::getRolId(), $rolesPermitidos);
    }

    static public function soloAdmin()
    {
        return self::tieneRol([4]);
    }

    static public function soloAdminODirector()
    {
        return self::tieneRol([4, 1]);
    }

    static public function soloAdminODirectorOSecretaria()
    {
        return self::tieneRol([4, 1, 2]);
    }

    static public function denegar($rutaDestino = 'error_403')
    {
        header('Location: index.php?ruta=' . $rutaDestino);
        exit;
    }
}
