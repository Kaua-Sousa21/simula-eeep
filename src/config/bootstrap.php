
<?php
/**
 * Bootstrap - Carrega todas as depend\u00eancias e configura\u00e7\u00f5es
 */

// Mostra erros em desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carrega configura\u00e7\u00f5es
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// Autoload manual (se n\u00e3o tiver Composer)
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../models/' . $class . '.php',
        __DIR__ . '/../controllers/' . $class . '.php',
        __DIR__ . '/../services/' . $class . '.php',
        __DIR__ . '/../helpers/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Se tiver Composer, descomente a linha abaixo:
// require_once __DIR__ . '/../../vendor/autoload.php';

// Inicia sess\u00e3o
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Fun\u00e7\u00e3o helper para redirecionar
function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

// Fun\u00e7\u00e3o helper para verificar autentica\u00e7\u00e3o
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        redirect('/login.php');
    }
}

// Fun\u00e7\u00e3o helper para verificar se \u00e9 admin
function checkAdmin() {
    checkAuth();
    if ($_SESSION['user_tipo'] !== 'admin') {
        redirect('/aluno/dashboard.php');
    }
}

// Fun\u00e7\u00e3o helper para verificar se \u00e9 aluno
function checkAluno() {
    checkAuth();
    if ($_SESSION['user_tipo'] !== 'aluno') {
        redirect('/admin/dashboard.php');
    }
}

// Fun\u00e7\u00e3o para gerar CSRF token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fun\u00e7\u00e3o para verificar CSRF token
function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
