/* jshint esversion: 6 */
/* global jQuery, gpaData */

(function ($) {
  'use strict';

  // ── State ──────────────────────────────────────────────────────────────────
  var state = {
    busca:   '',
    cidade:  0,
    pesca:   '',
    piscina: false,
    wifi:    false,
    ordenar: 'nota',
    pagina:  1,
    porPag:  12,
    total:   0,
    paginas: 1,
    loading: false,
  };

  // ── Init ───────────────────────────────────────────────────────────────────
  $(document).ready(function () {
    // Busca inicial ao carregar
    if ($('#gpa-grid').length) {
      // Grid já pode ter sido renderizado via PHP, ou carrega via AJAX
      initFilters();
      initLazyLoad();
    }
  });

  function initFilters() {
    // Busca — debounce 350ms
    var searchTimer;
    $(document).on('input', '#gpa-search-input', function () {
      clearTimeout(searchTimer);
      var val = $(this).val();
      searchTimer = setTimeout(function () {
        state.busca  = val;
        state.pagina = 1;
        loadRanchos();
      }, 350);
    });

    // Cidade
    $(document).on('change', '#gpa-filter-cidade', function () {
      state.cidade  = parseInt($(this).val()) || 0;
      state.pagina  = 1;
      loadRanchos();
    });

    // Pesca
    $(document).on('change', '#gpa-filter-pesca', function () {
      state.pesca  = $(this).val();
      state.pagina = 1;
      loadRanchos();
    });

    // Ordenação
    $(document).on('change', '#gpa-filter-ordenar', function () {
      state.ordenar = $(this).val();
      state.pagina  = 1;
      loadRanchos();
    });

    // Chips (piscina, wifi)
    $(document).on('change', '.gpa-chip-input', function () {
      var key  = $(this).data('filter');
      var val  = $(this).is(':checked');
      state[key]   = val;
      state.pagina = 1;
      $(this).closest('.gpa-filter-chip').toggleClass('active', val);
      loadRanchos();
    });

    // Paginação
    $(document).on('click', '.gpa-page-btn:not(.active):not(:disabled)', function () {
      var pg = parseInt($(this).data('page'));
      if (pg && pg !== state.pagina) {
        state.pagina = pg;
        loadRanchos();
        // Scroll to top of grid
        var grid = document.getElementById('gpa-grid');
        if (grid) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }

  // ── Load via AJAX ──────────────────────────────────────────────────────────
  function loadRanchos() {
    if (state.loading) return;
    state.loading = true;

    showLoading(true);

    $.ajax({
      url:  gpaData.ajaxUrl,
      type: 'POST',
      data: {
        action:  'gpa_filter',
        nonce:   gpaData.ajaxNonce,
        busca:   state.busca,
        cidade:  state.cidade,
        pesca:   state.pesca,
        piscina: state.piscina ? 1 : 0,
        wifi:    state.wifi ? 1 : 0,
        ordenar: state.ordenar,
        pagina:  state.pagina,
        por_pag: state.porPag,
      },
      success: function (res) {
        if (res.success) {
          var d = res.data;
          state.total   = d.total;
          state.paginas = d.paginas;
          state.pagina  = d.pagina;

          renderGrid(d.html);
          renderCount(d.total);
          renderPagination();
          initLazyLoad();
        }
      },
      error: function () {
        renderGrid('<div class="gpa-empty-state"><span>⚠️</span><p>Erro ao carregar. Tente novamente.</p></div>');
      },
      complete: function () {
        state.loading = false;
        showLoading(false);
      },
    });
  }

  // ── Render ─────────────────────────────────────────────────────────────────
  function renderGrid(html) {
    var grid = document.getElementById('gpa-grid');
    if (grid) {
      grid.innerHTML = html || '<div class="gpa-empty-state"><span>🎣</span><p>Nenhum rancho encontrado com esses filtros.</p></div>';
    }
  }

  function renderCount(total) {
    var el = document.getElementById('gpa-count');
    if (el) {
      el.textContent = total + ' rancho' + (total !== 1 ? 's' : '') + ' encontrado' + (total !== 1 ? 's' : '');
    }
  }

  function renderPagination() {
    var container = document.getElementById('gpa-pagination');
    if (!container) return;

    if (state.paginas <= 1) {
      container.innerHTML = '';
      return;
    }

    var html = '';
    var current = state.pagina;
    var total   = state.paginas;

    html += '<button class="gpa-page-btn" data-page="' + (current - 1) + '"' + (current === 1 ? ' disabled' : '') + '>← Anterior</button>';

    // Páginas
    var start = Math.max(1, current - 2);
    var end   = Math.min(total, current + 2);

    if (start > 1) html += '<button class="gpa-page-btn" data-page="1">1</button>';
    if (start > 2) html += '<span class="gpa-page-ellipsis">…</span>';

    for (var i = start; i <= end; i++) {
      html += '<button class="gpa-page-btn' + (i === current ? ' active' : '') + '" data-page="' + i + '">' + i + '</button>';
    }

    if (end < total - 1) html += '<span class="gpa-page-ellipsis">…</span>';
    if (end < total)     html += '<button class="gpa-page-btn" data-page="' + total + '">' + total + '</button>';

    html += '<button class="gpa-page-btn" data-page="' + (current + 1) + '"' + (current === total ? ' disabled' : '') + '>Próximo →</button>';

    container.innerHTML = html;
  }

  function showLoading(show) {
    var el = document.getElementById('gpa-loading');
    if (el) el.classList.toggle('visible', show);
  }

  // ── Lazy Load ──────────────────────────────────────────────────────────────
  function initLazyLoad() {
    if (!('IntersectionObserver' in window)) return;

    var imgs = document.querySelectorAll('img[data-src]');
    if (!imgs.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var img = entry.target;
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          observer.unobserve(img);
        }
      });
    }, { rootMargin: '100px' });

    imgs.forEach(function (img) { observer.observe(img); });
  }

  // ── Ranch Detail — Galeria Lightbox simples ───────────────────────────────
  $(document).on('click', '.gpa-gallery img', function () {
    var src  = $(this).attr('src');
    var alt  = $(this).attr('alt') || '';
    openLightbox(src, alt);
  });

  function openLightbox(src, alt) {
    var existing = document.getElementById('gpa-lightbox');
    if (existing) existing.remove();

    var lb = document.createElement('div');
    lb.id = 'gpa-lightbox';
    lb.style.cssText = [
      'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.92);',
      'display:flex;align-items:center;justify-content:center;padding:20px;',
    ].join('');

    lb.innerHTML = [
      '<button style="position:absolute;top:16px;right:20px;background:none;border:none;',
      'color:white;font-size:32px;cursor:pointer;line-height:1;" id="gpa-lb-close">✕</button>',
      '<img src="' + src + '" alt="' + alt + '" style="max-width:100%;max-height:90vh;',
      'border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.5);">',
    ].join('');

    document.body.appendChild(lb);

    lb.addEventListener('click', function (e) {
      if (e.target === lb || e.target.id === 'gpa-lb-close') lb.remove();
    });

    document.addEventListener('keydown', function closeLb(e) {
      if (e.key === 'Escape') { lb.remove(); document.removeEventListener('keydown', closeLb); }
    });
  }

  // ── WhatsApp CTA — Adiciona nome do rancho na mensagem ───────────────────
  $(document).on('click', '.gpa-cta-wa[data-ranch]', function () {
    var name = $(this).data('ranch');
    var msg  = (gpaData.whatsappMsg || 'Olá! Gostaria de mais informações sobre disponibilidade e valores.').replace('%s', name);
    var href = $(this).attr('href');
    // URL já vem montada do PHP, apenas abre
    window.open(href, '_blank');
    return false;
  });

  // ── Smooth card appearance ────────────────────────────────────────────────
  function animateCards() {
    var cards = document.querySelectorAll('.gpa-card');
    cards.forEach(function (card, i) {
      card.style.opacity  = '0';
      card.style.transform = 'translateY(16px)';
      setTimeout(function () {
        card.style.transition = 'opacity .3s ease, transform .3s ease';
        card.style.opacity    = '1';
        card.style.transform  = 'translateY(0)';
      }, i * 60);
    });
  }

  // Observer para re-animar após AJAX
  var gridEl = document.getElementById('gpa-grid');
  if (gridEl && window.MutationObserver) {
    var mo = new MutationObserver(function () { animateCards(); });
    mo.observe(gridEl, { childList: true });
  }

  // Animação inicial
  animateCards();

}(jQuery));
