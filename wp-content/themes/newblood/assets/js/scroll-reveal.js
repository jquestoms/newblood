/**
 * Intersection Observer for scroll-reveal animations.
 * Adds 'is-visible' class when elements enter the viewport.
 */
(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.nb-reveal, .nb-reveal-scale').forEach(function (el) {
      el.classList.add('is-visible');
    });
    return;
  }

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
  );

  document.querySelectorAll('.nb-reveal, .nb-reveal-scale').forEach(function (el) {
    observer.observe(el);
  });
})();
