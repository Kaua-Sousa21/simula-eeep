
<?php
// src/controllers/AuthController.php
// ============================================================

class AuthController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function login(string $email, string $senha): array
    {
        // Sanitiza\u00e7\u00e3o b\u00e1sica
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sucesso' => false, 'mensagem' => 'E-mail inv\u00e1lido.'];
        }

        if (empty($senha)) {
            return ['sucesso' => false, 'mensagem' => 'Senha obrigat\u00f3ria.'];
        }

        $usuario = $this->usuarioModel->findByEmail($email);

        if (!$usuario) {
            // Mensagem gen\u00e9rica para n\u00e3o revelar se o e-mail existe
            return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.'];
        }

        if (!$usuario['ativo']) {
            return ['sucesso' => false, 'mensagem' => 'Conta desativada. Contate a coordena\u00e7\u00e3o.'];
        }

        if (!$this->usuarioModel->verificarSenha($senha, $usuario['senha_hash'])) {
            return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.'];
        }

        // Autentica\u00e7\u00e3o bem-sucedida \u2014 inicia sess\u00e3o
        Session::regenerar(); // Prote\u00e7\u00e3o contra session fixation

        Session::set('usuario_id',   $usuario['id']);
        Session::set('usuario_tipo', $usuario['tipo']);
        Session::set('usuario_email',$usuario['email']);

        return [
            'sucesso'         => true,
            'tipo'            => $usuario['tipo'],
            'primeiro_acesso' => (bool) $usuario['primeiro_acesso'],
        ];
    }

    public function logout(): void
    {
        Session::destruir();
        Redirect::voltarLogin();
    }

    public function trocarSenha(int $usuarioId, string $senhaAtual, string $novaSenha, string $confirmacao): array
    {
        if ($novaSenha !== $confirmacao) {
            return ['sucesso' => false, 'mensagem' => 'As senhas n\u00e3o coincidem.'];
        }

        if (!$this->validarForcaSenha($novaSenha)) {
            return [
                'sucesso'  => false,
                'mensagem' => 'A senha deve ter no m\u00ednimo 8 caracteres, incluindo letras e n\u00fameros.'
            ];
        }

        $usuario = $this->usuarioModel->buscarPorId($usuarioId);

        if (!$this->usuarioModel->verificarSenha($senhaAtual, $usuario['senha_hash'])) {
            return ['sucesso' => false, 'mensagem' => 'Senha atual incorreta.'];
        }

        $this->usuarioModel->atualizarSenha($usuarioId, $novaSenha);

        return ['sucesso' => true, 'mensagem' => 'Senha atualizada com sucesso!'];
    }

    private function validarForcaSenha(string $senha): bool
    {
        return strlen($senha) >= 8
            && preg_match('/[A-Za-z]/', $senha)
            && preg_match('/[0-9]/', $senha);
    }
}
