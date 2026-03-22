/**
 * Animated gradient mesh background — Stripe-inspired.
 * Creates soft, drifting color blobs on a canvas behind the hero content.
 * Blobs gently follow the mouse cursor with soft easing.
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
  var mouse = { x: 0.5, y: 0.5, active: false };

  // Color blobs — positions are 0-1 relative, radius is relative to canvas size
  var blobs = [
    { x: 0.15, y: 0.25, baseX: 0.15, baseY: 0.25, vx: 0.0003, vy: 0.0002, r: 0.5,  color: [34, 197, 94, 0.15] },   // green
    { x: 0.85, y: 0.65, baseX: 0.85, baseY: 0.65, vx: -0.0002, vy: 0.0003, r: 0.55, color: [16, 163, 74, 0.1] },    // darker green
    { x: 0.5,  y: 0.15, baseX: 0.5,  baseY: 0.15, vx: 0.0001, vy: -0.0002, r: 0.45, color: [74, 222, 128, 0.08] },  // light green
    { x: 0.25, y: 0.8,  baseX: 0.25, baseY: 0.8,  vx: 0.0002, vy: -0.0001, r: 0.4,  color: [20, 184, 166, 0.1] },   // teal
    { x: 0.7,  y: 0.35, baseX: 0.7,  baseY: 0.35, vx: -0.0003, vy: -0.0002, r: 0.45, color: [34, 197, 94, 0.07] },  // subtle green
    { x: 0.4,  y: 0.6,  baseX: 0.4,  baseY: 0.6,  vx: 0.00015, vy: 0.00025, r: 0.35, color: [52, 211, 153, 0.06] }, // emerald
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

      // Drift the base position
      b.baseX += b.vx;
      b.baseY += b.vy;

      // Soft bounce
      if (b.baseX < -0.1 || b.baseX > 1.1) b.vx *= -1;
      if (b.baseY < -0.1 || b.baseY > 1.1) b.vy *= -1;

      // Mouse influence — blobs drift toward mouse with different strengths
      var targetX = b.baseX;
      var targetY = b.baseY;
      if (mouse.active) {
        var influence = 0.08 + (i * 0.02); // each blob reacts differently
        targetX = b.baseX + (mouse.x - b.baseX) * influence;
        targetY = b.baseY + (mouse.y - b.baseY) * influence;
      }

      // Ease toward target
      b.x += (targetX - b.x) * 0.02;
      b.y += (targetY - b.y) * 0.02;

      // Draw radial gradient blob
      var px = b.x * w;
      var py = b.y * h;
      var radius = b.r * Math.max(w, h);

      var grad = ctx.createRadialGradient(px, py, 0, px, py, radius);
      grad.addColorStop(0, 'rgba(' + b.color[0] + ',' + b.color[1] + ',' + b.color[2] + ',' + b.color[3] + ')');
      grad.addColorStop(0.6, 'rgba(' + b.color[0] + ',' + b.color[1] + ',' + b.color[2] + ',' + (b.color[3] * 0.3) + ')');
      grad.addColorStop(1, 'rgba(' + b.color[0] + ',' + b.color[1] + ',' + b.color[2] + ',0)');

      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, w, h);
    }

    animId = requestAnimationFrame(draw);
  }

  // Mouse tracking
  container.addEventListener('mousemove', function (e) {
    var rect = container.getBoundingClientRect();
    mouse.x = (e.clientX - rect.left) / rect.width;
    mouse.y = (e.clientY - rect.top) / rect.height;
    mouse.active = true;
  });

  container.addEventListener('mouseleave', function () {
    mouse.active = false;
  });

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
