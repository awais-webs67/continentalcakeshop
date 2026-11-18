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
        label && (label.querySelector('.field__head > label') || label.firstElementChild).appendChild(counter);
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
            // 8 random characters from the browser's secure generator: about a trillion possible codes.
            var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', code = 'CAKE', bytes = new Uint8Array(8);
            (window.crypto || window.msCrypto).getRandomValues(bytes);
            for (var i = 0; i < 8; i++) code += chars.charAt(bytes[i] % chars.length);
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
        // The AI writer (product editor) reads and replaces the text through these two.
        area.richHtml = function () { return sourceMode ? area.value : html(); };
        area.setRichHtml = function (value) {
            area.value = value;
            if (!sourceMode) quill.clipboard.dangerouslyPasteHTML(value);
        };

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
            var picked = input.files && input.files[0];
            if (!picked) return;
            // Made smaller in the browser first (shrink.js); this input is not on the page.
            (window.shrinkImage ? window.shrinkImage(picked) : Promise.resolve(picked)).then(function (file) {
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

    // Staff accounts: owners need no shop or page list, branch managers need a shop,
    // staff choose a shop (or every shop) and the pages they can open.
    $$('[data-access-form]').forEach(function (form) {
        var type = $('[data-access-type]', form);
        if (!type) return;
        var help = $('[data-access-help]', form);
        var branch = $('[data-access-branch]', form);
        var pages = $('[data-access-pages]', form);
        var sync = function () {
            var option = type.options[type.selectedIndex];
            if (help) help.textContent = option.getAttribute('data-help') || '';
            if (branch) {
                branch.hidden = type.value === 'owner';
                var every = $('[data-every]', branch);
                if (every) every.hidden = every.disabled = type.value === 'manager';
                var select = $('select', branch);
                if (type.value === 'manager' && select.value === '0' && select.options.length > 1) select.selectedIndex = 1;
            }
            if (pages) pages.hidden = type.value !== 'staff';
        };
        type.addEventListener('change', sync);
        sync();
    });

    // Conversations open at the newest message
    $$('.chat-admin').forEach(function (box) { box.scrollTop = box.scrollHeight; });

    // Table rows with a main link open when tapped anywhere on the row.
    document.addEventListener('click', function (e) {
        var row = e.target.closest && e.target.closest('.table tbody tr');
        if (!row || e.target.closest('a, button, input, select, textarea, label, summary, details, form')) return;
        if (window.getSelection && String(window.getSelection()).length) return; // Selecting text, not opening.
        var link = row.querySelector('a.row-link');
        if (!link) return;
        if (e.ctrlKey || e.metaKey) window.open(link.href, '_blank');
        else window.location.href = link.href;
    });
    var markRows = function () {
        $$('.table tbody tr').forEach(function (row) { if (row.querySelector('a.row-link')) row.classList.add('is-clickable'); });
    };
    markRows();
    document.addEventListener('admin:refreshed', markRows);

    // Top bar menus: only one open at a time, closed by a click outside or Esc
    var dropdowns = function () { return $$('details.dropdown, details.row-menu'); };
    document.addEventListener('toggle', function (e) {
        var d = e.target;
        if (d.open && d.matches && d.matches('details.dropdown, details.row-menu')) {
            dropdowns().forEach(function (o) { if (o !== d) o.open = false; });
            // Keep the menu on screen: slide it back in if it would open past an edge.
            var panel = d.querySelector('.dropdown__panel, .row-menu__panel');
            if (panel) {
                panel.style.transform = '';
                var r = panel.getBoundingClientRect(), shift = 0;
                if (r.left < 8) shift = 8 - r.left;
                else if (r.right > window.innerWidth - 8) shift = window.innerWidth - 8 - r.right;
                if (shift) panel.style.transform = 'translateX(' + Math.round(shift) + 'px)';
            }
        }
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
// Checklists: progress bar, counts and the "Show the done ones" link
window.ccsChecklist = function (wrap) {
    var items = Array.prototype.slice.call(wrap.querySelectorAll('.seo-checks li'));
    var done = items.filter(function (li) { return li.classList.contains('is-ok'); }).length;
    var total = items.length, ratio = total ? done / total : 1;
    items.forEach(function (li) {
        var state = li.querySelector('[data-check-state]');
        if (state) state.textContent = li.classList.contains('is-ok') ? 'Done: ' : 'To do: ';
    });
    wrap.querySelector('[data-checklist-todo]').textContent = total - done ? (total - done) + ' to do' : 'All done';
    wrap.querySelector('[data-checklist-count]').textContent = done + ' of ' + total + ' done';
    var meter = wrap.querySelector('[data-checklist-meter]');
    meter.className = 'seo-meter seo-meter--' + (ratio >= 0.8 ? 'good' : ratio >= 0.5 ? 'ok' : 'poor');
    meter.firstElementChild.style.width = Math.round(ratio * 100) + '%';
    var toggle = wrap.querySelector('[data-checklist-toggle]');
    if (toggle) {
        var collapsed = wrap.querySelector('.seo-checks').classList.contains('is-collapsed');
        toggle.hidden = done === 0;
        toggle.textContent = collapsed ? 'Show the ' + done + ' done' : 'Hide the done ones';
    }
};
document.querySelectorAll('[data-checklist-toggle]').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
        var wrap = toggle.closest('[data-checklist]');
        wrap.querySelector('.seo-checks').classList.toggle('is-collapsed');
        window.ccsChecklist(wrap);
    });
});

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
        // A space after each paragraph, heading and list item, so "frosting</li><h3>Red" stays two words (as content_text() in PHP).
        var html = (editor ? editor.innerHTML : (descArea ? descArea.value : '')).replace(/<\/(p|h[1-6]|li|div|blockquote)>|<br\s*\/?>/gi, '$& ');
        // DOMParser builds an inert document: no image loads and no handlers run, whatever the text holds.
        var text = new DOMParser().parseFromString(html, 'text/html').body.textContent || '';
        return text.replace(/\s+/g, ' ').trim();
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

    // Same rules as seo_words() in src/seo.php: "&" is "and", no apostrophes, hyphens are spaces.
    var seoWords = function (t) {
        return t.toLowerCase().replace(/&/g, ' and ').replace(/['\u2018\u2019]/g, '').replace(/[^\p{L}\p{N}]+/gu, ' ').trim();
    };
    var STOP = ['and', 'the', 'with', 'for', 'from', 'in', 'of', 'n'];
    var checks = function () {
        var kw = seoWords(keywordInput.value);
        var title = titleValue(), desc = metaInput.value.trim() || descDefault();
        var has = function (t) { return kw !== '' && (' ' + seoWords(t) + ' ').indexOf(' ' + kw + ' ') !== -1; };
        var slugWords = ' ' + slug().replace(/-/g, ' ') + ' ';
        var missing = kw.split(/\s+/).filter(function (w) { return w.length > 2 && STOP.indexOf(w) === -1 && slugWords.indexOf(' ' + w + ' ') === -1; }).length;
        var body = bodyText() + ' ' + (shortInput ? shortInput.value : '');
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
        window.ccsChecklist(box.querySelector('[data-checklist]'));
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
        if (kw && (' ' + seoWords(desc) + ' ').indexOf(' ' + seoWords(kw) + ' ') === -1) desc = 'Looking for ' + kw.toLowerCase() + '? ' + desc;
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
    // Each tab shows its own pop-up notice; the sound (or desktop alert) is claimed by one tab only,
    // and only by a tab that can actually make it, so two open tabs never ring twice or not at all.
    var known = {};
    var claim = function (kind, id) {
        var s = store(); var key = 'ccsRang.' + kind;
        var last = s ? parseInt(s.getItem(key) || '0', 10) : 0;
        if (id <= last) return false;
        if (s) s.setItem(key, String(id));
        return true;
    };
    // Anything that arrived since this device last rang (for example an order placed before this
    // page was opened) is announced once the page has loaded. On a first visit nothing old rings.
    ['order', 'message', 'request'].forEach(function (k) {
        var id = cfg[k] ? parseInt(cfg[k].id, 10) : 0;
        var s = store(); var key = 'ccsRang.' + k;
        var stored = s ? s.getItem(key) : null;
        var recent = cfg[k] && parseInt(cfg[k].age, 10) < 1800; // only things from the last 30 minutes
        if (stored === null || !recent) { known[k] = id; if (s && id > (parseInt(stored, 10) || 0)) s.setItem(key, String(id)); }
        else known[k] = Math.min(id, parseInt(stored, 10) || 0);
    });
    var isNew = function (kind, id) {
        if (id <= (known[kind] || 0)) return false;
        known[kind] = id;
        return true;
    };

    var setBadges = function (counts) {
        Object.keys(counts).forEach(function (k) {
            document.querySelectorAll('[data-badge="' + k + '"]').forEach(function (el) {
                var n = counts[k];
                el.hidden = !n;
                el.textContent = n > 99 ? '99+' : n;
            });
        });
    };

    var sound = window.CCSSound || { play: function () { return false; }, prefs: function () { return {}; } };

    // "Keep ringing": a new order rings every 15 seconds until someone touches the screen (max 5 minutes).
    var ringTimer = null, ringStop = 0;
    var stopRinging = function () { if (ringTimer) { clearInterval(ringTimer); ringTimer = null; } };
    var startRinging = function () {
        if (!sound.prefs().ring) return;
        ringStop = Date.now() + 5 * 60000;
        if (ringTimer) return;
        ringTimer = setInterval(function () {
            if (Date.now() > ringStop || !sound.prefs().ring) return stopRinging();
            sound.play('order');
        }, 15000);
    };
    ['pointerdown', 'keydown'].forEach(function (ev) { document.addEventListener(ev, stopRinging, { passive: true }); });

    var desktopReady = function () {
        return !!sound.prefs().desktop && 'Notification' in window && Notification.permission === 'granted';
    };
    var desktopAlert = function (kind, id, title, text, link) {
        try {
            var n = new Notification(title, { body: text || '', tag: kind + '-' + id, icon: cfg.icon, requireInteraction: kind === 'order' });
            n.onclick = function () { window.focus(); if (link) window.location.href = link; n.close(); };
            return true;
        } catch (e) { return false; }
    };
    // Sound and/or desktop alert for something new, from at most one tab.
    var alertFor = function (kind, id, title, text, link) {
        var canPlay = sound.prefs().on && sound.ready && sound.ready();
        var canDesktop = desktopReady();
        if (!canPlay && !canDesktop) return false;
        if (!claim(kind, id)) return true;
        if (canDesktop && (document.hidden || !canPlay)) desktopAlert(kind, id, title, text, link);
        else sound.play(kind);
        if (kind === 'order' && canPlay) startRinging();
        return true;
    };

    var notify = function (kind, id, title, text, link, linkLabel) {
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
        if (!alertFor(kind, id, title, text, link) && sound.prefs().on && !desktopReady()) {
            // This page cannot make a sound yet: say so on the notice itself.
            var hint = document.createElement('small');
            hint.className = 'toast__sound-off';
            hint.textContent = 'Sound is off on this page. Tap anywhere to turn it on.';
            body.appendChild(hint);
            // Another open tab that can play may still take it in the next few seconds.
            waiting.push([kind, id]);
        }
    };
    // Alerts that could not sound yet ring as soon as someone taps the page.
    var waiting = [];
    var flushWaiting = function () {
        if (!waiting.length || !(sound.ready && sound.ready())) return;
        var list = waiting; waiting = [];
        var played = false;
        list.forEach(function (w) { if (claim(w[0], w[1]) && !played) { sound.play(w[0]); played = true; if (w[0] === 'order') startRinging(); } });
        document.querySelectorAll('.toast__sound-off').forEach(function (el) { el.remove(); });
    };
    if (sound.onChange) sound.onChange(function () { setTimeout(flushWaiting, 50); });
    setInterval(flushWaiting, 1500);
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
    var schedule = function () { clearTimeout(timer); timer = setTimeout(check, document.hidden ? 20000 : 10000); };
    var check = function () {
        fetch(cfg.url, { credentials: 'same-origin', cache: 'no-store', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.ok && !r.redirected ? r.json() : null; })
            .then(function (d) { if (d) handle(d); })
            .catch(function () {})
            .then(schedule);
    };
    var handle = function (d) {
                setBadges(d.counts);
                if (d.order && isNew('order', parseInt(d.order.id, 10))) {
                    notify('order', parseInt(d.order.id, 10), 'New order ' + d.order.order_number, d.order.customer_name + ' · ' + money(d.order.total), adminUrl('orders/view', d.order.id), 'Open order');
                }
                if (d.message && isNew('message', parseInt(d.message.id, 10))) {
                    var onThread = document.querySelector('[data-chat][data-chat-url*="id=' + d.message.thread_id + '"]');
                    if (onThread) claim('message', parseInt(d.message.id, 10));
                    else notify('message', parseInt(d.message.id, 10), 'Message from ' + d.message.customer_name, d.message.body, adminUrl('messages/view', d.message.thread_id), 'Reply');
                }
                if (d.request && isNew('request', parseInt(d.request.id, 10))) {
                    notify('request', parseInt(d.request.id, 10), 'New custom cake request', d.request.name + ' · ' + d.request.occasion, adminUrl('custom-requests/view', d.request.id), 'Open');
                }
                if (d.stamp !== stamp || pending) {
                    stamp = d.stamp;
                    refreshRegions();
                }
    };
    handle(cfg);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) check(); });
    schedule();
})();

