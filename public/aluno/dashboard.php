<?php
// public/aluno/dashboard.php
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

$aluno    = $alunoModel->buscarPorUsuarioId(Session::get('usuario_id'));
$stats    = $alunoModel->obterEstatisticas($aluno['id']);
$evolucao = $resultadoModel->evolucaoPorAluno($aluno['id']);

$dadosGrafico = array_map(fn($r) => [
    'simulado'   => $r['titulo'],
    'nota_total' => (float) $r['nota_total'],
    'linguagens' => (float) $r['linguagens'],
    'humanas'    => (float) $r['humanas'],
    'natureza'   => (float) $r['natureza'],
    'matematica' => (float) $r['matematica'],
    'redacao'    => (float) $r['redacao'],
], $evolucao);

$dadosGraficoJSON = json_encode($dadosGrafico, JSON_HEX_TAG | JSON_HEX_AMP);

$mediaGeral = $stats['total_simulados'] > 0
    ? number_format((float)$stats['media_geral'], 1, ',', '.')
    : '—';

$ultimaNota = $stats['ultima_nota']
    ? number_format((float)$stats['ultima_nota'], 1, ',', '.')
    : '—';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Simula EEEP</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<header class="header-mobile">
  <button class="btn-menu" id="btnMenu" aria-label="Abrir menu">Menu</button>
  <span class="header-mobile__titulo">Simula EEEP</span>
  <span style="width:40px"></span>
</header>

<div class="overlay-sidebar" id="overlaySidebar"></div>

<div class="pagina">

  <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">

    <div class="sidebar__logo">
      <img
        src="../assets/img/logo.png"
        alt="Simula EEEP"
        style="width:120px; height:auto; display:block; margin: 0 auto var(--espaco-xs);"
        onerror="this.style.display='none'"
      >
      <div class="sidebar__logo-sub" style="text-align:center;">Area do Aluno</div>
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
      <div style="margin-bottom: 8px; color: rgba(255,255,255,.85); font-size: .85rem;">
        <?= htmlspecialchars($aluno['nome']) ?>
      </div>
      <div style="margin-bottom: 12px; font-size: .75rem; color: rgba(255,255,255,.6);">
        Turma <?= htmlspecialchars($aluno['turma']) ?>
        · Mat. <?= htmlspecialchars($aluno['matricula']) ?>
      </div>
      <a href="../logout.php" class="btn btn--secundario btn--sm"
         style="color:#a5d6a7; border-color:#a5d6a7; width:100%;">
        Sair
      </a>
    </div>
  </aside>

  <main class="conteudo">

    <?= Flash::renderizar() ?>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:var(--espaco-xl); flex-wrap:wrap; gap:var(--espaco-md);">
      <div class="pagina-titulo" style="margin-bottom:0;">
        <h1>Ola, <?= htmlspecialchars(explode(' ', $aluno['nome'])[0]) ?>!</h1>
        <p>Acompanhe seu desempenho nos simulados</p>
      </div>
      <img
        src="../assets/img/mascote.png"
        alt="Mascote Simula EEEP"
        style="width:120px; height:auto;"
        onerror="this.style.display='none'"
      >
    </div>

    <div class="cards-grid">

      <div class="card card--destaque">
        <div class="card__titulo">Media Geral</div>
        <div class="card__valor"><?= $mediaGeral ?></div>
        <div class="card__sub">
          <?= $stats['total_simulados'] ?> simulado(s) realizado(s)
        </div>
      </div>

      <div class="card card--destaque">
        <div class="card__titulo">Ultimo Simulado</div>
        <div class="card__valor"><?= $ultimaNota ?></div>
        <div class="card__sub">Nota total (media das 5 areas)</div>
      </div>

      <div class="card card--destaque">
        <div class="card__titulo">Melhor Area</div>
        <div class="card__valor" style="font-size:1.25rem; color: var(--verde-medio);">
          <?= $stats['total_simulados'] > 0
              ? htmlspecialchars($stats['melhor_area'])
              : '—' ?>
        </div>
        <div class="card__sub">
          <?php if ($stats['total_simulados'] > 0):
            $mediaArea = 'media_' . strtolower(
              iconv('UTF-8','ASCII//TRANSLIT', $stats['melhor_area'])
            );
            $mediaArea = preg_replace('/[^a-z_]/', '', $mediaArea);
          ?>
            Media: <?= number_format((float)($stats[$mediaArea] ?? 0), 1, ',', '.') ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="card card--destaque">
        <div class="card__titulo">Area para Melhorar</div>
        <div class="card__valor" style="font-size:1.25rem; color: #e57373;">
          <?= $stats['total_simulados'] > 0
              ? htmlspecialchars($stats['pior_area'])
              : '—' ?>
        </div>
        <div class="card__sub">Foco nos estudos!</div>
      </div>

    </div>

    <?php if (count($evolucao) > 0): ?>
    <div class="card mt-lg">
      <div class="tabela-wrapper__header" style="padding: 0 0 var(--espaco-md);">
        <h2 style="font-size: 1rem;">Evolucao de Desempenho</h2>
      </div>
      <div style="position: relative; height: 280px;">
        <canvas id="graficoEvolucao" aria-label="Grafico de evolucao de notas"></canvas>
      </div>
    </div>
    <?php endif; ?>

    <div class="tabela-wrapper mt-lg">
      <div class="tabela-wrapper__header">
        <h2 style="font-size: 1rem;">Ultimos Resultados</h2>
        <a href="resultados.php" class="btn btn--secundario btn--sm">Ver todos</a>
      </div>

      <?php
        $ultimos = array_slice($evolucao, -5);
        $ultimos = array_reverse($ultimos);
      ?>

      <?php if (empty($ultimos)): ?>
        <div style="padding: var(--espaco-xl); text-align: center; color: var(--texto-suave);">
          <p>Nenhum resultado disponivel ainda.</p>
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
                <th>Matematica</th>
                <th>Redacao</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ultimos as $r): ?>
              <tr>
                <td data-label="Simulado"><?= htmlspecialchars($r['titulo']) ?></td>
                <td data-label="Data"><?= date('d/m/Y', strtotime($r['data_aplicacao'])) ?></td>
                <td data-label="Linguagens"><?= number_format((float)$r['linguagens'], 1, ',', '.') ?></td>
                <td data-label="Humanas"><?= number_format((float)$r['humanas'], 1, ',', '.') ?></td>
                <td data-label="Natureza"><?= number_format((float)$r['natureza'], 1, ',', '.') ?></td>
                <td data-label="Matematica"><?= number_format((float)$r['matematica'], 1, ',', '.') ?></td>
                <td data-label="Redacao"><?= number_format((float)$r['redacao'], 1, ',', '.') ?></td>
                <td data-label="Total" class="nota-total">
                  <?php
                    $total = (float)$r['nota_total'];
                    $classe = $total >= 700 ? 'alta' : ($total >= 500 ? 'media' : 'baixa');
                  ?>
                  <span class="badge-nota badge-nota--<?= $classe ?>">
                    <?= number_format($total, 1, ',', '.') ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
  const dadosGrafico = <?= $dadosGraficoJSON ?>;
</script>
<script src="../assets/js/charts.js"></script>

</body>
</html>
