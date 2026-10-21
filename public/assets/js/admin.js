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
            if (btn) btn.closest('.repeater__row, .line-row').remove();
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

    // Random coupon code, avoiding letters that are easy to mix up (O/0, I/1)
    $$('[data-generate-code]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', code = 'CAKE';
            for (var i = 0; i < 5; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
            var input = $('[data-coupon-code-input]');
            input.value = code;
            input.focus();
        });
    });
    $$('[data-coupon-code-input]').forEach(function (input) {
        input.addEventListener('input', function () { input.value = input.value.toUpperCase().replace(/[^A-Z0-9_-]/g, ''); });
    });

    // Live preview of the promo popup
    var previewInputs = $$('[data-preview]');
    if (previewInputs.length) {
        var show = function (el, value) { el.textContent = value; };
        previewInputs.forEach(function (input) {
            var update = function () {
                var kind = input.getAttribute('data-preview');
                if (kind === 'title') show($('[data-preview-title]'), input.value);
                if (kind === 'text') show($('[data-preview-text]'), input.value);
                if (kind === 'button') {
                    var btn = $('[data-preview-button]');
                    show(btn, input.value);
                    btn.hidden = input.value.trim() === '';
                }
                if (kind === 'coupon') {
                    var opt = input.options[input.selectedIndex];
                    $('[data-preview-coupon]').hidden = !input.value;
                    if (input.value) {
                        show($('[data-preview-code]'), opt.getAttribute('data-code'));
                        show($('[data-preview-summary]'), 'Use this code at checkout to get ' + opt.getAttribute('data-summary') + '.');
                    }
                }
            };
            input.addEventListener('input', update);
            input.addEventListener('change', update);
        });
    }

    // Rich text editor (Quill) for page text, FAQ answers, product and homepage text.
    // The original textarea stays in the form and receives clean HTML when the form is sent.
    var richAreas = $$('textarea[data-rich-editor]');
    if (richAreas.length && window.CCS_ADMIN) {
        var css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = window.CCS_ADMIN.quillCss;
        document.head.appendChild(css);
        var script = document.createElement('script');
        script.src = window.CCS_ADMIN.quillJs;
        script.onload = function () { richAreas.forEach(makeEditor); };
        document.head.appendChild(script);
    }

    function makeEditor(area) {
        var Quill = window.Quill;
        if (!Quill.__ccsReady) {
            // Alignment as inline style so it works on the website without extra CSS.
            Quill.register(Quill.import('attributors/style/align'), true);
            // Allow site links written as {url:contact} or /contact.
            var Link = Quill.import('formats/link');
            var sanitize = Link.sanitize;
            Link.sanitize = function (url) {
                return /^\{url:[a-z0-9-]+\}$/.test(url) || /^\//.test(url) ? url : sanitize.call(Link, url);
            };
            Quill.__ccsReady = true;
        }

        var simple = area.getAttribute('data-rich-editor') === 'simple';
        var wrap = document.createElement('div');
        wrap.className = 'rich-editor';
        var holder = document.createElement('div');
        wrap.appendChild(holder);
        area.parentNode.insertBefore(wrap, area);
        area.classList.add('rich-editor__source');
        area.hidden = true;

        var toolbar = simple
            ? [['bold', 'italic', 'underline'], [{ align: [] }], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']]
            : [[{ header: [2, 3, 4, false] }], ['bold', 'italic', 'underline', 'strike'], [{ align: [] }],
               [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }], ['blockquote', 'link', 'image'], ['clean']];

        var quill = new Quill(holder, {
            theme: 'snow',
            placeholder: area.getAttribute('placeholder') || 'Start writing...',
            modules: {
                toolbar: { container: toolbar, handlers: { image: function () { pickImage(quill); } } },
                history: { delay: 800, maxStack: 200 }
            }
        });

        var value = area.value.trim();
        if (value && !/<[a-z][\s\S]*>/i.test(value)) {
            // Plain text from before the editor: keep the line breaks as paragraphs.
            value = value.split(/\n{2,}|\n/).map(function (line) {
                return '<p>' + line.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</p>';
            }).join('');
        }
        quill.clipboard.dangerouslyPasteHTML(value || '');
        quill.history.clear();

        // HTML view, for pasting or checking the code.
        var bar = wrap.querySelector('.ql-toolbar');
        var htmlBtn = document.createElement('button');
        htmlBtn.type = 'button';
        htmlBtn.className = 'rich-editor__html';
        htmlBtn.textContent = 'HTML';
        htmlBtn.title = 'Show or edit the HTML code';
        bar.appendChild(htmlBtn);
        var sourceMode = false;
        var html = function () {
            return quill.getSemanticHTML().replace(/&nbsp;/g, ' ').replace(/<p><\/p>/g, '');
        };
        htmlBtn.addEventListener('click', function () {
            sourceMode = !sourceMode;
            if (sourceMode) {
                area.value = html();
                area.hidden = false;
                holder.hidden = true;
            } else {
                quill.clipboard.dangerouslyPasteHTML(area.value);
                area.hidden = true;
                holder.hidden = false;
            }
            wrap.classList.toggle('is-source', sourceMode);
            htmlBtn.classList.toggle('is-active', sourceMode);
        });

        area.form && area.form.addEventListener('submit', function () {
            if (!sourceMode) area.value = quill.getText().trim() === '' && !holder.querySelector('img') ? '' : html();
        });
    }

    function pickImage(quill) {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/webp';
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            var body = new FormData();
            body.append('image', file);
            body.append('_token', window.CCS_ADMIN.csrf);
            var range = quill.getSelection(true);
            quill.insertText(range.index, 'Uploading picture...', { italic: true }, 'user');
            fetch(window.CCS_ADMIN.uploadUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    quill.deleteText(range.index, 'Uploading picture...'.length, 'user');
                    if (data.url) {
                        quill.insertEmbed(range.index, 'image', data.url, 'user');
                        quill.setSelection(range.index + 1, 0);
                    } else {
                        window.alert(data.error || 'The picture could not be uploaded.');
                    }
                })
                .catch(function () {
                    quill.deleteText(range.index, 'Uploading picture...'.length, 'user');
                    window.alert('The picture could not be uploaded. Please try again.');
                });
        });
        input.click();
    }

    // Email settings: SMTP fields only when SMTP is chosen
    var smtpFields = $('[data-smtp-fields]');
    $$('[data-mail-driver]').forEach(function (radio) {
        radio.addEventListener('change', function () { if (smtpFields) smtpFields.hidden = radio.value !== 'smtp' || !radio.checked; });
    });
    $$('select[name="smtp_encryption"]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var port = $('input[name="smtp_port"]');
            if (port) port.value = { ssl: 465, tls: 587, none: 25 }[sel.value];
        });
    });

    // Homepage sections: move up or down, and switch on or off without opening the panel
    var sectionList = $('[data-sections]');
    if (sectionList) {
        sectionList.addEventListener('click', function (e) {
            var move = e.target.closest('[data-move]');
            if (move) {
                e.preventDefault();
                var item = move.closest('[data-section]');
                var dir = parseInt(move.getAttribute('data-move'), 10);
                var sibling = dir < 0 ? item.previousElementSibling : item.nextElementSibling;
                if (sibling) sectionList.insertBefore(item, dir < 0 ? sibling : sibling.nextElementSibling);
                move.focus();
            }
            if (e.target.closest('[data-stop]')) e.stopPropagation();
        });
        $$('[data-stop]', sectionList).forEach(function (label) {
            label.addEventListener('click', function (e) { e.stopPropagation(); });
        });
        $$('[data-section-toggle]', sectionList).forEach(function (box) {
            box.addEventListener('change', function () { box.closest('[data-section]').classList.toggle('is-off', !box.checked); });
        });
        var openInput = $('[data-open-section]');
        $$('[data-section]', sectionList).forEach(function (d) {
            d.addEventListener('toggle', function () { if (d.open && openInput) openInput.value = d.id; });
        });
        if (location.hash && $(location.hash)) $(location.hash).open = true;
    }

    // New staff form: the access list only applies to staff accounts
    var roleSelect = $('[data-role-select]');
    var staffPerms = $('[data-staff-permissions]');
    if (roleSelect && staffPerms) {
        var syncRole = function () { staffPerms.hidden = roleSelect.value !== 'staff'; };
        roleSelect.addEventListener('change', syncRole);
        syncRole();
    }

    // Conversations open at the newest message
    $$('.chat-admin').forEach(function (box) { box.scrollTop = box.scrollHeight; });

    // Phone layout: give every table cell its column name so tables can stack as cards.
    $$('table.table').forEach(function (table) {
        var heads = $$('thead th', table).map(function (th) { return th.textContent.trim(); });
        if (!heads.length) return;
        $$('tbody tr', table).forEach(function (row) {
            $$('td', row).forEach(function (td, i) {
                if (!td.hasAttribute('data-label')) td.setAttribute('data-label', heads[i] || '');
            });
        });
        table.classList.add('is-stacked');
        if (heads.length >= 6) table.classList.add('is-wide');
    });

    // Top bar menus: only one open at a time, closed by a click outside or Esc
    var dropdowns = $$('details.dropdown, details.row-menu');
    dropdowns.forEach(function (d) {
        d.addEventListener('toggle', function () {
            if (d.open) dropdowns.forEach(function (o) { if (o !== d) o.open = false; });
        });
    });
    document.addEventListener('click', function (e) {
        dropdowns.forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdowns.forEach(function (d) { d.open = false; });
        var search = $('[data-search-input]');
        var typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName) || document.activeElement.isContentEditable;
        if (e.key === '/' && search && !typing && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            search.focus();
            search.select();
        }
    });
})();

