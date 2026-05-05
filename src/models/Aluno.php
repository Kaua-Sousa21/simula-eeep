<?php
// src/models/Aluno.php
// ============================================================

class Aluno
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function buscarPorUsuarioId(int $usuarioId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.email
             FROM alunos a
             INNER JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.usuario_id = :usuario_id"
        );
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPorMatricula(string $matricula): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.email, u.tipo, u.primeiro_acesso
             FROM alunos a
             INNER JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.matricula = :matricula"
        );
        $stmt->execute([':matricula' => $matricula]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.email, u.tipo, u.primeiro_acesso
             FROM alunos a
             INNER JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = "SELECT a.*
                FROM alunos a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                WHERE u.email = :email
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function buscarPorNome(string $nome): array{
    $stmt = $this->db->prepare("
        SELECT * FROM alunos
        WHERE nome LIKE ?
    ");

    $stmt->execute(['%' . $nome . '%']);

    return $stmt->fetchAll();
}

    // 🔥 MÉTODO CORRIGIDO (AGORA RECEBE ARRAY)
public function criar(array $dados): int
{
    $stmt = $this->db->prepare(
        "INSERT INTO alunos (usuario_id, matricula, nome, turma, turno)
         VALUES (:usuario_id, :matricula, :nome, :turma, :turno)"
    );

    $stmt->execute([
        ':usuario_id' => $dados['usuario_id'] ?? null,
        ':matricula'  => $dados['matricula'],
        ':nome'       => $dados['nome'],
        ':turma'      => $dados['turma'],
        ':turno'      => $dados['turno'] ?? 'manhã',
    ]);

    return (int) $this->db->lastInsertId();
}

    // Lista todos os alunos
    public function listarTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT 
                a.id, 
                a.matricula, 
                a.nome, 
                a.turma, 
                a.turno, 
                u.email, 
                u.ativo, 
                u.primeiro_acesso, 
                u.id as usuario_id
             FROM alunos a
             INNER JOIN usuarios u ON u.id = a.usuario_id
             ORDER BY a.turma, a.nome"
        );
        return $stmt->fetchAll();
    }

    // Lista turmas únicas
    public function listarTurmas(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT turma 
             FROM alunos 
             WHERE turma IS NOT NULL AND turma != '' 
             ORDER BY turma"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Estatísticas do aluno
    public function obterEstatisticas(int $alunoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(r.id)       AS total_simulados,
                AVG(r.nota_total) AS media_geral,
                MAX(r.nota_total) AS melhor_nota,
                MIN(r.nota_total) AS pior_nota,

                AVG(r.linguagens) AS media_linguagens,
                AVG(r.humanas)    AS media_humanas,
                AVG(r.natureza)   AS media_natureza,
                AVG(r.matematica) AS media_matematica,
                AVG(r.redacao)    AS media_redacao,

                (SELECT r2.nota_total
                 FROM resultados r2
                 INNER JOIN simulados s2 ON s2.id = r2.simulado_id
                 WHERE r2.aluno_id = :aluno_id2
                 ORDER BY s2.data_aplicacao DESC
                 LIMIT 1) AS ultima_nota

             FROM resultados r
             WHERE r.aluno_id = :aluno_id"
        );

        $stmt->execute([
            ':aluno_id'  => $alunoId,
            ':aluno_id2' => $alunoId,
        ]);

        $stats = $stmt->fetch();

        if ($stats && $stats['total_simulados'] > 0) {
            $areas = [
                'Linguagens' => (float) $stats['media_linguagens'],
                'Humanas'    => (float) $stats['media_humanas'],
                'Natureza'   => (float) $stats['media_natureza'],
                'Matemática' => (float) $stats['media_matematica'],
                'Redação'    => (float) $stats['media_redacao'],
            ];

            $stats['melhor_area'] = array_search(max($areas), $areas);
            $stats['pior_area']   = array_search(min($areas), $areas);
        }

        return $stats ?: [];
    }

    // Deletar aluno
    public function deletar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM alunos WHERE id = ?");
        return $stmt->execute([$id]);
    }
}