<?php
// public/admin/upload.php
// ============================================================

require_once '../../src/config/config.php';
require_once '../../src/config/database.php';
require_once '../../src/helpers/Session.php';
require_once '../../src/helpers/Redirect.php';
require_once '../../src/helpers/Flash.php';
require_once '../../src/models/Simulado.php';
require_once '../../src/models/Aluno.php';
require_once '../../src/models/Resultado.php';
require_once '../../src/services/PlanilhaService.php';
require_once '../../src/controllers/UploadController.php';

Session::iniciar();

// Proteção de rota: apenas administradores
if (!Session::autenticado() || !Session::isAdmin()) {
    Redirect::semPermissao();
}

// Busca simulados para o select
$db = Database::getInstance()->getConnection();
$simulados = $db->query(
    "SELECT id, titulo, data_aplicacao FROM simulados
     WHERE ativo = 1 ORDER BY data_aplicacao DESC"
)->fetchAll();

$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        Flash::erro('Requisição inválida.');
        Redirect::para(BASE_URL . '/admin/upload.php');
    }

    $simuladoId = filter_input(INPUT_POST, 'simulado_id', FILTER_VALIDATE_INT);

    if (!$simuladoId) {
        Flash::erro('Selecione um simulado válido.');
        Redirect::para(BASE_URL . '/admin/upload.php');
    }

    if (!isset($_FILES['planilha']) || $_FILES['planilha']['error'] === UPLOAD_ERR_NO_FILE) {
        Flash::erro('Nenhum arquivo selecionado.');
        Redirect::para(BASE_URL . '/admin/upload.php');
    }

    $controller = new UploadController();
    $resultado  = $controller->processar(
        $_FILES['planilha'],
        $simuladoId,
        Session::get('usuario_id')
    );
}

