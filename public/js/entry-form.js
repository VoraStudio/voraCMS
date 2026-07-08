/* ═══════════════════════════════════════════════════
   VoraCMS · Entry Form JavaScript
   Quill editor, YouTube preview, gallery management,
   media picker bridge
   ═══════════════════════════════════════════════════ */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    /* ─── extractYoutubeId ─── */
    function extractYoutubeId(url) {
      if (!url) return null;
      var m = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
      return m ? m[1] : null;
    }

    /* ─── addMediaToGallery (unified) ─── */
    function addMediaToGallery(fieldId, mediaId, mediaUrl) {
      var galField = document.querySelector('.gallery-field[data-field-id="' + fieldId + '"]');
      if (!galField) return;
      var previews = galField.querySelector('.gallery-previews');
      var hidden = galField.querySelector('.gallery-value');
      var div = document.createElement('div');
      div.className = 'gallery-thumb';
      div.innerHTML = '<img src="' + mediaUrl + '" alt=""><button type="button" class="remove-gallery-item" aria-label="Eliminar"><i class="bi bi-x"></i></button>';
      previews.appendChild(div);
      var current = hidden.value ? hidden.value.split(',') : [];
      current.push(mediaId);
      hidden.value = current.join(',');
    }

    /* ─── YouTube preview ─── */
    document.querySelectorAll('.youtube-input').forEach(function (input) {
      function updatePreview() {
        var preview = input.closest('.mb-3').querySelector('.youtube-preview');
        if (!preview) return;
        var iframe = preview.querySelector('iframe');
        var id = extractYoutubeId(input.value);
        if (id) {
          iframe.src = 'https://www.youtube.com/embed/' + id;
          preview.classList.remove('d-none');
        } else {
          preview.classList.add('d-none');
          iframe.src = '';
        }
      }
      input.addEventListener('input', updatePreview);
      updatePreview();
    });

    /* ─── Gallery / Image: preview on file pick ─── */
    document.querySelectorAll('.gallery-field input[type="file"]').forEach(function (input) {
      input.addEventListener('change', function () {
        var previews = this.closest('.gallery-field').querySelector('.gallery-previews');
        var hidden = this.closest('.gallery-field').querySelector('.gallery-value');
        Array.from(this.files).forEach(function (file) {
          var reader = new FileReader();
          reader.onload = function (e) {
            var div = document.createElement('div');
            div.className = 'gallery-thumb';
            div.innerHTML = '<img src="' + e.target.result + '" alt=""><button type="button" class="remove-gallery-item" aria-label="Eliminar"><i class="bi bi-x"></i></button>';
            previews.appendChild(div);
          };
          reader.readAsDataURL(file);
        });
        hidden.value = hidden.value ? hidden.value + ',__upload__' : '__upload__';
      });
    });

    /* ─── Remove gallery items (event delegation) ─── */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.remove-gallery-item');
      if (!btn) return;
      var preview = btn.parentElement;
      var field = btn.closest('.gallery-field');
      if (field) {
        var hidden = field.querySelector('.gallery-value');
        var allItems = field.querySelectorAll('.gallery-previews > div');
        var idx = Array.from(allItems).indexOf(preview);
        var ids = hidden.value ? hidden.value.split(',') : [];
        if (idx >= 0 && idx < ids.length) ids.splice(idx, 1);
        hidden.value = ids.join(',');
      }
      preview.remove();
    });

    /* ─── Media picker modal ─── */
    var configEl = document.getElementById('entryFormConfig');
    var mediaPickerUrl = configEl ? configEl.getAttribute('data-media-picker-url') : null;
    document.querySelectorAll('.pick-media').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var fieldId = this.dataset.field;
        var multiple = this.dataset.multiple === 'true';
        var pickerUrl = mediaPickerUrl ? mediaPickerUrl.replace('__FIELD_ID__', encodeURIComponent(fieldId)) + '&multiple=' + multiple : null;
        if (pickerUrl) window.open(pickerUrl, 'mediaPicker', 'width=750,height=600');
      });
    });

    /* ─── PostMessage handler from media picker ─── */
    window.addEventListener('message', function (e) {
      if (e.data.type === 'media-pick') {
        addMediaToGallery(e.data.fieldId || e.data.field, e.data.id, e.data.url);
      }
      if (e.data.type === 'media-pick-multi') {
        e.data.items.forEach(function (item) {
          addMediaToGallery(e.data.fieldId, item.id, item.url);
        });
      }
    });

    /* ─── Quill editor init ─── */
    if (typeof Quill !== 'undefined') {
      document.querySelectorAll('.quill-wrapper').forEach(function (wrapper) {
        try {
          var editorEl = wrapper.querySelector('.js-quill-editor');
          var textarea = wrapper.querySelector('textarea');
          if (!editorEl || !textarea) return;

          var quill = new Quill(editorEl, {
            theme: 'snow',
            modules: {
              toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'header': [1, 2, 3, false] }],
                ['link', 'clean'],
                [{ 'align': [] }],
                ['blockquote', 'code-block']
              ]
            },
            placeholder: 'Escriu aquí...'
          });

          /* Restore initial value */
          if (textarea.value) {
            quill.root.innerHTML = textarea.value;
          }

          /* Sync on text change */
          quill.on('text-change', function () {
            textarea.value = quill.root.innerHTML;
          });
        } catch (e) {
          console.warn('Quill init failed for', wrapper, e);
        }
      });
    }

    /* ─── Quill sync on form submit ─── */
    var entryForm = document.getElementById('entryForm');
    if (entryForm) {
      entryForm.addEventListener('submit', function () {
        if (typeof Quill !== 'undefined') {
          document.querySelectorAll('.quill-wrapper').forEach(function (wrapper) {
            try {
              var editorEl = wrapper.querySelector('.js-quill-editor');
              var textarea = wrapper.querySelector('textarea');
              var quill = Quill.find(editorEl);
              if (quill) textarea.value = quill.root.innerHTML;
            } catch (e) {}
          });
        }
        /* Sync all repeaters before submit */
        document.querySelectorAll('.repeater-field').forEach(function (field) {
          syncRepeater(field);
        });
      });
    }

    /* ═══════════════════════════════════════════════════
       Repeater Logic
       ═══════════════════════════════════════════════════ */

    function getRepeaterData(container) {
      var items = [];
      container.querySelectorAll('.repeater-row').forEach(function (row) {
        var año = row.querySelector('.repeater-row__año').value.trim();
        var texto = row.querySelector('.repeater-row__texto').value.trim();
        if (año || texto) {
          items.push({ año: año, texto: texto });
        }
      });
      return items;
    }

    function syncRepeater(container) {
      var hidden = container.querySelector('.repeater-value');
      if (!hidden) return;
      hidden.value = JSON.stringify(getRepeaterData(container));
    }

    function createRepeaterRow(item) {
      var row = document.createElement('div');
      row.className = 'repeater-row';
      row.innerHTML =
        '<div class="repeater-row__fields">' +
          '<input type="text" class="form-control form-control-sm repeater-row__año" placeholder="Any (ex: 2024)" value="' + (item.año || '') + '">' +
          '<textarea class="form-control form-control-sm repeater-row__texto" rows="2" placeholder="Descripció del logro">' + (item.texto || '') + '</textarea>' +
        '</div>' +
        '<button type="button" class="repeater-row__remove" aria-label="Eliminar" tabindex="-1">' +
          '<i class="bi bi-x"></i>' +
        '</button>';
      return row;
    }

    function initRepeater(container) {
      var hidden = container.querySelector('.repeater-value');
      var rowsContainer = container.querySelector('.repeater-rows');
      var addBtn = container.querySelector('.repeater-add');
      if (!hidden || !rowsContainer || !addBtn) return;

      /* Add button */
      addBtn.addEventListener('click', function () {
        var row = createRepeaterRow({ año: '', texto: '' });
        rowsContainer.appendChild(row);
        syncRepeater(container);
        row.querySelector('.repeater-row__año').focus();
      });

      /* Delegate remove + change */
      rowsContainer.addEventListener('click', function (e) {
        var btn = e.target.closest('.repeater-row__remove');
        if (!btn) return;
        var row = btn.closest('.repeater-row');
        if (row) {
          row.remove();
          syncRepeater(container);
        }
      });

      rowsContainer.addEventListener('input', function () {
        syncRepeater(container);
      });
    }

    /* Init all repeaters on load */
    document.querySelectorAll('.repeater-field').forEach(function (el) {
      initRepeater(el);
    });
  });

})();
