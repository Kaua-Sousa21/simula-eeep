<?php
// src/helpers/Redirect.php
// ============================================================

class Redirect
{
    public static function para(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    public static function voltarLogin(): void
    {
        self::para(BASE_URL . '/login.php');
    }

    public static function semPermissao(): void
    {
        self::para(BASE_URL . '/login.php?erro=sem_permissao');
    }
}
