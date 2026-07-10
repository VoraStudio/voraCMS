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

  });

})();
