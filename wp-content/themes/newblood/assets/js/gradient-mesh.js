/**
 * Animated gradient mesh background — Stripe-inspired.
 * Creates soft, drifting color blobs on a canvas behind the hero content.
 * Lightweight: no WebGL, pure 2D canvas with radial gradients.
 */
(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var container = document.querySelector('.nb-hero-gradient');
  if (!container) return;

  var canvas = document.createElement('canvas');
  canvas.className = 'nb-gradient-canvas';
  container.prepend(canvas);

  var ctx = canvas.getContext('2d');
  var dpr = Math.min(window.devicePixelRatio || 1, 2);
  var w, h;
  var animId;

  // Color blobs — positions are 0-1 relative, radius is relative to canvas size
  var blobs = [
    { x: 0.2, y: 0.3, vx: 0.0003, vy: 0.0002, r: 0.45, color: [34, 197, 94, 0.12] },    // green
    { x: 0.8, y: 0.6, vx: -0.0002, vy: 0.0003, r: 0.5, color: [16, 163, 74, 0.08] },     // darker green
    { x: 0.5, y: 0.2, vx: 0.0001, vy: -0.0002, r: 0.4, color: [74, 222, 128, 0.06] },    // light green
    { x: 0.3, y: 0.8, vx: 0.0002, vy: -0.0001, r: 0.35, color: [20, 184, 166, 0.08] },   // teal
    { x: 0.7, y: 0.4, vx: -0.0003, vy: -0.0002, r: 0.4, color: [34, 197, 94, 0.05] },    // subtle green
  ];

  function resize() {
    var rect = container.getBoundingClientRect();
    w = rect.width;
    h = rect.height;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function draw() {
    ctx.clearRect(0, 0, w, h);

    for (var i = 0; i < blobs.length; i++) {
      var b = blobs[i];

      // Drift the blob
      b.x += b.vx;
      b.y += b.vy;

      // Soft bounce off edges
      if (b.x < -0.1 || b.x > 1.1) b.vx *= -1;
      if (b.y < -0.1 || b.y > 1.1) b.vy *= -1;

      // Draw radial gradient blob
      var px = b.x * w;
      var py = b.y * h;
      var radius = b.r * Math.max(w, h);

      var grad = ctx.createRadialGradient(px, py, 0, px, py, radius);
      grad.addColorStop(0, 'rgba(' + b.color[0] + ',' + b.color[1] + ',' + b.color[2] + ',' + b.color[3] + ')');
      grad.addColorStop(1, 'rgba(' + b.color[0] + ',' + b.color[1] + ',' + b.color[2] + ',0)');

      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, w, h);
    }

    animId = requestAnimationFrame(draw);
  }

  // Only animate when visible
  var observer = new IntersectionObserver(function (entries) {
    if (entries[0].isIntersecting) {
      if (!animId) draw();
    } else {
      if (animId) {
        cancelAnimationFrame(animId);
        animId = null;
      }
    }
  }, { threshold: 0 });

  resize();
  observer.observe(container);

  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(resize, 150);
  });
})();
