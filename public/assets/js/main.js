/* The Continental Cake Shop: small progressive enhancements. Every page still works without JavaScript. */
(function () {
    'use strict';

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    /* ---------- Mobile menu ---------- */
    var drawer = $('[data-drawer]');
    var openBtn = $('[data-menu-open]');
    var lastFocus = null;

    function openMenu() {
        if (!drawer) return;
        lastFocus = document.activeElement;
        drawer.hidden = false;
        document.body.classList.add('menu-open');
        openBtn && openBtn.setAttribute('aria-expanded', 'true');
        requestAnimationFrame(function () {
            drawer.classList.add('is-open');
            var close = $('.drawer__close', drawer);
            close && close.focus();
        });
    }

    function closeMenu() {
        if (!drawer || drawer.hidden) return;
        drawer.classList.remove('is-open');
        document.body.classList.remove('menu-open');
        openBtn && openBtn.setAttribute('aria-expanded', 'false');
        setTimeout(function () { drawer.hidden = true; }, 320);
        lastFocus && lastFocus.focus();
    }

    openBtn && openBtn.addEventListener('click', openMenu);
    $$('[data-menu-close]').forEach(function (el) { el.addEventListener('click', closeMenu); });

    document.addEventListener('keydown', function (e) {
        if (!drawer || drawer.hidden) return;
        if (e.key === 'Escape') { closeMenu(); return; }
        if (e.key !== 'Tab') return;
        // Keep keyboard focus inside the open menu.
        var focusable = $$('a[href], button, summary', $('.drawer__panel', drawer));
        var first = focusable[0], last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    window.matchMedia('(min-width: 1024px)').addEventListener('change', function (mq) { if (mq.matches) closeMenu(); });

    /* ---------- Scroll reveal ---------- */
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var revealTargets = $$('.section-head, .tile, .product-card, .reviews__summary, .review-card, .mosaic__item, .custom-feature__copy, .visit__info, .visit__map, .gallery-grid figure, .order-card, .usp__item');
    if ('IntersectionObserver' in window && !reduceMotion) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        revealTargets.forEach(function (el) {
            // Stagger siblings so cards appear one after another.
            var index = Array.prototype.indexOf.call(el.parentNode.children, el) % 4;
            el.style.setProperty('--reveal-delay', (index * 0.08) + 's');
            el.classList.add('reveal');
            observer.observe(el);
        });
    }

    /* ---------- Account nudge tooltip (once per visit) ---------- */
    var nudge = $('.account-link--nudge');
    if (nudge) {
        var seen = false;
        try { seen = sessionStorage.getItem('ccsTipSeen') === '1'; } catch (e) {}
        if (!seen) {
            setTimeout(function () { nudge.classList.add('show-tip'); }, 2500);
            setTimeout(function () { nudge.classList.remove('show-tip'); }, 7000);
            try { sessionStorage.setItem('ccsTipSeen', '1'); } catch (e) {}
        }
    }

    /* ---------- Cart count bump after adding ---------- */
    var countEl = $('[data-cart-count]');
    if (countEl && $('.toast.alert--success') && /added to your cart/i.test($('.toast.alert--success').textContent)) {
        countEl.classList.add('is-bumping');
    }

    /* ---------- Toast messages ---------- */
    $$('.toast').forEach(function (toast) {
        var hide = function () {
            toast.classList.add('is-hiding');
            setTimeout(function () { toast.remove(); }, 300);
        };
        var btn = $('[data-toast-close]', toast);
        btn && btn.addEventListener('click', hide);
        if (!toast.classList.contains('alert--error')) setTimeout(hide, 6000);
    });

    /* ---------- Quantity steppers ---------- */
    $$('[data-qty]').forEach(function (wrap) {
        var input = $('input', wrap);
        $$('[data-qty-step]', wrap).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var min = parseInt(input.min || '0', 10);
                var max = parseInt(input.max || '99', 10);
                var next = (parseInt(input.value, 10) || 0) + parseInt(btn.getAttribute('data-qty-step'), 10);
                input.value = Math.min(max, Math.max(min, next));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });

    // Cart quantities save automatically.
    var autoTimer;
    $$('[data-auto-update]').forEach(function (input) {
        input.addEventListener('change', function () {
            clearTimeout(autoTimer);
            autoTimer = setTimeout(function () { input.form.submit(); }, 450);
        });
    });

    $$('[data-auto-submit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });

    $$('.product-card__action').forEach(function (form) {
        if (form.tagName !== 'FORM') return;
        form.addEventListener('submit', function () {
            var btn = $('button', form);
            btn.disabled = true;
            btn.lastChild.textContent = ' Adding...';
        });
    });

    /* ---------- Product page ---------- */
    var productForm = $('[data-product-form]');
    if (productForm) {
        var priceEl = $('[data-price]');
        $$('input[name="size_id"]', productForm).forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (radio.checked && priceEl) priceEl.textContent = radio.getAttribute('data-price');
            });
        });
        var msg = $('[data-char-count]', productForm);
        var left = $('[data-char-left]', productForm);
        if (msg && left) {
            msg.addEventListener('input', function () { left.textContent = msg.maxLength - msg.value.length; });
        }
    }

    /* ---------- File inputs ---------- */
    $$('[data-file-drop]').forEach(function (drop) {
        var input = $('input[type="file"]', drop);
        var label = $('[data-file-name]', drop);
        var original = label.textContent;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            label.textContent = file ? file.name : original;
            drop.classList.toggle('has-file', !!file);
        });
    });

    /* ---------- Copy buttons ---------- */
    $$('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy');
            var done = function () {
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = 'Copy'; }, 1600);
            };
            if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, function () {});
        });
    });

    /* ---------- Checkout ---------- */
    var checkout = $('[data-checkout]');
    if (checkout) {
        var dateInput = $('[data-slot-date]', checkout);
        var timeSelect = $('[data-slot-time]', checkout);

        var loadSlots = function () {
            if (!dateInput.value) return;
            timeSelect.disabled = true;
            fetch(dateInput.getAttribute('data-slots-url') + '?date=' + encodeURIComponent(dateInput.value), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var previous = timeSelect.value;
                    timeSelect.innerHTML = '';
                    if (!data.slots.length) {
                        timeSelect.add(new Option('No times available on this day', ''));
                    }
                    data.slots.forEach(function (slot) {
                        timeSelect.add(new Option(slot.label, slot.value, false, slot.value === previous));
                    });
                })
                .catch(function () {})
                .then(function () { timeSelect.disabled = false; });
        };
        dateInput && dateInput.addEventListener('change', loadSlots);

        // Pickup or delivery
        var totalEl = $('[data-order-total]', checkout);
        var feeRow = $('[data-delivery-fee-row]', checkout);
        var deliveryFields = $$('[data-delivery-only]', checkout);
        var payAmounts = $$('[data-pay-amount]', checkout);
        var formatMoney = function (n) { return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };

        var updateFulfilment = function () {
            var checked = $('[data-fulfilment]:checked', checkout);
            var delivery = checked && checked.value === 'delivery';
            deliveryFields.forEach(function (el) { el.hidden = !delivery; });
            if (feeRow) feeRow.hidden = !delivery;
            if (totalEl) {
                var total = parseFloat(totalEl.getAttribute('data-subtotal')) + (delivery ? parseFloat(totalEl.getAttribute('data-delivery-fee')) : 0);
                totalEl.textContent = formatMoney(total);
                payAmounts.forEach(function (el) { el.textContent = formatMoney(total); });
            }
        };
        $$('[data-fulfilment]', checkout).forEach(function (r) { r.addEventListener('change', updateFulfilment); });

        // Payment method
        var proofFields = $('[data-proof-fields]', checkout);
        var submitLabel = $('[data-submit-label]', checkout);
        var submitBtn = submitLabel && submitLabel.closest('button');

        var updatePayment = function () {
            var checked = $('[data-payment-method]:checked', checkout);
            if (!checked) return;
            $$('[data-payment-details]', checkout).forEach(function (el) {
                el.hidden = el.getAttribute('data-payment-details') !== checked.value;
            });
            if (proofFields) proofFields.hidden = checked.getAttribute('data-needs-proof') !== '1';
            if (submitLabel && submitBtn) {
                submitLabel.textContent = checked.value === 'stripe'
                    ? submitBtn.getAttribute('data-submit-label-card')
                    : submitBtn.getAttribute('data-submit-label-default');
            }
        };
        $$('[data-payment-method]', checkout).forEach(function (r) { r.addEventListener('change', updatePayment); });

        // Optional account creation
        var createToggle = $('[data-create-account]', checkout);
        var passwordField = $('[data-account-password]', checkout);
        if (createToggle && passwordField) {
            createToggle.addEventListener('change', function () {
                passwordField.hidden = !createToggle.checked;
                if (createToggle.checked) $('input', passwordField).focus();
            });
        }

        checkout.addEventListener('submit', function () {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitLabel.textContent = 'Please wait...';
            }
        });
    }
})();
