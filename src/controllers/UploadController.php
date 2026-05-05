<?php
// src/controllers/UploadController.php

require_once __DIR__ . '/../config/config.php';

class UploadController
{
    private PlanilhaService $planilhaService;
    private Resultado       $resultadoModel;
    private Aluno           $alunoModel;
    private PDO             $db;

    public function __construct()
    {
        $this->planilhaService = new PlanilhaService();
        $this->resultadoModel  = new Resultado();
        $this->alunoModel      = new Aluno();
        $this->db              = Database::getInstance()->getConnection();
    }

    public function processar(array $arquivo, int $simuladoId, int $adminId): array
    {
        // 🔹 validar arquivo
        $validacao = $this->validarArquivo($arquivo);
        if (!$validacao['valido']) {
            return $this->erro($validacao['mensagem']);
        }

        // 🔹 criar pasta
        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0777, true);
        }

        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $caminhoFinal = UPLOAD_PATH . uniqid('planilha_', true) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
            return $this->erro('Erro ao salvar arquivo');
        }

        // 🔹 processar planilha
        try {
            $resultado = $this->planilhaService->processar(
                $caminhoFinal,
                $extensao,
                []
            );
        } catch (Exception $e) {
            return $this->erro('Erro ao processar planilha: ' . $e->getMessage());
        }

        $dados     = $resultado['dados'] ?? [];
        $errosPlan = $resultado['erros'] ?? [];

        if (empty($dados)) {
            return [
                'sucesso' => false,
                'status' => 'erro',
                'mensagem' => 'Nenhuma linha válida encontrada.',
                'processados' => 0,
                'erros' => count($errosPlan),
                'detalhes' => $errosPlan
            ];
        }

        $linhasOk  = 0;
        $errosDB   = [];
        $conflitos = [];

        $this->db->beginTransaction();

        try {
            foreach ($dados as $item) {

                $aluno = null;

                // 🔹 1. Buscar por matrícula
                $matricula = preg_replace('/\D/', '', (string)($item['matricula'] ?? ''));

                if (!empty($matricula)) {
                    $aluno = $this->alunoModel->buscarPorMatricula($matricula);
                }

                // 🔹 2. Se não encontrou → buscar por nome
                if (!$aluno && !empty($item['nome'])) {

                    $nome = trim($item['nome']);

                    if ($nome === '') {
                        $errosDB[] = "Nome vazio na planilha";
                        continue;
                    }

                    $resultados = $this->alunoModel->buscarPorNome($nome);

                    if (count($resultados) === 1) {
                        $aluno = $resultados[0];
                    }
                    elseif (count($resultados) > 1) {
                        $conflitos[] = [
                            'nome_planilha' => $nome,
                            'opcoes' => $resultados
                        ];
                        continue;
                    }
                    else {
                        $errosDB[] = "Aluno não encontrado: {$nome}";
                        continue;
                    }
                }

                if (!$aluno) {
                    $errosDB[] = "Aluno não identificado";
                    continue;
                }

                // 🔥 NOVA LÓGICA DE NOTA
                $respostas = $item['respostas'] ?? [];

                if (count($respostas) !== 52) {
                    $errosDB[] = "Quantidade inválida de questões para {$aluno['nome']}";
                    continue;
                }

                // 🔹 Linguagens (1–26)
                $ling = array_slice($respostas, 0, 26);
                $acertosLing = array_sum($ling);
                $notaLing = ($acertosLing / 26) * 100;

                // 🔹 Exatas (27–52)
                $exatas = array_slice($respostas, 26, 26);
                $acertosExatas = array_sum($exatas);
                $notaExatas = ($acertosExatas / 26) * 100;

                // 🔹 Média final
                $notaTotal = ($notaLing + $notaExatas) / 2;

                // 🔹 Salvar
                $this->resultadoModel->salvar(
                    (int)$aluno['id'],
                    $simuladoId,
                    [
                        'linguagens' => $notaLing,
                        'matematica' => $notaExatas,
                        'nota_total' => $notaTotal
                    ]
                );

                $linhasOk++;
            }

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->erro('Erro no banco: ' . $e->getMessage());
        }

        @unlink($caminhoFinal);

        $linhasErro = count($errosPlan) + count($errosDB);

        return [
            'sucesso'     => $linhasOk > 0,
            'status'      => !empty($conflitos)
                ? 'conflito'
                : ($linhasErro === 0 ? 'sucesso' : 'parcial'),

            'processados' => $linhasOk,
            'erros'       => $linhasErro,
            'detalhes'    => array_merge($errosPlan, $errosDB),
            'conflitos'   => $conflitos
        ];
    }

    private function validarArquivo(array $arquivo): array
    {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['valido' => false, 'mensagem' => 'Erro no upload'];
        }

        if (($arquivo['size'] ?? 0) > UPLOAD_MAX_SIZE) {
            return ['valido' => false, 'mensagem' => 'Arquivo muito grande'];
        }

        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, ALLOWED_EXTENSIONS, true)) {
            return ['valido' => false, 'mensagem' => 'Formato inválido'];
        }

        return ['valido' => true, 'mensagem' => 'OK'];
    }

    private function erro(string $msg): array
    {
        return [
            'sucesso' => false,
            'status' => 'erro',
            'mensagem' => $msg,
            'processados' => 0,
            'erros' => 0,
            'detalhes' => []
        ];
    }
}