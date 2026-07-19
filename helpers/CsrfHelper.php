<?php

class CsrfHelper
{
    static public function token()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $t =& $_SESSION['csrf_token'];
        if (empty($t)) $t = bin2hex(random_bytes(32));
        return $t;
    }

    static public function field()
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    static public function validate($token)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    }

    static public function regenerate()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
