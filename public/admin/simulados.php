
<?php
// public/admin/simulados.php
// ============================================================

require_once '../../src/config/config.php';
require_once '../../src/config/database.php';
require_once '../../src/helpers/Session.php';
require_once '../../src/helpers/Redirect.php';
require_once '../../src/helpers/Flash.php';
require_once '../../src/models/Simulado.php';

Session::iniciar();

if (!Session::autenticado() || !Session::isAdmin()) {
    Redirect::semPermissao();
}

$simuladoModel = new Simulado();

// Processar acoes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'criar') {
        $dados = [
            'titulo' => trim($_POST['titulo']),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'data_aplicacao' => $_POST['data_aplicacao']
        ];
        
        if (empty($dados['titulo']) || empty($dados['data_aplicacao'])) {
            Flash::erro('Preencha todos os campos obrigatorios.');
        } else {
            if ($simuladoModel->criar($dados)) {
                Flash::sucesso('Simulado criado com sucesso!');
            } else {
                Flash::erro('Erro ao criar simulado.');
            }
        }
        Redirect::para('simulados.php');
    }
    
    if ($acao === 'editar') {
        $id = (int)$_POST['id'];
        $dados = [
            'titulo' => trim($_POST['titulo']),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'data_aplicacao' => $_POST['data_aplicacao']
        ];
        
        if ($simuladoModel->atualizar($id, $dados)) {
            Flash::sucesso('Simulado atualizado!');
        } else {
            Flash::erro('Erro ao atualizar.');
        }
        Redirect::para('simulados.php');
    }
    
    if ($acao === 'toggle_status') {
        $id = (int)$_POST['id'];
        $simulado = $simuladoModel->buscarPorId($id);
        
        if ($simulado) {
            if ($simulado['ativo']) {
                $simuladoModel->desativar($id);
                Flash::sucesso('Simulado desativado.');
            } else {
                $simuladoModel->ativar($id);
                Flash::sucesso('Simulado reativado.');
            }
        }
        Redirect::para('simulados.php');
    }
    
    if ($acao === 'deletar') {
        $id = (int)$_POST['id'];
        if ($simuladoModel->deletar($id)) {
            Flash::sucesso('Simulado removido permanentemente.');
        } else {
            Flash::erro('Erro ao deletar.');
        }
        Redirect::para('simulados.php');
    }
}

// Listar simulados
$simulados = $simuladoModel->listarTodos();

// Adicionar contadores
foreach ($simulados as &$s) {
    $s['total_resultados'] = $simuladoModel->contarResultados($s['id']);
    $s['media_notas'] = $simuladoModel->mediaNotas($s['id']);
}
unset($s);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simulados - Simula EEEP</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    .modal--aberto {
      display: flex;
    }
    .modal__conteudo {
      background: white;
      border-radius: 12px;
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      margin: 1rem;
    }
    .modal__header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 1.5rem;
      border-bottom: 1px solid #eee;
    }
    .modal__header h2 {
      margin: 0;
      font-size: 1.1rem;
    }
    .modal__fechar {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #666;
    }
    .modal__conteudo form {
      padding: 1.5rem;
    }
    .modal__footer {
      display: flex;
      gap: 0.5rem;
      justify-content: flex-end;
      margin-top: 1.5rem;
      padding-top: 1rem;
      border-top: 1px solid #eee;
    }
    .linha-inativa {
      opacity: 0.5;
    }
  </style>
</head>
<body>

<header class="header-mobile">
  <button class="btn-menu" id="btnMenu" aria-label="Abrir menu">Menu</button>
  <span class="header-mobile__titulo">Simulados</span>
  <span style="width:40px"></span>
</header>

<div class="overlay-sidebar" id="overlaySidebar"></div>

