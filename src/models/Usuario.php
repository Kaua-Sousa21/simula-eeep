
<?php
/**
 * Model de Usu\u00e1rio
 */

class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    // Alias para compatibilidade com AuthController
    public function buscarPorId($id) {
        return $this->findById($id);
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function verificarSenha($senha, $hash) {
        return password_verify($senha, $hash);
    }
    
    // Alias para compatibilidade
    public function verificaSenha($senha, $hash) {
        return $this->verificarSenha($senha, $hash);
    }
    
    public function atualizarSenha($id, $novaSenha) {
        return $this->updatePassword($id, $novaSenha);
    }
    
    public function login($email, $senha) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Usu\u00e1rio n\u00e3o encontrado'];
        }
        
        if (!password_verify($senha, $user['senha_hash'])) {
            return ['success' => false, 'message' => 'Senha incorreta'];
        }
        
        // Remove a senha da sess\u00e3o
        unset($user['senha_hash']);
        
        return ['success' => true, 'user' => $user];
    }
    
    public function updatePassword($id, $novaSenha) {
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE usuarios SET senha_hash = ?, primeiro_acesso = 0 WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }
    
    
    public function create($data) {
    $hash = password_hash($data['senha'], PASSWORD_DEFAULT);

    $stmt = $this->db->prepare("
        INSERT INTO usuarios (email, senha_hash, tipo, primeiro_acesso) 
        VALUES (?, ?, ?, 1)
    ");

    $ok = $stmt->execute([
        $data['email'],
        $hash,
        $data['tipo']
    ]);

    if ($ok) {
        return $this->db->lastInsertId();
    }

    return false;
}
    
    public function getAll() {
        $stmt = $this->db->query("SELECT id, email, tipo, primeiro_acesso, created_at FROM usuarios ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
    public function criarComSenha($data) {
    $hash = password_hash($data['senha'], PASSWORD_DEFAULT);

    $stmt = $this->db->prepare("
        INSERT INTO usuarios (email, senha_hash, tipo, primeiro_acesso) 
        VALUES (?, ?, ?, 1)
    ");

    $ok = $stmt->execute([
        $data['email'],
        $hash,
        $data['tipo']
    ]);

    if ($ok) {
        return $this->db->lastInsertId(); // 🔥 retorna o ID (IMPORTANTE)
    }

    return false;
}
    


    
    


}

