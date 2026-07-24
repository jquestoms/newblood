(function () {
  'use strict';
  var cfg = window.nbDiscovery || {};
  var threshold = typeof cfg.threshold === 'number' ? cfg.threshold : 7;
  var form = document.getElementById('nb-discovery-form');
  if (!form) return;

  // --- Segmented buttons: single-select within each .nb-d-seg group ---
  function selectedVal(group) {
    if (!group) return null;
    var btn = group.querySelector('.nb-d-seg-btn.is-selected');
    return btn ? parseInt(btn.getAttribute('data-val'), 10) : null;
  }
  function bindSeg(group, onChange) {
    Array.prototype.forEach.call(group.querySelectorAll('.nb-d-seg-btn'), function (btn) {
      btn.addEventListener('click', function () {
        Array.prototype.forEach.call(group.querySelectorAll('.nb-d-seg-btn'), function (b) {
          b.classList.remove('is-selected');
          b.setAttribute('aria-checked', 'false');
        });
        btn.classList.add('is-selected');
        btn.setAttribute('aria-checked', 'true');
        if (onChange) onChange(selectedVal(group));
      });
    });
  }

  // Importance groups reveal/hide the sibling "handled today?" group.
  Array.prototype.forEach.call(document.querySelectorAll('.nb-d-service'), function (row) {
    var impGroup = row.querySelector('.nb-d-importance');
    var handling = row.querySelector('.nb-d-handling');
    var handGroup = row.querySelector('.nb-d-handling-seg');
    bindSeg(impGroup, function (val) {
      if (val !== null && val >= threshold) {
        handling.hidden = false;
      } else {
        handling.hidden = true;
        // clear any handling selection when it no longer applies
        Array.prototype.forEach.call(handGroup.querySelectorAll('.nb-d-seg-btn'), function (b) {
          b.classList.remove('is-selected');
          b.setAttribute('aria-checked', 'false');
        });
      }
    });
    bindSeg(handGroup, null);
  });

  // Vector groups (goals + fix_invest) — default "No pref" (0) already selected in markup.
  Array.prototype.forEach.call(document.querySelectorAll('.nb-d-vector'), function (g) { bindSeg(g, null); });

  // --- Scroll progress bar ---
  var fill = document.querySelector('.nb-d-progress-fill');
  if (fill) {
    var onScroll = function () {
      var doc = document.documentElement;
      var max = doc.scrollHeight - doc.clientHeight;
      var pct = max > 0 ? Math.min(100, Math.max(0, (doc.scrollTop || window.pageYOffset) / max * 100)) : 0;
      fill.style.width = pct + '%';
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    onScroll();
  }

  function collect() {
    var get = function (name) { var el = form.querySelector('[name="' + name + '"]'); return el ? el.value.trim() : ''; };

    var services = [];
    Array.prototype.forEach.call(document.querySelectorAll('.nb-d-service'), function (row) {
      var impGroup = row.querySelector('.nb-d-importance');
      var key = impGroup ? impGroup.getAttribute('data-key') : row.getAttribute('data-key');
      var imp = selectedVal(impGroup);
      if (imp === null) return; // untouched → omit (treated as not-rated)
      var obj = { key: key, importance: imp };
      if (imp >= threshold) {
        obj.handling = selectedVal(row.querySelector('.nb-d-handling-seg'));
      } else {
        obj.handling = null;
      }
      services.push(obj);
    });

    var vectors = {};
    Array.prototype.forEach.call(document.querySelectorAll('.nb-d-vector'), function (g) {
      var vkey = g.getAttribute('data-key');
      if (!vkey) return;
      vectors[vkey] = selectedVal(g) || 0;
    });

    var goal_vectors = {};
    Object.keys(vectors).forEach(function (k) {
      if (k !== 'fix_invest') goal_vectors[k] = vectors[k] || 0;
    });

    var systems = {};
    var sysSection = document.getElementById('nb-d-systems');
    if (sysSection) {
      Array.prototype.forEach.call(sysSection.querySelectorAll('input[type="text"], textarea'), function (el) {
        if (el.name) systems[el.name] = el.value.trim();
      });
      var radioNames = {};
      Array.prototype.forEach.call(sysSection.querySelectorAll('input[type="radio"]'), function (el) {
        if (el.name) radioNames[el.name] = true;
      });
      Object.keys(radioNames).forEach(function (n) {
        var checked = sysSection.querySelector('input[name="' + n + '"]:checked');
        systems[n] = checked ? checked.value : '';
      });
    }

    return {
      instance: cfg.instance || form.getAttribute('data-instance'),
      hp: get('hp_company'),
      respondent: { name: get('respondent_name'), email: get('respondent_email') },
      services: services,
      vision: get('vision'),
      goal_vectors: goal_vectors,
      systems: systems,
      posture: { fix_invest: vectors.fix_invest || 0, timeline: get('timeline') },
      open: get('open')
    };
  }

  var errEl = document.getElementById('nb-d-error');
  var btn = document.getElementById('nb-d-submit');
  function showError(msg) { if (errEl) { errEl.textContent = msg; errEl.hidden = false; } }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (errEl) errEl.hidden = true;
    var payload = collect();
    if (!payload.respondent.name || !payload.respondent.email) {
      showError('Please add your name and email so we know who this is from.');
      return;
    }
    btn.disabled = true;
    btn.textContent = 'Sending…';
    fetch(cfg.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' },
      body: JSON.stringify(payload)
    }).then(function (res) {
      if (!res.ok) throw new Error('bad status ' + res.status);
      return res.json();
    }).then(function () {
      form.hidden = true;
      var ty = document.getElementById('nb-d-thankyou');
      if (ty) { ty.hidden = false; ty.scrollIntoView({ behavior: 'smooth' }); }
    }).catch(function () {
      btn.disabled = false;
      btn.textContent = 'Send to New Blood';
      showError('Something went wrong sending your answers. Please try again, or reply to Jeremy\'s email.');
    });
  });
})();
