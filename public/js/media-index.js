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

  });

})();
