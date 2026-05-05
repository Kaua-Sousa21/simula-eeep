<?php
use PhpOffice\PhpSpreadsheet\IOFactory;

class PlanilhaService
{
    // =========================================================
    // 🔥 MÉTODO PRINCIPAL
    // =========================================================
    public function processar(string $caminhoArquivo, string $extensao): array
    {
        $linhas = match (strtolower($extensao)) {
            'csv'  => $this->lerCSVBruto($caminhoArquivo),
            'xlsx' => $this->lerXLSXBruto($caminhoArquivo),
            default => throw new Exception("Formato não suportado")
        };

        return $this->processarLinhasBrutas($linhas);
    }

    // =========================================================
    // 🔥 PROCESSAMENTO INTELIGENTE
    // =========================================================
    private function processarLinhasBrutas(array $linhas): array
    {
        $dados = [];
        $erros = [];
        $linhaNum = 0;

        foreach ($linhas as $linha) {
            $linhaNum++;

            $nome = null;
            $matricula = null;
            $respostas = [];

            foreach ($linha as $valor) {

                $valor = trim((string)$valor);
                if ($valor === '') continue;

                $valorUpper = mb_strtoupper($valor);

                // ❌ ignora lixo
                if (
                    str_contains($valorUpper, 'AVALIA') ||
                    str_contains($valorUpper, 'DISCIPLINA') ||
                    str_contains($valorUpper, 'ESCOLA') ||
                    str_contains($valorUpper, 'PROVA') ||
                    str_contains($valorUpper, 'NOTA') ||
                    str_contains($valorUpper, 'AC') ||
                    preg_match('/^Q\d+$/', $valorUpper)
                ) {
                    continue;
                }

                // 🔹 MATRÍCULA
                if (preg_match('/^\d{6,}$/', $valor)) {
                    $matricula = $valor;
                    continue;
                }

                // 🔹 NOME
                if (
                    strlen($valor) > 5 &&
                    preg_match('/[A-Za-z]/', $valor) &&
                    str_contains($valor, ' ')
                ) {
                    $nome = $valorUpper;
                    continue;
                }

                // 🔥 QUESTÕES (0 ou 1 SOMENTE)
                if ($valor === '0' || $valor === '1' || $valor === 0 || $valor === 1) {
                    $respostas[] = (int)$valor;
                }
            }

            // ❌ validação
            if (!$nome) {
                continue;
            }

            if (count($respostas) < 52) {
                $erros[] = [
                    'linha' => $linhaNum,
                    'erros' => ["Menos de 52 respostas encontradas ({$nome})"]
                ];
                continue;
            }

            // 🔥 GARANTE EXATAMENTE 52
            $respostas = array_slice($respostas, 0, 52);

            $dados[] = [
                'matricula' => $matricula,
                'nome'      => $nome,
                'respostas' => $respostas
            ];
        }

        return [
            'sucesso' => true,
            'dados' => $dados,
            'erros' => $erros,
            'total' => count($linhas),
            'processados' => count($dados)
        ];
    }

    // =========================================================
    // CSV
    // =========================================================
    private function lerCSVBruto(string $caminho): array
    {
        $linhas = [];
        $handle = fopen($caminho, 'r');

        if (!$handle) {
            throw new Exception("Erro ao abrir CSV");
        }

        $primeiraLinha = fgets($handle);
        rewind($handle);

        $delimitador = str_contains($primeiraLinha, ';') ? ';' : ',';

        while (($linha = fgetcsv($handle, 2000, $delimitador)) !== false) {
            if (empty(array_filter($linha))) continue;
            $linhas[] = $linha;
        }

        fclose($handle);
        return $linhas;
    }

    // =========================================================
    // XLSX
    // =========================================================
    private function lerXLSXBruto(string $caminho): array
    {
        $spreadsheet = IOFactory::load($caminho);
        $sheet = $spreadsheet->getActiveSheet();

        $linhas = [];

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {

            $linha = [];

            foreach (range('A', $highestColumn) as $col) {
                $valor = $sheet->getCell($col . $row)->getFormattedValue();
                $linha[] = $valor;
            }

            if (empty(array_filter($linha))) continue;

            $linhas[] = $linha;
        }

        return $linhas;
    }
}