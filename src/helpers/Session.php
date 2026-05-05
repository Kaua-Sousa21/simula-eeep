<?php
// src/helpers/Session.php
// ============================================================
// Gerenciamento seguro de sessões
// ============================================================

class Session
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurações de segurança da sessão
            ini_set('session.cookie_httponly', 1);   // Bloqueia acesso JS ao cookie
            ini_set('session.cookie_secure',   1);   // Apenas HTTPS
            ini_set('session.use_strict_mode', 1);   // Rejeita IDs externos
            ini_set('session.cookie_samesite', 'Strict');

            session_start();
        }
    }

    public static function set(string $chave, mixed $valor): void
    {
        $_SESSION[$chave] = $valor;
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        return $_SESSION[$chave] ?? $padrao;
    }

    public static function existe(string $chave): bool
    {
        return isset($_SESSION[$chave]);
    }

    public static function remover(string $chave): void
    {
        unset($_SESSION[$chave]);
    }

    public static function destruir(): void
    {
        session_unset();
        session_destroy();

        // Remove o cookie de sessão
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
    }

    // Verifica se o usuário está autenticado
    public static function autenticado(): bool
    {
        return self::existe('usuario_id') && self::existe('usuario_tipo');
    }

    // Verifica se é administrador
    public static function isAdmin(): bool
    {
        return self::get('usuario_tipo') === 'admin';
    }

    // Regenera ID da sessão (proteção contra fixação)
    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }
}
