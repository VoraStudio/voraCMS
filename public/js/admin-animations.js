/* ═══════════════════════════════════════════════════
   VoraCMS · Admin Entrance Animations
   GSAP-powered, respects prefers-reduced-motion
   ═══════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function () {
  var mm = gsap.matchMedia();

  /* ─── Full animations (no reduced motion) ─── */
  mm.add('(prefers-reduced-motion: no-preference)', function () {

    var tl = gsap.timeline();

    /* ─── 1. Sidebar links ─── */
    var sidebarLinks = gsap.utils.toArray('.s-sidebar-link');
    if (sidebarLinks.length) {
      tl.from(sidebarLinks,
        { opacity: 0, x: -14, duration: 0.25, stagger: 0.025, ease: 'power2.out', clearProps: 'transform' }
      );
    }

    /* ─── 2. Mètriques del dashboard (stagger lent) ─── */
    var metricCards = gsap.utils.toArray('[data-anim="metric"]');
    if (metricCards.length) {
      tl.from(metricCards,
        { opacity: 0, y: 20, duration: 0.45, stagger: 0.15, ease: 'power3.out', clearProps: 'transform' },
        '-=0.1'
      );
    }

    /* ─── 3. Cards de contingut del dashboard ─── */
    var contentCards = gsap.utils.toArray('[data-anim="card"]');
    if (contentCards.length) {
      tl.from(contentCards,
        { opacity: 0, y: 40, duration: 0.5, stagger: 0.12, ease: 'power3.out', clearProps: 'transform' },
        '-=0.1'
      );
    }

    /* ─── 4. Stat cards ─── */
    var statCards = gsap.utils.toArray('.s-stat-card:not([data-anim="metric"]):not([data-anim="card"])');
    if (statCards.length) {
      tl.from(statCards,
        { opacity: 0, y: 20, duration: 0.35, stagger: 0.08, ease: 'power2.out', clearProps: 'transform' },
        '-=0.05'
      );
    }

    /* ─── 5. Cyber card ─── */
    var cyberCards = gsap.utils.toArray('[data-anim="cyber-card"]');
    if (cyberCards.length) {
      tl.from(cyberCards,
        { opacity: 0, y: 24, scale: 0.98, duration: 0.5, ease: 'power3.out', clearProps: 'transform' },
        '-=0.05'
      );
    }

    /* ─── 6. Cyber rows (stagger) ─── */
    var cyberRows = gsap.utils.toArray('[data-anim="cyber-row"]');
    if (cyberRows.length) {
      tl.from(cyberRows,
        { opacity: 0, x: -16, duration: 0.35, stagger: 0.04, ease: 'power2.out', clearProps: 'transform' },
        '-=0.1'
      );
    }

    /* ─── 7. Cyber buttons (topbar actions) ─── */
    var cyberBtns = gsap.utils.toArray('[data-anim="cyber-btn"]');
    if (cyberBtns.length) {
      tl.from(cyberBtns,
        { y: -8, scale: 0.92, duration: 0.3, stagger: 0.06, ease: 'back.out(1.5)', clearProps: 'transform' },
        '-=0.1'
      );
    }

    /* ─── 8. Table rows (existing fallback) ─── */
    var tableRows = gsap.utils.toArray('.table-hover tbody tr');
    if (tableRows.length && !cyberRows.length) {
      tl.from(tableRows,
        { opacity: 0, y: 10, duration: 0.25, stagger: 0.025, ease: 'power1.out', clearProps: 'transform' },
        '-=0.05'
      );
    }

    /* ─── 9. Media items ─── */
    var mediaItems = gsap.utils.toArray('.row.g-3 > [class*="col-"] .card');
    if (mediaItems.length) {
      tl.from(mediaItems,
        { opacity: 0, y: 14, duration: 0.3, stagger: 0.04, ease: 'power1.out', clearProps: 'transform' },
        '-=0.05'
      );
    }
  });

  /* ─── Reduced motion: fade only ─── */
  mm.add('(prefers-reduced-motion: reduce)', function () {
    var fadeEls = document.querySelectorAll(
      '[data-anim="metric"], [data-anim="card"], [data-anim="cyber-card"], [data-anim="cyber-row"], .s-stat-card, .table-hover tbody tr, .s-sidebar-link'
    );
    if (fadeEls.length) {
      gsap.from(fadeEls,
        { opacity: 0, duration: 0.15, stagger: 0.01, ease: 'none' }
      );
    }
  });
});
