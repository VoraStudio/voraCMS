/* ═══════════════════════════════════════════════════
   VoraCMS · Media Picker JavaScript
   Accordion projects, selection, upload, postMessage
   ═══════════════════════════════════════════════════ */

(function () {
  'use strict';

  var configEl = document.getElementById('mediaPickerConfig');
  if (!configEl) return;

  var FIELD_ID = configEl.getAttribute('data-field-id') || '';
  var MULTIPLE = configEl.getAttribute('data-multiple') === 'true';
  var selected = new Set();

  /* Both send buttons (top and bottom) */
  var sendBtns = document.querySelectorAll('.media-picker__send-btn');
  var selectedCount = document.getElementById('selectedCount');

  /* ─── Update send buttons ─── */
  function updateSendBtn() {
    var count = selected.size;
    sendBtns.forEach(function (btn) {
      if (count === 0) {
        btn.disabled = true;
        btn.querySelector('span').textContent = 'Enviar';
      } else if (MULTIPLE) {
        btn.disabled = false;
        btn.querySelector('span').textContent = 'Enviar ' + count + ' imatge' + (count > 1 ? 's' : '');
      } else {
        btn.disabled = false;
        btn.querySelector('span').textContent = 'Enviar';
      }
    });
    if (selectedCount) {
      if (count === 0) {
        selectedCount.textContent = '';
      } else if (MULTIPLE) {
        selectedCount.textContent = count + ' seleccionada' + (count > 1 ? 's' : '');
      } else {
        selectedCount.textContent = '1 seleccionada';
      }
    }
  }

  /* ─── Send action (shared by both buttons) ─── */
  function sendSelected() {
    if (selected.size === 0) return;
    if (MULTIPLE) {
      var items = [];
      selected.forEach(function (id) {
        var el = document.querySelector('.media-picker__item[data-id="' + id + '"]');
        if (el) items.push({ id: id, url: el.dataset.url });
      });
      window.opener.postMessage({ type: 'media-pick-multi', fieldId: FIELD_ID, items: items }, '*');
    } else {
      var id = Array.from(selected)[0];
      var el = document.querySelector('.media-picker__item[data-id="' + id + '"]');
      window.opener.postMessage({ type: 'media-pick', fieldId: FIELD_ID, id: id, url: el ? el.dataset.url : '' }, '*');
    }
    setTimeout(function () { window.close(); }, 150);
  }

  sendBtns.forEach(function (btn) {
    if (btn) btn.addEventListener('click', sendSelected);
  });

  var closeBtn = document.getElementById('closeBtn');
  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      window.close();
    });
  }

  /* ─── Delete item from media library ─── */
  function handleDelete(btn) {
    var item = btn.closest('.media-picker__item');
    if (!item) return;

    var id = item.dataset.id;
    var deleteUrl = item.dataset.deleteUrl;
    var csrfToken = item.dataset.csrfToken;
    if (!deleteUrl || !csrfToken) return;

    var confirmed = confirm('Eliminar aquesta imatge de la mediateca?');
    if (!confirmed) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass"></i>';

    var formData = new FormData();
    formData.append('_token', csrfToken);

    fetch(deleteUrl, { method: 'POST', body: formData })
      .then(function (res) {
        if (res.ok || res.redirected) {
          selected.delete(id);
          item.remove();
          updateSendBtn();
        } else {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-x"></i>';
          alert('Error en eliminar la imatge.');
        }
      })
      .catch(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x"></i>';
        alert('Error de connexió en eliminar la imatge.');
      });
  }

  /* ─── Item click: select or deselect ─── */
  function setupItemClick(el) {
    el.addEventListener('click', function (e) {
      if (e.target.closest('.media-picker__item-delete')) return;
      e.stopPropagation();
      var id = this.dataset.id;
      if (MULTIPLE) {
        if (selected.has(id)) {
          selected.delete(id);
          this.classList.remove('media-picker__item--sel');
          this.setAttribute('aria-selected', 'false');
        } else {
          selected.add(id);
          this.classList.add('media-picker__item--sel');
          this.setAttribute('aria-selected', 'true');
        }
      } else {
        selected.clear();
        document.querySelectorAll('.media-picker__item').forEach(function (i) {
          i.classList.remove('media-picker__item--sel');
          i.setAttribute('aria-selected', 'false');
        });
        selected.add(id);
        this.classList.add('media-picker__item--sel');
        this.setAttribute('aria-selected', 'true');
      }
      updateSendBtn();
    });
  }

  document.querySelectorAll('.media-picker__item').forEach(setupItemClick);

  /* ─── Delete delegation ─── */
  var projectsEl = document.querySelector('.media-picker__projects');
  if (projectsEl) {
    projectsEl.addEventListener('click', function (e) {
      var delBtn = e.target.closest('.media-picker__item-delete');
      if (delBtn) {
        e.stopPropagation();
        handleDelete(delBtn);
      }
    });
  }

  /* ─── File preview with remove ─── */
  var uploadForm = document.getElementById('uploadForm');
  var fileInput = uploadForm ? uploadForm.querySelector('input[type="file"]') : null;
  var previewEl = document.getElementById('uploadPreview');
  var fileDt = new DataTransfer();

  function renderPreview() {
    if (!previewEl) return;
    previewEl.innerHTML = '';
    var files = fileDt.files;
    if (files.length === 0) {
      previewEl.classList.remove('media-picker__preview--show');
      return;
    }
    previewEl.classList.add('media-picker__preview--show');
    for (var i = 0; i < files.length; i++) {
      (function (file, idx) {
        var div = document.createElement('div');
        div.className = 'media-picker__preview-item';
        div.style.position = 'relative';

        var img = document.createElement('img');
        var reader = new FileReader();
        reader.onload = function (e2) { img.src = e2.target.result; };
        reader.readAsDataURL(file);
        img.alt = file.name;

        var span = document.createElement('span');
        span.textContent = file.name;

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'media-picker__preview-remove';
        removeBtn.setAttribute('aria-label', 'Eliminar fitxer');
        removeBtn.innerHTML = '<i class="bi bi-x"></i>';
        removeBtn.addEventListener('click', function (ev) {
          ev.stopPropagation();
          var newDt = new DataTransfer();
          for (var j = 0; j < fileDt.files.length; j++) {
            if (j !== idx) newDt.items.add(fileDt.files[j]);
          }
          fileDt = newDt;
          fileInput.files = fileDt.files;
          renderPreview();
        });

        div.appendChild(img);
        div.appendChild(span);
        div.appendChild(removeBtn);
        previewEl.appendChild(div);
      })(files[i], i);
    }
  }

  if (fileInput && previewEl) {
    fileInput.addEventListener('change', function () {
      var newFiles = this.files;
      if (!newFiles || newFiles.length === 0) return;
      for (var k = 0; k < newFiles.length; k++) {
        fileDt.items.add(newFiles[k]);
      }
      this.files = fileDt.files;
      renderPreview();
    });
  }

  /* ─── Upload ─── */
  if (uploadForm) {
    uploadForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    var btn = document.getElementById('uploadBtn');
    var msg = document.getElementById('uploadMsg');
    if (!btn || !msg) return;

    if (fileDt.files.length === 0) {
      msg.innerHTML = '<span style="color:#FFA726;">Selecciona almenys un fitxer.</span>';
      return;
    }

    /* Must have an open card (selected project) */
    var targetCard = document.querySelector('.media-picker__card--open');
    if (!targetCard) {
      msg.innerHTML = '<span style="color:#FFA726;">Obre un projecte fent-hi clic. Les imatges noves s\'hi afegiran.</span>';
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="media-picker__spinner"></span> Pujant...';

    var formData = new FormData();
    for (var fi = 0; fi < fileDt.files.length; fi++) {
      formData.append('files[' + fi + ']', fileDt.files[fi]);
    }
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
        var uploaded = data.uploaded || [];
        var errors = data.errors || [];
        var count = uploaded.length;

        uploaded.forEach(function (item) {
          var el = document.createElement('div');
          el.className = 'media-picker__item media-picker__item--sel';
          el.dataset.id = item.id;
          el.dataset.url = item.url;
          el.dataset.deleteUrl = item.deleteUrl || '';
          el.dataset.csrfToken = item.csrfToken || '';
          el.setAttribute('role', 'option');
          el.setAttribute('aria-selected', 'true');
          el.innerHTML =
            '<div class="media-picker__item-thumb">' +
            '<img src="' + item.url + '" alt="' + (item.filename || '') + '" loading="lazy">' +
            '<button type="button" class="media-picker__item-delete" aria-label="Eliminar imatge" title="Eliminar"><i class="bi bi-x"></i></button>' +
            '<div class="media-picker__item-check"><i class="bi bi-check-lg"></i></div>' +
            '</div>';
          selected.add(String(item.id));

          var targetGrid = targetCard.querySelector('.media-picker__grid');
          if (targetGrid) targetGrid.prepend(el);
          var countEl = targetCard.querySelector('.media-picker__card-count');
          if (countEl) countEl.textContent = parseInt(countEl.textContent || '0') + 1;

          setupItemClick(el);
        });

        var statusMsg = '';
        if (count > 0) {
          statusMsg = count + ' imatge' + (count > 1 ? 's' : '') + ' pujada' + (count > 1 ? 's' : '') + ' correctament.';
        }
        if (errors.length > 0) {
          var errorNames = errors.map(function (er) { return er.filename; }).join(', ');
          statusMsg += ' Errors: ' + errorNames;
          msg.style.color = '#FFA726';
        } else {
          msg.style.color = '#66BB6A';
        }
        msg.innerHTML = '<span>' + statusMsg + '</span>';
        updateSendBtn();

        /* Reset file input and preview */
        fileDt = new DataTransfer();
        fileInput.value = '';
        fileInput.files = fileDt.files;
        renderPreview();
        this.reset();
      }
    } catch (err) {
      msg.innerHTML = '<span style="color:#EF5350;">Error en pujar les imatges.</span>';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-arrow-up"></i> Pujar';
  });
  } /* end if uploadForm */

  updateSendBtn();
})();
