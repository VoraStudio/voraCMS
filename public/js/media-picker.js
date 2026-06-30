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
    var formData = new FormData(this);
    try {
      var res = await fetch(this.getAttribute('data-upload-url'), { method: 'POST', body: formData });
      var data = await res.json();
      if (data.error) {
        msg.innerHTML = '<span style="color:#EF5350;">' + data.error + '</span>';
      } else {
        msg.innerHTML = '<span style="color:#66BB6A;">Imatge pujada correctament.</span>';
        var grid = document.getElementById('mediaGrid');
        var col = document.createElement('div');
        col.className = 'col-4 col-md-3';
        col.innerHTML =
          '<div class="media-picker-item card p-1 text-center selected" data-id="' + data.id + '" data-url="' + data.url + '">' +
          '<div class="media-picker-item__thumb">' +
          '<img src="' + data.url + '" alt="">' +
          '<div class="media-picker-item__check"><i class="bi bi-check"></i></div>' +
          '</div>' +
          '<small class="text-truncate d-block mt-1 media-picker-item__filename">' + data.filename + '</small>' +
          '</div>';
        grid.prepend(col);
        selected.add(String(data.id));
        updateSelectBtn();
        col.querySelector('.media-picker-item').addEventListener('click', function () {
          var id = this.dataset.id;
          if (selected.has(id)) { selected.delete(id); this.classList.remove('selected'); updateSelectBtn(); }
          else { selected.add(id); this.classList.add('selected'); updateSelectBtn(); }
        });
        this.reset();
      }
    } catch (err) {
      msg.innerHTML = '<span style="color:#EF5350;">Error en pujar la imatge.</span>';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-upload"></i> Pujar';
  });

  updateSelectBtn();
})();
