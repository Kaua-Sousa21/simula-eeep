
<?php
/**
 * Script para gerar hash da senha e atualizar o admin
 * Acesse: http://localhost/simula-eeep/gerar_hash.php
 */

// Mostra erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$senha = 'Admin@2025';
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "<h2>Hash gerado para a senha '$senha':</h2>";
echo "<textarea style='width:100%;height:100px;'>$hash</textarea>";

// Conex\u00e3o com o banco
$host = 'localhost';
$dbname = 'simula_eeep';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Atualiza a senha do admin
    $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE email = 'admin@escola.edu.br'");
    $stmt->execute([$hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "<h3 style='color:green'>\u2705 Senha do admin atualizada com sucesso!</h3>";
    } else {
        // Se n\u00e3o atualizou, tenta inserir o admin
        echo "<p>Admin n\u00e3o encontrado. Tentando criar...</p>";
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (email, senha_hash, tipo, primeiro_acesso) 
            VALUES (?, ?, 'admin', 0)
            ON DUPLICATE KEY UPDATE senha_hash = VALUES(senha_hash)
        ");
        $stmt->execute(['admin@escola.edu.br', $hash]);
        echo "<h3 style='color:green'>\u2705 Admin criado/atualizado com sucesso!</h3>";
    }
    
    // Verifica se funcionou
    $stmt = $pdo->query("SELECT * FROM usuarios WHERE email = 'admin@escola.edu.br'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Dados do admin:</h3>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
    
    // Testa a verifica\u00e7\u00e3o da senha
    echo "<h3>Testando verifica\u00e7\u00e3o da senha:</h3>";
    if (password_verify($senha, $admin['senha_hash'])) {
        echo "<p style='color:green;font-weight:bold'>\u2705 Senha 'Admin@2025' verificada com sucesso!</p>";
    } else {
        echo "<p style='color:red'>\u274c Erro na verifica\u00e7\u00e3o da senha!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se o banco de dados 'simula_eeep' existe.</p>";
}
?>