// Product editor: the date field only matters for "Sold out until a date".
(function () {
    var select = document.querySelector('[data-sold-out]');
    var dateField = document.querySelector('[data-sold-out-date]');
    if (!select || !dateField) return;
    select.addEventListener('change', function () { dateField.hidden = select.value !== 'until'; });
})();

// Order line rows: choosing a cake fills in its price; "Something else" asks for a name.
document.addEventListener('change', function (e) {
    var select = e.target.closest && e.target.closest('[data-line-item]');
    if (!select) return;
    var row = select.closest('[data-line-row]');
    var price = row.querySelector('[data-line-price]');
    var custom = row.querySelector('[data-line-custom]');
    var option = select.options[select.selectedIndex];
    custom.hidden = select.value !== 'custom';
    if (price && (price.value === '' || price.getAttribute('data-auto') === '1')) {
        price.value = option && option.getAttribute('data-price') ? option.getAttribute('data-price') : '';
        price.setAttribute('data-auto', '1');
    }
});
document.addEventListener('input', function (e) {
    if (e.target.matches && e.target.matches('[data-line-price]')) e.target.removeAttribute('data-auto');
});

// New order form: delivery fields, live total and "Paid in full".
(function () {
    var form = document.querySelector('[data-new-order]');
    if (!form) return;
    var money = function (n) { return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
    var num = function (el) { var v = parseFloat(String(el && el.value || '').replace(/[$,]/g, '')); return isNaN(v) ? 0 : v; };
    var isDelivery = function () { var r = form.querySelector('[data-fulfilment]:checked'); return r && r.value === 'delivery'; };
    var total = function () {
        var sum = 0;
        form.querySelectorAll('[data-line-row]').forEach(function (row) {
            var item = row.querySelector('[data-line-item]');
            if (!item || !item.value) return;
            sum += num(row.querySelector('[data-line-price]')) * Math.max(0, num(row.querySelector('input[name$="[qty]"]')));
        });
        sum -= Math.min(sum, num(form.querySelector('[name="discount"]')));
        if (isDelivery()) sum += num(form.querySelector('[name="delivery_fee"]'));
        return Math.round(sum * 100) / 100;
    };
    var refresh = function () {
        form.querySelectorAll('[data-show-delivery]').forEach(function (el) { el.hidden = !isDelivery(); });
        var label = form.querySelector('[data-time-label]');
        if (label) label.textContent = isDelivery() ? 'From' : 'Time';
        var out = form.querySelector('[data-new-order-total]');
        if (out) out.textContent = money(total());
    };
    form.addEventListener('input', refresh);
    form.addEventListener('change', function () { setTimeout(refresh, 0); });
    form.addEventListener('click', function (e) {
        if (e.target.closest('[data-pay-full]')) { form.querySelector('[data-paid-now]').value = total().toFixed(2); }
        if (e.target.closest('[data-repeater-add], [data-repeater-remove]')) setTimeout(refresh, 0);
    });
    refresh();
})();

// Custom cake quote: suggest the usual deposit when a price is typed.
(function () {
    var price = document.querySelector('[data-quote-price]');
    var deposit = document.querySelector('[data-quote-deposit]');
    if (!price || !deposit) return;
    var percent = parseFloat((deposit.getAttribute('placeholder').match(/(\d+)%/) || [0, 50])[1]);
    price.addEventListener('change', function () {
        var p = parseFloat(price.value.replace(/[$,]/g, ''));
        if (deposit.value === '' && p > 0) deposit.value = (Math.round(p * percent) / 100).toFixed(2);
    });
})();
