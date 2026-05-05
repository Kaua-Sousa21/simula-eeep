<?php
// public/aluno/perfil.php
// ============================================================

require_once '../../src/config/config.php';
require_once '../../src/config/database.php';
require_once '../../src/helpers/Session.php';
require_once '../../src/helpers/Redirect.php';
require_once '../../src/helpers/Flash.php';
require_once '../../src/models/Aluno.php';
require_once '../../src/models/Usuario.php';
require_once '../../src/controllers/AuthController.php';

Session::iniciar();

if (!Session::autenticado() || Session::isAdmin()) {
    Redirect::semPermissao();
}

$alunoModel = new Aluno();
$aluno      = $alunoModel->buscarPorUsuarioId(Session::get('usuario_id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        Flash::erro('Requisição inválida.');
        Redirect::para(BASE_URL . '/aluno/perfil.php');
    }

    $auth      = new AuthController();
    $resultado = $auth->trocarSenha(
        Session::get('usuario_id'),
        $_POST['senha_atual']    ?? '',
        $_POST['nova_senha']     ?? '',
        $_POST['confirmar_senha']?? ''
    );

    if ($resultado['sucesso']) {
        Flash::sucesso($resultado['mensagem']);
    } else {
        Flash::erro($resultado['mensagem']);
    }

    Redirect::para(BASE_URL . '/aluno/perfil.php');
}

$csrfToken = bin2hex(random_bytes(32));
Session::set('csrf_token', $csrfToken);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu Perfil — Simula EEEP</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>

<header class="header-mobile">
  <button class="btn-menu" id="btnMenu" aria-label="Abrir menu">☰</button>
  <span class="header-mobile__titulo">Meu Perfil</span>
  <span style="width:40px"></span>
</header>

<div class="overlay-sidebar" id="overlaySidebar"></div>

<div class="pagina">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar__logo">
      <img
        src="../assets/img/logo.png"
        alt="Simula EEEP"
        style="width:120px; height:auto; display:block; margin: 0 auto var(--espaco-xs);"
        onerror="this.style.display='none'"
      >
      <div>
        <div class="sidebar__logo-sub">Área do Aluno</div>
      </div>
    </div>
    <nav class="sidebar__nav">
      <a href="dashboard.php" class="nav__item nav__item--ativo">
        <span class="nav__icone">Dashboard</span>
      </a>
      <a href="resultados.php" class="nav__item">
        <span class="nav__icone">Meus Resultados</span>
      </a>
      <a href="perfil.php" class="nav__item">
        <span class="nav__icone">Meu Perfil</span>
      </a>
    </nav>
    <div class="sidebar__rodape">
      <a href="../logout.php" class="btn btn--secundario btn--sm"
         style="color:#a5d6a7; border-color:#a5d6a7; width:100%;">Sair</a>
    </div>
  </aside>

  <main class="conteudo">

    <div class="pagina-titulo">
      <h1>👤 Meu Perfil</h1>
      <p>Informações da sua conta</p>
    </div>

    <?= Flash::renderizar() ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px,1fr));
                gap:var(--espaco-lg);">

      <!-- Card de dados pessoais -->
      <div class="card">
        <h2 style="font-size:1rem; margin-bottom:var(--espaco-lg);
                   color:var(--verde-escuro);">
          📋 Dados Acadêmicos
        </h2>

        <div style="display:flex; flex-direction:column; gap:var(--espaco-md);">

          <?php
          $campos = [
            'Nome completo' => $aluno['nome'],
            'Matrícula'     => $aluno['matricula'],
            'Turma'         => $aluno['turma'],
            'Turno'         => ucfirst($aluno['turno']),
            'E-mail'        => $aluno['email'],
          ];
          foreach ($campos as $label => $valor): ?>
          <div style="display:flex; flex-direction:column; gap:2px;">
            <span style="font-size:.75rem; font-weight:600; text-transform:uppercase;
                         letter-spacing:.05em; color:var(--texto-suave);">
              <?= $label ?>
            </span>
            <span style="font-size:.95rem; color:var(--texto);">
              <?= htmlspecialchars($valor) ?>
            </span>
          </div>
          <?php endforeach; ?>

        </div>
      </div>

      <!-- Card de troca de senha -->
      <div class="card">
        <h2 style="font-size:1rem; margin-bottom:var(--espaco-lg);
                   color:var(--verde-escuro);">
          🔒 Alterar Senha
        </h2>

        <form method="POST" action="perfil.php" novalidate id="formSenha">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

          <div class="form-grupo">
            <label class="form-label form-label--obrigatorio" for="senha_atual">
              Senha atual
            </label>
            <div class="input-senha-wrapper">
              <input type="password" id="senha_atual" name="senha_atual"
                     class="form-input" required autocomplete="current-password">
              <button type="button" class="btn-toggle-senha"
                      data-alvo="senha_atual" aria-label="Mostrar senha">👁</button>
            </div>
          </div>

          <div class="form-grupo">
            <label class="form-label form-label--obrigatorio" for="nova_senha">
              Nova senha
            </label>
            <div class="input-senha-wrapper">
              <input type="password" id="nova_senha" name="nova_senha"
                     class="form-input" required autocomplete="new-password"
                     minlength="8">
              <button type="button" class="btn-toggle-senha"
                      data-alvo="nova_senha" aria-label="Mostrar senha">👁</button>
            </div>
            <p class="form-erro-msg" style="color:var(--texto-suave);">
              Mínimo 8 caracteres, com letras e números.
            </p>
          </div>

          <div class="form-grupo">
            <label class="form-label form-label--obrigatorio" for="confirmar_senha">
              Confirmar nova senha
            </label>
            <div class="input-senha-wrapper">
              <input type="password" id="confirmar_senha" name="confirmar_senha"
                     class="form-input" required autocomplete="new-password">
              <button type="button" class="btn-toggle-senha"
                      data-alvo="confirmar_senha" aria-label="Mostrar senha">👁</button>
            </div>
            <!-- Feedback de correspondência em tempo real -->
            <p class="form-erro-msg" id="msgConfirmacao" style="display:none;"></p>
          </div>

          <button type="submit" class="btn btn--primario btn--bloco mt-md">
            🔒 Atualizar Senha
          </button>
        </form>
      </div>

    </div>
  </main>
</div>

<script src="../assets/js/main.js"></script>
<script>
// Validação em tempo real da confirmação de senha
(function() {
  const nova       = document.getElementById('nova_senha');
  const confirmar  = document.getElementById('confirmar_senha');
  const msg        = document.getElementById('msgConfirmacao');

  function verificar() {
    if (!confirmar.value) { msg.style.display = 'none'; return; }

    if (nova.value !== confirmar.value) {
      msg.textContent    = 'As senhas não coincidem.';
      msg.style.color    = 'var(--erro)';
      msg.style.display  = 'block';
    } else {
      msg.textContent    = '✅ Senhas coincidem.';
      msg.style.color    = 'var(--sucesso)';
      msg.style.display  = 'block';
    }
  }

  nova.addEventListener('input', verificar);
  confirmar.addEventListener('input', verificar);
})();
</script>
</body>
</html>
