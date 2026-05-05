<?php
// public/aluno/resultados.php
// ============================================================

require_once '../../src/config/config.php';
require_once '../../src/config/database.php';
require_once '../../src/helpers/Session.php';
require_once '../../src/helpers/Redirect.php';
require_once '../../src/helpers/Flash.php';
require_once '../../src/models/Aluno.php';
require_once '../../src/models/Resultado.php';

Session::iniciar();

if (!Session::autenticado() || Session::isAdmin()) {
    Redirect::semPermissao();
}

$alunoModel     = new Aluno();
$resultadoModel = new Resultado();

$aluno      = $alunoModel->buscarPorUsuarioId(Session::get('usuario_id'));
$resultados = $resultadoModel->listarPorAluno($aluno['id']);
$stats      = $alunoModel->obterEstatisticas($aluno['id']);

// Médias por área para o card de resumo
$areas = [
    'Linguagens' => number_format((float)($stats['media_linguagens'] ?? 0), 1, ',', '.'),
    'Humanas'    => number_format((float)($stats['media_humanas']    ?? 0), 1, ',', '.'),
    'Natureza'   => number_format((float)($stats['media_natureza']   ?? 0), 1, ',', '.'),
    'Matemática' => number_format((float)($stats['media_matematica'] ?? 0), 1, ',', '.'),
    'Redação'    => number_format((float)($stats['media_redacao']    ?? 0), 1, ',', '.'),
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meus Resultados — Simula EEEP</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>

<header class="header-mobile">
  <button class="btn-menu" id="btnMenu" aria-label="Abrir menu">☰</button>
  <span class="header-mobile__titulo">Meus Resultados</span>
  <span style="width:40px"></span>
</header>

<div class="overlay-sidebar" id="overlaySidebar"></div>

<div class="pagina">

  <!-- Sidebar -->
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
      <div style="margin-bottom:8px; color:rgba(255,255,255,.85); font-size:.85rem;">
        <?= htmlspecialchars($aluno['nome']) ?>
      </div>
      <a href="../logout.php" class="btn btn--secundario btn--sm"
         style="color:#a5d6a7; border-color:#a5d6a7; width:100%;">Sair</a>
    </div>
  </aside>

  <!-- Conteúdo -->
  <main class="conteudo">

    <div class="pagina-titulo">
      <h1>📋 Meus Resultados</h1>
      <p>Histórico completo de desempenho nos simulados</p>
    </div>

    <?= Flash::renderizar() ?>

    <!-- Cards de médias por área -->
    <?php if ($stats['total_simulados'] > 0): ?>
    <div class="cards-grid mb-lg">
      <?php foreach ($areas as $nome => $media): ?>
      <div class="card" style="text-align:center; padding: var(--espaco-md);">
        <div class="card__titulo"><?= $nome ?></div>
        <div class="card__valor" style="font-size:1.5rem;"><?= $media ?></div>
        <div class="card__sub">média geral</div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tabela de resultados -->
    <div class="tabela-wrapper">
      <div class="tabela-wrapper__header">
        <h2 style="font-size:1rem;">
          Todos os Simulados
          <span style="font-weight:400; color:var(--texto-suave); font-size:.85rem;">
            (<?= count($resultados) ?> registro(s))
          </span>
        </h2>
      </div>

      <?php if (empty($resultados)): ?>
        <div style="padding:var(--espaco-2xl); text-align:center; color:var(--texto-suave);">
          <p style="font-size:2.5rem; margin-bottom:var(--espaco-md);">📭</p>
          <p style="font-size:1rem;">Nenhum resultado disponível ainda.</p>
          <p style="font-size:.85rem; margin-top:var(--espaco-xs);">
            Seus resultados aparecerão aqui após a importação pelo coordenador.
          </p>
        </div>
      <?php else: ?>
        <div class="tabela-scroll">
          <table class="tabela">
            <thead>
              <tr>
                <th>Simulado</th>
                <th>Data</th>
                <th>Linguagens</th>
                <th>Humanas</th>
                <th>Natureza</th>
                <th>Matemática</th>
                <th>Redação</th>
                <th>Nota Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($resultados as $r):
                $total  = (float) $r['nota_total'];
                $classe = $total >= 700 ? 'alta' : ($total >= 500 ? 'media' : 'baixa');
              ?>
              <tr>
                <td data-label="Simulado">
                  <strong><?= htmlspecialchars($r['simulado']) ?></strong>
                </td>
                <td data-label="Data">
                  <?= date('d/m/Y', strtotime($r['data_aplicacao'])) ?>
                </td>
                <td data-label="Linguagens">
                  <?= number_format((float)$r['linguagens'], 1, ',', '.') ?>
                </td>
                <td data-label="Humanas">
                  <?= number_format((float)$r['humanas'], 1, ',', '.') ?>
                </td>
                <td data-label="Natureza">
                  <?= number_format((float)$r['natureza'], 1, ',', '.') ?>
                </td>
                <td data-label="Matemática">
                  <?= number_format((float)$r['matematica'], 1, ',', '.') ?>
                </td>
                <td data-label="Redação">
                  <?= number_format((float)$r['redacao'], 1, ',', '.') ?>
                </td>
                <td data-label="Nota Total">
                  <span class="badge-nota badge-nota--<?= $classe ?>">
                    <?= number_format($total, 1, ',', '.') ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>

            <!-- Rodapé com médias -->
            <tfoot>
              <tr style="background:var(--verde-palido); font-weight:600;">
                <td colspan="2" data-label="">Média Geral</td>
                <td data-label="Linguagens">
                  <?= number_format((float)($stats['media_linguagens'] ?? 0), 1, ',', '.') ?>
                </td>
                <td data-label="Humanas">
                  <?= number_format((float)($stats['media_humanas'] ?? 0), 1, ',', '.') ?>
                </td>
                <td data-label="Natureza">
                  <?= number_format((float)($stats['media_natureza'] ?? 0), 1, ',', '.') ?>
                </td>
                <td data-label="Matemática">
                  <?= number_format((float)($stats['media_matematica'] ?? 0), 1, ',', '.') ?>
                </td>
                <td data-label="Redação">
                  <?= number_format((float)($stats['media_redacao'] ?? 0), 1, ',', '.') ?>
                </td>
                <td data-label="Total">
                  <span class="badge-nota badge-nota--alta">
                    <?= number_format((float)($stats['media_geral'] ?? 0), 1, ',', '.') ?>
                  </span>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
