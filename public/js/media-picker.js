/* ═══════════════════════════════════════════════════
   VoraCMS · Media Picker JavaScript
   Selection, upload, postMessage bridge
   ═══════════════════════════════════════════════════ */

(function () {
  'use strict';

  var configEl = document.getElementById('mediaPickerConfig');
  if (!configEl) return;
  var FIELD_ID = configEl.getAttribute('data-field-id') || '';
  var MULTIPLE = configEl.getAttribute('data-multiple') === 'true';
  var selected = new Set();
  var selectBtn = document.getElementById('selectBtn');

  function updateSelectBtn() {
    var count = selected.size;
    if (count === 0) {
      selectBtn.disabled = true;
      selectBtn.innerHTML = 'Seleccionar';
    } else if (MULTIPLE) {
      selectBtn.disabled = false;
      selectBtn.innerHTML = 'Enviar ' + count + ' imatges';
    } else {
      selectBtn.disabled = false;
      selectBtn.innerHTML = 'Seleccionar';
    }
  }

  document.querySelectorAll('.media-picker-item').forEach(function (el) {
    el.addEventListener('click', function () {
      var id = this.dataset.id;
      if (MULTIPLE) {
        if (selected.has(id)) {
          selected.delete(id);
          this.classList.remove('selected');
        } else {
          selected.add(id);
          this.classList.add('selected');
        }
      } else {
        selected.clear();
        document.querySelectorAll('.media-picker-item').forEach(function (i) { i.classList.remove('selected'); });
        selected.add(id);
        this.classList.add('selected');
      }
      updateSelectBtn();
    });
  });

  document.getElementById('selectBtn').addEventListener('click', function () {
    if (selected.size === 0) return;
    if (MULTIPLE) {
      var items = [];
      selected.forEach(function (id) {
        var el = document.querySelector('.media-picker-item[data-id="' + id + '"]');
        if (el) items.push({ id: id, url: el.dataset.url });
      });
      window.opener.postMessage({ type: 'media-pick-multi', fieldId: FIELD_ID, items: items }, '*');
    } else {
      var id = Array.from(selected)[0];
      var el = document.querySelector('.media-picker-item[data-id="' + id + '"]');
      window.opener.postMessage({ type: 'media-pick', fieldId: FIELD_ID, id: id, url: el ? el.dataset.url : '' }, '*');
    }
    setTimeout(function () { window.close(); }, 150);
  });

  document.getElementById('closeBtn').addEventListener('click', function () {
    window.close();
  });

  document.getElementById('uploadForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var btn = document.getElementById('uploadBtn');
    var msg = document.getElementById('uploadMsg');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Pujant...';

    /* Construir FormData manualment per garantir que TOTS els fitxers s'envien */
    var formData = new FormData();
    var fileInput = this.querySelector('input[type="file"]');
    if (fileInput && fileInput.files) {
      for (var fi = 0; fi < fileInput.files.length; fi++) {
        formData.append('files[' + fi + ']', fileInput.files[fi]);
      }
    }
    /* Copiar la resta de camps del formulari */
    var fields = this.querySelectorAll('input:not([type="file"]), select, textarea');
    for (var fi2 = 0; fi2 < fields.length; fi2++) {
      var f = fields[fi2];
      if (f.name && f.value) {
        formData.append(f.name, f.value);
      }
    }

    try {
      var res = await fetch(this.getAttribute('data-upload-url'), { method: 'POST', body: formData });
      var data = await res.json();
      if (data.error) {
        msg.innerHTML = '<span style="color:#EF5350;">' + data.error + '</span>';
      } else {
        var grid = document.getElementById('mediaGrid');
        var uploaded = data.uploaded || [];
        var errors = data.errors || [];
        var count = uploaded.length;

        uploaded.forEach(function (item) {
          var col = document.createElement('div');
          col.className = 'col-4 col-md-3';
          col.innerHTML =
            '<div class="media-picker-item card p-1 text-center selected" data-id="' + item.id + '" data-url="' + item.url + '">' +
            '<div class="media-picker-item__thumb">' +
            '<img src="' + item.url + '" alt="">' +
            '<div class="media-picker-item__check"><i class="bi bi-check"></i></div>' +
            '</div>' +
            '<small class="text-truncate d-block mt-1 media-picker-item__filename">' + item.filename + '</small>' +
            '</div>';
          grid.prepend(col);
          selected.add(String(item.id));
          col.querySelector('.media-picker-item').addEventListener('click', function () {
            var id = this.dataset.id;
            if (selected.has(id)) { selected.delete(id); this.classList.remove('selected'); updateSelectBtn(); }
            else { selected.add(id); this.classList.add('selected'); updateSelectBtn(); }
          });
        });

        var statusMsg = '';
        if (count > 0) {
          statusMsg = count + ' imatge' + (count > 1 ? 's' : '') + ' pujada' + (count > 1 ? 's' : '') + ' correctament.';
        }
        if (errors.length > 0) {
          var errorNames = errors.map(function (e) { return e.filename; }).join(', ');
          statusMsg += ' Errors: ' + errorNames;
          msg.style.color = '#FFA726';
        } else {
          msg.style.color = '#66BB6A';
        }
        msg.innerHTML = '<span>' + statusMsg + '</span>';
        updateSelectBtn();
        this.reset();
      }
    } catch (err) {
      msg.innerHTML = '<span style="color:#EF5350;">Error en pujar les imatges.</span>';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-upload"></i> Pujar';
  });

  updateSelectBtn();
})();
