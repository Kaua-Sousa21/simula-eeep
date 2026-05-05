<?php
// src/services/ValidacaoService.php
// ============================================================
// Centraliza todas as validações de entrada do sistema
// ============================================================

class ValidacaoService
{
    private array $erros = [];

    // ── Validações de usuário ──────────────────────────────────

    public function validarEmail(string $email): bool
    {
        $email = trim($email);

        if (empty($email)) {
            $this->erros[] = 'E-mail é obrigatório.';
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->erros[] = 'Formato de e-mail inválido.';
            return false;
        }

        if (strlen($email) > 150) {
            $this->erros[] = 'E-mail muito longo.';
            return false;
        }

        return true;
    }

    public function validarSenha(string $senha, bool $verificarForca = true): bool
    {
        if (empty($senha)) {
            $this->erros[] = 'Senha é obrigatória.';
            return false;
        }

        if ($verificarForca) {
            if (strlen($senha) < 8) {
                $this->erros[] = 'Senha deve ter no mínimo 8 caracteres.';
                return false;
            }
            if (!preg_match('/[A-Za-z]/', $senha)) {
                $this->erros[] = 'Senha deve conter letras.';
                return false;
            }
            if (!preg_match('/[0-9]/', $senha)) {
                $this->erros[] = 'Senha deve conter números.';
                return false;
            }
        }

        return true;
    }

    public function validarMatricula(string $matricula): bool
    {
        $matricula = trim($matricula);

        if (empty($matricula)) {
            $this->erros[] = 'Matrícula é obrigatória.';
            return false;
        }

        // Apenas alfanumérico, traços e pontos
        if (!preg_match('/^[A-Za-z0-9\-\.]{3,20}$/', $matricula)) {
            $this->erros[] = 'Matrícula inválida (3-20 caracteres alfanuméricos).';
            return false;
        }

        return true;
    }

    public function validarNota(mixed $valor, string $campo): bool
    {
        $valor = str_replace(',', '.', (string) $valor);

        if (!is_numeric($valor)) {
            $this->erros[] = "Nota inválida no campo '{$campo}'.";
            return false;
        }

        $nota = (float) $valor;

        if ($nota < 0 || $nota > 1000) {
            $this->erros[] = "Nota fora do intervalo (0-1000) em '{$campo}': {$nota}.";
            return false;
        }

        return true;
    }

    // ── Sanitização ────────────────────────────────────────────

    public static function sanitizarTexto(string $texto): string
    {
        return htmlspecialchars(strip_tags(trim($texto)), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizarInteiro(mixed $valor): int
    {
        return (int) filter_var($valor, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function sanitizarEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    // ── Acesso aos erros ───────────────────────────────────────

    public function temErros(): bool
    {
        return !empty($this->erros);
    }

    public function getErros(): array
    {
        return $this->erros;
    }

    public function limpar(): void
    {
        $this->erros = [];
    }
}
