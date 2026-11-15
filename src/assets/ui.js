/* vuln-lab UI: generated sounds (Web Audio), selection blink, boot sequence. No dependency. */
(function () {
  'use strict';
  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var ctx = null;
  var muted = false;
  try { muted = localStorage.getItem('vulnlab_muted') === '1'; } catch (e) {}

  function ensureCtx() {
    if (ctx) return ctx;
    var AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return null;
    try { ctx = new AC(); } catch (e) { ctx = null; }
    return ctx;
  }
  function resume() {
    var c = ensureCtx();
    if (c && c.state === 'suspended') { try { c.resume(); } catch (e) {} }
    return c;
  }
  var VOL = 0.05;

  function tone(freq, start, dur, type, vol, endFreq) {
    var c = ctx;
    if (!c || muted) return;
    var t0 = c.currentTime + start;
    var o = c.createOscillator();
    var g = c.createGain();
    o.type = type || 'square';
    o.frequency.setValueAtTime(freq, t0);
    if (endFreq) o.frequency.linearRampToValueAtTime(endFreq, t0 + dur);
    g.gain.setValueAtTime(vol || VOL, t0);
    g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    o.connect(g); g.connect(c.destination);
    o.start(t0); o.stop(t0 + dur + 0.02);
  }
  function noise(start, dur, vol) {
    var c = ctx;
    if (!c || muted) return;
    var n = Math.max(1, Math.floor(c.sampleRate * dur));
    var buf = c.createBuffer(1, n, c.sampleRate);
    var d = buf.getChannelData(0);
    for (var i = 0; i < n; i++) d[i] = Math.random() * 2 - 1;
    var s = c.createBufferSource();
    var g = c.createGain();
    var t0 = c.currentTime + start;
    s.buffer = buf;
    g.gain.setValueAtTime(vol, t0);
    g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    s.connect(g); g.connect(c.destination);
    s.start(t0);
  }

  var sound = {
    tick: function () { tone(1400, 0, 0.03, 'square', 0.03); },
    blip: function () { tone(660, 0, 0.06, 'square', VOL); tone(990, 0.07, 0.08, 'square', VOL); },
    buzz: function () { tone(90, 0, 0.3, 'sawtooth', 0.08); tone(95, 0, 0.3, 'square', 0.04); },
    success: function () {
      [392, 494, 587, 784].forEach(function (f, i) { tone(f, i * 0.08, 0.12, 'triangle', 0.06); });
    },
    type: function () { noise(0, 0.015, 0.012); tone(2000 + Math.random() * 400, 0, 0.012, 'square', 0.008); }
  };
  window.VulnLab = { sound: sound };

  var sndBtn = null;
  function renderSnd() {
    if (!sndBtn) return;
    sndBtn.textContent = muted ? '[SND: OFF]' : '[SND: ON]';
    sndBtn.setAttribute('aria-pressed', muted ? 'false' : 'true');
  }

  /* AudioContext is created only after a user gesture */
  function onGesture() { resume(); }
  document.addEventListener('click', onGesture, true);
  document.addEventListener('keydown', onGesture, true);

  document.addEventListener('DOMContentLoaded', function () {
    sndBtn = document.getElementById('snd-toggle');
    renderSnd();
    if (sndBtn) {
      sndBtn.addEventListener('click', function () {
        muted = !muted;
        try { localStorage.setItem('vulnlab_muted', muted ? '1' : '0'); } catch (e) {}
        renderSnd();
        if (!muted) sound.blip();
      });
    }

    var items = document.querySelectorAll('.menu a.item');
    Array.prototype.forEach.call(items, function (a) {
      a.addEventListener('mouseenter', sound.tick);
      a.addEventListener('focus', sound.tick);
    });

    /* very quiet typing clicks in text fields */
    document.addEventListener('keydown', function (ev) {
      var t = ev.target;
      if (t && (t.tagName === 'TEXTAREA' || (t.tagName === 'INPUT' && /^(text|password|search)$/.test(t.type)))) {
        if (ev.key && ev.key.length === 1) sound.type();
      }
    });

    /* selection blink (2-3 blinks, ~100ms) then navigate / submit */
    var busy = false;
    function blinkThen(el, done) {
      sound.blip();
      if (reduced) { done(); return; }
      var count = 0;
      var total = 5;
      var step = function () {
        el.classList.toggle('blinking');
        count++;
        if (count < total) { setTimeout(step, 100); }
        else { el.classList.add('blinking'); done(); }
      };
      step();
    }
    Array.prototype.forEach.call(items, function (a) {
      a.addEventListener('click', function (ev) {
        if (ev.defaultPrevented || ev.button !== 0 || ev.ctrlKey || ev.metaKey || ev.shiftKey || ev.altKey) return;
        if (a.classList.contains('on')) return;
        ev.preventDefault();
        if (busy) return;
        busy = true;
        blinkThen(a, function () { window.location.href = a.href; });
      });
    });
    var lvlBtns = document.querySelectorAll('.gauge .lvl');
    Array.prototype.forEach.call(lvlBtns, function (b) {
      b.addEventListener('click', function (ev) {
        ev.preventDefault();
        if (busy) return;
        busy = true;
        var form = b.form;
        blinkThen(b, function () {
          if (form.requestSubmit) { form.requestSubmit(b); } else { form.submit(); }
        });
      });
    });

    /* boot sequence, once per browser session */
    var seen = false;
    try { seen = sessionStorage.getItem('vulnlab_boot') === '1'; } catch (e) {}
    if (seen || reduced) return;
    try { sessionStorage.setItem('vulnlab_boot', '1'); } catch (e) {}
    var lines = [
      'VULN-LAB BIOS v0.1  ........ OK',
      'mounting /var/www ........... OK',
      'connecting to db ............ OK',
      'loading threat matrix ....... OK',
      'WARNING: local use only (127.0.0.1)',
      'system ready_'
    ];
    var boot = document.createElement('div');
    boot.id = 'boot';
    var text = document.createElement('div');
    var hint = document.createElement('div');
    hint.className = 'hint';
    hint.textContent = '[click, Esc or any key to skip]';
    boot.appendChild(text);
    boot.appendChild(hint);
    document.body.appendChild(boot);
    var i = 0, closed = false, timer = null;
    function close() {
      if (closed) return;
      closed = true;
      clearTimeout(timer);
      document.removeEventListener('keydown', close, true);
      if (boot.parentNode) boot.parentNode.removeChild(boot);
    }
    boot.addEventListener('click', close);
    document.addEventListener('keydown', close, true);
    function next() {
      if (closed) return;
      if (i < lines.length) {
        text.appendChild(document.createTextNode(lines[i] + '\n'));
        i++;
        timer = setTimeout(next, 220);
      } else {
        timer = setTimeout(close, 300);
      }
    }
    next();
  });
})();
