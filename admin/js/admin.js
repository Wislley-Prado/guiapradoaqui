/* jshint esversion: 6 */
/* global jQuery, gpaAdmin, wp */

(function ($) {
  'use strict';

  // ── Nota Prado Aqui — Auto-Cálculo ─────────────────────────────────────────
  const notaInputs = [
    '_gpa_nota_pesca',
    '_gpa_nota_estrutura',
    '_gpa_nota_localizacao',
    '_gpa_nota_acesso',
    '_gpa_nota_custo_beneficio',
  ];

  function calcNota() {
    const manualInput = document.getElementById('gpa__gpa_nota_manual');
    if (manualInput && manualInput.value !== '') {
      // Se tiver override manual, usa ele
      const manualVal = parseFloat(manualInput.value);
      updateNotaDisplay(manualVal);
      setNotaFinal(manualVal);
      return;
    }

    let soma = 0;
    let count = 0;

    notaInputs.forEach(function (key) {
      const el = document.getElementById('gpa_' + key);
      if (el && el.value !== '') {
        soma += parseFloat(el.value) || 0;
        count++;
      }
    });

    if (count === 0) {
      updateNotaDisplay(null);
      setNotaFinal('');
      return;
    }

    const media = parseFloat((soma / count).toFixed(1));
    updateNotaDisplay(media);
    setNotaFinal(media);
  }

  function updateNotaDisplay(nota) {
    const displayEl = document.getElementById('gpa-nota-calculada');
    if (!displayEl) return;
    if (nota === null || nota === undefined || nota === '') {
      displayEl.textContent = '—';
      displayEl.style.color = '#ccc';
    } else {
      displayEl.textContent = nota.toFixed(1);
      displayEl.style.color = nota >= 8 ? '#087443' : nota >= 6 ? '#e6a817' : '#c0392b';
    }
  }

  function setNotaFinal(valor) {
    const hidden = document.getElementById('gpa_nota_final_hidden');
    if (hidden) hidden.value = valor;
  }

  // Bind nos inputs de nota
  $(document).on('input change', '.gpa-nota-input', function () {
    // Atualiza o display individual
    const display = $(this).siblings('.gpa-nota-display');
    if ($(this).val() !== '') {
      display.text(parseFloat($(this).val()).toFixed(1));
    } else {
      display.text('—');
    }
    calcNota();
  });

  // Calcula na carga
  $(document).ready(function () {
    calcNota();
  });

  // ── Media Uploader — Foto Principal ────────────────────────────────────────
  // (WordPress usa o campo nativo de Imagem Destacada — sem JS adicional necessário)

  // ── Media Uploader — Galeria ───────────────────────────────────────────────
  var galleryFrame = null;

  $(document).on('click', '#gpa-add-gallery', function (e) {
    e.preventDefault();

    if (galleryFrame) {
      galleryFrame.open();
      return;
    }

    galleryFrame = wp.media({
      title:    gpaAdmin.galleryTitle,
      button:   { text: gpaAdmin.galleryBtn },
      multiple: true,
      library:  { type: 'image' },
    });

    galleryFrame.on('select', function () {
      var selection = galleryFrame.state().get('selection');
      selection.each(function (attachment) {
        addGalleryItem(attachment.toJSON());
      });
      updateGalleryInput();
    });

    galleryFrame.open();
  });

  function addGalleryItem(att) {
    var preview = document.getElementById('gpa-gallery-preview');
    if (!preview) return;

    // Verifica se já está na galeria
    if (preview.querySelector('[data-id="' + att.id + '"]')) return;

    var thumb = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
    var div = document.createElement('div');
    div.className = 'gpa-gallery-item';
    div.dataset.id = att.id;
    div.innerHTML =
      '<img class="gpa-gallery-thumb" src="' + thumb + '" alt="">' +
      '<button type="button" class="gpa-gallery-remove" data-id="' + att.id + '" title="Remover">✕</button>';

    preview.appendChild(div);

    // Mostra botão limpar
    var clearBtn = document.getElementById('gpa-clear-gallery');
    if (clearBtn) clearBtn.style.display = '';
  }

  $(document).on('click', '.gpa-gallery-remove', function () {
    var item = $(this).closest('.gpa-gallery-item');
    item.remove();
    updateGalleryInput();

    var preview = document.getElementById('gpa-gallery-preview');
    if (preview && preview.querySelectorAll('.gpa-gallery-item').length === 0) {
      var clearBtn = document.getElementById('gpa-clear-gallery');
      if (clearBtn) clearBtn.style.display = 'none';
    }
  });

  $(document).on('click', '#gpa-clear-gallery', function () {
    var preview = document.getElementById('gpa-gallery-preview');
    if (preview) preview.innerHTML = '';
    $(this).hide();
    updateGalleryInput();
  });

  function updateGalleryInput() {
    var preview = document.getElementById('gpa-gallery-preview');
    var input   = document.getElementById('gpa_galeria_ids');
    if (!preview || !input) return;

    var ids = [];
    preview.querySelectorAll('.gpa-gallery-item').forEach(function (el) {
      ids.push(el.dataset.id);
    });
    input.value = ids.join(',');
  }

  // ── Auto-fill WhatsApp a partir do telefone ───────────────────────────────
  $(document).on('change blur', '#gpa__gpa_telefone', function () {
    var waInput = document.getElementById('gpa__gpa_whatsapp');
    if (waInput && waInput.value === '') {
      waInput.placeholder = $(this).val() || '5538999000000 (somente números)';
    }
  });

  // ── Quick Status — Fila de Verificação ───────────────────────────────────
  $(document).on('change', '.gpa-quick-status', function () {
    var select  = $(this);
    var postId  = select.data('post-id');
    var status  = select.val();
    var row     = select.closest('tr');

    select.prop('disabled', true);

    $.ajax({
      url:  gpaAdmin.ajaxUrl,
      type: 'POST',
      data: {
        action:  'gpa_quick_verify',
        nonce:   gpaAdmin.nonce,
        post_id: postId,
        status:  status,
      },
      success: function (res) {
        if (res.success) {
          row.addClass('gpa-row-updated');
          setTimeout(function () { row.removeClass('gpa-row-updated'); }, 1500);
        } else {
          alert('Erro ao atualizar: ' + (res.data.message || 'Tente novamente.'));
        }
      },
      error: function () {
        alert('Erro de conexão ao atualizar o status.');
      },
      complete: function () {
        select.prop('disabled', false);
      },
    });
  });

  // ── Toggle Capability de Usuário ──────────────────────────────────────────
  $(document).on('click', '.gpa-cap-toggle', function () {
    var btn    = $(this);
    var userId = btn.data('user');
    var action = btn.data('action');

    if (!confirm('Confirmar ' + (action === 'grant' ? 'concessão' : 'revogação') + ' de acesso?')) return;

    btn.prop('disabled', true).text('Aguarde...');

    $.ajax({
      url:  gpaAdmin.ajaxUrl,
      type: 'POST',
      data: {
        action:     'gpa_toggle_user_cap',
        nonce:      gpaAdmin.nonce,
        user_id:    userId,
        cap_action: action,
      },
      success: function (res) {
        if (res.success) {
          alert(res.data.message);
          location.reload();
        } else {
          alert('Erro: ' + (res.data.message || 'Tente novamente.'));
          btn.prop('disabled', false).text(action === 'grant' ? 'Conceder acesso' : 'Revogar acesso');
        }
      },
    });
  });

  // ── Status Badge — Admin List ──────────────────────────────────────────────
  // (Status é atualizado via quick-verify no select dropdown na página de verificações)

  // ── Confirmação de reset ───────────────────────────────────────────────────
  $(document).on('click', '.gpa-btn-danger[href*="reset_all"]', function (e) {
    if (!confirm(gpaAdmin.confirmReset)) {
      e.preventDefault();
    }
  });

  // ── Highlight row atualizado ───────────────────────────────────────────────
  $('<style>.gpa-row-updated td { background: #e7f8ef !important; transition: background .5s; }</style>').appendTo('head');

}(jQuery));
