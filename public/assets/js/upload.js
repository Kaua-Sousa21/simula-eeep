// public/assets/js/upload.js
// ============================================================
// Drag-and-drop e feedback visual do upload de planilha
// ============================================================

'use strict';

(function iniciarUpload() {
  const uploadArea    = document.getElementById('uploadArea');
  const inputArquivo  = document.getElementById('inputArquivo');
  const btnSelecionar = document.getElementById('btnSelecionarArquivo');
  const uploadIcone   = document.getElementById('uploadIcone');
  const uploadTitulo  = document.getElementById('uploadTitulo');
  const uploadSub     = document.getElementById('uploadSub');
  const formUpload    = document.getElementById('formUpload');
  const btnEnviar     = document.getElementById('btnEnviar');
  const progresso     = document.getElementById('uploadProgresso');
  const progressoBarra= document.getElementById('progressoBarra');
  const progressoTexto= document.getElementById('progressoTexto');

  if (!uploadArea) return;

  // ── Clique na área ou botão ────────────────────────────────
  uploadArea.addEventListener('click', (e) => {
    if (e.target !== btnSelecionar) {
      inputArquivo.click();
    }
  });

  btnSelecionar.addEventListener('click', (e) => {
    e.stopPropagation();
    inputArquivo.click();
  });

  // Acessibilidade: Enter/Space na área
  uploadArea.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      inputArquivo.click();
    }
  });

  // ── Arquivo selecionado via input ──────────────────────────
  inputArquivo.addEventListener('change', () => {
    if (inputArquivo.files.length > 0) {
      exibirArquivoSelecionado(inputArquivo.files[0]);
    }
  });

  // ── Drag and Drop ──────────────────────────────────────────
  ['dragenter', 'dragover'].forEach(evento => {
    uploadArea.addEventListener(evento, (e) => {
      e.preventDefault();
      e.stopPropagation();
      uploadArea.classList.add('upload-area--dragover');
    });
  });

  ['dragleave', 'drop'].forEach(evento => {
    uploadArea.addEventListener(evento, (e) => {
      e.preventDefault();
      e.stopPropagation();
      uploadArea.classList.remove('upload-area--dragover');
    });
  });

  uploadArea.addEventListener('drop', (e) => {
    const arquivos = e.dataTransfer.files;
    if (arquivos.length === 0) return;

    const arquivo = arquivos[0];

    // Valida extensão no frontend (validação real é no backend)
    const extensao = arquivo.name.split('.').pop().toLowerCase();
    if (!['csv', 'xlsx'].includes(extensao)) {
      exibirErroArquivo('Formato inválido. Use apenas CSV ou XLSX.');
      return;
    }

    // Transfere para o input
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(arquivo);
    inputArquivo.files = dataTransfer.files;

    exibirArquivoSelecionado(arquivo);
  });

  // ── Exibe informações do arquivo selecionado ───────────────
  function exibirArquivoSelecionado(arquivo) {
    const tamanhoMB = (arquivo.size / (1024 * 1024)).toFixed(2);
    const extensao  = arquivo.name.split('.').pop().toUpperCase();

    uploadIcone.textContent  = extensao === 'CSV' ? '📄' : '📊';
    uploadTitulo.textContent = arquivo.name;
    uploadSub.textContent    = `${extensao} · ${tamanhoMB} MB`;

    uploadArea.style.borderColor = 'var(--verde-medio)';
    uploadArea.style.background  = '#c8e6c9';
    btnSelecionar.textContent    = 'Trocar arquivo';
  }

  function exibirErroArquivo(mensagem) {
    uploadIcone.textContent  = '❌';
    uploadTitulo.textContent = mensagem;
    uploadSub.textContent    = 'Selecione um arquivo válido';
    uploadArea.style.borderColor = 'var(--erro)';
    uploadArea.style.background  = '#ffebee';
  }

  // ── Feedback visual durante envio ─────────────────────────
  formUpload.addEventListener('submit', (e) => {
    if (!inputArquivo.files.length) {
      e.preventDefault();
      exibirErroArquivo('Selecione um arquivo antes de enviar.');
      return;
    }

    // Desabilita botão e exibe progresso
    btnEnviar.disabled       = true;
    btnEnviar.textContent    = '⏳ Processando...';
    progresso.classList.add('upload-progresso--visivel');

    // Animação de progresso (simulada, pois é POST síncrono)
    let pct = 0;
    const intervalo = setInterval(() => {
      pct = Math.min(pct + Math.random() * 15, 90);
      progressoBarra.style.width = pct + '%';
      progressoTexto.textContent = `Processando... ${Math.round(pct)}%`;
    }, 300);

    // Limpa intervalo quando a página recarregar
    window.addEventListener('beforeunload', () => clearInterval(intervalo));
  });

})();