/* Chat screen on phones: full screen like WhatsApp. It follows the visible part of the screen,
   so the reply box stays just above the on-screen keyboard and the newest message stays in view. */
window.ccsChatScreen = function (list) {
    var screen = list.closest('[data-chat-screen]');
    var vv = window.visualViewport;
    list.scrollTop = list.scrollHeight;
    // Photos that load later make the chat taller: stay at the newest message unless the user scrolled up.
    var stick = true;
    list.addEventListener('scroll', function () { stick = list.scrollHeight - list.scrollTop - list.clientHeight < 80; });
    list.addEventListener('load', function () { if (stick) list.scrollTop = list.scrollHeight; }, true);
    if (!screen || !vv) return;
    var fit = function () {
        var full = getComputedStyle(screen).position === 'fixed';
        var atEnd = list.scrollHeight - list.scrollTop - list.clientHeight < 60;
        screen.style.height = full ? vv.height + 'px' : '';
        screen.style.top = full ? vv.offsetTop + 'px' : '';
        if (atEnd) list.scrollTop = list.scrollHeight;
    };
    vv.addEventListener('resize', fit);
    vv.addEventListener('scroll', fit);
    fit();
};

/* The paperclip in a chat: shows the chosen photo or PDF, which can be removed before sending. */
window.ccsChatFile = function (form) {
    var input = form.querySelector('[data-chat-file]');
    var chip = form.querySelector('[data-chat-picked]');
    var name = chip && chip.querySelector('[data-chat-picked-name]');
    var show = function () {
        var file = input && input.files && input.files[0];
        if (chip) chip.hidden = !file;
        if (name) name.textContent = file ? file.name : '';
    };
    if (input) input.addEventListener('change', show);
    var unpick = chip && chip.querySelector('[data-chat-unpick]');
    var clear = function () { if (input) input.value = ''; show(); };
    if (unpick) unpick.addEventListener('click', clear);
    return { has: function () { return !!(input && input.files && input.files.length); }, clear: clear };
};

