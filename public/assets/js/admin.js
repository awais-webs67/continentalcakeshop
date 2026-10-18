/* Admin panel interactions */
(function () {
    'use strict';
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    // Sidebar on small screens
    $$('[data-sidebar-open]').forEach(function (b) { b.addEventListener('click', function () { document.body.classList.add('sidebar-open'); }); });
    $$('[data-sidebar-close]').forEach(function (b) { b.addEventListener('click', function () { document.body.classList.remove('sidebar-open'); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') document.body.classList.remove('sidebar-open'); });

    // Toasts
    $$('.toast').forEach(function (t) {
        var hide = function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 300); };
        var b = $('[data-toast-close]', t);
        b && b.addEventListener('click', hide);
        if (!t.classList.contains('toast--error')) setTimeout(hide, 5000);
    });

    // Confirm before destructive actions
    $$('[data-confirm]').forEach(function (el) {
        el.addEventListener(el.tagName === 'FORM' ? 'submit' : 'click', function (e) {
            if (!window.confirm(el.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    // Auto-submit filters
    $$('[data-auto-submit]').forEach(function (el) { el.addEventListener('change', function () { el.form.submit(); }); });

    // Image preview before upload
    $$('[data-image-input]').forEach(function (input) {
        var preview = document.getElementById(input.getAttribute('data-image-input'));
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (file && preview) preview.src = URL.createObjectURL(file);
        });
    });

    // Repeater rows (product sizes)
    $$('[data-repeater]').forEach(function (wrap) {
        var template = $('template', wrap);
        var list = $('[data-repeater-list]', wrap);
        var counter = list.children.length + 1000;
        $('[data-repeater-add]', wrap).addEventListener('click', function () {
            var html = template.innerHTML.replace(/__INDEX__/g, String(counter++));
            list.insertAdjacentHTML('beforeend', html);
            var inputs = $$('input', list.lastElementChild);
            inputs[0] && inputs[0].focus();
        });
        list.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-repeater-remove]');
            if (btn) btn.closest('.repeater__row').remove();
        });
    });

    // Slug field follows the name until edited by hand
    var nameInput = $('[data-slug-source]');
    var slugInput = $('[data-slug-target]');
    if (nameInput && slugInput) {
        var touched = slugInput.value !== '';
        slugInput.addEventListener('input', function () { touched = true; });
        nameInput.addEventListener('input', function () {
            if (!touched) slugInput.value = nameInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        });
    }

    // Closed toggle on opening hours
    $$('[data-closed-toggle]').forEach(function (box) {
        var row = box.closest('.hours-grid__row');
        var sync = function () { $$('input[type="time"]', row).forEach(function (i) { i.disabled = box.checked; }); };
        box.addEventListener('change', sync);
        sync();
    });

    // Simple formatting buttons for page text (wraps the selection in HTML tags)
    $$('[data-editor]').forEach(function (wrap) {
        var area = $('textarea', wrap);
        var insert = function (before, after, placeholder) {
            var start = area.selectionStart, end = area.selectionEnd;
            var text = area.value.slice(start, end) || placeholder;
            area.setRangeText(before + text + after, start, end, 'end');
            area.focus();
        };
        $$('[data-wrap]', wrap).forEach(function (b) {
            b.addEventListener('click', function () { var t = b.getAttribute('data-wrap'); insert('<' + t + '>', '</' + t + '>', 'Text'); });
        });
        $$('[data-list]', wrap).forEach(function (b) {
            b.addEventListener('click', function () {
                var start = area.selectionStart, end = area.selectionEnd;
                var lines = (area.value.slice(start, end) || 'First point\nSecond point').split('\n').filter(Boolean);
                area.setRangeText('<ul>\n' + lines.map(function (l) { return '  <li>' + l + '</li>'; }).join('\n') + '\n</ul>', start, end, 'end');
                area.focus();
            });
        });
        $$('[data-link]', wrap).forEach(function (b) {
            b.addEventListener('click', function () {
                var href = window.prompt('Link address (for example /contact or https://...)');
                if (href) insert('<a href="' + href.replace(/"/g, '') + '">', '</a>', 'link text');
            });
        });
    });

    // Character counters for search engine fields
    $$('[data-count]').forEach(function (input) {
        var label = input.closest('.field');
        var limit = /description/.test(input.name) ? 155 : 60;
        var counter = document.createElement('span');
        counter.className = 'char-count';
        label && label.firstElementChild.appendChild(counter);
        var update = function () {
            counter.textContent = ' ' + input.value.length + ' / ' + limit;
            counter.classList.toggle('is-over', input.value.length > limit);
            var preview = document.querySelector(/description/.test(input.name) ? '.serp__desc' : '.serp__title');
            if (preview) preview.textContent = input.value;
        };
        input.addEventListener('input', update);
        update();
    });
})();
