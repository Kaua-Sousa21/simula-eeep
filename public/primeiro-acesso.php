<?php
// public/primeiro-acesso.php
// ============================================================

require_once '../src/config/config.php';
require_once '../src/config/database.php';
require_once '../src/helpers/Session.php';
require_once '../src/helpers/Redirect.php';
require_once '../src/helpers/Flash.php';
require_once '../src/models/Usuario.php';
require_once '../src/controllers/AuthController.php';

Session::iniciar();

// Só acessa esta página se estiver logado e for primeiro acesso
if (!Session::autenticado()) {
    Redirect::voltarLogin();
}

// Verifica se realmente é primeiro acesso
$usuarioModel = new Usuario();
$usuario      = $usuarioModel->buscarPorId(Session::get('usuario_id'));

if (!$usuario || !$usuario['primeiro_acesso']) {
    $destino = Session::isAdmin()
        ? BASE_URL . '/admin/dashboard.php'
        : BASE_URL . '/aluno/dashboard.php';
    Redirect::para($destino);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        Flash::erro('Requisição inválida.');
        Redirect::para(BASE_URL . '/primeiro-acesso.php');
    }

    $auth      = new AuthController();
    $resultado = $auth->trocarSenha(
        Session::get('usuario_id'),
        $_POST['senha_atual']    ?? '',
        $_POST['nova_senha']     ?? '',
        $_POST['confirmar_senha']?? ''
    );

    if ($resultado['sucesso']) {
        Flash::sucesso('Senha definida com sucesso! Bem-vindo ao Simula EEEP.');
        $destino = Session::isAdmin()
            ? BASE_URL . '/admin/dashboard.php'
            : BASE_URL . '/aluno/dashboard.php';
        Redirect::para($destino);
    } else {
        Flash::erro($resultado['mensagem']);
        Redirect::para(BASE_URL . '/primeiro-acesso.php');
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
  <title>Definir Nova Senha — Simula EEEP</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<main class="login-pagina">
  <div class="login-card" style="max-width:460px;">

    <div class="login-card__logo">
      <div class="login-card__logo-icone">🔐</div>
      <h1 class="login-card__titulo">Defina sua Senha</h1>
      <p class="login-card__subtitulo">
        Por segurança, você precisa criar uma nova senha antes de continuar.
      </p>
    </div>

    <?= Flash::renderizar() ?>

    <!-- Banner informativo -->
    <div class="alert alert--aviso">
      ⚠️ Esta é sua senha temporária de primeiro acesso.
      Crie uma senha pessoal e segura para continuar.
    </div>

    <form method="POST" action="primeiro-acesso.php" novalidate style="margin-top:var(--espaco-md);">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <div class="form-grupo">
        <label class="form-label form-label--obrigatorio" for="senha_atual">
          Senha temporária (atual)
        </label>
        <div class="input-senha-wrapper">
          <input type="password" id="senha_atual" name="senha_atual"
                 class="form-input" required autocomplete="current-password"
                 placeholder="Digite a senha temporária recebida">
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
                 placeholder="Mínimo 8 caracteres" minlength="8">
          <button type="button" class="btn-toggle-senha"
                  data-alvo="nova_senha" aria-label="Mostrar senha">👁</button>
        </div>

        <!-- Indicador de força da senha -->
        <div id="forcaSenha" style="margin-top:var(--espaco-xs); display:none;">
          <div style="height:4px; border-radius:var(--raio-full);
                      background:var(--cinza-claro); overflow:hidden;">
            <div id="forcaBarra" style="height:100%; width:0%;
                                        border-radius:var(--raio-full);
                                        transition:all .3s;"></div>
          </div>
          <p id="forcaTexto" style="font-size:.75rem; margin-top:4px;"></p>
        </div>
      </div>

      <div class="form-grupo">
        <label class="form-label form-label--obrigatorio" for="confirmar_senha">
          Confirmar nova senha
        </label>
        <div class="input-senha-wrapper">
          <input type="password" id="confirmar_senha" name="confirmar_senha"
                 class="form-input" required autocomplete="new-password"
                 placeholder="Repita a nova senha">
          <button type="button" class="btn-toggle-senha"
                  data-alvo="confirmar_senha" aria-label="Mostrar senha">👁</button>
        </div>
        <p id="msgMatch" style="font-size:.8rem; margin-top:4px; display:none;"></p>
      </div>

      <button type="submit" class="btn btn--primario btn--lg btn--bloco mt-md">
        ✅ Confirmar e Entrar
      </button>
    </form>

  </div>
</main>

<script src="assets/js/main.js"></script>
<script>
// ── Indicador de força da senha ──────────────────────────────
(function() {
  const novaSenha  = document.getElementById('nova_senha');
  const confirmar  = document.getElementById('confirmar_senha');
  const forcaDiv   = document.getElementById('forcaSenha');
  const forcaBarra = document.getElementById('forcaBarra');
  const forcaTexto = document.getElementById('forcaTexto');
  const msgMatch   = document.getElementById('msgMatch');

  const niveis = [
    { min: 0,  cor: '#ef5350', texto: 'Muito fraca',  pct: '20%'  },
    { min: 1,  cor: '#ff7043', texto: 'Fraca',        pct: '40%'  },
    { min: 2,  cor: '#ffa726', texto: 'Razoável',     pct: '60%'  },
    { min: 3,  cor: '#66bb6a', texto: 'Boa',          pct: '80%'  },
    { min: 4,  cor: '#2e7d32', texto: 'Forte',        pct: '100%' },
  ];

  function calcularForca(senha) {
    let pontos = 0;
    if (senha.length >= 8)              pontos++;
    if (senha.length >= 12)             pontos++;
    if (/[A-Z]/.test(senha))            pontos++;
    if (/[0-9]/.test(senha))            pontos++;
    if (/[^A-Za-z0-9]/.test(senha))     pontos++;
    return Math.min(pontos, 4);
  }

  novaSenha.addEventListener('input', () => {
    const senha = novaSenha.value;

    if (!senha) {
      forcaDiv.style.display = 'none';
      return;
    }

    forcaDiv.style.display = 'block';
    const forca  = calcularForca(senha);
    const nivel  = niveis[forca];

    forcaBarra.style.width      = nivel.pct;
    forcaBarra.style.background = nivel.cor;
    forcaTexto.textContent      = `Força: ${nivel.texto}`;
    forcaTexto.style.color      = nivel.cor;

    verificarMatch();
  });

  confirmar.addEventListener('input', verificarMatch);

  function verificarMatch() {
    if (!confirmar.value) { msgMatch.style.display = 'none'; return; }

    msgMatch.style.display = 'block';
    if (novaSenha.value === confirmar.value) {
      msgMatch.textContent = '✅ Senhas coincidem.';
      msgMatch.style.color = 'var(--sucesso)';
    } else {
      msgMatch.textContent = '❌ As senhas não coincidem.';
      msgMatch.style.color = 'var(--erro)';
    }
  }
})();
</script>
</body>
</html>
