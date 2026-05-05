
<?php
// public/admin/alunos.php
// ============================================================

require_once '../../src/config/config.php';
require_once '../../src/config/database.php';
require_once '../../src/helpers/Session.php';
require_once '../../src/helpers/Redirect.php';
require_once '../../src/helpers/Flash.php';
require_once '../../src/models/Aluno.php';
require_once '../../src/models/Usuario.php';

Session::iniciar();

if (!Session::autenticado() || !Session::isAdmin()) {
    Redirect::semPermissao();
}

$alunoModel = new Aluno();
$usuarioModel = new Usuario();

// Variavel para armazenar senhas geradas
$senhasGeradas = [];
$mostrarSenhas = false;

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    // =========================
    // CRIAR ALUNO
    // =========================
    if ($acao === 'criar') {

        $nome       = trim($_POST['nome']);
        $matricula  = trim($_POST['matricula']);
        $turma      = trim($_POST['turma'] ?? '');
        $email      = trim($_POST['email']);
        $criarUsuario = isset($_POST['criar_usuario']);

        if (empty($nome) || empty($matricula) || empty($email)) {
            Flash::erro('Preencha todos os campos obrigatórios.');
        }

        elseif ($alunoModel->buscarPorMatricula($matricula)) {
            Flash::erro('Já existe aluno com essa matrícula.');
        }

        elseif ($usuarioModel->findByEmail($email)) {
            Flash::erro('Já existe usuário com esse email.');
        }

        else {
            $usuarioId = null;
            $senhaTemp = null;

            // Criar usuário
            if ($criarUsuario) {
                $senhaTemp = gerarSenhaTemporaria();

                if (!$usuarioModel->findByEmail($email)) {
                    $usuarioModel->create([
                        'email' => $email,
                        'senha' => $senhaTemp,
                        'tipo'  => 'aluno'
                    ]);

                    $usuario = $usuarioModel->findByEmail($email);
                    $usuarioId = $usuario['id'] ?? null;
                }

                if ($usuarioId) {
                    $senhasGeradas[] = [
                        'nome'  => $nome,
                        'email' => $email,
                        'senha' => $senhaTemp
                    ];
                    $mostrarSenhas = true;
                }
            }

            // Criar aluno (CORRETO)
            $alunoModel->criar([
              'usuario_id' => $usuarioId,
              'matricula'  => $matricula,
              'nome'       => $nome,
              'turma'      => $turma,
              'turno'      => 'manhã'
        ]);

            Flash::sucesso('Aluno cadastrado com sucesso!');
        }

        Redirect::para('alunos.php');
    }

    // =========================
    // CADASTRO EM LOTE
    // =========================
    if ($acao === 'criar_lote') {

        $turma  = trim($_POST['turma_lote']);
        $linhas = explode("\n", trim($_POST['emails_lote']));

        $cadastrados = 0;
        $erros = 0;

        foreach ($linhas as $linha) {

            $partes = array_map('trim', explode('|', $linha));

            if (count($partes) < 3) {
                $erros++;
                continue;
            }

            [$nome, $matricula, $email] = $partes;

            if ($alunoModel->buscarPorMatricula($matricula) || $usuarioModel->findByEmail($email)) {
                $erros++;
                continue;
            }

            $senhaTemp = gerarSenhaTemporaria();

            $usuarioModel->create([
                'email' => $email,
                'senha' => $senhaTemp,
                'tipo'  => 'aluno'
            ]);

            $usuario = $usuarioModel->findByEmail($email);
            $usuarioId = $usuario['id'] ?? null;

            if ($usuarioId) {
                $alunoModel->criar([
                  'usuario_id' => $usuarioId,
                  'matricula'  => $matricula,
                  'nome'       => $nome,
                  'turma'      => $turma,
                  'turno'      => 'manhã'
              ]);

                $cadastrados++;
            } else {
                $erros++;
            }
        }

        if ($cadastrados > 0) {
            $mostrarSenhas = true;
            Flash::sucesso("$cadastrados aluno(s) cadastrados!");
        }

        if ($erros > 0) {
            Flash::erro("$erros erro(s) ou duplicados.");
        }
    }

    // =========================
    // REGENERAR SENHA
    // =========================
    if ($acao === 'regenerar_senha') {

        $usuarioId = (int) $_POST['usuario_id'];
        $alunoId   = (int) $_POST['aluno_id'];

        $aluno = $alunoModel->buscarPorId($alunoId);
        $novaSenha = gerarSenhaTemporaria();

        if ($usuarioModel->atualizarSenha($usuarioId, $novaSenha)) {
            $senhasGeradas[] = [
                'nome'  => $aluno['nome'],
                'email' => $aluno['email'],
                'senha' => $novaSenha
            ];
            $mostrarSenhas = true;
            Flash::sucesso('Nova senha gerada!');
        } else {
            Flash::erro('Erro ao gerar senha.');
        }
    }

    // =========================
    // DELETAR
    // =========================
    if ($acao === 'deletar') {

        $id = (int) $_POST['id'];

        if ($alunoModel->deletar($id)) {
            Flash::sucesso('Aluno removido.');
        } else {
            Flash::erro('Erro ao remover.');
        }

        Redirect::para('alunos.php');
    }
}
// Listar alunos
$alunos = $alunoModel->listarTodos();

