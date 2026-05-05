// public/assets/js/charts.js
// ============================================================
// Gráficos com Chart.js — Dashboard do Aluno
// Depende de: dadosGrafico (definido inline no PHP)
// ============================================================

'use strict';

(function iniciarGraficos() {
  const canvas = document.getElementById('graficoEvolucao');
  if (!canvas || typeof dadosGrafico === 'undefined' || dadosGrafico.length === 0) return;

  // Paleta de cores do sistema
  const CORES = {
    total:       { linha: '#2e7d32', fundo: 'rgba(46,125,50,.12)'  },
    linguagens:  { linha: '#1565c0', fundo: 'rgba(21,101,192,.08)' },
    humanas:     { linha: '#6a1b9a', fundo: 'rgba(106,27,154,.08)' },
    natureza:    { linha: '#00695c', fundo: 'rgba(0,105,92,.08)'   },
    matematica:  { linha: '#e65100', fundo: 'rgba(230,81,0,.08)'   },
    redacao:     { linha: '#c62828', fundo: 'rgba(198,40,40,.08)'  },
  };

  const labels   = dadosGrafico.map(d => d.simulado);
  const datasets = [
    {
      label:           'Nota Total',
      data:            dadosGrafico.map(d => d.nota_total),
      borderColor:     CORES.total.linha,
      backgroundColor: CORES.total.fundo,
      borderWidth:     3,
      pointRadius:     5,
      pointHoverRadius:7,
      tension:         0.3,
      fill:            true,
    },
    {
      label:           'Linguagens',
      data:            dadosGrafico.map(d => d.linguagens),
      borderColor:     CORES.linguagens.linha,
      backgroundColor: 'transparent',
      borderWidth:     1.5,
      pointRadius:     3,
      tension:         0.3,
      hidden:          true, // Oculto por padrão, ativável na legenda
    },
    {
      label:           'Humanas',
      data:            dadosGrafico.map(d => d.humanas),
      borderColor:     CORES.humanas.linha,
      backgroundColor: 'transparent',
      borderWidth:     1.5,
      pointRadius:     3,
      tension:         0.3,
      hidden:          true,
    },
    {
      label:           'Natureza',
      data:            dadosGrafico.map(d => d.natureza),
      borderColor:     CORES.natureza.linha,
      backgroundColor: 'transparent',
      borderWidth:     1.5,
      pointRadius:     3,
      tension:         0.3,
      hidden:          true,
    },
    {
      label:           'Matemática',
      data:            dadosGrafico.map(d => d.matematica),
      borderColor:     CORES.matematica.linha,
      backgroundColor: 'transparent',
      borderWidth:     1.5,
      pointRadius:     3,
      tension:         0.3,
      hidden:          true,
    },
    {
      label:           'Redação',
      data:            dadosGrafico.map(d => d.redacao),
      borderColor:     CORES.redacao.linha,
      backgroundColor: 'transparent',
      borderWidth:     1.5,
      pointRadius:     3,
      tension:         0.3,
      hidden:          true,
    },
  ];

  new Chart(canvas, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive:          true,
      maintainAspectRatio: false,
      interaction: {
        mode:      'index',
        intersect: false,
      },
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            usePointStyle: true,
            padding:       16,
            font:          { size: 12, family: 'Inter, sans-serif' },
          },
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,.85)',
          padding:         12,
          cornerRadius:    8,
          callbacks: {
            label: (ctx) => {
              const valor = ctx.parsed.y;
              return ` ${ctx.dataset.label}: ${valor.toLocaleString('pt-BR', {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1,
              })}`;
            },
          },
        },
      },
      scales: {
        x: {
          grid:  { display: false },
          ticks: { font: { size: 11 }, maxRotation: 30 },
        },
        y: {
          min:   0,
          max:   1000,
          grid:  { color: 'rgba(0,0,0,.06)' },
          ticks: {
            stepSize: 200,
            font:     { size: 11 },
            callback: (v) => v.toLocaleString('pt-BR'),
          },
        },
      },
    },
  });
})();
