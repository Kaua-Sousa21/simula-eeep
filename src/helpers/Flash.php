<?php
// src/helpers/Flash.php
// ============================================================
// Mensagens temporárias de feedback (sucesso, erro, aviso)
// ============================================================

class Flash
{
    public static function definir(string $tipo, string $mensagem): void
    {
        Session::set('flash', ['tipo' => $tipo, 'mensagem' => $mensagem]);
    }

    public static function sucesso(string $mensagem): void
    {
        self::definir('sucesso', $mensagem);
    }

    public static function erro(string $mensagem): void
    {
        self::definir('erro', $mensagem);
    }

    public static function aviso(string $mensagem): void
    {
        self::definir('aviso', $mensagem);
    }

    // Retorna e limpa a mensagem (exibe apenas uma vez)
    public static function obter(): ?array
    {
        $flash = Session::get('flash');
        Session::remover('flash');
        return $flash;
    }

    // Renderiza o HTML da mensagem
    public static function renderizar(): string
    {
        $flash = self::obter();
        if (!$flash) return '';

        $tipo      = htmlspecialchars($flash['tipo']);
        $mensagem  = htmlspecialchars($flash['mensagem']);
        $icones    = [
            'sucesso' => '✅',
            'erro'    => '❌',
            'aviso'   => '⚠️',
        ];
        $icone = $icones[$tipo] ?? 'ℹ️';

        return "
        <div class='alert alert--{$tipo}' role='alert'>
            <span class='alert__icone'>{$icone}</span>
            <span class='alert__mensagem'>{$mensagem}</span>
        </div>";
    }
}
