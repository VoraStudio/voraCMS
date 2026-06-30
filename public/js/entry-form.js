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
      div.style.cssText = 'position:relative;width:80px;height:80px;border-radius:4px;overflow:hidden;background:var(--s-bg);';
      div.innerHTML = '<img src="' + mediaUrl + '" style="width:100%;height:100%;object-fit:cover;"><button type="button" class="remove-gallery-item" aria-label="Eliminar" style="position:absolute;top:3px;right:3px;width:18px;height:18px;border-radius:50%;border:none;background:rgba(0,0,0,0.50);color:#fff;font-size:12px;line-height:1;padding:0;cursor:pointer;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.15s;"><i class="bi bi-x" style="font-size:10px;"></i></button>';
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
            div.style.cssText = 'position:relative;width:80px;height:80px;border-radius:4px;overflow:hidden;background:var(--s-bg);';
            div.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;"><button type="button" class="remove-gallery-item" aria-label="Eliminar" style="position:absolute;top:3px;right:3px;width:18px;height:18px;border-radius:50%;border:none;background:rgba(0,0,0,0.50);color:#fff;font-size:12px;line-height:1;padding:0;cursor:pointer;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.15s;"><i class="bi bi-x" style="font-size:10px;"></i></button>';
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
    document.querySelectorAll('.quill-wrapper').forEach(function (wrapper) {
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
    });

    /* ─── Quill sync on form submit ─── */
    var entryForm = document.getElementById('entryForm');
    if (entryForm) {
      entryForm.addEventListener('submit', function () {
        document.querySelectorAll('.quill-wrapper').forEach(function (wrapper) {
          var editorEl = wrapper.querySelector('.js-quill-editor');
          var textarea = wrapper.querySelector('textarea');
          var quill = Quill.find(editorEl);
          if (quill) textarea.value = quill.root.innerHTML;
        });
      });
    }
  });

})();
