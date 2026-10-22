/* Alert sounds, made in the browser (no audio files to download):
     order    a bright three-note bell, like a shop counter bell
     message  a short bubbly "pop", like a chat app
     sent     a soft tick when your own message goes
     request  a gentle two-note bell for a custom cake request
   Browsers only allow sound after the person has tapped or clicked the page once, so the
   first tap anywhere switches sound on. Settings are saved per device in localStorage. */
(function () {
    var Ctx = window.AudioContext || window.webkitAudioContext;
    var ctx = null;
    var KEY = 'ccsSound';
    var prefs = { on: true, ring: false, desktop: false };
    try { var saved = JSON.parse(window.localStorage.getItem(KEY) || '{}'); if (saved && typeof saved === 'object') { prefs.on = saved.on !== false; prefs.ring = saved.ring === true; prefs.desktop = saved.desktop === true; } } catch (e) {}
    var save = function () { try { window.localStorage.setItem(KEY, JSON.stringify(prefs)); } catch (e) {} };
    var listeners = [];
    var changed = function () { listeners.forEach(function (fn) { try { fn(); } catch (e) {} }); };

    var context = function () {
        if (!Ctx) return null;
        if (!ctx) { try { ctx = new Ctx(); } catch (e) { return null; } }
        return ctx;
    };
    var ready = function () { var c = context(); return !!c && c.state === 'running'; };
    var unlock = function () {
        var c = context();
        if (!c) return;
        if (c.state === 'suspended') c.resume().then(changed, function () {});
        // A silent blip finishes the unlock on iPhone and iPad.
        try { var b = c.createBuffer(1, 1, 22050), s = c.createBufferSource(); s.buffer = b; s.connect(c.destination); s.start(0); } catch (e) {}
        changed();
    };
    ['pointerdown', 'keydown', 'touchend'].forEach(function (ev) { document.addEventListener(ev, unlock, { passive: true }); });

    // One note: a sine with a quick attack and a soft tail, plus a quieter overtone for a bell feel.
    var tone = function (c, at, freq, len, vol, opts) {
        opts = opts || {};
        var out = c.createGain();
        out.gain.setValueAtTime(0.0001, at);
        out.gain.exponentialRampToValueAtTime(vol, at + (opts.attack || 0.008));
        out.gain.exponentialRampToValueAtTime(0.0001, at + len);
        out.connect(c.destination);
        var osc = c.createOscillator();
        osc.type = opts.type || 'sine';
        osc.frequency.setValueAtTime(freq, at);
        if (opts.to) osc.frequency.exponentialRampToValueAtTime(opts.to, at + (opts.glide || len * 0.6));
        osc.connect(out);
        osc.start(at); osc.stop(at + len + 0.02);
        if (opts.bell) {
            var g2 = c.createGain();
            g2.gain.setValueAtTime(0.0001, at);
            g2.gain.exponentialRampToValueAtTime(vol * 0.35, at + 0.006);
            g2.gain.exponentialRampToValueAtTime(0.0001, at + len * 0.6);
            g2.connect(c.destination);
            var o2 = c.createOscillator();
            o2.type = 'sine';
            o2.frequency.setValueAtTime(freq * 2.76, at);
            o2.connect(g2);
            o2.start(at); o2.stop(at + len);
        }
    };

    var SOUNDS = {
        order: function (c, t) {
            [[1046.5, 0], [1318.5, 0.16], [1568, 0.32], [2093, 0.5]].forEach(function (n, i) {
                tone(c, t + n[1], n[0], i === 3 ? 1.1 : 0.55, i === 3 ? 0.28 : 0.22, { bell: true });
            });
        },
        message: function (c, t) {
            tone(c, t, 620, 0.09, 0.35, { to: 1250, glide: 0.06, attack: 0.004 });
            tone(c, t + 0.1, 980, 0.16, 0.28, { to: 1500, glide: 0.05, attack: 0.004 });
        },
        sent: function (c, t) {
            tone(c, t, 1700, 0.05, 0.12, { to: 2300, glide: 0.03, attack: 0.002 });
        },
        request: function (c, t) {
            tone(c, t, 784, 0.6, 0.2, { bell: true });
            tone(c, t + 0.22, 1174.7, 0.9, 0.2, { bell: true });
        }
    };
    var VIBRATE = { order: [220, 120, 220, 120, 320], message: [90], request: [160, 90, 160] };

    /** Plays a sound. Returns false when it could not play yet (sound off, or no tap on the page). */
    var play = function (name, force) {
        if (!prefs.on && !force) return false;
        if (VIBRATE[name] && navigator.vibrate && !force) { try { navigator.vibrate(VIBRATE[name]); } catch (e) {} }
        var c = context();
        if (!c || !SOUNDS[name]) return false;
        var go = function () { SOUNDS[name](c, c.currentTime + 0.02); };
        if (c.state === 'running') { go(); return true; }
        // Not allowed yet: it plays as soon as the browser lets it, otherwise the page shows "Tap to turn on sound".
        var asked = Date.now();
        c.resume().then(function () { if (c.state === 'running' && Date.now() - asked < 3000) go(); changed(); }, changed);
        changed();
        return false;
    };

    window.CCSSound = {
        play: play,
        ready: ready,
        unlock: unlock,
        prefs: function () { return { on: prefs.on, ring: prefs.ring, desktop: prefs.desktop }; },
        set: function (key, value) { prefs[key] = !!value; save(); changed(); },
        onChange: function (fn) { listeners.push(fn); }
    };
})();