<div class="pagina">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar__logo">
      <div>
        <div class="sidebar__logo-texto">Simula EEEP</div>
        <div class="sidebar__logo-sub">Administracao</div>
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
      <h1>Gerenciar Simulados</h1>
      <p>Cadastre e gerencie os simulados aplicados</p>
    </div>

    <?= Flash::renderizar() ?>

    <!-- Botao Novo Simulado -->
    <div class="mb-lg">
      <button class="btn btn--primario" onclick="abrirModalNovo()">
        + Novo Simulado
      </button>
    </div>

    <!-- Lista de Simulados -->
    <div class="tabela-wrapper">
      <div class="tabela-scroll">
        <table class="tabela">
          <thead>
            <tr>
              <th>Simulado</th>
              <th>Data</th>
              <th>Resultados</th>
              <th>Media</th>
              <th>Status</th>
              <th>Acoes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($simulados)): ?>
            <tr>
              <td colspan="6" style="text-align:center; color:var(--texto-suave); padding:var(--espaco-lg);">
                Nenhum simulado cadastrado ainda.
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($simulados as $s): ?>
            <tr class="<?= $s['ativo'] ? '' : 'linha-inativa' ?>">
              <td data-label="Simulado">
                <strong><?= htmlspecialchars($s['titulo']) ?></strong>
                <?php if (!empty($s['descricao'])): ?>
                <br><small style="color:var(--texto-suave)"><?= htmlspecialchars($s['descricao']) ?></small>
                <?php endif; ?>
              </td>
              <td data-label="Data">
                <?= date('d/m/Y', strtotime($s['data_aplicacao'])) ?>
              </td>
              <td data-label="Resultados">
                <?= (int)$s['total_resultados'] ?>
              </td>
              <td data-label="Media">
                <strong><?= $s['media_notas'] ? number_format($s['media_notas'], 1, ',', '.') : '-' ?></strong>
              </td>
              <td data-label="Status">
                <span class="badge-nota badge-nota--<?= $s['ativo'] ? 'alta' : 'baixa' ?>">
                  <?= $s['ativo'] ? 'Ativo' : 'Inativo' ?>
                </span>
              </td>
              <td data-label="Acoes">
                <div class="flex gap-sm" style="flex-wrap:wrap;">
                  <button class="btn btn--secundario btn--sm" onclick='abrirModalEditar(<?= json_encode($s) ?>)'>
                    Editar
                  </button>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Confirma esta acao?')">
                    <input type="hidden" name="acao" value="toggle_status">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn--secundario btn--sm">
                      <?= $s['ativo'] ? 'Desativar' : 'Reativar' ?>
                    </button>
                  </form>
                  <?php if ($s['total_resultados'] == 0): ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Deletar permanentemente?')">
                    <input type="hidden" name="acao" value="deletar">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn--perigo btn--sm">Excluir</button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Modal Novo Simulado -->
<div class="modal" id="modalNovo">
  <div class="modal__conteudo">
    <div class="modal__header">
      <h2>Novo Simulado</h2>
      <button class="modal__fechar" type="button" onclick="fecharModal('modalNovo')">X</button>
    </div>
    <form method="POST">
      <input type="hidden" name="acao" value="criar">
      
      <div class="form-grupo">
        <label for="titulo">Titulo *</label>
        <input type="text" id="titulo" name="titulo" required placeholder="Ex: 1 Simulado ENEM 2024" class="form-input">
      </div>
      
      <div class="form-grupo">
        <label for="descricao">Descricao</label>
        <textarea id="descricao" name="descricao" rows="3" placeholder="Observacoes opcionais..." class="form-textarea"></textarea>
      </div>
      
      <div class="form-grupo">
        <label for="data_aplicacao">Data de Aplicacao *</label>
        <input type="date" id="data_aplicacao" name="data_aplicacao" required class="form-input">
      </div>
      <label><input type="checkbox" name="tem_linguagens" checked> Linguagens</label>
      <label><input type="checkbox" name="tem_humanas" checked> Humanas</label>
      <label><input type="checkbox" name="tem_natureza" checked> Natureza</label>
      <label><input type="checkbox" name="tem_matematica" checked> Matemática</label>
      <label><input type="checkbox" name="tem_redacao"> Redação</label>
      
      <div class="modal__footer">
        <button type="button" class="btn btn--secundario" onclick="fecharModal('modalNovo')">Cancelar</button>
        <button type="submit" class="btn btn--primario">Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Simulado -->
<div class="modal" id="modalEditar">
  <div class="modal__conteudo">
    <div class="modal__header">
      <h2>Editar Simulado</h2>
      <button class="modal__fechar" type="button" onclick="fecharModal('modalEditar')">X</button>
    </div>
    <form method="POST">
      <input type="hidden" name="acao" value="editar">
      <input type="hidden" name="id" id="editar_id">
      
      <div class="form-grupo">
        <label for="editar_titulo">Titulo *</label>
        <input type="text" id="editar_titulo" name="titulo" required class="form-input">
      </div>
      
      <div class="form-grupo">
        <label for="editar_descricao">Descricao</label>
        <textarea id="editar_descricao" name="descricao" rows="3" class="form-textarea"></textarea>
      </div>
      
      <div class="form-grupo">
        <label for="editar_data_aplicacao">Data de Aplicacao *</label>
        <input type="date" id="editar_data_aplicacao" name="data_aplicacao" required class="form-input">
      </div>
      
      <div class="modal__footer">
        <button type="button" class="btn btn--secundario" onclick="fecharModal('modalEditar')">Cancelar</button>
        <button type="submit" class="btn btn--primario">Salvar Alteracoes</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function abrirModalNovo() {
  document.getElementById('modalNovo').classList.add('modal--aberto');
}

function abrirModalEditar(simulado) {
  document.getElementById('editar_id').value = simulado.id;
  document.getElementById('editar_titulo').value = simulado.titulo;
  document.getElementById('editar_descricao').value = simulado.descricao || '';
  document.getElementById('editar_data_aplicacao').value = simulado.data_aplicacao;
  document.getElementById('modalEditar').classList.add('modal--aberto');
}

function fecharModal(id) {
  document.getElementById(id).classList.remove('modal--aberto');
}

document.querySelectorAll('.modal').forEach(function(modal) {
  modal.addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.remove('modal--aberto');
    }
  });
});
</script>
</body>
</html>
