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

    // Confirm before destructive actions (delegated, so lists refreshed live keep working)
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.hasAttribute && form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
    document.addEventListener('click', function (e) {
        var el = e.target.closest && e.target.closest('[data-confirm]');
        if (el && el.tagName !== 'FORM' && !window.confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });

    // Auto-submit filters
    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('[data-auto-submit]') && e.target.form) e.target.form.submit();
    });

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
    var dropdowns = function () { return $$('details.dropdown, details.row-menu'); };
    document.addEventListener('toggle', function (e) {
        var d = e.target;
        if (d.open && d.matches && d.matches('details.dropdown, details.row-menu')) dropdowns().forEach(function (o) { if (o !== d) o.open = false; });
    }, true);
    document.addEventListener('click', function (e) {
        dropdowns().forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdowns().forEach(function (d) { d.open = false; });
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

// Product editor SEO box: live Google preview, the checklist (same rules as
// product_seo_checks() in src/seo.php) and "Fill in for me".
(function () {
    var box = document.querySelector('[data-seo-box]');
    if (!box) return;
    var form = box.closest('form');
    var q = function (sel) { return form.querySelector(sel); };
    var nameInput = q('[data-slug-source]');
    var slugInput = q('[data-slug-target]');
    var shortInput = q('[name="short_description"]');
    var descArea = q('textarea[name="description"]');
    var keywordInput = box.querySelector('[data-seo-keyword]');
    var titleInput = box.querySelector('[data-seo-title]');
    var metaInput = box.querySelector('[data-seo-desc]');
    var altInput = q('[data-seo-alt]');
    var mainFile = q('input[name="image"]');
    var preview = document.getElementById('product-preview');
    var galleryInput = q('[data-seo-gallery]');
    var store = box.getAttribute('data-store');
    var name = function () { return (nameInput && nameInput.value.trim()) || 'Cake name'; };
    var slug = function () {
        return (slugInput && slugInput.value.trim()) || name().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    };
    var bodyText = function () {
        var editor = descArea && descArea.parentNode.querySelector('.ql-editor');
        var html = editor ? editor.innerHTML : (descArea ? descArea.value : '');
        var div = document.createElement('div');
        div.innerHTML = html;
        return (div.textContent || '').replace(/\s+/g, ' ').trim();
    };
    var titleValue = function () {
        if (titleInput.value.trim()) return titleInput.value.trim();
        var patterns = JSON.parse(titleInput.getAttribute('data-patterns') || '["%s"]');
        var fits = patterns.map(function (p) { return p.replace('%s', name()); }).filter(function (t) { return t.length <= 60; });
        return fits[0] || name();
    };
    var descDefault = function () { return metaInput.getAttribute('data-default').replace('__NAME__', name()); };
    var hasMainPhoto = function () {
        return (mainFile && mainFile.files && mainFile.files.length > 0) || (preview && !/placeholder-cake/.test(preview.getAttribute('src') || ''));
    };
    var extraCount = function () {
        return form.querySelectorAll('.photo-row').length + (galleryInput && galleryInput.files ? galleryInput.files.length : 0);
    };

    var checks = function () {
        var kw = keywordInput.value.trim().toLowerCase();
        var title = titleValue(), desc = metaInput.value.trim() || descDefault();
        var has = function (t) { return kw !== '' && t.toLowerCase().indexOf(kw) !== -1; };
        var slugWords = slug().replace(/-/g, ' ');
        var missing = kw.split(/\s+/).filter(function (w) { return w.length > 2 && slugWords.indexOf(w) === -1; }).length;
        var body = (bodyText() + ' ' + (shortInput ? shortInput.value : '')).toLowerCase();
        var words = bodyText().split(/\s+/).filter(Boolean).length;
        return [
            kw !== '', has(title), has(desc), kw !== '' && missing <= 1, has(body),
            title.length >= 30 && title.length <= 60, desc.length >= 120 && desc.length <= 160,
            /canberra|greenway|tuggeranong|act\b/i.test(title + ' ' + desc), words >= 60,
            hasMainPhoto(), hasMainPhoto() && extraCount() >= 1
        ];
    };

    var refresh = function () {
        box.querySelector('[data-seo-title-preview]').textContent = titleValue();
        box.querySelector('[data-seo-desc-preview]').textContent = metaInput.value.trim() || descDefault();
        box.querySelector('[data-seo-url]').textContent = box.getAttribute('data-base-url') + slug();
        var results = checks();
        var items = box.querySelectorAll('[data-seo-checks] li');
        results.forEach(function (ok, i) {
            if (!items[i]) return;
            items[i].classList.toggle('is-ok', ok);
            items[i].classList.toggle('is-todo', !ok);
            var tip = items[i].querySelector('small');
            if (tip) tip.hidden = ok;
        });
        var passed = results.filter(Boolean).length;
        var score = box.querySelector('[data-seo-score]');
        score.textContent = passed + '/' + results.length;
        var ratio = passed / results.length;
        score.className = 'seo-score seo-score--' + (ratio >= 0.8 ? 'good' : ratio >= 0.5 ? 'ok' : 'poor');
    };

    var titleCase = function (s) { return s.replace(/\b[a-z]/g, function (c) { return c.toUpperCase(); }).replace(/\bAct\b/g, 'ACT'); };
    var cut = function (text, max) {
        if (text.length <= max) return text;
        var part = text.slice(0, max + 1);
        var end = Math.max(part.lastIndexOf('. '), part.lastIndexOf(', '));
        return (end > max * 0.6 ? part.slice(0, end + 1) : part.slice(0, part.lastIndexOf(' '))).replace(/[,\s]+$/, '') ;
    };
    box.querySelector('[data-seo-fill]').addEventListener('click', function () {
        var kw = keywordInput.value.trim();
        var base = kw ? titleCase(kw) : name() + ' Canberra';
        var options = [base + ' | Order Online | ' + store, base + ' | ' + store, base + ' | Order Online', base];
        titleInput.value = options.filter(function (t) { return t.length <= 60; })[0] || cut(base, 60);
        var desc = descDefault();
        if (kw && desc.toLowerCase().indexOf(kw.toLowerCase()) === -1) desc = 'Looking for ' + kw.toLowerCase() + '? ' + desc;
        metaInput.value = cut(desc, 160);
        [titleInput, metaInput].forEach(function (el) { el.dispatchEvent(new Event('input', { bubbles: true })); });
        if (altInput && altInput.value.trim() === '' && hasMainPhoto()) altInput.value = name() + (shortInput && shortInput.value.trim() ? ', ' + shortInput.value.trim().replace(/\.$/, '').toLowerCase() : ' from ' + store);
        refresh();
    });

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    document.addEventListener('keyup', function (e) { if (e.target.closest && e.target.closest('.ql-editor')) refresh(); });
    refresh();
})();

/* ---------- Live updates ----------
   Every open admin page checks in every few seconds. New orders, customer messages and custom
   requests pop up with a sound, the badges update, and lists marked data-live refresh themselves
   (unless you are typing in them). */
(function () {
    var cfg = window.CCS_ADMIN && window.CCS_ADMIN.live;
    if (!cfg || !window.fetch) return;
    var stamp = cfg.stamp;
    var baseTitle = document.title;
    var unseen = 0;
    var store = function () { try { return window.localStorage; } catch (e) { return null; } };
    var seen = function (kind, id) {
        // Shared by all open tabs, so a new order pops up once and not in every tab.
        var s = store(); var key = 'ccsLive.' + kind;
        var last = s ? parseInt(s.getItem(key) || '0', 10) : 0;
        if (id <= last) return true;
        if (s) s.setItem(key, String(id));
        return false;
    };
    ['order', 'message', 'request'].forEach(function (k) { if (cfg[k]) seen(k, parseInt(cfg[k].id, 10)); });

    var setBadges = function (counts) {
        Object.keys(counts).forEach(function (k) {
            document.querySelectorAll('[data-badge="' + k + '"]').forEach(function (el) {
                var n = counts[k];
                el.hidden = !n;
                el.textContent = n > 99 ? '99+' : n;
            });
        });
    };

    var audio;
    var chime = function () {
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            [[880, 0], [1320, 0.16]].forEach(function (n) {
                var o = audio.createOscillator(), g = audio.createGain(), t = audio.currentTime + n[1];
                o.type = 'sine'; o.frequency.value = n[0];
                g.gain.setValueAtTime(0.0001, t);
                g.gain.exponentialRampToValueAtTime(0.25, t + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
                o.connect(g); g.connect(audio.destination);
                o.start(t); o.stop(t + 0.4);
            });
        } catch (e) {}
    };
    // Browsers only allow sound after the first click on the page.
    document.addEventListener('click', function () { try { audio = audio || new (window.AudioContext || window.webkitAudioContext)(); if (audio.state === 'suspended') audio.resume(); } catch (e) {} }, { once: true });

    var notify = function (title, text, link, linkLabel) {
        var stack = document.querySelector('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            document.querySelector('.admin-main').insertBefore(stack, document.querySelector('.admin-content'));
        }
        var t = document.createElement('div');
        t.className = 'toast toast--info toast--live';
        t.setAttribute('role', 'status');
        var body = document.createElement('span');
        var strong = document.createElement('strong');
        strong.textContent = title;
        body.appendChild(strong);
        if (text) { var small = document.createElement('small'); small.textContent = text; body.appendChild(small); }
        var dot = document.createElement('i');
        dot.className = 'toast__pulse';
        t.appendChild(dot);
        t.appendChild(body);
        if (link) {
            var a = document.createElement('a');
            a.className = 'btn btn--primary btn--sm';
            a.href = link;
            a.textContent = linkLabel || 'Open';
            t.appendChild(a);
        }
        var close = document.createElement('button');
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss');
        close.innerHTML = '&times;';
        close.className = 'toast__x';
        close.addEventListener('click', function () { t.remove(); });
        t.appendChild(close);
        stack.appendChild(t);
        setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 300); }, 15000);
        unseen++;
        document.title = '(' + unseen + ') ' + baseTitle;
        chime();
    };
    window.addEventListener('focus', function () { unseen = 0; document.title = baseTitle; });

    var money = function (n) { return '$' + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
    var adminUrl = function (path, id) { return cfg.url.replace(/live$/, path) + (cfg.url.indexOf('?') < 0 ? '?' : '&') + 'id=' + id; };

    // Lists marked data-live are swapped for fresh copies, unless someone is working in them.
    var dirty = false;
    document.addEventListener('input', function (e) { if (e.target.closest && e.target.closest('[data-live] form')) dirty = true; });
    var busy = function (region) {
        var a = document.activeElement;
        if (a && region.contains(a) && /^(INPUT|SELECT|TEXTAREA)$/.test(a.tagName)) return true;
        if (region.querySelector('details[open]:not(.dropdown__live-ok)') && region.getAttribute('data-live') !== 'alerts') return true;
        return false;
    };
    var pending = false;
    var refreshRegions = function () {
        var regions = Array.prototype.slice.call(document.querySelectorAll('[data-live]'));
        if (!regions.length) return;
        if (dirty || regions.some(busy)) { pending = true; return; }
        pending = false;
        fetch(location.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch-page' } })
            .then(function (r) { return r.ok && !r.redirected ? r.text() : null; })
            .then(function (html) {
                if (!html) return;
                var doc = new DOMParser().parseFromString(html, 'text/html');
                regions.forEach(function (region) {
                    if (busy(region)) return;
                    var fresh = doc.querySelector('[data-live="' + region.getAttribute('data-live') + '"]');
                    if (fresh && fresh.innerHTML !== region.innerHTML) {
                        region.innerHTML = fresh.innerHTML;
                        region.classList.remove('is-refreshed');
                        void region.offsetWidth;
                        region.classList.add('is-refreshed');
                    }
                });
                document.dispatchEvent(new CustomEvent('admin:refreshed'));
            })
            .catch(function () {});
    };

    var timer;
    var schedule = function () { clearTimeout(timer); timer = setTimeout(check, document.hidden ? 60000 : 10000); };
    var check = function () {
        fetch(cfg.url, { credentials: 'same-origin', cache: 'no-store', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.ok && !r.redirected ? r.json() : null; })
            .then(function (d) {
                if (!d) return;
                setBadges(d.counts);
                if (d.order && !seen('order', parseInt(d.order.id, 10))) {
                    notify('New order ' + d.order.order_number, d.order.customer_name + ' · ' + money(d.order.total), adminUrl('orders/view', d.order.id), 'Open order');
                }
                if (d.message && !seen('message', parseInt(d.message.id, 10))) {
                    var onThread = document.querySelector('[data-chat][data-chat-url*="id=' + d.message.thread_id + '"]');
                    if (!onThread) notify('Message from ' + d.message.customer_name, d.message.body, adminUrl('messages/view', d.message.thread_id), 'Reply');
                }
                if (d.request && !seen('request', parseInt(d.request.id, 10))) {
                    notify('New custom cake request', d.request.name + ' · ' + d.request.occasion, adminUrl('custom-requests/view', d.request.id), 'Open');
                }
                if (d.stamp !== stamp || pending) {
                    stamp = d.stamp;
                    refreshRegions();
                }
            })
            .catch(function () {})
            .then(schedule);
    };
    document.addEventListener('visibilitychange', function () { if (!document.hidden) check(); });
    schedule();
})();

/* Conversation page: new messages appear on their own, replies send without reloading. */
(function () {
    var list = document.querySelector('[data-chat]');
    if (!list || !window.fetch) return;
    var form = document.querySelector('[data-chat-form]');
    var url = list.getAttribute('data-chat-url');
    var lastId = parseInt(list.getAttribute('data-last-id'), 10) || 0;
    var timer, busy = false;
    var append = function (messages) {
        var added = false;
        messages.forEach(function (m) {
            if (m.id <= lastId || list.querySelector('[data-msg-id="' + m.id + '"]')) return;
            var empty = list.querySelector('.chat-admin__empty');
            if (empty) empty.remove();
            list.insertAdjacentHTML('beforeend', m.html);
            lastId = Math.max(lastId, m.id);
            added = true;
        });
        if (added) list.scrollTo({ top: list.scrollHeight, behavior: 'smooth' });
    };
    var schedule = function () { clearTimeout(timer); timer = setTimeout(poll, document.hidden ? 30000 : 4000); };
    var poll = function () {
        if (busy) return schedule();
        busy = true;
        fetch(url + '&poll=1&after=' + lastId, { credentials: 'same-origin', cache: 'no-store', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) { append(d.messages || []); })
            .catch(function () {})
            .then(function () { busy = false; schedule(); });
    };
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
    schedule();

    if (!form) return;
    var error = form.querySelector('[data-chat-error]');
    var note = form.querySelector('[data-chat-note]');
    var textarea = form.querySelector('textarea');
    textarea.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.submit(); }
    });
    form.addEventListener('submit', function (e) {
        var close = form.querySelector('input[name="close"]');
        if (close && close.checked) return; // Closing the conversation reloads the page.
        e.preventDefault();
        var button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        error.hidden = true;
        fetch(form.getAttribute('action') || location.href, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { error.textContent = d.error || 'The reply could not be sent.'; error.hidden = false; return; }
                textarea.value = '';
                if (note && d.note) { note.textContent = d.note; note.hidden = false; }
                poll();
            })
            .catch(function () { error.textContent = 'Could not send. Check your connection and try again.'; error.hidden = false; })
            .then(function () { button.disabled = false; textarea.focus(); });
    });
})();
