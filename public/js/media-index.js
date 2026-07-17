/* ═══════════════════════════════════════════════════
   VoraCMS · Media Index v2
   Upload AJAX, accordion (picker-style), delete, copy URL
   ═══════════════════════════════════════════════════ */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var browser = document.getElementById('mediaBrowser');
    if (!browser) return;

    /* ─── Accordion: single-open per client ─── */
    var clients = browser.querySelectorAll('.media-picker__client');

    clients.forEach(function (client) {
      var cards = client.querySelectorAll('.media-picker__card');

      function closeAllCards (skip) {
        cards.forEach(function (c) {
          if (c !== skip) {
            c.classList.remove('media-picker__card--open');
            var h = c.querySelector('.media-picker__card-header');
            if (h) h.setAttribute('aria-expanded', 'false');
          }
        });
      }

      cards.forEach(function (card) {
        var header = card.querySelector('.media-picker__card-header');
        if (!header) return;

        header.addEventListener('click', function (e) {
          e.stopPropagation();
          var isOpen = card.classList.contains('media-picker__card--open');
          if (isOpen) {
            closeAllCards();
          } else {
            closeAllCards();
            card.classList.add('media-picker__card--open');
            header.setAttribute('aria-expanded', 'true');
          }
        });

        /* Keyboard */
        header.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            header.click();
          }
        });
      });
    });

    /* Click outside → close all */
    document.addEventListener('click', function (e) {
      if (!browser.contains(e.target)) {
        browser.querySelectorAll('.media-picker__card--open').forEach(function (c) {
          c.classList.remove('media-picker__card--open');
          var h = c.querySelector('.media-picker__card-header');
          if (h) h.setAttribute('aria-expanded', 'false');
        });
      }
    });

    /* ─── Upload form ─── */
    var uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
      var fileInput = uploadForm.querySelector('.media-picker__file-input');
      var uploadPreview = document.getElementById('uploadPreview');
      var uploadMsg = document.getElementById('uploadMsg');

      /* Preview on file select */
      if (fileInput && uploadPreview) {
        fileInput.addEventListener('change', function () {
          if (this.files && this.files.length > 0) {
            uploadPreview.innerHTML = '';
            for (var i = 0; i < this.files.length; i++) {
              var f = this.files[i];
              var div = document.createElement('div');
              div.className = 'media-picker__preview-item';

              var img = document.createElement('img');
              img.src = URL.createObjectURL(f);
              img.alt = f.name;
              div.appendChild(img);

              var span = document.createElement('span');
              span.textContent = f.name;
              div.appendChild(span);

              var rem = document.createElement('button');
              rem.type = 'button';
              rem.className = 'media-picker__preview-remove';
              rem.innerHTML = '<i class="bi bi-x"></i>';
              rem.setAttribute('aria-label', 'Eliminar');
              (function (fi) {
                rem.addEventListener('click', function () {
                  /* No es pot eliminar del FileList, però traiem la preview */
                  div.remove();
                  if (uploadPreview.children.length === 0) {
                    uploadPreview.classList.remove('media-picker__preview--show');
                  }
                });
              })(i);
              div.appendChild(rem);

              uploadPreview.appendChild(div);
            }
            uploadPreview.classList.add('media-picker__preview--show');
          } else {
            uploadPreview.classList.remove('media-picker__preview--show');
            uploadPreview.innerHTML = '';
          }
        });
      }

      uploadForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        var uploadUrl = this.getAttribute('data-upload-url');
        if (!uploadUrl) return;

        /* Validar: si hi ha select de projecte, cal escollir-ne un */
        var projectSelect = this.querySelector('select[name="project_id"]');
        if (projectSelect && !projectSelect.value) {
          Swal.fire({
            icon: 'warning',
            title: 'Selecciona un projecte',
            text: 'Has de triar un projecte on pujar la imatge abans de continuar.',
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'D\'acord'
          });
          return;
        }

        var btn = document.getElementById('uploadBtn');
        btn.disabled = true;
        var origHtml = btn.innerHTML;
        btn.innerHTML = '<span class="media-picker__spinner"></span> Pujant...';

        var formData = new FormData();
        var fi = this.querySelector('.media-picker__file-input');
        if (fi && fi.files) {
          for (var i = 0; i < fi.files.length; i++) {
            formData.append('files[' + i + ']', fi.files[i]);
          }
        }
        /* Altres camps */
        var fields = this.querySelectorAll('input:not([type="file"]), select, textarea');
        for (var j = 0; j < fields.length; j++) {
          var f = fields[j];
          if (f.name && f.value) {
            formData.append(f.name, f.value);
          }
        }

        try {
          var res = await fetch(uploadUrl, { method: 'POST', body: formData });
          var data = await res.json();
          if (data.error) {
            if (uploadMsg) uploadMsg.textContent = data.error;
          } else if (data.uploaded && data.uploaded.length > 0) {
            location.reload();
          }
          if (data.errors && data.errors.length > 0) {
            var msgs = data.errors.map(function (e) { return e.filename + ': ' + e.error; }).join('\n');
            if (uploadMsg) uploadMsg.textContent = 'Errors:\n' + msgs;
          }
        } catch (err) {
          if (uploadMsg) uploadMsg.textContent = 'Error en pujar les imatges.';
        }
        btn.disabled = false;
        btn.innerHTML = origHtml;
      });
    }

    /* ─── Delete (event delegation) ─── */
    browser.addEventListener('click', function (e) {
      var delBtn = e.target.closest('.media-picker__item-delete');
      if (!delBtn) return;

      var confirmMsg = delBtn.getAttribute('data-confirm');
      var formId = delBtn.getAttribute('data-form');
      if (!confirmMsg || !formId) return;

      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'Eliminar imatge',
        text: confirmMsg,
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancel·lar',
        reverseButtons: true
      }).then(function (result) {
        if (result.isConfirmed) {
          var form = document.getElementById(formId);
          if (form) form.submit();
        }
      });
    });

    /* ─── Copy URL (event delegation) ─── */
    browser.addEventListener('click', function (e) {
      var copyBtn = e.target.closest('.media-index__item-copy');
      if (!copyBtn) return;

      var url = copyBtn.getAttribute('data-url');
      if (!url) return;

      navigator.clipboard.writeText(url).then(function () {
        var icon = copyBtn.querySelector('i');
        if (icon) {
          icon.className = 'bi bi-check';
          setTimeout(function () { icon.className = 'bi bi-link-45deg'; }, 1500);
        }
      }).catch(function () {});
    });

    /* ─── Batch selection ─── */
    var toolbar = document.getElementById('batchToolbar');
    var countEl = document.getElementById('batchCount');
    var moveBtn = document.getElementById('batchMoveBtn');
    var clearBtn = document.getElementById('batchClearBtn');
    var batchForm = document.getElementById('batchMoveForm');
    var batchProjectId = document.getElementById('batchProjectId');

    function updateBatchToolbar () {
      var checked = document.querySelectorAll('.js-media-check:checked');
      var total = checked.length;
      if (total === 0) {
        toolbar.hidden = true;
        return;
      }
      toolbar.hidden = false;
      countEl.textContent = total + ' seleccionada' + (total !== 1 ? 's' : '');
    }

    /* Click on checkbox → update toolbar */
    browser.addEventListener('change', function (e) {
      if (e.target.classList.contains('js-media-check')) {
        updateBatchToolbar();
      }
    });

    /* Click on item (not on checkbox or delete or copy) → toggle checkbox */
    browser.addEventListener('click', function (e) {
      var item = e.target.closest('.media-index__item');
      if (!item) return;
      if (e.target.closest('.media-picker__item-delete')) return;
      if (e.target.closest('.media-index__item-copy')) return;
      if (e.target.closest('.media-index__item-check')) return;

      var cb = item.querySelector('.js-media-check');
      if (cb) {
        cb.checked = !cb.checked;
        updateBatchToolbar();
      }
    });

    /* Clear selection */
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        document.querySelectorAll('.js-media-check:checked').forEach(function (cb) {
          cb.checked = false;
        });
        updateBatchToolbar();
      });
    }

    /* Move selected → project selector */
    if (moveBtn) {
      moveBtn.addEventListener('click', function () {
        /* Recollir projectes dels projectGroups (generats al Twig) */
        var projectOptions = [];

        var optgroups = document.querySelectorAll('#uploadForm optgroup');
        optgroups.forEach(function (og) {
          Array.from(og.options).forEach(function (opt) {
            if (opt.value) {
              projectOptions.push({ id: opt.value, label: og.label + ' → ' + opt.text });
            }
          });
        });

        /* Construir llista HTML per al selector SweetAlert */
        var html = '<select id="swal-project-select" class="swal2-input" style="height:auto;padding:8px;">';
        html += '<option value="">Sense projecte</option>';
        projectOptions.forEach(function (p) {
          html += '<option value="' + p.id + '">' + p.label + '</option>';
        });
        html += '</select>';

        /* Obtenir els IDs seleccionats */
        var ids = [];
        document.querySelectorAll('.js-media-check:checked').forEach(function (cb) {
          ids.push(cb.value);
        });

        Swal.fire({
          icon: 'question',
          title: 'Moure ' + ids.length + ' imatge' + (ids.length !== 1 ? 's' : ''),
          html: '<label style="display:block;text-align:left;margin-bottom:6px;font-size:.85rem;color:#666;">Projecte destí:</label>' + html,
          showCancelButton: true,
          confirmButtonText: 'Moure',
          cancelButtonText: 'Cancel·lar',
          confirmButtonColor: '#f59e0b',
          reverseButtons: true,
          preConfirm: function () {
            var sel = document.getElementById('swal-project-select');
            return sel ? sel.value : '';
          }
        }).then(function (result) {
          if (!result.isConfirmed) return;

          var projectId = result.value;
          batchProjectId.value = projectId || '';

          /* Afegir els IDs seleccionats al form */
          ids.forEach(function (id) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'media_ids[]';
            inp.value = id;
            batchForm.appendChild(inp);
          });

          /* Enviar via fetch */
          var formData = new FormData(batchForm);
          fetch(batchForm.action, {
            method: 'POST',
            body: formData
          })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.error) {
              Swal.fire({ icon: 'error', title: 'Error', text: data.error });
              return;
            }
            Swal.fire({
              icon: 'success',
              title: 'Fet!',
              text: data.moved + ' imatge' + (data.moved !== 1 ? 's' : '') + ' moguda' + (data.moved !== 1 ? 's' : '') + ' a «' + data.to + '»',
              timer: 2000,
              showConfirmButton: false
            }).then(function () {
              location.reload();
            });
          })
          .catch(function () {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No s\'han pogut moure les imatges.' });
          });
        });
      });
    }

  });

})();
