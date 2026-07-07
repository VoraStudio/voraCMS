/* ═══════════════════════════════════════════════════
   VoraCMS · Content-Type Field Management
   Field clone/remove + auto-slug generation + select options
   ═══════════════════════════════════════════════════ */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    /* ─── Add field row ─── */
    var addBtn = document.getElementById('addFieldBtn');
    if (addBtn) {
      addBtn.addEventListener('click', function () {
        var container = document.getElementById('fieldsContainer');
        if (!container) return;
        var row = container.querySelector('.field-row');
        if (!row) return;
        var clone = row.cloneNode(true);
        clone.querySelectorAll('input[type="text"]').forEach(function (i) { i.value = ''; });
        clone.querySelectorAll('input[type="checkbox"]').forEach(function (c) { c.checked = false; });
        clone.querySelectorAll('textarea').forEach(function (t) { t.value = ''; });
        container.appendChild(clone);
      });
    }

    /* ─── Remove field row ─── */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.remove-field');
      if (!btn) return;
      var rows = document.querySelectorAll('.field-row');
      if (rows.length > 1) {
        btn.closest('.field-row').remove();
      }
    });

    /* ─── Auto-slug from name input (new content-type only) ─── */
    var nameInput = document.getElementById('nameInput');
    var slugInput = document.getElementById('slugInput');
    if (nameInput && slugInput) {
      nameInput.addEventListener('input', function () {
        var slug = this.value
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .replace(/[^a-z0-9]+/g, '_')
          .replace(/^_|_$/g, '');
        slugInput.value = slug;
      });
    }

  });

})();

/* ─── Toggle field options (select type) ─── */
function toggleFieldOptions(selectEl) {
  var row = selectEl.closest('.field-row');
  if (!row) return;
  var wrap = row.querySelector('.field-options-wrap');
  if (!wrap) return;
  wrap.style.display = selectEl.value === 'select' ? '' : 'none';
}