/* Conversation page: new messages appear on their own, replies send without reloading. */
(function () {
    var list = document.querySelector('[data-chat]');
    if (!list || !window.fetch) return;
    var form = document.querySelector('[data-chat-form]');
    var url = list.getAttribute('data-chat-url');
    var lastId = parseInt(list.getAttribute('data-last-id'), 10) || 0;
    var timer, busy = false;
    window.ccsChatScreen(list);
    var append = function (messages) {
        var added = false;
        messages.forEach(function (m) {
            if (m.id <= lastId || list.querySelector('[data-msg-id="' + m.id + '"]')) return;
            var empty = list.querySelector('.chat-admin__empty');
            if (empty) empty.remove();
            list.insertAdjacentHTML('beforeend', m.html);
            lastId = Math.max(lastId, m.id);
            added = true;
            if (m.html.indexOf('chat-admin__msg--customer') !== -1 && window.CCSSound) {
                try { window.localStorage.setItem('ccsRang.message', String(Math.max(m.id, parseInt(window.localStorage.getItem('ccsRang.message') || '0', 10)))); } catch (e) {}
                window.CCSSound.play('message');
            }
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
    var picker = window.ccsChatFile(form);
    var grow = function () { textarea.style.height = 'auto'; textarea.style.height = Math.min(textarea.scrollHeight + 3, 180) + 'px'; };
    textarea.addEventListener('input', grow);
    var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    textarea.addEventListener('keydown', function (e) {
        // Enter sends (Shift+Enter for a new line) on a computer; Ctrl+Enter always sends.
        if (e.key === 'Enter' && !e.isComposing && ((e.ctrlKey || e.metaKey) || (!e.shiftKey && finePointer))) {
            e.preventDefault();
            if (textarea.value.trim() !== '' || picker.has()) form.requestSubmit ? form.requestSubmit() : form.submit();
        }
    });
    form.addEventListener('submit', function (e) {
        var close = form.querySelector('input[name="close"]');
        if (close && close.checked) return; // Closing the conversation reloads the page.
        e.preventDefault();
        if (textarea.value.trim() === '' && !picker.has()) { textarea.focus(); return; }
        var button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        error.hidden = true;
        fetch(form.getAttribute('action') || location.href, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { error.textContent = d.error || 'The reply could not be sent.'; error.hidden = false; return; }
                textarea.value = '';
                picker.clear();
                grow();
                if (window.CCSSound) window.CCSSound.play('sent');
                if (note && d.note) { note.textContent = d.note; note.hidden = false; }
                poll();
            })
            .catch(function () { error.textContent = 'Could not send. Check your connection and try again.'; error.hidden = false; })
            .then(function () { button.disabled = false; textarea.focus(); });
    });
})();

/* Alert sound settings in the top bar, and the "Tap to turn on order sounds" button. */
(function () {
    var sound = window.CCSSound;
    var menu = document.querySelector('[data-sound-menu]');
    var pill = document.querySelector('[data-sound-unlock]');
    if (!sound) { if (menu) menu.hidden = true; return; }
    var sync = function () {
        var prefs = sound.prefs();
        document.querySelectorAll('[data-sound-pref]').forEach(function (box) { box.checked = !!prefs[box.getAttribute('data-sound-pref')]; });
        document.querySelectorAll('[data-sound-icon]').forEach(function (el) { el.hidden = (el.getAttribute('data-sound-icon') === 'on') !== prefs.on; });
        // With desktop alerts on, new orders and messages still ring (through the computer's own
        // notification sound) after a reload, so the page does not ask for a tap every time.
        var desktopOn = !!prefs.desktop && 'Notification' in window && Notification.permission === 'granted';
        var blocked = prefs.on && !sound.ready() && !desktopOn;
        if (pill) pill.hidden = !blocked;
        // Phones show the same hint as a red dot on the speaker button instead of a floating button.
        var summary = document.querySelector('[data-sound-menu] > summary');
        if (summary) { summary.classList.toggle('is-blocked', blocked); summary.title = blocked ? 'Sounds are off until you tap the page' : 'Alert sounds'; }
    };
    var desktopNote = document.querySelector('[data-desktop-status]');
    var showDesktop = function () {
        if (!desktopNote) return;
        if (!('Notification' in window)) { desktopNote.textContent = 'This browser cannot show desktop alerts.'; return; }
        desktopNote.textContent = Notification.permission === 'granted' ? (sound.prefs().desktop ? 'On. Alerts show even when this tab is in the background.' : 'Allowed by the browser.')
            : Notification.permission === 'denied' ? 'Blocked by the browser. Click the lock icon next to the web address and allow Notifications.' : 'You will be asked to allow notifications.';
    };
    document.querySelectorAll('[data-sound-pref]').forEach(function (box) {
        box.addEventListener('change', function () {
            var key = box.getAttribute('data-sound-pref');
            if (key === 'desktop' && box.checked) {
                if (!('Notification' in window)) { box.checked = false; showDesktop(); return; }
                Notification.requestPermission().then(function (p) {
                    sound.set('desktop', p === 'granted');
                    if (p === 'granted') { try { new Notification('Desktop alerts are on', { body: 'New orders and messages will show here, with your computer\'s alert sound.', icon: (window.CCS_ADMIN.live || {}).icon }); } catch (e) {} }
                    showDesktop();
                });
                return;
            }
            sound.set(key, box.checked);
            showDesktop();
            if (box.checked && key !== 'desktop') sound.play(key === 'ring' ? 'order' : 'message', true);
        });
    });
    showDesktop();
    document.querySelectorAll('[data-sound-test]').forEach(function (btn) {
        btn.addEventListener('click', function () { sound.unlock(); sound.play(btn.getAttribute('data-sound-test'), true); });
    });
    if (pill) pill.addEventListener('click', function () {
        sound.unlock();
        setTimeout(function () { sound.play('order', true); sync(); }, 60);
        // Desktop alerts keep working after the page reloads (sound needs a tap on every new page).
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(function (p) { if (p === 'granted') sound.set('desktop', true); showDesktop(); });
        } else if ('Notification' in window && Notification.permission === 'granted' && !sound.prefs().desktop) {
            sound.set('desktop', true);
        }
    });
    sound.onChange(sync);
    sync();
})();

// Admin > Reviews: dim the per-place switches while the main switch is off.
(function () {
    var master = document.querySelector('[data-reviews-master]');
    var list = document.querySelector('[data-reviews-places]');
    if (!master || !list) return;
    master.addEventListener('change', function () {
        if (master.checked) list.removeAttribute('aria-disabled'); else list.setAttribute('aria-disabled', 'true');
    });
})();

// Admin > Menu: reorder, make sub items, add and remove rows, custom web addresses.
(function () {
    var list = document.querySelector('[data-menu-list]');
    var tpl = document.querySelector('[data-menu-template]');
    if (!list || !tpl) return;
    var next = list.children.length + 1;
    var sync = function () {
        Array.prototype.forEach.call(list.children, function (row, i) {
            var sub = row.querySelector('[data-menu-sub]');
            // The first row has nothing above it, so it is always a top item.
            var isSub = i > 0 && sub.checked;
            row.classList.toggle('menu-row--sub', isSub);
            row.querySelector('[data-menu-level]').value = isSub ? '1' : '0';
            var kind = row.querySelector('[data-menu-kind]');
            if (kind) kind.textContent = isSub ? 'Sub item' : 'Top item';
            sub.disabled = i === 0;
        });
    };
    var wire = function (row) {
        var select = row.querySelector('[data-menu-link]');
        var url = row.querySelector('[data-menu-url]');
        select.addEventListener('change', function () {
            url.hidden = select.value !== '__custom';
            if (!url.hidden) url.focus();
            // A sensible name when the row is still empty.
            var label = row.querySelector('input[name$="[label]"]');
            if (label && label.value.trim() === '' && select.value !== '__custom') {
                var text = select.options[select.selectedIndex].text.replace(/ \(.*\)$/, '');
                if (select.value !== '') label.value = text;
            }
        });
    };
    Array.prototype.forEach.call(list.children, wire);
    list.addEventListener('click', function (e) {
        var move = e.target.closest('[data-menu-move]');
        var remove = e.target.closest('[data-menu-remove]');
        var row = e.target.closest('[data-menu-row]');
        if (move && row) {
            var dir = parseInt(move.getAttribute('data-menu-move'), 10);
            var sibling = dir < 0 ? row.previousElementSibling : row.nextElementSibling;
            if (sibling) list.insertBefore(row, dir < 0 ? sibling : sibling.nextElementSibling);
            move.focus();
            sync();
        }
        if (remove && row) {
            row.remove();
            sync();
        }
    });
    list.addEventListener('change', function (e) { if (e.target.matches('[data-menu-sub]')) sync(); });
    var add = document.querySelector('[data-menu-add]');
    if (add) add.addEventListener('click', function () {
        var holder = document.createElement('div');
        holder.innerHTML = tpl.innerHTML.replace(/__i__/g, 'n' + (next++));
        var row = holder.firstElementChild;
        list.appendChild(row);
        wire(row);
        sync();
        var label = row.querySelector('input[name$="[label]"]');
        if (label) label.focus();
    });
    sync();
})();

// Admin > Gallery: upload many photos one at a time, so a big batch never passes the server's size limit.
(function () {
    var form = document.querySelector('[data-gallery-upload]');
    if (!form || !window.fetch || !window.FormData) return;
    var input = form.querySelector('input[type=file]');
    var status = form.querySelector('[data-gallery-progress]');
    var button = form.querySelector('button[type=submit]');
    form.addEventListener('submit', function (e) {
        var files = Array.prototype.slice.call(input.files || []);
        if (!files.length) return;
        e.preventDefault();
        button.disabled = true;
        status.hidden = false;
        var done = 0, failed = [];
        var sendNext = function (i) {
            if (i >= files.length) {
                if (failed.length) {
                    status.textContent = done + ' of ' + files.length + ' added. Not added: ' + failed.join(' ');
                    button.disabled = false;
                    if (done) setTimeout(function () { location.href = location.pathname + '?added=' + done; }, 4000);
                } else {
                    location.href = location.pathname + '?added=' + done;
                }
                return;
            }
            status.textContent = 'Uploading photo ' + (i + 1) + ' of ' + files.length + '...';
            var data = new FormData();
            data.append('_token', form.querySelector('[name=_token]').value);
            data.append('action', 'upload');
            data.append('occasion', form.querySelector('[name=occasion]').value);
            data.append('caption', form.querySelector('[name=caption]').value);
            data.append('image', files[i]);
            fetch(form.getAttribute('action') || location.href, { method: 'POST', body: data, headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
                .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'The server did not answer. The photo may be too big.' }; }); })
                .then(function (res) { if (res.ok) done++; else failed.push(files[i].name + ' (' + (res.error || 'error') + ')'); })
                .catch(function () { failed.push(files[i].name + ' (no connection)'); })
                .then(function () { sendNext(i + 1); });
        };
        sendNext(0);
    });
})();

