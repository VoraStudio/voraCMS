/* ═══════════════════════════════════════════════════
   VoraCMS · Admin Core JavaScript
   SweetAlert, flash toasts, theme toggle, form validation,
   toggle AJAX, color picker, inline event handlers
   ═══════════════════════════════════════════════════ */

(function () {
  'use strict';

  /* ─── Global: swalConfirm (must be on window for inline onclick migration) ─── */
  window.swalConfirm = function (msg, cb) {
    Swal.fire({
      html: '<div class="swal-icon-wrap"><div class="swal-icon-inner"><i class="bi bi-exclamation-triangle"></i></div></div><p class="swal-msg">' + msg + '</p>',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancel·lar',
      confirmButtonColor: '#DC2626',
      reverseButtons: true,
      background: 'linear-gradient(135deg, rgba(18,22,55,0.98), rgba(12,15,40,0.98))',
      backdrop: 'rgba(0,0,0,0.65)',
      color: 'rgba(255,255,255,0.90)',
      padding: '32px 28px 28px',
      width: '420px',
      showClass: { popup: 'swal-popup-in' },
      hideClass: { popup: 'swal-popup-out' },
      customClass: {
        popup: 'swal-popup-custom',
        confirmButton: 'btn btn-sm px-4 swal-confirm-btn',
        cancelButton: 'btn btn-outline-secondary btn-sm px-4',
        htmlContainer: 'swal-html-custom',
        actions: 'swal-actions-custom'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.isConfirmed && cb) cb();
    });
  };

  document.addEventListener('DOMContentLoaded', function () {

    /* ─── Flash messages → SweetAlert toasts ─── */
    var flashEl = document.getElementById('flashData');
    if (flashEl) {
      try {
        var flashes = JSON.parse(flashEl.getAttribute('data-flash'));
        var icons = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
        var gradMap = {
          success: ['#065F46', '#10B981'],
          error: ['#7F1D1D', '#EF4444'],
          warning: ['#78350F', '#F59E0B'],
          info: ['#1E3A5F', '#3B82F6']
        };
        Object.keys(flashes).forEach(function (type) {
          flashes[type].forEach(function (msg) {
            var g = gradMap[type];
            var iconName = type === 'success' ? 'check-lg' : type === 'error' ? 'x-lg' : type === 'warning' ? 'dash-lg' : 'info-lg';
            Swal.fire({
              toast: true,
              position: 'bottom-end',
              html: '<div style="display:flex;align-items:center;gap:12px;">' +
                '<div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
                '<i class="bi bi-' + iconName + '" style="color:#fff;font-size:1rem;"></i></div>' +
                '<div style="color:#fff;font-size:0.85rem;font-weight:500;line-height:1.4;">' + msg + '</div></div>',
              background: 'linear-gradient(135deg, ' + g[0] + ', ' + g[1] + ')',
              icon: icons[type],
              iconColor: 'transparent',
              showConfirmButton: false,
              timer: 4500,
              timerProgressBar: true,
              customClass: { popup: 'swal-flash-toast' }
            });
          });
        });
      } catch (e) { /* silent */ }
    }

    /* ─── Theme Toggle ─── */
    var html = document.documentElement;
    var toggle = document.getElementById('themeToggle');
    var saved = localStorage.getItem('voracms_theme');

    if (saved === 'light') {
      html.setAttribute('data-theme', 'light');
    }

    if (toggle) {
      toggle.addEventListener('click', function () {
        var isLight = html.getAttribute('data-theme') === 'light';
        if (isLight) {
          html.removeAttribute('data-theme');
          localStorage.setItem('voracms_theme', 'dark');
        } else {
          html.setAttribute('data-theme', 'light');
          localStorage.setItem('voracms_theme', 'light');
        }
      });
    }

    /* ─── Form validation ─── */
    document.querySelectorAll('form').forEach(function (form) {
      form.setAttribute('novalidate', '');

      function isQuillEmpty(wrapper) {
        if (!wrapper) return false;
        var editorEl = wrapper.querySelector('.js-quill-editor');
        if (!editorEl) return false;
        var quill = Quill.find(editorEl);
        if (quill) return quill.getText().trim().length === 0;
        return !editorEl.textContent.trim();
      }

      form.addEventListener('submit', function (e) {
        form.querySelectorAll('.is-invalid, .quill-wrapper.is-invalid').forEach(function (el) {
          el.classList.remove('is-invalid');
        });
        var firstInvalid = null;
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
          el.classList.remove('is-invalid');
          var isRequired = el.hasAttribute('required') || el.dataset.required === 'true';
          if (!isRequired) return;

          var quillWrapper = el.closest('.quill-wrapper');
          if (quillWrapper && isQuillEmpty(quillWrapper)) {
            el.classList.add('is-invalid');
            quillWrapper.classList.add('is-invalid');
            if (!firstInvalid) firstInvalid = el;
            return;
          }

          if (!el.value || !el.value.trim()) {
            el.classList.add('is-invalid');
            if (!firstInvalid) firstInvalid = el;
          }
        });
        if (firstInvalid) {
          e.preventDefault();
          firstInvalid.focus({ preventScroll: true });
          firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });

      form.querySelectorAll('input, select, textarea').forEach(function (el) {
        el.addEventListener('input', function () {
          this.classList.remove('is-invalid');
          var wrapper = this.closest('.quill-wrapper');
          if (wrapper) wrapper.classList.remove('is-invalid');
        });
        el.addEventListener('change', function () {
          this.classList.remove('is-invalid');
          var wrapper = this.closest('.quill-wrapper');
          if (wrapper) wrapper.classList.remove('is-invalid');
        });
      });
    });

    /* ─── Color picker preview ─── */
    var colorPicker = document.querySelector('input[type="color"]');
    var colorPreview = document.getElementById('colorPreview');
    if (colorPicker) {
      colorPicker.addEventListener('input', function () {
        if (colorPreview) {
          colorPreview.textContent = this.value;
        }
      });
    }

    /* ─── Toggle AJAX (event delegation) ─── */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.toggle-btn');
      if (!btn) return;
      var f = btn.closest('form');
      if (!f) return;

      /* Project toggle submits normally (not AJAX) */
      if (btn.type === 'submit' && !btn.classList.contains('toggle-btn-ajax')) {
        /* Do nothing for submit-button toggles — let them submit naturally */
        return;
      }
      if (btn.type === 'submit') return;

      e.preventDefault();
      fetch(f.action, {
        method: 'POST',
        body: new FormData(f),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.error) return;
          var a = d.active;
          btn.className = 'cyber-btn cyber-btn--icon toggle-btn ' + (a ? 'cyber-btn--toggle-active' : 'cyber-btn--toggle-inactive');
          btn.title = a ? 'Desactivar' : 'Activar';
          btn.dataset.active = a ? '1' : '0';
          btn.querySelector('i').className = 'bi ' + (a ? 'bi-toggle2-on' : 'bi-toggle2-off');

          var row = btn.closest('tr');
          if (row) {
            row.className = 'cyber-row ' + (a ? 'row-active' : 'row-inactive');

            /* Update status cell — try multiple data-label values */
            var cell = row.querySelector('[data-label="Actiu"]') ||
                       row.querySelector('[data-label="Estat"]');
            if (cell) {
              cell.innerHTML = a
                ? '<span class="cyber-status"><span class="cyber-status-dot cyber-status-dot--active"></span><span class="cyber-status-text">Actiu</span></span>'
                : '<span class="cyber-status"><span class="cyber-status-dot cyber-status-dot--inactive"></span><span class="cyber-status-text cyber-status-text--inactive">Inactiu</span></span>';
            }
          }
        });
    });

    /* ─── Inline event handlers replacement ─── */

    /* Sidebar toggle */
    var sidebarToggle = document.querySelector('.s-sidebar-toggle');
    var sidebarOverlay = document.querySelector('.s-sidebar-overlay');
    var sidebar = document.getElementById('adminSidebar');

    function toggleSidebar() {
      if (sidebar) sidebar.classList.toggle('open');
    }

    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', toggleSidebar);
    }
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    /* Delete confirmation via data-confirm attribute */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-confirm]');
      if (!btn) return;
      e.preventDefault();
      var msg = btn.getAttribute('data-confirm');
      var formId = btn.getAttribute('data-form');
      if (formId) {
        swalConfirm(msg, function () {
          var form = document.getElementById(formId);
          if (form) form.submit();
        });
      }
    });

    /* Data-link: navigate on click */
    document.addEventListener('click', function (e) {
      var el = e.target.closest('[data-link]');
      if (!el) return;
      var link = el.getAttribute('data-link');
      if (link) window.location = link;
    });

    /* Stop propagation on no-propagate and cyber-cell--actions */
    document.addEventListener('click', function (e) {
      if (e.target.closest('.no-propagate') || e.target.closest('.cyber-cell--actions')) {
        e.stopPropagation();
      }
    });

    /* ─── Field type toggle (content-type / base-content fields) ─── */
    function toggleFieldWrap(select) {
      var row = select.closest('.field-row');
      if (row) {
        var wrap = row.querySelector('.field-options-wrap');
        if (wrap) {
          var isSelect = select.value === 'select';
          wrap.style.display = isSelect ? 'block' : 'none';
        }
      }
    }

    /* Initial toggle for existing fields */
    document.querySelectorAll('[data-field-type="toggle"]').forEach(function (sel) {
      toggleFieldWrap(sel);
    });

    /* Delegated change listener */
    document.addEventListener('change', function (e) {
      if (e.target.matches('[data-field-type="toggle"]')) {
        toggleFieldWrap(e.target);
      }
    });

    /* ─── FAQ Panel ─── */
    var faqBtn = document.querySelector('.faq-btn');
    var faqPanel = document.getElementById('faqPanel');
    if (faqBtn && faqPanel) {
      faqBtn.addEventListener('click', function () {
        var isOpen = faqPanel.classList.contains('faq-panel--open');
        if (isOpen) {
          faqPanel.classList.remove('faq-panel--open');
          faqPanel.setAttribute('aria-hidden', 'true');
        } else {
          faqPanel.classList.add('faq-panel--open');
          faqPanel.setAttribute('aria-hidden', 'false');
        }
      });

      /* Close btn */
      var faqClose = faqPanel.querySelector('.faq-panel__close');
      if (faqClose) {
        faqClose.addEventListener('click', function () {
          faqPanel.classList.remove('faq-panel--open');
          faqPanel.setAttribute('aria-hidden', 'true');
        });
      }

      /* Backdrop click */
      var faqBackdrop = faqPanel.querySelector('.faq-panel__backdrop');
      if (faqBackdrop) {
        faqBackdrop.addEventListener('click', function () {
          faqPanel.classList.remove('faq-panel--open');
          faqPanel.setAttribute('aria-hidden', 'true');
        });
      }

      /* Accordion */
      faqPanel.querySelectorAll('.faq-item__question').forEach(function (q) {
        q.addEventListener('click', function () {
          var item = this.parentElement;
          var isOpen = item.classList.contains('faq-item--open');
          item.classList.toggle('faq-item--open');
          this.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });
      });
    }

    /* WOW init */
    if (typeof WOW !== 'undefined') {
      new WOW().init();
    }

    /* ─── Modal de Previsualització (Estil Victoria Taylor) ─── */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.js-preview-modal');
      if (!btn) return;
      e.preventDefault();
      
      var url = btn.getAttribute('href');
      var type = btn.getAttribute('data-type') || 'event';
      openPreviewModal(url, type);
    });

    /* ─── Obrir preview personalitzada en pestanya nova ─── */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.js-preview-newtab');
      if (!btn) return;
      e.preventDefault();
      window.open(btn.getAttribute('href'), '_blank');
    });

    function openPreviewModal(url, type) {
      var existing = document.querySelector('.vt-preview-modal');
      if (existing) existing.remove();

      /* Passar el tema del admin al preview via query param */
      var saved = localStorage.getItem('voracms_theme');
      var theme = saved || 'dark';
      url += (url.indexOf('?') === -1 ? '?' : '&') + 'theme=' + encodeURIComponent(theme);

      var containerClass = 'vt-preview-modal-container';
      if (type === 'noticia') {
        containerClass += ' vt-preview-modal-container--noticia';
      } else {
        containerClass += ' vt-preview-modal-container--event';
      }

      var modalHTML = 
        '<div class="vt-preview-modal" aria-hidden="true" role="dialog">' +
          '<div class="vt-preview-modal-overlay"></div>' +
          '<div class="' + containerClass + '">' +
            '<button class="vt-preview-modal-close" aria-label="Tancar">' +
              '<i class="bi bi-x-lg"></i>' +
            '</button>' +
            '<div class="vt-preview-modal-iframe-wrapper">' +
              '<iframe class="vt-preview-modal-iframe" src="' + url + '"></iframe>' +
            '</div>' +
          '</div>' +
        '</div>';

      document.body.insertAdjacentHTML('beforeend', modalHTML);

      var modal = document.querySelector('.vt-preview-modal');
      var container = modal.querySelector('.vt-preview-modal-container');
      var overlay = modal.querySelector('.vt-preview-modal-overlay');
      var closeBtn = modal.querySelector('.vt-preview-modal-close');

      // Animació d'entrada amb GSAP (que ja està carregat al layout)
      modal.setAttribute('aria-hidden', 'false');
      gsap.set(modal, { display: 'flex', opacity: 0 });
      gsap.to(modal, { opacity: 1, duration: 0.3, ease: 'power2.out' });
      gsap.fromTo(container, 
        { scale: 0.9, opacity: 0, y: 20 }, 
        { scale: 1, opacity: 1, y: 0, duration: 0.4, ease: 'power3.out', delay: 0.1 }
      );

      function closeModal() {
        gsap.to(container, { scale: 0.9, opacity: 0, y: 20, duration: 0.3, ease: 'power3.in' });
        gsap.to(modal, {
          opacity: 0,
          duration: 0.3,
          ease: 'power2.in',
          onComplete: function () {
            modal.remove();
          }
        });
      }

      closeBtn.addEventListener('click', closeModal);
      overlay.addEventListener('click', closeModal);
      
      var escHandler = function (e) {
        if (e.key === 'Escape') {
          closeModal();
          document.removeEventListener('keydown', escHandler);
        }
      };
      document.addEventListener('keydown', escHandler);
    }
  });

})();
