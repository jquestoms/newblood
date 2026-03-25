/**
 * Animated wireframe globe — network of particles forming a slowly
 * rotating sphere with connecting lines. Mouse interaction gently
 * influences rotation speed and direction.
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

  // Globe parameters
  var points = [];
  var numPoints = 250;
  var globeRadius;
  var rotationY = 0;
  var rotationX = 0.3;
  var baseSpeedY = 0.002;
  var baseSpeedX = 0.0003;
  var connectionDist = 0.32; // relative to globeRadius

  // Generate points on a sphere using fibonacci distribution
  function generatePoints() {
    points = [];
    var goldenAngle = Math.PI * (3 - Math.sqrt(5));
    for (var i = 0; i < numPoints; i++) {
      var y = 1 - (i / (numPoints - 1)) * 2; // -1 to 1
      var radiusAtY = Math.sqrt(1 - y * y);
      var theta = goldenAngle * i;
      points.push({
        // Original position on unit sphere
        ox: Math.cos(theta) * radiusAtY,
        oy: y,
        oz: Math.sin(theta) * radiusAtY,
        // Projected 2D position (set during draw)
        px: 0,
        py: 0,
        pz: 0,
        size: Math.random() * 0.8 + 0.6
      });
    }
  }

  function resize() {
    // Use the container's full size — not the canvas element, which may be
    // constrained by WordPress content width wrappers
    var rect = container.getBoundingClientRect();
    w = rect.width;
    h = rect.height;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    // Fixed radius — scales with viewport, clamped 220-380px
    globeRadius = Math.min(Math.max(window.innerWidth * 0.22, 220), 380);
  }

  function project(point) {
    var x = point.ox;
    var y = point.oy;
    var z = point.oz;

    // Rotate around Y axis
    var cosY = Math.cos(rotationY);
    var sinY = Math.sin(rotationY);
    var x1 = x * cosY - z * sinY;
    var z1 = x * sinY + z * cosY;

    // Rotate around X axis
    var cosX = Math.cos(rotationX);
    var sinX = Math.sin(rotationX);
    var y1 = y * cosX - z1 * sinX;
    var z2 = y * sinX + z1 * cosX;

    point.px = x1;
    point.py = y1;
    point.pz = z2;
  }

  function draw() {
    ctx.clearRect(0, 0, w, h);

    // Mouse influence on rotation
    var speedY = baseSpeedY;
    var speedX = baseSpeedX;
    if (mouse.active) {
      speedY += (mouse.x - 0.5) * 0.004;
      speedX += (mouse.y - 0.5) * 0.002;
    }
    rotationY += speedY;
    rotationX += speedX;

    // Center of globe — positioned to the right side of the hero
    var cx = w * 0.65;
    var cy = h * 0.5;

    // Project all points
    for (var i = 0; i < points.length; i++) {
      project(points[i]);
    }

    // Sort by z-depth for proper layering
    var sorted = points.slice().sort(function (a, b) { return a.pz - b.pz; });

    // Draw connections first (behind points)
    var maxDist = connectionDist;
    for (var i = 0; i < sorted.length; i++) {
      for (var j = i + 1; j < sorted.length; j++) {
        var dx = sorted[i].px - sorted[j].px;
        var dy = sorted[i].py - sorted[j].py;
        var dz = sorted[i].pz - sorted[j].pz;
        var dist = Math.sqrt(dx * dx + dy * dy + dz * dz);

        if (dist < maxDist) {
          // Fade based on distance and depth
          var avgZ = (sorted[i].pz + sorted[j].pz) / 2;
          var depthAlpha = (avgZ + 1) / 2; // 0 (back) to 1 (front)
          var distAlpha = 1 - dist / maxDist;
          var alpha = distAlpha * depthAlpha * 0.2;

          if (alpha > 0.01) {
            ctx.beginPath();
            ctx.moveTo(cx + sorted[i].px * globeRadius, cy + sorted[i].py * globeRadius);
            ctx.lineTo(cx + sorted[j].px * globeRadius, cy + sorted[j].py * globeRadius);
            ctx.strokeStyle = 'rgba(74, 222, 128, ' + alpha + ')';
            ctx.lineWidth = 0.5;
            ctx.stroke();
          }
        }
      }
    }

    // Draw points
    for (var i = 0; i < sorted.length; i++) {
      var p = sorted[i];
      var depthAlpha = (p.pz + 1) / 2; // 0 (back) to 1 (front)
      var alpha = 0.15 + depthAlpha * 0.55;
      var size = p.size * (0.7 + depthAlpha * 0.8);

      ctx.beginPath();
      ctx.arc(cx + p.px * globeRadius, cy + p.py * globeRadius, size, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(74, 222, 128, ' + alpha + ')';
      ctx.fill();
    }

    // Subtle ambient glow in the center of the globe
    var glow = ctx.createRadialGradient(cx, cy, 0, cx, cy, globeRadius * 1.2);
    glow.addColorStop(0, 'rgba(34, 197, 94, 0.06)');
    glow.addColorStop(0.5, 'rgba(34, 197, 94, 0.02)');
    glow.addColorStop(1, 'rgba(34, 197, 94, 0)');
    ctx.fillStyle = glow;
    ctx.fillRect(0, 0, w, h);

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

  generatePoints();
  resize();
  observer.observe(container);

  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(resize, 150);
  });
})();