// Forms that show parts only for some choices: data-show-if="field:value" (a radio group or a
// select), "field:a|b" for either, "field:!value" for anything else.
// Admin > Branches > Email (own account, provider fields) and Payments (own details).
(function () {
    document.querySelectorAll('[data-reveal-form]').forEach(function (form) {
        var parts = form.querySelectorAll('[data-show-if]');
        var root = form.closest('.branch-form') || form;
        var current = function (name) {
            var field = root.querySelector('[name="' + name + '"]:checked') || root.querySelector('select[name="' + name + '"]');
            return field ? field.value : '';
        };
        var sync = function () {
            parts.forEach(function (el) {
                var rule = el.getAttribute('data-show-if').split(':');
                var not = rule[1].charAt(0) === '!';
                var match = (not ? rule[1].slice(1) : rule[1]).split('|').indexOf(current(rule[0])) !== -1;
                el.hidden = not ? match : !match;
            });
            var provider = root.querySelector('[data-provider]');
            var help = root.querySelector('[data-provider-help]');
            if (provider && help) help.textContent = provider.options[provider.selectedIndex].getAttribute('data-help') || '';
        };
        form.addEventListener('change', sync);
        sync();
    });
})();

// Admin > Gallery > Photo tools: "Make all photos smaller" in batches, with progress.
(function () {
    var box = document.querySelector('[data-photo-optimise]');
    if (!box || !window.fetch || !window.FormData) return;
    var form = box.querySelector('[data-optimise-form]');
    var button = form.querySelector('button');
    var bar = box.querySelector('[data-optimise-bar]');
    var progress = box.querySelector('[data-optimise-progress]');
    var status = box.querySelector('[data-optimise-status]');
    var mb = function (bytes) { return (bytes / 1048576).toFixed(1) + ' MB'; };
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        button.disabled = true;
        progress.hidden = false;
        var saved = 0, changed = 0;
        var step = function (offset) {
            var data = new FormData(form);
            data.set('offset', offset);
            fetch(location.pathname, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then(function (res) {
                    saved += res.saved; changed += res.changed;
                    bar.style.width = (res.total ? Math.round(res.next / res.total * 100) : 100) + '%';
                    status.textContent = res.done
                        ? (changed ? 'Done: ' + changed + ' of ' + res.total + ' photos made smaller, ' + mb(saved) + ' saved.' : 'Done: all ' + res.total + ' photos were already small.')
                        : 'Working: ' + res.next + ' of ' + res.total + ' photos checked, ' + mb(saved) + ' saved so far...';
                    if (res.done) { button.disabled = false; } else { step(res.next); }
                })
                .catch(function () {
                    status.textContent = 'Stopped: the server did not answer. Press the button again to carry on.';
                    button.disabled = false;
                });
        };
        status.textContent = 'Starting...';
        step(0);
    });
})();

