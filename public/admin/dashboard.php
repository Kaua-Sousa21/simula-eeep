<?php
// public/admin/dashboard.php
// ============================================================

require_once '../../src/config/config.php';
require_once '../../src/config/database.php';
require_once '../../src/helpers/Session.php';
require_once '../../src/helpers/Redirect.php';
require_once '../../src/helpers/Flash.php';

Session::iniciar();

if (!Session::autenticado() || !Session::isAdmin()) {
    Redirect::semPermissao();
}

$db = Database::getInstance()->getConnection();


// Estatísticas gerais do sistema
$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM alunos)                          AS total_alunos,
        (SELECT COUNT(*) FROM simulados WHERE ativo = 1)       AS total_simulados,
        (SELECT COUNT(*) FROM resultados)                      AS total_resultados,
        (SELECT AVG(nota_total) FROM resultados)               AS media_geral,
        (SELECT COUNT(*) FROM logs_upload WHERE
            DATE(enviado_em) = CURDATE())                      AS uploads_hoje
")->fetch();

// Últimos uploads
$ultimosUploads = $db->query("
    SELECT
        l.enviado_em,
        l.nome_arquivo,
        l.linhas_ok,
        l.linhas_erro,
        l.status,
        s.titulo AS simulado,
        u.email  AS admin_email
    FROM logs_upload l
    INNER JOIN simulados s ON s.id = l.simulado_id
    INNER JOIN usuarios  u ON u.id = l.admin_id
    ORDER BY l.enviado_em DESC
    LIMIT 8
")->fetchAll();

// Simulados recentes
$simuladosRecentes = $db->query("
    SELECT
        s.id,
        s.titulo,
        s.data_aplicacao,
        COUNT(r.id)      AS total_resultados,
        AVG(r.nota_total) AS media_notas
    FROM simulados s
    LEFT JOIN resultados r ON r.simulado_id = s.id
    WHERE s.ativo = 1
    GROUP BY s.id
    ORDER BY s.data_aplicacao DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin — Simula EEEP</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
      <div style="margin-bottom:8px; color:rgba(255,255,255,.85); font-size:.85rem;">
        <?= htmlspecialchars(Session::get('usuario_email')) ?>
      </div>
      <a href="../logout.php" class="btn btn--secundario btn--sm"
         style="color:#a5d6a7; border-color:#a5d6a7; width:100%;">Sair</a>
    </div>
  </aside>

  <main class="conteudo">

    <div class="pagina-titulo">
      <h1>Painel Administrativo</h1>
      <p>Visão geral do sistema — <?= date('d/m/Y') ?></p>
    </div>

    <?= Flash::renderizar() ?>

    <!-- Cards de estatísticas -->
    <div class="cards-grid">

      <div class="card card--destaque">
        <div class="card__titulo">Total de Alunos</div>
        <div class="card__valor"><?= number_format((int)$stats['total_alunos']) ?></div>
        <div class="card__sub">cadastrados no sistema</div>
      </div>

      <div class="card card--destaque">
        <div class="card__titulo">Simulados Ativos</div>
        <div class="card__valor"><?= (int)$stats['total_simulados'] ?></div>
        <div class="card__sub">aplicados até hoje</div>
      </div>

      <div class="card card--destaque">
        <div class="card__titulo">Resultados Importados</div>
        <div class="card__valor"><?= number_format((int)$stats['total_resultados']) ?></div>
        <div class="card__sub">registros no banco</div>
      </div>

      <div class="card card--destaque">
        <div class="card__titulo">Média Geral do Sistema</div>
        <div class="card__valor">
          <?= $stats['media_geral']
              ? number_format((float)$stats['media_geral'], 1, ',', '.')
              : '—' ?>
        </div>
        <div class="card__sub">nota média de todos os alunos</div>
      </div>

    </div>

    <!-- Ações rápidas -->
    <div class="card mb-lg">
      <h2 style="font-size:1rem; margin-bottom:var(--espaco-md);">⚡ Ações Rápidas</h2>
      <div class="flex gap-md" style="flex-wrap:wrap;">
        <a href="upload.php"    class="btn btn--primario">📤 Importar Planilha</a>
        <a href="simulados.php" class="btn btn--secundario">📝 Novo Simulado</a>
        <a href="alunos.php"    class="btn btn--secundario">👥 Cadastrar Aluno</a>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
                gap:var(--espaco-lg);">

      <!-- Simulados recentes -->
      <div class="tabela-wrapper">
        <div class="tabela-wrapper__header">
          <h2 style="font-size:1rem;">📝 Simulados Recentes</h2>
          <a href="simulados.php" class="btn btn--secundario btn--sm">Ver todos</a>
        </div>
        <div class="tabela-scroll">
          <table class="tabela">
            <thead>
              <tr>
                <th>Simulado</th>
                <th>Data</th>
                <th>Alunos</th>
                <th>Média</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($simuladosRecentes)): ?>
              <tr>
                <td colspan="4" style="text-align:center; color:var(--texto-suave);
                                       padding:var(--espaco-lg);">
                  Nenhum simulado cadastrado.
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($simuladosRecentes as $s): ?>
              <tr>
                <td data-label="Simulado">
                  <?= htmlspecialchars($s['titulo']) ?>
                </td>
                <td data-label="Data">
                  <?= date('d/m/Y', strtotime($s['data_aplicacao'])) ?>
                </td>
                <td data-label="Alunos">
                  <?= (int)$s['total_resultados'] ?>
                </td>
                <td data-label="Média">
                  <?= $s['media_notas']
                      ? number_format((float)$s['media_notas'], 1, ',', '.')
                      : '—' ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Histórico de uploads -->
      <div class="tabela-wrapper">
        <div class="tabela-wrapper__header">
          <h2 style="font-size:1rem;">📤 Últimos Uploads</h2>
        </div>
        <div class="tabela-scroll">
          <table class="tabela">
            <thead>
              <tr>
                <th>Arquivo</th>
                <th>Simulado</th>
                <th>Ok</th>
                <th>Erros</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($ultimosUploads)): ?>
              <tr>
                <td colspan="5" style="text-align:center; color:var(--texto-suave);
                                       padding:var(--espaco-lg);">
                  Nenhum upload realizado.
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($ultimosUploads as $log):
                $statusCor = match($log['status']) {
                  'sucesso' => 'alta',
                  'parcial' => 'media',
                  default   => 'baixa',
                };
              ?>
              <tr>
                <td data-label="Arquivo" style="font-size:.8rem; font-family:var(--fonte-mono);">
                  <?= htmlspecialchars(basename($log['nome_arquivo'])) ?>
                  <br>
                  <span style="color:var(--texto-suave); font-family:var(--fonte);">
                    <?= date('d/m H:i', strtotime($log['enviado_em'])) ?>
                  </span>
                </td>
                <td data-label="Simulado">
                  <?= htmlspecialchars($log['simulado']) ?>
                </td>
                <td data-label="Ok" style="color:var(--sucesso); font-weight:600;">
                  <?= (int)$log['linhas_ok'] ?>
                </td>
                <td data-label="Erros" style="color:var(--erro); font-weight:600;">
                  <?= (int)$log['linhas_erro'] ?>
                </td>
                <td data-label="Status">
                  <span class="badge-nota badge-nota--<?= $statusCor ?>">
                    <?= ucfirst($log['status']) ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
