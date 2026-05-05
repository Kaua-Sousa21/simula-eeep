<?php
// public/login.php
// ============================================================

require_once '../src/config/config.php';
require_once '../src/config/database.php';
require_once '../src/helpers/Session.php';
require_once '../src/helpers/Redirect.php';
require_once '../src/helpers/Flash.php';
require_once '../src/models/Usuario.php';
require_once '../src/controllers/AuthController.php';

Session::iniciar();

if (Session::autenticado()) {
    $destino = Session::isAdmin() ? '/admin/dashboard.php' : '/aluno/dashboard.php';
    Redirect::para(BASE_URL . $destino);
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        $erro = 'Requisicao invalida. Tente novamente.';
    } else {
        $auth      = new AuthController();
        $resultado = $auth->login(
            $_POST['email']  ?? '',
            $_POST['senha']  ?? ''
        );

        if ($resultado['sucesso']) {
            if ($resultado['primeiro_acesso']) {
                Flash::aviso('Bem-vindo! Por seguranca, defina uma nova senha para continuar.');
                Redirect::para(BASE_URL . '/primeiro-acesso.php');
            }

            $destino = $resultado['tipo'] === 'admin'
                ? BASE_URL . '/admin/dashboard.php'
                : BASE_URL . '/aluno/dashboard.php';

            Redirect::para($destino);
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}

$csrfToken = bin2hex(random_bytes(32));
Session::set('csrf_token', $csrfToken);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Simula EEEP</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<main class="login-pagina">
  <div class="login-card">

    <div class="login-card__logo">
      <img
        src="assets/img/logo.png"
        alt="Simula EEEP"
        class="login-card__logo-img"
        onerror="this.style.display='none'; document.getElementById('login-logo-fallback').style.display='block';"
      >
      <div id="login-logo-fallback" style="display:none;">
        <h1 class="login-card__titulo">Simula EEEP</h1>
      </div>
      <p class="login-card__subtitulo">Acesse com seu e-mail institucional</p>
    </div>

    <?php if ($erro): ?>
      <div class="alert alert--erro" role="alert">
        <span class="alert__icone">X</span>
        <span class="alert__mensagem"><?= htmlspecialchars($erro) ?></span>
      </div>
    <?php endif; ?>

    <?= Flash::renderizar() ?>

    <form class="login-card__form" method="POST" action="login.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <div class="form-grupo">
        <label class="form-label form-label--obrigatorio" for="email">
          E-mail institucional
        </label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-input"
          placeholder="seu.email@escola.edu.br"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          autocomplete="email"
          required
          autofocus
        >
      </div>

      <div class="form-grupo">
        <label class="form-label form-label--obrigatorio" for="senha">
          Senha
        </label>
        <div class="input-senha-wrapper">
          <input
            type="password"
            id="senha"
            name="senha"
            class="form-input"
            placeholder="Digite sua senha"
            autocomplete="current-password"
            required
          >
          <button
            type="button"
            class="btn-toggle-senha"
            aria-label="Mostrar/ocultar senha"
            data-alvo="senha"
          >👁</button>
        </div>
      </div>

      <button type="submit" class="btn btn--primario btn--lg btn--bloco mt-md">
        Entrar
      </button>
    </form>

    <div class="login-card__rodape">
      <p>Problemas de acesso? Fale com a coordenacao.</p>
    </div>

  </div>
</main>

<script src="assets/js/main.js"></script>
</body>
</html>