// Buttons with data-open="id" show or hide that panel (Edit on order cards, payment actions).
document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-open]');
    if (!btn) return;
    var panel = document.getElementById(btn.getAttribute('data-open'));
    if (!panel) return;
    e.preventDefault();
    var opening = panel.hidden;
    // Only one payment form at a time.
    if (opening && panel.closest('#payment')) {
        document.querySelectorAll('#payment .ov-panel').forEach(function (p) { p.hidden = true; });
    }
    panel.hidden = !opening;
    var menu = btn.closest('details.dropdown');
    if (menu) menu.open = false;
    if (opening) {
        var r = panel.getBoundingClientRect();
        if (r.top < 70 || r.bottom > window.innerHeight) panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var field = panel.querySelector('input:not([type=hidden]):not([type=checkbox]), select, textarea');
        if (field) setTimeout(function () { field.focus({ preventScroll: true }); }, 50);
    }
});

/* Pop-ups: a payment screenshot full size (click to zoom in), and an order's receipt from the
   Orders list. Close with the back arrow, the browser's Back button, Esc or a click outside. */
(function () {
    var open = null;
    var close = function (fromHistory) {
        if (!open) return;
        open.remove();
        open = null;
        document.body.classList.remove('has-modal');
        if (!fromHistory && history.state && history.state.ccsModal) history.back();
    };
    var show = function (box) {
        close(true);
        open = box;
        document.body.appendChild(box);
        document.body.classList.add('has-modal');
        history.pushState({ ccsModal: true }, '');
        var focus = box.querySelector('[data-modal-close]');
        if (focus) focus.focus();
    };
    window.addEventListener('popstate', function () { close(true); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && open) close(false); });
    var shell = function (title, extra) {
        var box = document.createElement('div');
        box.className = 'modal';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-label', title);
        box.innerHTML = '<div class="modal__bar"><button type="button" class="modal__back" data-modal-close aria-label="Close">'
            + '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg> Back</button>'
            + '<strong></strong><span class="modal__tools">' + (extra || '') + '</span></div><div class="modal__body"></div>';
        box.querySelector('strong').textContent = title;
        box.addEventListener('click', function (e) {
            if (e.target === box || e.target.closest('[data-modal-close]')) close(false);
        });
        return box;
    };
    document.addEventListener('click', function (e) {
        var link = e.target.closest && e.target.closest('a[data-lightbox], a[data-receipt]');
        if (!link || e.metaKey || e.ctrlKey || e.shiftKey) return;
        e.preventDefault();
        if (link.hasAttribute('data-receipt')) {
            var url = link.href + (link.href.indexOf('?') < 0 ? '?' : '&') + 'embed=1';
            var box = shell('Receipt ' + link.getAttribute('data-receipt'),
                '<button type="button" class="btn btn--secondary btn--sm" data-modal-print>Print</button><a class="btn btn--secondary btn--sm" href="' + link.href + '" target="_blank" rel="noopener">Open</a>');
            box.classList.add('modal--receipt');
            var frame = document.createElement('iframe');
            frame.src = url;
            frame.title = 'Receipt';
            box.querySelector('.modal__body').appendChild(frame);
            box.querySelector('[data-modal-print]').addEventListener('click', function () { try { frame.contentWindow.print(); } catch (err) { window.open(link.href, '_blank'); } });
            show(box);
            return;
        }
        var label = link.getAttribute('data-lightbox') || 'Payment screenshot';
        var lb = shell(label, '<a class="btn btn--secondary btn--sm" href="' + link.href + '" target="_blank" rel="noopener">Open</a>');
        lb.classList.add('modal--image');
        var img = document.createElement('img');
        img.src = link.href;
        img.alt = label;
        // Click the picture to zoom in on that spot; move the mouse to look around; click again to zoom out.
        // The spot under the mouse, measured on the picture before zooming (its box in the page).
        var aim = function (ev) {
            var p = img.parentNode.getBoundingClientRect();
            var x = (ev.clientX - p.left - img.offsetLeft) / img.offsetWidth * 100;
            var y = (ev.clientY - p.top - img.offsetTop + img.parentNode.scrollTop) / img.offsetHeight * 100;
            img.style.transformOrigin = Math.max(0, Math.min(100, x)) + '% ' + Math.max(0, Math.min(100, y)) + '%';
        };
        img.addEventListener('click', function (ev) { aim(ev); img.classList.toggle('is-zoomed'); });
        img.addEventListener('mousemove', function (ev) { if (img.classList.contains('is-zoomed')) aim(ev); });
        lb.querySelector('.modal__body').appendChild(img);
        show(lb);
    });
})();

// Custom request > Confirm as an order: the address fields show for a delivery.
document.addEventListener('change', function (e) {
    var select = e.target.closest && e.target.closest('[data-make-fulfilment]');
    if (!select) return;
    var box = select.form.querySelector('[data-make-delivery]');
    if (box) box.hidden = select.value !== 'delivery';
});

/* Marking an order Collected / Delivered / Completed while money is owing asks first:
   "Have you received $89.90 from Olivia?" Yes records the payment and completes the order;
   the cross (or Esc) leaves the order as it is. */
