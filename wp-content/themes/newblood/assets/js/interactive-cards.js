/**
 * Interactive card effects:
 * 1. 3D tilt on hover (follows cursor)
 * 2. Neural network particle system inside each card
 * 3. Particles react to mouse position
 */
(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  // ===== 3D TILT EFFECT =====
  function initTilt(card) {
    var maxTilt = 8; // degrees

    card.addEventListener('mousemove', function (e) {
      var rect = card.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width;
      var y = (e.clientY - rect.top) / rect.height;
      var tiltX = (0.5 - y) * maxTilt;
      var tiltY = (x - 0.5) * maxTilt;
      card.style.transform = 'perspective(800px) rotateX(' + tiltX + 'deg) rotateY(' + tiltY + 'deg) translateY(-4px)';
    });

    card.addEventListener('mouseleave', function () {
      card.style.transform = 'perspective(800px) rotateX(0deg) rotateY(0deg) translateY(0)';
    });
  }

  // ===== PARTICLE NETWORK =====
  function initParticles(card) {
    var canvas = document.createElement('canvas');
    canvas.className = 'nb-card-particles';
    card.style.position = 'relative';
    card.prepend(canvas);

    var ctx = canvas.getContext('2d');
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var w, h;
    var mouse = { x: -1000, y: -1000, active: false };
    var particles = [];
    var animId = null;
    var particleCount = 35;
    var connectionDist = 100;
    var mouseDist = 150;

    function resize() {
      var rect = card.getBoundingClientRect();
      w = rect.width;
      h = rect.height;
      canvas.width = w * dpr;
      canvas.height = h * dpr;
      canvas.style.width = w + 'px';
      canvas.style.height = h + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function createParticles() {
      particles = [];
      for (var i = 0; i < particleCount; i++) {
        particles.push({
          x: Math.random() * w,
          y: Math.random() * h,
          vx: (Math.random() - 0.5) * 0.4,
          vy: (Math.random() - 0.5) * 0.4,
          r: Math.random() * 1.5 + 0.5,
          opacity: Math.random() * 0.4 + 0.1
        });
      }
    }

    function draw() {
      ctx.clearRect(0, 0, w, h);

      // Update and draw particles
      for (var i = 0; i < particles.length; i++) {
        var p = particles[i];

        // Mouse attraction
        if (mouse.active) {
          var dx = mouse.x - p.x;
          var dy = mouse.y - p.y;
          var dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < mouseDist) {
            var force = (1 - dist / mouseDist) * 0.02;
            p.vx += dx * force;
            p.vy += dy * force;
          }
        }

        // Damping
        p.vx *= 0.98;
        p.vy *= 0.98;

        // Move
        p.x += p.vx;
        p.y += p.vy;

        // Wrap around edges
        if (p.x < 0) p.x = w;
        if (p.x > w) p.x = 0;
        if (p.y < 0) p.y = h;
        if (p.y > h) p.y = 0;

        // Draw particle
        var alpha = mouse.active ? p.opacity + 0.2 : p.opacity;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(74, 222, 128, ' + alpha + ')';
        ctx.fill();
      }

      // Draw connections
      for (var i = 0; i < particles.length; i++) {
        for (var j = i + 1; j < particles.length; j++) {
          var dx = particles[i].x - particles[j].x;
          var dy = particles[i].y - particles[j].y;
          var dist = Math.sqrt(dx * dx + dy * dy);
          var maxDist = mouse.active ? connectionDist * 1.3 : connectionDist;

          if (dist < maxDist) {
            var lineAlpha = (1 - dist / maxDist) * (mouse.active ? 0.15 : 0.06);
            ctx.beginPath();
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.strokeStyle = 'rgba(74, 222, 128, ' + lineAlpha + ')';
            ctx.lineWidth = 0.5;
            ctx.stroke();
          }
        }

        // Draw connections to mouse
        if (mouse.active) {
          var dx = particles[i].x - mouse.x;
          var dy = particles[i].y - mouse.y;
          var dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < mouseDist) {
            var lineAlpha = (1 - dist / mouseDist) * 0.2;
            ctx.beginPath();
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(mouse.x, mouse.y);
            ctx.strokeStyle = 'rgba(74, 222, 128, ' + lineAlpha + ')';
            ctx.lineWidth = 0.5;
            ctx.stroke();
          }
        }
      }

      animId = requestAnimationFrame(draw);
    }

    card.addEventListener('mouseenter', function () {
      mouse.active = true;
      if (!animId) {
        resize();
        if (particles.length === 0) createParticles();
        draw();
      }
    });

    card.addEventListener('mousemove', function (e) {
      var rect = card.getBoundingClientRect();
      mouse.x = e.clientX - rect.left;
      mouse.y = e.clientY - rect.top;
    });

    card.addEventListener('mouseleave', function () {
      mouse.active = false;
      mouse.x = -1000;
      mouse.y = -1000;
    });

    // Start idle animation when visible
    var observer = new IntersectionObserver(function (entries) {
      if (entries[0].isIntersecting) {
        resize();
        if (particles.length === 0) createParticles();
        if (!animId) draw();
      } else {
        if (animId) {
          cancelAnimationFrame(animId);
          animId = null;
        }
      }
    }, { threshold: 0.1 });

    observer.observe(card);

    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        resize();
        createParticles();
      }, 200);
    });
  }

  // ===== INIT =====
  function init() {
    var cards = document.querySelectorAll('.nb-glass');
    cards.forEach(function (card) {
      card.style.transition = 'transform 0.15s ease-out, background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease';
      initTilt(card);
      initParticles(card);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
