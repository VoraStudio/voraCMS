/* ===========================================================
   preview.js — GSAP animations for entry preview page
   Replicates dynamic-content.js animateSections() behavior
   =========================================================== */

document.addEventListener('DOMContentLoaded', function () {

    /* ─── Refresh ScrollTrigger after DOM settles ─── */
    setTimeout(function () {
        if (typeof ScrollTrigger !== 'undefined') {
            ScrollTrigger.refresh();
        }
    }, 300);

    /* ─── Hero reveal ─── */
    gsap.set('.project-hero__left, .project-hero__right', { autoAlpha: 0, y: 50 });

    var tlHero = gsap.timeline({ defaults: { duration: 1, ease: 'power3.out' } });
    tlHero
        .to('.project-hero__left', { autoAlpha: 1, y: 0 })
        .to('.project-hero__right', { autoAlpha: 1, y: 0 }, '-=0.8');

    /* ─── Strategy stagger ─── */
    gsap.set('.project-strategy__block', { autoAlpha: 0, y: 50 });
    gsap.to('.project-strategy__block', {
        autoAlpha: 1,
        y: 0,
        stagger: 0.4,
        duration: 2,
        ease: 'power2.out',
        scrollTrigger: {
            trigger: '.project-strategy',
            start: 'top 80%'
        }
    });

    /* ─── Gallery reveal ─── */
    gsap.from('.project-gallery__item', {
        autoAlpha: 0,
        y: 120,
        duration: 1.5,
        stagger: 0.35,
        ease: 'power3.out',
        scrollTrigger: {
            trigger: '#project-gallery',
            start: 'top 55%'
        }
    });

});
