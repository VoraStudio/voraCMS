/* ═══════════════════════════════════════════════════
   VoraCMS · Media Index
   Upload AJAX, file preview, copy URL
   ═══════════════════════════════════════════════════ */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    /* ─── Upload form ─── */
    var uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
      uploadForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        var uploadUrl = this.getAttribute('data-upload-url');
        if (!uploadUrl) return;

        var btn = document.getElementById('uploadBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Pujant...';

        var formData = new FormData(this);
        try {
          var res = await fetch(uploadUrl, { method: 'POST', body: formData });
          var data = await res.json();
          if (data.error) {
            alert(data.error);
          } else {
            location.reload();
          }
        } catch (err) {
          alert('Error en pujar la imatge.');
        }
        btn.disabled = false;
        btn.innerHTML = 'Pujar';
      });
    }

    /* ─── File preview ─── */
    var fileInput = document.querySelector('#uploadModal input[type="file"]');
    if (fileInput) {
      fileInput.addEventListener('change', function () {
        var preview = document.getElementById('uploadPreview');
        if (!preview) return;
        if (this.files && this.files[0]) {
          var reader = new FileReader();
          reader.onload = function (e) {
            preview.querySelector('img').src = e.target.result;
            preview.classList.remove('d-none');
          };
          reader.readAsDataURL(this.files[0]);
        } else {
          preview.classList.add('d-none');
        }
      });
    }

    /* ─── Copy URL ─── */
    document.querySelectorAll('.copy-url').forEach(function (btn) {
      btn.addEventListener('click', function () {
        navigator.clipboard.writeText(this.dataset.url);
        var orig = this.innerHTML;
        this.innerHTML = '<i class="bi bi-check"></i>';
        setTimeout(function () { btn.innerHTML = orig; }, 1500);
      });
    });

    /* ═══════════════════════════════════════════════════
       Media Browser — acordeó per projecte (single-open per client)
       ═══════════════════════════════════════════════════ */
    var browser = document.getElementById('mediaBrowser');
    if (browser) {
      /* Per cada client, gestionar els seus projectes independentment */
      var clientCards = browser.querySelectorAll('.media-client-card');

      clientCards.forEach(function (client) {
        var projectCards = client.querySelectorAll('.media-project-card');

        function closeAllProjects (skip) {
          projectCards.forEach(function (pc) {
            if (pc !== skip) {
              pc.classList.remove('media-project-card--open');
              var h = pc.querySelector('.media-project-card__header');
              if (h) { h.setAttribute('aria-expanded', 'false'); }
            }
          });
        }

        function openProject (pc) {
          pc.classList.add('media-project-card--open');
          var h = pc.querySelector('.media-project-card__header');
          if (h) { h.setAttribute('aria-expanded', 'true'); }
        }

        projectCards.forEach(function (pc) {
          var header = pc.querySelector('.media-project-card__header');
          if (!header) { return; }

          header.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = pc.classList.contains('media-project-card--open');
            if (isOpen) {
              closeAllProjects();
            } else {
              closeAllProjects();
              openProject(pc);
            }
          });

          /* Keyboard: Enter / Space */
          header.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              header.click();
            }
          });
        });
      });

      /* Click outside → close all project cards across all clients */
      document.addEventListener('click', function (e) {
        if (!browser.contains(e.target)) {
          browser.querySelectorAll('.media-project-card--open').forEach(function (pc) {
            pc.classList.remove('media-project-card--open');
            var h = pc.querySelector('.media-project-card__header');
            if (h) { h.setAttribute('aria-expanded', 'false'); }
          });
        }
      });
    }

  });

})();