// Gera token CSRF
$csrfToken = bin2hex(random_bytes(32));
Session::set('csrf_token', $csrfToken);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload de Planilha — Admin | Simula EEEP</title>
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

  <!-- Conteúdo -->
  <main class="conteudo">

    <div class="pagina-titulo">
      <h1>📤 Upload de Planilha</h1>
      <p>Importe os resultados do simulado via arquivo CSV ou XLSX</p>
    </div>

    <?= Flash::renderizar() ?>

    <!-- Resultado do processamento -->
    <?php if ($resultado !== null): ?>
      <div class="card mb-lg">
        <h2 style="margin-bottom: var(--espaco-md); font-size: 1rem;">
          Resultado do Processamento
        </h2>

        <?php if ($resultado['sucesso']): ?>
          <div class="alert alert--sucesso">
            ✅ <strong><?= $resultado['processados'] ?> aluno(s)</strong> importado(s) com sucesso!
          </div>
        <?php else: ?>
          <div class="alert alert--erro">
            ❌ Nenhum registro foi importado. Verifique os erros abaixo.
          </div>
        <?php endif; ?>

        <!-- Resumo numérico -->
        <div class="cards-grid" style="margin-top: var(--espaco-md);">
          <div class="card" style="text-align:center; padding: var(--espaco-md);">
            <div class="card__titulo">Importados</div>
            <div class="card__valor" style="color: var(--sucesso);">
              <?= $resultado['processados'] ?>
            </div>
          </div>
          <div class="card" style="text-align:center; padding: var(--espaco-md);">
            <div class="card__titulo">Com Erro</div>
            <div class="card__valor" style="color: var(--erro);">
              <?= $resultado['erros'] ?>
            </div>
          </div>
        </div>

        <!-- Lista de erros detalhados -->
        <?php if (!empty($resultado['detalhes'])): ?>
          <div style="margin-top: var(--espaco-md);">
            <h3 style="font-size: .9rem; margin-bottom: var(--espaco-sm); color: var(--erro);">
              ⚠️ Erros encontrados:
            </h3>
            <div style="max-height: 250px; overflow-y: auto; background: #fff8f8;
                        border: 1px solid #ffcdd2; border-radius: var(--raio-md);
                        padding: var(--espaco-sm);">
              <?php foreach ($resultado['detalhes'] as $detalhe): ?>
                <div style="padding: 4px 0; border-bottom: 1px solid #ffcdd2;
                            font-size: .8rem; font-family: var(--fonte-mono);">
                  <?php if (is_array($detalhe)): ?>
                    <strong>Linha <?= (int)$detalhe['linha'] ?>:</strong>
                    <?= htmlspecialchars(implode(', ', $detalhe['erros'])) ?>
                  <?php else: ?>
                    <?= htmlspecialchars($detalhe) ?>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Formulário de upload -->
    <div class="card">
      <form method="POST" action="upload.php" enctype="multipart/form-data" id="formUpload">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <!-- Seleção do simulado -->
        <div class="form-grupo">
          <label class="form-label form-label--obrigatorio" for="simulado_id">
            Simulado
          </label>
          <select name="simulado_id" id="simulado_id" class="form-select" required>
            <option value="">— Selecione o simulado —</option>
            <?php foreach ($simulados as $s): ?>
              <option value="<?= (int)$s['id'] ?>">
                <?= htmlspecialchars($s['titulo']) ?>
                (<?= date('d/m/Y', strtotime($s['data_aplicacao'])) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Área de upload drag-and-drop -->
        <div class="form-grupo">
          <label class="form-label form-label--obrigatorio">Arquivo da Planilha</label>

          <div class="upload-area" id="uploadArea" role="button" tabindex="0"
               aria-label="Clique ou arraste um arquivo CSV ou XLSX">
            <div class="upload-area__icone" id="uploadIcone">📂</div>
            <div class="upload-area__titulo" id="uploadTitulo">
              Arraste o arquivo aqui ou clique para selecionar
            </div>
            <p class="upload-area__sub" id="uploadSub">
              Formatos aceitos: CSV ou XLSX · Tamanho máximo: 5MB
            </p>
            <input
              type="file"
              name="planilha"
              id="inputArquivo"
              class="upload-area__input"
              accept=".csv,.xlsx"
              required
            >
            <button type="button" class="btn btn--secundario btn--sm" id="btnSelecionarArquivo">
              Selecionar arquivo
            </button>
          </div>

          <!-- Barra de progresso (visual durante envio) -->
          <div class="upload-progresso" id="uploadProgresso">
            <div class="progresso-barra">
              <div class="progresso-barra__preenchimento" id="progressoBarra"></div>
            </div>
            <p class="progresso-texto" id="progressoTexto">Processando...</p>
          </div>
        </div>

        <!-- Instruções do formato -->
        <div class="alert alert--aviso" style="margin-bottom: var(--espaco-md);">
          <div>
            <strong>📋 Formato esperado da planilha:</strong>
            <br>
            A primeira linha deve conter os cabeçalhos:
            <code style="background: rgba(0,0,0,.08); padding: 2px 6px;
                         border-radius: 4px; font-family: var(--fonte-mono); font-size: .8rem;">
              matricula | linguagens | humanas | natureza | matematica | redacao
            </code>
            <br>
            <small>Separador CSV: ponto e vírgula (;) · Notas: 0 a 1000</small>
          </div>
        </div>

        <div class="flex gap-md" style="flex-wrap: wrap;">
          <button type="submit" class="btn btn--primario btn--lg" id="btnEnviar">
            📤 Importar Resultados
          </button>
          <a href="dashboard.php" class="btn btn--secundario btn--lg">
            Cancelar
          </a>
        </div>

      </form>
    </div>

    <!-- Modelo de planilha para download -->
    <div class="card mt-lg" style="background: var(--verde-palido); border: 1px solid var(--verde-claro);">
      <h3 style="font-size: .95rem; color: var(--verde-escuro); margin-bottom: var(--espaco-sm);">
        💡 Modelo de Planilha
      </h3>
      <p style="font-size: .85rem; color: var(--texto-suave); margin-bottom: var(--espaco-md);">
        Baixe o modelo para preencher corretamente os dados dos alunos.
      </p>
      <a href="../assets/modelo_planilha.csv" download class="btn btn--primario btn--sm">
        ⬇️ Baixar Modelo CSV
      </a>
    </div>

  </main>
</div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/upload.js"></script>
</body>
</html>
