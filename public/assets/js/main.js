// public/assets/js/main.js
// ============================================================
// Scripts globais do sistema
// ============================================================

'use strict';

// ── Sidebar Mobile ──────────────────────────────────────────
(function iniciarSidebarMobile() {
  const btnMenu       = document.getElementById('btnMenu');
  const sidebar       = document.getElementById('sidebar');
  const overlay       = document.getElementById('overlaySidebar');

  if (!btnMenu || !sidebar) return;

  function abrirSidebar() {
    sidebar.classList.add('sidebar--aberta');
    overlay.classList.add('overlay-sidebar--visivel');
    document.body.style.overflow = 'hidden';
    btnMenu.setAttribute('aria-expanded', 'true');
  }

  function fecharSidebar() {
    sidebar.classList.remove('sidebar--aberta');
    overlay.classList.remove('overlay-sidebar--visivel');
    document.body.style.overflow = '';
    btnMenu.setAttribute('aria-expanded', 'false');
  }

  btnMenu.addEventListener('click', () => {
    const aberta = sidebar.classList.contains('sidebar--aberta');
    aberta ? fecharSidebar() : abrirSidebar();
  });

  overlay.addEventListener('click', fecharSidebar);

  // Fecha com ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') fecharSidebar();
  });
})();

// ── Toggle de visibilidade da senha ─────────────────────────
(function iniciarToggleSenha() {
  document.querySelectorAll('[data-alvo]').forEach(btn => {
    btn.addEventListener('click', () => {
      const alvo  = document.getElementById(btn.dataset.alvo);
      if (!alvo) return;

      const visivel = alvo.type === 'text';
      alvo.type     = visivel ? 'password' : 'text';
      btn.textContent = visivel ? '👁' : '🙈';
      btn.setAttribute('aria-label', visivel ? 'Mostrar senha' : 'Ocultar senha');
    });
  });
})();

// ── Auto-dismiss de alertas ──────────────────────────────────
(function iniciarAutoDismissAlertas() {
  document.querySelectorAll('.alert--sucesso').forEach(alerta => {
    setTimeout(() => {
      alerta.style.transition = 'opacity 0.5s ease';
      alerta.style.opacity    = '0';
      setTimeout(() => alerta.remove(), 500);
    }, 5000);
  });
})();

// ── Confirmação antes de ações destrutivas ───────────────────
document.querySelectorAll('[data-confirmar]').forEach(el => {
  el.addEventListener('click', (e) => {
    const mensagem = el.dataset.confirmar || 'Tem certeza?';
    if (!confirm(mensagem)) {
      e.preventDefault();
    }
  });
});

// ── Utilitário: formata número no padrão BR ──────────────────
function formatarNota(valor) {
  return Number(valor).toLocaleString('pt-BR', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  });
}