(function () {
    var methods = (window.CCS_ADMIN && window.CCS_ADMIN.payMethods) || { cash: 'Cash' };
    var ask = function (form) {
        var box = document.createElement('div');
        box.className = 'ask';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-labelledby', 'ask-title');
        var options = Object.keys(methods).map(function (k) {
            return '<option value="' + k + '"' + (k === form.getAttribute('data-collect-method') ? ' selected' : '') + '>' + methods[k].replace(/</g, '&lt;') + '</option>';
        }).join('');
        box.innerHTML = '<div class="ask__card">'
            + '<button type="button" class="ask__x" data-ask-no aria-label="Not yet">&times;</button>'
            + '<p class="ask__kicker">Payment</p>'
            + '<h2 id="ask-title"></h2>'
            + '<p class="ask__text">The order is only marked done once the money is in. Saying yes records it as paid.</p>'
            + '<label class="field"><span>Paid by</span><select class="input" data-ask-method>' + options + '</select></label>'
            + '<div class="ask__buttons"><button type="button" class="btn btn--primary" data-ask-yes></button>'
            + '<button type="button" class="btn btn--secondary" data-ask-no>Not yet</button></div></div>';
        box.querySelector('h2').textContent = 'Have you received ' + form.getAttribute('data-collect') + ' from ' + form.getAttribute('data-collect-name') + '?';
        box.querySelector('[data-ask-yes]').textContent = 'Yes, received ' + form.getAttribute('data-collect');
        var close = function () { box.remove(); document.removeEventListener('keydown', onKey); };
        var onKey = function (e) { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', onKey);
        box.addEventListener('click', function (e) {
            if (e.target === box || e.target.closest('[data-ask-no]')) return close();
            if (e.target.closest('[data-ask-yes]')) {
                [['paid_now', '1'], ['paid_method', box.querySelector('[data-ask-method]').value]].forEach(function (p) {
                    var input = form.querySelector('input[name="' + p[0] + '"]') || document.createElement('input');
                    input.type = 'hidden'; input.name = p[0]; input.value = p[1];
                    form.appendChild(input);
                });
                form.setAttribute('data-collect-ok', '1');
                close();
                if (form.requestSubmit) form.requestSubmit(); else form.submit();
            }
        });
        var menu = form.closest('details.dropdown');
        if (menu) menu.open = false;
        document.body.appendChild(box);
        box.querySelector('[data-ask-yes]').focus();
    };
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute || !form.hasAttribute('data-collect') || form.hasAttribute('data-collect-ok')) return;
        var status = form.querySelector('[name="order_status"]');
        if (!status || status.value !== 'completed') return;
        e.preventDefault();
        e.stopImmediatePropagation();
        ask(form);
    }, true);
})();

/* ---------- AI SEO writer ----------
   Product editor: "Write with AI" fills the SEO fields from the form (nothing is saved until
   Save product; Undo puts the old text back). AI SEO agent page: Test buttons, the shop profile
   draft and writing many cakes one after another. */
