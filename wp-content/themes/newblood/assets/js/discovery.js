(function () {
  'use strict';
  var cfg = window.nbDiscovery || {};
  var threshold = typeof cfg.threshold === 'number' ? cfg.threshold : 7;
  var form = document.getElementById('nb-discovery-form');
  if (!form) return;

  // Live slider readouts + progressive reveal of the "handled today?" slider.
  function bindServiceRow(row) {
    var imp = row.querySelector('.nb-d-importance');
    var impOut = row.querySelector('.nb-d-importance-out');
    var handling = row.querySelector('.nb-d-handling');
    var handlingInput = row.querySelector('.nb-d-handling-input');
    var handlingOut = row.querySelector('.nb-d-handling-out');
    function syncImp() {
      impOut.textContent = imp.value;
      if (parseInt(imp.value, 10) >= threshold) {
        handling.hidden = false;
      } else {
        handling.hidden = true;
      }
    }
    imp.addEventListener('input', syncImp);
    handlingInput.addEventListener('input', function () { handlingOut.textContent = handlingInput.value; });
    syncImp();
  }
  Array.prototype.forEach.call(document.querySelectorAll('.nb-d-service'), bindServiceRow);

  function collect() {
    var get = function (name) { var el = form.querySelector('[name="' + name + '"]'); return el ? el.value.trim() : ''; };
    var services = [];
    Array.prototype.forEach.call(document.querySelectorAll('.nb-d-service'), function (row) {
      var key = row.getAttribute('data-key');
      var imp = parseInt(row.querySelector('.nb-d-importance').value, 10);
      var obj = { key: key, importance: imp };
      if (imp >= threshold) {
        obj.handling = parseInt(row.querySelector('.nb-d-handling-input').value, 10);
      } else {
        obj.handling = null;
      }
      services.push(obj);
    });
    var vectors = {};
    Array.prototype.forEach.call(document.querySelectorAll('.nb-d-vector'), function (v) {
      vectors[v.getAttribute('data-key')] = parseInt(v.value, 10);
    });
    var gbp = form.querySelector('input[name="gbp_access"]:checked');
    return {
      instance: cfg.instance || form.getAttribute('data-instance'),
      respondent: { name: get('respondent_name'), email: get('respondent_email') },
      services: services,
      vision: get('vision'),
      goal_vectors: {
        residential_commercial: vectors.residential_commercial || 0,
        leads_volume_quality: vectors.leads_volume_quality || 0,
        topline_lean: vectors.topline_lean || 0,
        defend_expand: vectors.defend_expand || 0,
        handson_managed: vectors.handson_managed || 0
      },
      systems: {
        crm: get('crm'),
        lead_handling: get('lead_handling'),
        reviews_system: get('reviews_system'),
        call_tracking: get('call_tracking'),
        gbp_access: gbp ? gbp.value : 'unsure',
        territories: get('territories')
      },
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
