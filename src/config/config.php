
<?php
/**
 * Configuracoes do sistema
 */

// Caminho raiz do sistema
define('ROOT_PATH', dirname(__DIR__, 2));

// URL base do sistema (ALTERE PARA SUA URL LOCAL)
define('BASE_URL', 'http://localhost/simula-eeep/public');

// Configuracoes do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'simula_eeep');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuracoes de sessao
define('SESSION_NAME', 'simula_eeep_session');
define('SESSION_LIFETIME', 7200); // 2 horas

// Configuracoes de upload
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['csv', 'xlsx', 'xls']);
define('UPLOAD_PATH', __DIR__ . '/../../public/uploads/');


// Timezone
date_default_timezone_set('America/Fortaleza');
