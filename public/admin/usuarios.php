<?php
require_once '../../src/config/config.php';
require_once '../../src/config/database.php';
require_once '../../src/helpers/Session.php';
require_once '../../src/helpers/Redirect.php';
require_once '../../src/helpers/Flash.php';

Session::iniciar();

// 🔒 apenas admin
if (!Session::autenticado() || !Session::isAdmin()) {
    Redirect::semPermissao();
}

$db = Database::getInstance()->getConnection();


// ======================================================
// 🗑️ EXCLUIR ADMIN
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        Flash::erro('Requisição inválida.');
        Redirect::para(BASE_URL . '/admin/usuarios.php');
    }

    $id = (int) $_POST['delete_id'];

    // ❌ impedir deletar a si mesmo
    if ($id === Session::get('usuario_id')) {
        Flash::erro('Você não pode excluir seu próprio usuário.');
        Redirect::para(BASE_URL . '/admin/usuarios.php');
    }

    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'admin'");
    $stmt->execute([$id]);

    Flash::sucesso('Admin removido com sucesso.');
    Redirect::para(BASE_URL . '/admin/usuarios.php');
}


// ======================================================
// ➕ CRIAR ADMIN (SENHA TEMPORÁRIA)
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        Flash::erro('Requisição inválida.');
        Redirect::para(BASE_URL . '/admin/usuarios.php');
    }

    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        Flash::erro('Informe o email.');
        Redirect::para(BASE_URL . '/admin/usuarios.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Flash::erro('Email inválido.');
        Redirect::para(BASE_URL . '/admin/usuarios.php');
    }

    // 🔍 verificar duplicado
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        Flash::erro('Email já cadastrado.');
        Redirect::para(BASE_URL . '/admin/usuarios.php');
    }

    // 🔥 gerar senha temporária
    $senhaTemp = bin2hex(random_bytes(4)); // ex: a7f3c9d1
    $senhaHash = password_hash($senhaTemp, PASSWORD_DEFAULT);

    // 🔥 salvar com senha_hash
    $stmt = $db->prepare("
        INSERT INTO usuarios (email, senha_hash, tipo, primeiro_acesso)
        VALUES (?, ?, 'admin', 1)
    ");

    $stmt->execute([$email, $senhaHash]);

    Flash::sucesso("Admin criado! Senha temporária: $senhaTemp");

    Redirect::para(BASE_URL . '/admin/usuarios.php');
}


// ======================================================
// 📋 LISTAR ADMINS
// ======================================================
$admins = $db->query("
    SELECT id, email, primeiro_acesso
    FROM usuarios
    WHERE tipo = 'admin'
    ORDER BY id DESC
")->fetchAll();


// CSRF
$csrfToken = bin2hex(random_bytes(32));
Session::set('csrf_token', $csrfToken);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Administradores</title>
<link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <header class="header-mobile">
  <button class="btn-menu" id="btnMenu" aria-label="Abrir menu">☰</button>
  <span class="header-mobile__titulo">Admin — Simula EEEP</span>
  <span style="width:40px"></span>
</header>

<div class="overlay-sidebar" id="overlaySidebar"></div>

<div class="pagina">

  <!-- Sidebar Admin -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar__logo">
      <div>
        <div class="sidebar__logo-texto">Simula EEEP</div>
        <div class="sidebar__logo-sub">Administração</div>
      </div>
    </div>

    <nav class="sidebar__nav">
      <a href="dashboard.php" class="nav__item">
        <span class="nav__icone">Dashboard</span>
      </a>
      <a href="alunos.php" class="nav__item">
        <span class="nav__icone">Alunos</span>
      </a>
      <a href="simulados.php" class="nav__item nav__item--ativo">
        <span class="nav__icone">Simulados</span>
      </a>
      <a href="usuarios.php" class="nav__item">
      <span class="nav__icone">Admins</span>
      </a>
      <a href="upload.php" class="nav__item">
        <span class="nav__icone">Upload Planilha</span>
      </a>
    </nav>

    <div class="sidebar__rodape">
      <a href="../logout.php" class="btn btn--secundario btn--sm"
         style="color:#a5d6a7; border-color:#a5d6a7; width:100%;">
        Sair
      </a>
    </div>
  </aside>

<div class="conteudo">

<h1>👥 Administradores</h1>


<?= Flash::renderizar() ?>

<!-- ======================================================
➕ CRIAR ADMIN
====================================================== -->
<div class="card">
    <h2>Novo Admin</h2>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="form-grupo">
            <label>Email</label>
            <input type="email" name="email" class="form-input" required>
        </div>

        <button type="submit" class="btn btn--primario mt-md">
            ➕ Criar Admin
        </button>
    </form>
</div>


<!-- ======================================================
📋 LISTA DE ADMINS
====================================================== -->
<div class="card mt-lg">
    <h2>Admins cadastrados</h2>

    <?php if (empty($admins)): ?>
        <p>Nenhum admin cadastrado.</p>
    <?php else: ?>

    <table class="tabela">
        <thead>
            <tr>
                <th>Email</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['email']) ?></td>

                <td>
                    <?= $a['primeiro_acesso']
                        ? '🔑 Primeiro acesso'
                        : '✅ Ativo' ?>
                </td>

                <td>
                    <?php if ($a['id'] !== Session::get('usuario_id')): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="delete_id" value="<?= $a['id'] ?>">

                            <button type="submit" class="btn btn--secundario btn--sm"
                                onclick="return confirm('Deseja excluir este admin?')">
                                🗑️
                            </button>
                        </form>
                    <?php else: ?>
                        <span style="color:#999;">Você</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>

</div>

</body>
</html>