(function () {
    var cfg = window.CCS_ADMIN || {};
    var post = function (url, body) {
        body.set('_token', cfg.csrf);
        return fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) {
                return r.json().catch(function () { return { ok: false, error: 'The server answered with an error (' + r.status + '). Please try again.' }; });
            })
            .catch(function () { return { ok: false, error: 'No connection. Please try again.' }; });
    };
    var say = function (el, text, kind) {
        if (!el) return;
        el.textContent = text;
        el.className = el.className.replace(/\bis-(ok|bad)\b/g, '').trim() + (kind ? ' is-' + kind : '');
    };

    // Product editor
    var writer = document.querySelector('[data-ai-writer]');
    var writeBtn = writer && writer.querySelector('[data-ai-write]');
    if (writeBtn) {
        var form = writer.closest('form');
        var status = writer.querySelector('[data-ai-status]');
        var undoBtn = writer.querySelector('[data-ai-undo]');
        var written = writer.querySelector('[data-ai-written]');
        var notes = writer.querySelector('[data-ai-notes]');
        var FIELDS = ['focus_keyword', 'meta_title', 'meta_description', 'short_description', 'description', 'search_words', 'image_alt'];
        var input = function (name) { return form.querySelector('[name="' + name + '"]'); };
        var read = function (name) {
            var el = input(name);
            if (!el) return '';
            return name === 'description' && el.richHtml ? el.richHtml() : el.value;
        };
        var write = function (name, value) {
            var el = input(name);
            if (!el) return;
            if (name === 'description' && el.setRichHtml) el.setRichHtml(value); else el.value = value;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            var shown = name === 'description' ? (el.parentNode.querySelector('.rich-editor') || el) : el;
            shown.classList.add('is-ai-filled');
            setTimeout(function () { shown.classList.remove('is-ai-filled'); }, 2500);
        };
        var before = null;
        var refresh = function () { form.dispatchEvent(new Event('input', { bubbles: true })); };

        // The form as it is now, without the photos
        var formBody = function (action) {
            var body = new FormData(form);
            body.delete('image');
            body.delete('gallery[]');
            body.set('description', read('description'));
            body.set('ai_notes', notes ? notes.value : '');
            body.set('action', action);
            return body;
        };
        var hasName = function (sayIn) {
            var name = input('name');
            if (name && name.value.trim()) return true;
            say(sayIn, 'Type the cake name first.', 'bad');
            name && name.focus();
            return false;
        };
        var tokens = function (data) { return data.tokens ? ', ' + data.tokens.toLocaleString('en-AU') + ' tokens' : ''; };
        var searches = function (data) { return data.keywords && data.keywords.length ? ', using ' + data.keywords.length + ' real Google searches' : ''; };

        writeBtn.addEventListener('click', function () {
            if (!hasName(status)) return;
            var body = formBody('write');
            writer.classList.add('is-busy');
            say(status, 'Writing... this takes 5 to 30 seconds.');
            post(writer.getAttribute('data-url'), body).then(function (data) {
                writer.classList.remove('is-busy');
                if (!data.ok) { say(status, data.error || 'The AI could not write it. Please try again.', 'bad'); return; }
                before = {};
                FIELDS.forEach(function (f) { before[f] = read(f); });
                FIELDS.forEach(function (f) { if (typeof data.fields[f] === 'string' && data.fields[f] !== '') write(f, data.fields[f]); });
                if (written) written.value = '1';
                if (undoBtn) undoBtn.hidden = false;
                refresh();
                var skipped = Object.keys(data.failed || {});
                say(status, 'Written by ' + data.service + searches(data) + tokens(data) + (skipped.length ? ' (' + skipped.join(', ') + ' did not answer)' : '') + '. Read it, change anything you like, then press Save product.', 'ok');
            });
        });
        undoBtn && undoBtn.addEventListener('click', function () {
            if (!before) return;
            FIELDS.forEach(function (f) { write(f, before[f]); });
            before = null;
            if (written) written.value = '0';
            undoBtn.hidden = true;
            refresh();
            say(status, 'The text from before is back.');
        });

        // One field at a time: the "Write with AI" button beside each field
        form.querySelectorAll('[data-ai-field]').forEach(function (btn) {
            var field = btn.getAttribute('data-ai-field');
            var wrap = btn.parentNode;
            var undo = wrap.querySelector('[data-ai-field-undo]');
            var note = document.createElement('span');
            note.className = 'ai-mini-note';
            note.setAttribute('role', 'status');
            wrap.appendChild(note);
            var old = null;
            btn.addEventListener('click', function () {
                if (!hasName(note)) return;
                var body = formBody('field');
                body.set('field', field);
                btn.disabled = true;
                wrap.classList.add('is-busy');
                say(note, 'Writing...');
                post(writer.getAttribute('data-url'), body).then(function (data) {
                    btn.disabled = false;
                    wrap.classList.remove('is-busy');
                    if (!data.ok || !data.fields || typeof data.fields[field] !== 'string' || data.fields[field] === '') {
                        say(note, data.error || 'The AI could not write it. Please try again.', 'bad');
                        return;
                    }
                    old = read(field);
                    write(field, data.fields[field]);
                    if (written) written.value = '1';
                    undo.hidden = false;
                    refresh();
                    say(note, 'Written' + searches(data) + tokens(data) + '.', 'ok');
                });
            });
            undo.addEventListener('click', function () {
                if (old === null) return;
                write(field, old);
                old = null;
                undo.hidden = true;
                refresh();
                say(note, 'Back as it was.');
            });
        });
    }

    // Keyword ideas: real Google searches for the cake (works without an AI service)
    var ideas = document.querySelector('[data-kw-ideas]');
    if (ideas) {
        var ideasForm = ideas.closest('form');
        var ideasList = ideas.querySelector('[data-kw-list]');
        var ideasBtn = ideas.querySelector('[data-kw-load]');
        var field = function (name) { return ideasForm.querySelector('[name="' + name + '"]'); };
        ideasBtn.addEventListener('click', function () {
            var name = field('name');
            ideasList.hidden = false;
            if (!name.value.trim()) { ideasList.textContent = 'Type the cake name first.'; name.focus(); return; }
            ideasList.textContent = 'Asking Google...';
            ideasBtn.disabled = true;
            var body = new FormData();
            body.set('action', 'keywords');
            body.set('name', name.value);
            body.set('category_id', field('category_id') ? field('category_id').value : '');
            post(ideas.getAttribute('data-url'), body).then(function (data) {
                ideasBtn.disabled = false;
                ideasList.textContent = '';
                if (!data.ok || !data.ideas || !data.ideas.length) {
                    ideasList.textContent = data.error || 'Google had no suggestions for this name. Try a shorter name.';
                    return;
                }
                var head = document.createElement('p');
                head.className = 'kw-ideas__hint';
                head.textContent = 'Real Google searches, most searched first. Tap one to make it the focus keyword.';
                ideasList.appendChild(head);
                var chips = document.createElement('div');
                chips.className = 'kw-ideas__chips';
                var keyword = field('focus_keyword');
                data.ideas.forEach(function (idea) {
                    var chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'kw-chip' + (idea.local ? ' is-local' : '') + (idea.phrase === keyword.value.trim().toLowerCase() ? ' is-chosen' : '');
                    chip.textContent = idea.phrase;
                    if (idea.local) chip.title = 'Local search: people near the shop';
                    chip.addEventListener('click', function () {
                        keyword.value = idea.phrase;
                        keyword.dispatchEvent(new Event('input', { bubbles: true }));
                        chips.querySelectorAll('.kw-chip').forEach(function (c) { c.classList.toggle('is-chosen', c === chip); });
                    });
                    chips.appendChild(chip);
                });
                ideasList.appendChild(chips);
                var add = document.createElement('button');
                add.type = 'button';
                add.className = 'link-btn';
                add.textContent = 'Add these to the search words';
                add.addEventListener('click', function () {
                    var words = field('search_words');
                    var have = words.value.split(',').map(function (w) { return w.trim().toLowerCase(); }).filter(Boolean);
                    data.ideas.forEach(function (idea) {
                        if (have.indexOf(idea.phrase) === -1 && idea.phrase !== keyword.value.trim().toLowerCase() && (have.join(', ') + ', ' + idea.phrase).length <= 255) have.push(idea.phrase);
                    });
                    words.value = have.join(', ');
                    words.dispatchEvent(new Event('input', { bubbles: true }));
                    add.textContent = 'Added';
                });
                ideasList.appendChild(add);
            });
        });
    }

    // AI SEO agent page: add form follows the chosen service
    var preset = document.querySelector('[data-ai-preset]');
    if (preset) {
        var addForm = preset.form;
        var pick = function () {
            var opt = preset.options[preset.selectedIndex];
            var model = addForm.querySelector('[data-ai-model]');
            var link = addForm.querySelector('[data-ai-keys-link]');
            model.placeholder = opt.getAttribute('data-model') || 'model name';
            model.required = !opt.getAttribute('data-model');
            link.hidden = !opt.getAttribute('data-keys');
            if (opt.getAttribute('data-keys')) link.href = opt.getAttribute('data-keys');
            addForm.querySelector('[data-ai-url]').hidden = preset.value !== 'custom';
            addForm.querySelector('[data-ai-name]').hidden = preset.value !== 'custom';
            addForm.querySelector('[name="base_url"]').required = preset.value === 'custom';
        };
        preset.addEventListener('change', pick);
        pick();
    }

    // Test buttons
    document.querySelectorAll('[data-ai-test]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('[data-ai-service]');
            var id = row.getAttribute('data-ai-service');
            var result = row.querySelector('[data-ai-result]');
            result.textContent = 'Testing...';
            btn.disabled = true;
            var body = new FormData();
            body.set('action', 'test');
            body.set('id', id);
            post(location.pathname + location.search, body).then(function (data) {
                btn.disabled = false;
                result.textContent = '';
                var badge = document.createElement('span');
                badge.className = 'badge badge--' + (data.ok ? 'active' : 'failed');
                badge.textContent = data.ok ? 'Working' : 'Failed';
                var note = document.createElement('small');
                note.textContent = data.message || data.error || '';
                result.appendChild(badge);
                result.appendChild(note);
                if (data.model) {
                    row.querySelector('[data-ai-model-name]').textContent = data.model;
                    row.querySelector('[data-model-input]').value = data.model;
                }
            });
        });
    });

    // Choose a model: the service's own list, recommended ones first, with what each costs in tokens
    var SHOW_FIRST = 6;
    var renderModels = function (picker, data) {
        var list = picker.querySelector('[data-model-list]');
        var input = picker.querySelector('[data-model-input]');
        list.hidden = false;
        list.textContent = '';
        if (!data.ok) {
            var err = document.createElement('p');
            err.className = 'hint model-picker__error';
            err.textContent = data.error || 'Could not load the models.';
            list.appendChild(err);
            return;
        }
        var head = document.createElement('div');
        head.className = 'model-picker__head';
        var count = document.createElement('span');
        count.textContent = data.models.length + ' writing models' + (data.hidden ? ' (' + data.hidden + ' speech, image and safety models hidden)' : '') + '. Most tokens go on the shop details sent with every cake; models that think first add more.';
        head.appendChild(count);
        var search = document.createElement('input');
        search.className = 'input input--sm';
        search.type = 'search';
        search.placeholder = 'Search models';
        search.setAttribute('aria-label', 'Search models');
        if (data.models.length > SHOW_FIRST) head.appendChild(search);
        list.appendChild(head);

        var group = document.createElement('div');
        group.className = 'model-picker__options';
        group.setAttribute('role', 'radiogroup');
        var name = 'model-pick-' + Math.random().toString(36).slice(2);
        var rows = data.models.map(function (m, i) {
            var opt = document.createElement('label');
            opt.className = 'model-opt';
            var radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = name;
            radio.value = m.id;
            radio.checked = m.id === input.value.trim();
            radio.addEventListener('change', function () { input.value = m.id; });
            var main = document.createElement('span');
            main.className = 'model-opt__main';
            var title = document.createElement('span');
            title.className = 'model-opt__id';
            title.textContent = m.id;
            main.appendChild(title);
            var tags = document.createElement('span');
            tags.className = 'model-opt__tags';
            m.tags.forEach(function (t) {
                var chip = document.createElement('span');
                chip.className = 'mtag mtag--' + t[1];
                chip.textContent = t[0];
                tags.appendChild(chip);
            });
            if (m.note) {
                var note = document.createElement('span');
                note.className = 'model-opt__note';
                note.textContent = m.note;
                tags.appendChild(note);
            }
            main.appendChild(tags);
            opt.appendChild(radio);
            opt.appendChild(main);
            opt.hidden = i >= SHOW_FIRST && !radio.checked;
            group.appendChild(opt);
            return opt;
        });
        list.appendChild(group);
        input.addEventListener('input', function () {
            rows.forEach(function (r) { r.querySelector('input').checked = r.querySelector('input').value === input.value.trim(); });
        });

        var more = document.createElement('button');
        more.type = 'button';
        more.className = 'link-btn';
        more.textContent = 'Show all ' + data.models.length + ' models';
        more.hidden = data.models.length <= SHOW_FIRST;
        more.addEventListener('click', function () {
            rows.forEach(function (r) { r.hidden = false; });
            more.hidden = true;
            group.classList.add('is-all');
        });
        list.appendChild(more);
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            rows.forEach(function (r, i) {
                var id = r.querySelector('input').value.toLowerCase();
                r.hidden = q ? id.indexOf(q) === -1 : (i >= SHOW_FIRST && more.hidden === false && !r.querySelector('input').checked);
            });
        });
    };
    var loadModels = function (picker, body, button) {
        var list = picker.querySelector('[data-model-list]');
        list.hidden = false;
        list.innerHTML = '<p class="hint">Asking the service for its models...</p>';
        if (button) button.disabled = true;
        body.set('action', 'models');
        post(location.pathname + location.search, body).then(function (data) {
            if (button) button.disabled = false;
            renderModels(picker, data);
        });
    };
    document.querySelectorAll('[data-ai-edit]').forEach(function (details) {
        var picker = details.querySelector('[data-model-picker]');
        details.addEventListener('toggle', function () {
            if (!details.open || picker.getAttribute('data-loaded')) return;
            picker.setAttribute('data-loaded', '1');
            var body = new FormData();
            body.set('id', picker.getAttribute('data-id'));
            loadModels(picker, body);
        });
    });
    var addLoad = document.querySelector('[data-ai-add] [data-model-load]');
    if (addLoad) {
        addLoad.addEventListener('click', function () {
            var form = addLoad.form;
            var body = new FormData();
            ['service', 'api_key', 'base_url'].forEach(function (k) { body.set(k, form.elements[k].value); });
            loadModels(form.querySelector('[data-model-picker]'), body, addLoad);
        });
        // A new service or key: the list shown belongs to the old one
        ['service', 'api_key'].forEach(function (k) {
            addLoad.form.elements[k].addEventListener('change', function () {
                var list = addLoad.form.querySelector('[data-model-list]');
                list.hidden = true;
                list.textContent = '';
            });
        });
    }

    // Shop profile draft
    var draftBtn = document.querySelector('[data-ai-draft]');
    if (draftBtn) {
        var profile = document.querySelector('[data-ai-profile]');
        var draftStatus = document.querySelector('[data-ai-draft-status]');
        draftBtn.addEventListener('click', function () {
            if (profile.value.trim() && !window.confirm('Replace the text in the box with a new draft? It is not saved until you press Save.')) return;
            var body = new FormData();
            body.set('action', 'profile_draft');
            draftBtn.disabled = true;
            say(draftStatus, 'Writing a draft...');
            post(location.pathname + location.search, body).then(function (data) {
                draftBtn.disabled = false;
                if (!data.ok) { say(draftStatus, data.error, 'bad'); return; }
                profile.value = data.text;
                say(draftStatus, 'Draft written. Correct anything that is not right, then press Save.', 'ok');
            });
        });
    }

    // Many cakes
    var table = document.querySelector('[data-bulk-table]');
    var runBtn = document.querySelector('[data-bulk-run]');
    if (table && runBtn) {
        var rows = Array.prototype.slice.call(table.querySelectorAll('[data-bulk-row]'));
        var bulkStatus = document.querySelector('[data-bulk-status]');
        document.querySelectorAll('[data-bulk-select]').forEach(function (b) {
            b.addEventListener('click', function () {
                var mode = b.getAttribute('data-bulk-select');
                rows.forEach(function (r) {
                    r.querySelector('input').checked = mode === 'all' || (mode === 'weak' && parseInt(r.getAttribute('data-score'), 10) < 9);
                });
            });
        });
        var running = false;
        runBtn.addEventListener('click', function () {
            if (running) return;
            var todo = rows.filter(function (r) { return r.querySelector('input').checked; });
            if (!todo.length) { say(bulkStatus, 'Tick the cakes first.', 'bad'); return; }
            if (!window.confirm('Write and save new SEO text for ' + todo.length + ' cakes? Each old text is kept under Earlier versions on the cake.')) return;
            running = true;
            runBtn.disabled = true;
            var done = 0, failed = 0;
            var next = function () {
                var row = todo.shift();
                if (!row) {
                    running = false;
                    runBtn.disabled = false;
                    say(bulkStatus, 'Finished: ' + done + ' written' + (failed ? ', ' + failed + ' failed (try those again later)' : '') + '.', failed ? 'bad' : 'ok');
                    return;
                }
                var cell = row.querySelector('[data-bulk-result]');
                cell.textContent = 'Writing...';
                say(bulkStatus, 'Writing ' + (done + failed + 1) + ' of ' + (done + failed + 1 + todo.length) + '...');
                var body = new FormData();
                body.set('action', 'rewrite');
                body.set('id', row.getAttribute('data-bulk-row'));
                post(runBtn.getAttribute('data-url'), body).then(function (data) {
                    if (data.ok) {
                        done++;
                        var score = row.querySelector('[data-bulk-score]');
                        score.textContent = data.score;
                        score.className = 'seo-score seo-score--' + data.level;
                        cell.textContent = 'Done: ' + (data.title || '');
                        row.querySelector('input').checked = false;
                    } else {
                        failed++;
                        cell.textContent = data.error || 'Failed';
                    }
                    // A short pause keeps free services within their per-minute limits.
                    setTimeout(next, 1500);
                });
            };
            next();
        });
    }
})();
