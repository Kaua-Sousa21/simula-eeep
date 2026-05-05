<?php
// src/models/Resultado.php
// ============================================================

class Resultado
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // =========================================================
    // Histórico completo de resultados do aluno
    // =========================================================
    public function listarPorAluno(int $alunoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                r.id,
                s.titulo        AS simulado,
                s.data_aplicacao,
                r.linguagens,
                r.humanas,
                r.natureza,
                r.matematica,
                r.redacao,
                r.nota_total
             FROM resultados r
             INNER JOIN simulados s ON s.id = r.simulado_id
             WHERE r.aluno_id = :aluno_id
             ORDER BY s.data_aplicacao DESC"
        );

        $stmt->execute([':aluno_id' => $alunoId]);
        return $stmt->fetchAll();
    }

    // =========================================================
    // 🔥 SALVAR (AGORA FLEXÍVEL)
    // =========================================================
    public function salvar(int $alunoId, int $simuladoId, array $notas): bool
    {
        // 🔥 evita erro de índice inexistente
        $linguagens = $notas['linguagens'] ?? null;
        $humanas    = $notas['humanas'] ?? null;
        $natureza   = $notas['natureza'] ?? null;
        $matematica = $notas['matematica'] ?? null;
        $redacao    = $notas['redacao'] ?? null;

        // 🔥 NOVO: suporta nota_total direto
        $notaTotal = $notas['nota_total']
            ?? $notas['nota']
            ?? $this->calcularNotaTotal($notas);

        $stmt = $this->db->prepare(
            "INSERT INTO resultados
                (aluno_id, simulado_id, linguagens, humanas, natureza, matematica, redacao, nota_total)
             VALUES
                (:aluno_id, :simulado_id, :linguagens, :humanas, :natureza, :matematica, :redacao, :nota_total)
             ON DUPLICATE KEY UPDATE
                linguagens  = VALUES(linguagens),
                humanas     = VALUES(humanas),
                natureza    = VALUES(natureza),
                matematica  = VALUES(matematica),
                redacao     = VALUES(redacao),
                nota_total  = VALUES(nota_total),
                importado_em = CURRENT_TIMESTAMP"
        );

        return $stmt->execute([
            ':aluno_id'   => $alunoId,
            ':simulado_id'=> $simuladoId,
            ':linguagens' => $linguagens,
            ':humanas'    => $humanas,
            ':natureza'   => $natureza,
            ':matematica' => $matematica,
            ':redacao'    => $redacao,
            ':nota_total' => $notaTotal,
        ]);
    }

    // =========================================================
    // Evolução do aluno
    // =========================================================
    public function evolucaoPorAluno(int $alunoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                s.titulo,
                s.data_aplicacao,
                r.nota_total,
                r.linguagens,
                r.humanas,
                r.natureza,
                r.matematica,
                r.redacao
             FROM resultados r
             INNER JOIN simulados s ON s.id = r.simulado_id
             WHERE r.aluno_id = :aluno_id
             ORDER BY s.data_aplicacao ASC"
        );

        $stmt->execute([':aluno_id' => $alunoId]);
        return $stmt->fetchAll();
    }

    // =========================================================
    // 🔥 Cálculo automático (fallback)
    // =========================================================
    private function calcularNotaTotal(array $notas): float
    {
        $valores = [];

        foreach ($notas as $nota) {
            if ($nota !== null && is_numeric($nota)) {
                $valores[] = (float)$nota;
            }
        }

        if (empty($valores)) {
            return 0;
        }

        return round(array_sum($valores) / count($valores), 2);
    }
}