// Funcao para gerar senha temporaria
function gerarSenhaTemporaria($tamanho = 8) {
    $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$';
    $senha = '';
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $senha;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alunos - Simula EEEP</title>
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
    .alert--info {
      background: #e3f2fd;
      border-left: 4px solid #1565c0;
      color: #1565c0;
    }
    .senha-destaque {
      background: #fff3cd;
      border-left: 4px solid #ffc107;
      padding: 1rem;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>

<header class="header-mobile">
  <button class="btn-menu" id="btnMenu" aria-label="Abrir menu">Menu</button>
  <span class="header-mobile__titulo">Alunos</span>
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
      <a href="alunos.php" class="nav__item nav__item--ativo">
        <span class="nav__icone">Alunos</span>
      </a>
      <a href="simulados.php" class="nav__item">
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
      <h1>Gerenciar Alunos</h1>
      <p>Cadastre alunos e gere senhas temporarias para acesso ao sistema</p>
    </div>

    <?= Flash::renderizar() ?>

    <!-- Exibir senhas geradas -->
    <?php if ($mostrarSenhas && !empty($senhasGeradas)): ?>
    <div class="senha-destaque">
      <h2 style="font-size:1rem; margin-bottom:0.5rem; color:#856404;">
        Senhas Temporarias Geradas
      </h2>
      <p style="color:#856404; margin-bottom:1rem;">
        <strong>IMPORTANTE:</strong> Copie estas senhas e entregue aos alunos. Elas nao serao exibidas novamente!
      </p>
      
      <div class="tabela-scroll">
        <table class="tabela" style="background:white;">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Email</th>
              <th>Senha Temporaria</th>
              <th>Copiar</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($senhasGeradas as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['nome']) ?></td>
              <td><?= htmlspecialchars($s['email']) ?></td>
              <td style="font-family:monospace; font-weight:bold; color:#d63384; font-size:1.1rem;">
                <?= htmlspecialchars($s['senha']) ?>
              </td>
              <td>
                <button class="btn btn--secundario btn--sm" onclick="copiarSenha('<?= htmlspecialchars($s['senha']) ?>', this)">
                  Copiar
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Botoes de acao -->
    <div class="flex gap-md mb-lg" style="flex-wrap:wrap;">
      <button class="btn btn--primario" onclick="abrirModal('modalNovo')">
        + Novo Aluno
      </button>
      <button class="btn btn--secundario" onclick="abrirModal('modalLote')">
        Cadastro em Lote
      </button>
    </div>

    <!-- Filtros -->
    <div class="card mb-lg">
      <div class="flex gap-md" style="flex-wrap:wrap; align-items:end;">
        <div class="form-grupo" style="flex:1; min-width:200px;">
          <label for="filtro_turma">Filtrar por Turma</label>
          <select id="filtro_turma" onchange="filtrarTabela()" class="form-input">
            <option value="">Todas as turmas</option>
            <?php foreach ($alunoModel->listarTurmas() as $turma): ?>
            <option value="<?= htmlspecialchars($turma) ?>"><?= htmlspecialchars($turma) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grupo" style="flex:1; min-width:200px;">
          <label for="filtro_busca">Buscar</label>
          <input type="text" id="filtro_busca" placeholder="Nome, matricula ou email..." onkeyup="filtrarTabela()" class="form-input">
        </div>
      </div>
    </div>

    <!-- Lista de Alunos -->
    <div class="tabela-wrapper">
      <div class="tabela-scroll">
        <table class="tabela" id="tabelaAlunos">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Matricula</th>
              <th>Turma</th>
              <th>Email</th>
              <th>Status</th>
              <th>Acoes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($alunos)): ?>
            <tr>
              <td colspan="6" style="text-align:center; color:var(--texto-suave); padding:var(--espaco-lg);">
                Nenhum aluno cadastrado ainda.
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($alunos as $a): ?>
            <tr data-turma="<?= htmlspecialchars($a['turma'] ?? '') ?>">
              <td data-label="Nome">
                <strong><?= htmlspecialchars($a['nome']) ?></strong>
              </td>
              <td data-label="Matricula">
                <?= htmlspecialchars($a['matricula']) ?>
              </td>
              <td data-label="Turma">
                <?= htmlspecialchars($a['turma'] ?? '-') ?>
              </td>
              <td data-label="Email">
                <?= htmlspecialchars($a['email']) ?>
              </td>
              <td data-label="Status">
                <?php if ($a['usuario_id']): ?>
                  <?php if ($a['primeiro_acesso']): ?>
                    <span class="badge-nota badge-nota--media">
                      Senha Temporaria
                    </span>
                  <?php else: ?>
                    <span class="badge-nota badge-nota--alta">
                      Ativo
                    </span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge-nota badge-nota--baixa">Sem acesso</span>
                <?php endif; ?>
              </td>
              <td data-label="Acoes">
                <div class="flex gap-sm" style="flex-wrap:wrap;">
                  <?php if ($a['usuario_id']): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="acao" value="regenerar_senha">
                    <input type="hidden" name="usuario_id" value="<?= $a['usuario_id'] ?>">
                    <input type="hidden" name="aluno_id" value="<?= $a['id'] ?>">
                    <button type="submit" class="btn btn--secundario btn--sm" title="Gerar nova senha temporaria">
                      Nova Senha
                    </button>
                  </form>
                  <?php endif; ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Remover este aluno?')">
                    <input type="hidden" name="acao" value="deletar">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <button type="submit" class="btn btn--perigo btn--sm">Excluir</button>
                  </form>
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

<!-- Modal Novo Aluno -->
<div class="modal" id="modalNovo">
  <div class="modal__conteudo">
    <div class="modal__header">
      <h2>Novo Aluno</h2>
      <button class="modal__fechar" type="button" onclick="fecharModal('modalNovo')">X</button>
    </div>
    <form method="POST">
      <input type="hidden" name="acao" value="criar">
      
      <div class="form-grupo">
        <label for="nome">Nome Completo *</label>
        <input type="text" id="nome" name="nome" required placeholder="Nome do aluno" class="form-input">
      </div>
      
      <div class="form-grupo">
        <label for="matricula">Matricula *</label>
        <input type="text" id="matricula" name="matricula" required placeholder="Ex: 2024001" class="form-input">
      </div>
      
      <div class="form-grupo">
        <label for="turma">Turma</label>
        <input type="text" id="turma" name="turma" placeholder="Ex: 3 ano A" class="form-input">
      </div>
      
      <div class="form-grupo">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" required placeholder="email@exemplo.com" class="form-input">
      </div>
      
      <div class="form-grupo">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
          <input type="checkbox" name="criar_usuario" checked style="width:auto;">
          <span>Criar usuario para acesso ao sistema (gera senha temporaria)</span>
        </label>
      </div>
      
      <div class="modal__footer">
        <button type="button" class="btn btn--secundario" onclick="fecharModal('modalNovo')">Cancelar</button>
        <button type="submit" class="btn btn--primario">Cadastrar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Cadastro em Lote -->
<div class="modal" id="modalLote">
  <div class="modal__conteudo" style="max-width:600px;">
    <div class="modal__header">
      <h2>Cadastro em Lote</h2>
      <button class="modal__fechar" type="button" onclick="fecharModal('modalLote')">X</button>
    </div>
    <form method="POST">
      <input type="hidden" name="acao" value="criar_lote">
      
      <div class="form-grupo">
        <label for="turma_lote">Turma (todos os alunos)</label>
        <input type="text" id="turma_lote" name="turma_lote" placeholder="Ex: 3 ano A" class="form-input">
      </div>
      
      <div class="form-grupo">
        <label for="emails_lote">Lista de Alunos</label>
        <textarea id="emails_lote" name="emails_lote" rows="8" 
                  placeholder="Um por linha, no formato:
Nome | Matricula | Email

Exemplo:
Joao Silva | 2024001 | joao@email.com
Maria Santos | 2024002 | maria@email.com"
                  class="form-textarea" style="font-family:monospace; font-size:0.9rem;"></textarea>
        <small style="color:var(--texto-suave);">
          Cada linha deve conter: Nome | Matricula | Email (separados por |)
        </small>
      </div>
      
      <div class="alert alert--info" style="padding:0.5rem; margin-bottom:1rem;">
        Senhas temporarias serao geradas automaticamente para todos os alunos cadastrados.
      </div>
      
      <div class="modal__footer">
        <button type="button" class="btn btn--secundario" onclick="fecharModal('modalLote')">Cancelar</button>
        <button type="submit" class="btn btn--primario">Cadastrar Alunos</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function abrirModal(id) {
  document.getElementById(id).classList.add('modal--aberto');
}

function fecharModal(id) {
  document.getElementById(id).classList.remove('modal--aberto');
}

function copiarSenha(senha, btn) {
  navigator.clipboard.writeText(senha).then(function() {
    btn.textContent = 'Copiado!';
    setTimeout(function() { btn.textContent = 'Copiar'; }, 2000);
  });
}

function filtrarTabela() {
  var turma = document.getElementById('filtro_turma').value.toLowerCase();
  var busca = document.getElementById('filtro_busca').value.toLowerCase();
  var linhas = document.querySelectorAll('#tabelaAlunos tbody tr');
  
  linhas.forEach(function(linha) {
    var turmaLinha = linha.dataset.turma ? linha.dataset.turma.toLowerCase() : '';
    var texto = linha.textContent.toLowerCase();
    
    var okTurma = !turma || turmaLinha.indexOf(turma) !== -1;
    var okBusca = !busca || texto.indexOf(busca) !== -1;
    
    linha.style.display = (okTurma && okBusca) ? '' : 'none';
  });
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
