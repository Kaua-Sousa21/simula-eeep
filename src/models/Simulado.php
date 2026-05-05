
<?php
// src/models/Simulado.php
// ============================================================

class Simulado
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO simulados (titulo, descricao, data_aplicacao, ativo)
             VALUES (:titulo, :descricao, :data_aplicacao, 1)"
        );
        $stmt->execute([
            ':titulo'        => $dados['titulo'],
            ':descricao'     => $dados['descricao'] ?? '',
            ':data_aplicacao'=> $dados['data_aplicacao'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE simulados
             SET titulo = :titulo, descricao = :descricao, data_aplicacao = :data_aplicacao
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'             => $id,
            ':titulo'         => $dados['titulo'],
            ':descricao'      => $dados['descricao'] ?? '',
            ':data_aplicacao' => $dados['data_aplicacao'],
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM simulados WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function listarTodos(bool $apenasAtivos = false): array
    {
        if ($apenasAtivos) {
            $stmt = $this->db->query("SELECT * FROM simulados WHERE ativo = 1 ORDER BY data_aplicacao DESC");
        } else {
            $stmt = $this->db->query("SELECT * FROM simulados ORDER BY data_aplicacao DESC");
        }
        return $stmt->fetchAll();
    }

    public function desativar(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE simulados SET ativo = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function ativar(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE simulados SET ativo = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function deletar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM simulados WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function contarResultados(int $simuladoId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM resultados WHERE simulado_id = :id");
        $stmt->execute([':id' => $simuladoId]);
        return (int) $stmt->fetch()['total'];
    }

    public function mediaNotas(int $simuladoId): float
    {
        $stmt = $this->db->prepare("SELECT AVG(nota_total) as media FROM resultados WHERE simulado_id = :id");
        $stmt->execute([':id' => $simuladoId]);
        $result = $stmt->fetch();
        return $result['media'] ? round((float) $result['media'], 1) : 0.0;
    }
}
