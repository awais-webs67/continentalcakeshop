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
        // Price shown = chosen size + ticked options.
        var showPrice = function () {
            var size = $('input[name="size_id"]:checked', productForm);
            if (!size || !priceEl) return;
            var total = parseFloat(size.getAttribute('data-amount') || '0');
            $$('input[name="options[]"]:checked', productForm).forEach(function (o) { total += parseFloat(o.getAttribute('data-extra') || '0'); });
            priceEl.textContent = '$' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            $$('[data-price-mirror]').forEach(function (el) { el.textContent = priceEl.textContent; });
        };
        $$('input[name="size_id"], input[name="options[]"]', productForm).forEach(function (input) {
            input.addEventListener('change', showPrice);
        });
        var msg = $('[data-char-count]', productForm);
        var left = $('[data-char-left]', productForm);
        if (msg && left) {
            msg.addEventListener('input', function () { left.textContent = msg.maxLength - msg.value.length; });
        }
    }

    /* ---------- File inputs ---------- */
    // Fields marked data-async-upload send the file as soon as it is chosen and show the
    // progress, so a slow phone upload is never lost when the customer presses submit.
    $$('[data-file-drop]').forEach(function (drop) {
        var input = $('input[type="file"]', drop);
        var label = $('[data-file-name]', drop);
        var hint = label.getAttribute('data-hint') || label.textContent;
        var purpose = drop.getAttribute('data-async-upload');
        var form = drop.closest('form');

        if (!purpose || !window.FormData || !window.XMLHttpRequest) {
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                label.textContent = file ? file.name : hint;
                drop.classList.toggle('has-file', !!file);
            });
            return;
        }

        var tokenInput = drop.parentNode.querySelector('[data-upload-token]');
        var errorEl = drop.parentNode.querySelector('[data-upload-error]');
        var progress = $('[data-upload-progress]', drop);
        var bar = $('[data-upload-bar]', drop);
        var percent = $('[data-upload-percent]', drop);
        var maxBytes = parseInt(drop.getAttribute('data-max-bytes'), 10) || 0;

        var showError = function (message) {
            errorEl.textContent = message;
            errorEl.hidden = !message;
        };
        var setProgress = function (value) {
            bar.style.width = value + '%';
            percent.textContent = value + '%';
        };
        var finish = function () {
            drop.classList.remove('is-uploading');
            form.uploading = Math.max(0, (form.uploading || 1) - 1);
        };

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            showError('');
            if (maxBytes && file.size > maxBytes) {
                showError('This file is ' + (file.size / 1048576).toFixed(1) + ' MB. Please choose one under ' + Math.round(maxBytes / 1048576) + ' MB.');
                input.value = '';
                return;
            }

            var data = new FormData();
            data.append('file', file);
            data.append('purpose', purpose);
            data.append('_token', $('input[name="_token"]', form).value);

            tokenInput.value = '';
            drop.classList.remove('is-uploaded');
            drop.classList.add('has-file', 'is-uploading');
            label.textContent = 'Uploading ' + file.name;
            progress.hidden = false;
            setProgress(0);
            form.uploading = (form.uploading || 0) + 1;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', drop.getAttribute('data-upload-url'));
            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) setProgress(Math.min(99, Math.round(e.loaded / e.total * 100)));
            });
            xhr.addEventListener('load', function () {
                finish();
                var reply = {};
                try { reply = JSON.parse(xhr.responseText); } catch (err) {}
                if (xhr.status === 200 && reply.token) {
                    showError('');
                    setProgress(100);
                    tokenInput.value = reply.token;
                    input.value = '';
                    drop.classList.add('is-uploaded');
                    label.textContent = file.name + ' uploaded';
                    setTimeout(function () { progress.hidden = true; }, 600);
                } else {
                    progress.hidden = true;
                    drop.classList.remove('has-file');
                    label.textContent = hint;
                    input.value = '';
                    showError(reply.error || 'The upload did not finish. Please try again.');
                }
            });
            xhr.addEventListener('error', function () {
                finish();
                progress.hidden = true;
                drop.classList.remove('has-file');
                label.textContent = hint;
                input.value = '';
                showError('The upload did not finish. Please check your connection and try again.');
            });
            xhr.send(data);
        });

        form.addEventListener('submit', function (e) {
            if (form.uploading > 0) {
                e.preventDefault();
                e.stopImmediatePropagation();
                showError('Please wait until the upload reaches 100%.');
            }
        });
    });

    /* ---------- Promo popup ---------- */
    // Shown once per campaign (or once per visit), after a short delay. Saving the popup
    // in the admin panel starts a new campaign, so people see the new offer.
    var promo = $('[data-promo]');
    if (promo) {
        var version = promo.getAttribute('data-promo-version');
        var perVisit = promo.getAttribute('data-promo-frequency') === 'session';
        var store = function () { try { return perVisit ? window.sessionStorage : window.localStorage; } catch (e) { return null; } };
        var seen = false;
        try { seen = store() && store().getItem('ccsPromo') === version; } catch (e) {}
        var closePromo = function () {
            promo.classList.remove('is-open');
            document.body.classList.remove('promo-open');
            setTimeout(function () { promo.hidden = true; }, 300);
            try { store() && store().setItem('ccsPromo', version); } catch (e) {}
        };
        if (!seen) {
            setTimeout(function () {
                if (document.body.classList.contains('menu-open')) return;
                promo.hidden = false;
                document.body.classList.add('promo-open');
                requestAnimationFrame(function () { promo.classList.add('is-open'); });
                var close = $('.promo__close', promo);
                close && close.focus();
            }, Math.max(0, parseInt(promo.getAttribute('data-promo-delay'), 10) || 0) * 1000);
            $$('[data-promo-close]', promo).forEach(function (el) { el.addEventListener('click', closePromo); });
            var cta = $('[data-promo-cta]', promo);
            cta && cta.addEventListener('click', function () { try { store() && store().setItem('ccsPromo', version); } catch (e) {} });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !promo.hidden) closePromo(); });
        }
    }

    /* ---------- Custom cake: quick pick for number of people ---------- */
    var servingsInput = $('#servings');
    $$('[data-serving]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            servingsInput.value = btn.getAttribute('data-serving');
            $$('[data-serving]').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
        });
    });

    /* ---------- Copy buttons ---------- */
    $$('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy');
            var label = $('span', btn) || btn;
            var done = function () {
                label.textContent = 'Copied';
                setTimeout(function () { label.textContent = 'Copy'; }, 1600);
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
        var pickupFields = $$('[data-pickup-only]', checkout);
        var payAmounts = $$('[data-pay-amount]', checkout);
        var formatMoney = function (n) { return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };

        var feeValue = $('[data-delivery-fee-value]', checkout);
        var discountRow = $('[data-discount-row]', checkout);
        var discountValue = $('[data-discount-value]', checkout);

        // Same sums as order_totals() in PHP: the discount comes off the cakes, then delivery is added.
        var updateTotals = function () {
            if (!totalEl) return;
            var checked = $('[data-fulfilment]:checked', checkout) || $('input[name="fulfilment"]', checkout);
            var delivery = checked && checked.value === 'delivery';
            var num = function (name) { return parseFloat(totalEl.getAttribute(name)) || 0; };
            var afterDiscount = num('data-subtotal') - num('data-discount');
            var fee = num('data-delivery-fee');
            var freeOver = num('data-free-over');
            var deliveryFee = fee > 0 && !(freeOver > 0 && afterDiscount >= freeOver) ? fee : 0;
            if (feeValue) feeValue.textContent = deliveryFee > 0 ? formatMoney(deliveryFee) : 'Free';
            if (discountRow) {
                discountRow.hidden = num('data-discount') <= 0;
                discountValue.textContent = '-' + formatMoney(num('data-discount'));
            }
            var total = afterDiscount + (delivery ? deliveryFee : 0);
            totalEl.textContent = formatMoney(total);
            payAmounts.forEach(function (el) { el.textContent = formatMoney(total); });
        };

        var updateFulfilment = function () {
            var checked = $('[data-fulfilment]:checked', checkout);
            var delivery = checked && checked.value === 'delivery';
            deliveryFields.forEach(function (el) { el.hidden = !delivery; });
            pickupFields.forEach(function (el) { el.hidden = delivery; });
            if (feeRow) feeRow.hidden = !delivery;
            updateTotals();
        };
        $$('[data-fulfilment]', checkout).forEach(function (r) { r.addEventListener('change', updateFulfilment); });

        // Tell the customer straight away if a delivery date cannot be booked.
        var deliveryDate = $('[data-delivery-date]', checkout);
        if (deliveryDate) {
            var dateError = deliveryDate.parentNode.querySelector('.field-error');
            deliveryDate.addEventListener('change', function () {
                if (!deliveryDate.value) return;
                fetch(deliveryDate.getAttribute('data-check-url') + '?type=delivery&date=' + encodeURIComponent(deliveryDate.value), { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!dateError) return;
                        dateError.textContent = data.message || '';
                        dateError.hidden = !!data.available;
                        deliveryDate.setAttribute('aria-invalid', data.available ? 'false' : 'true');
                    })
                    .catch(function () {});
            });
        }

        // Payment method
        var proofFields = $('[data-proof-fields]', checkout);
        var submitBtns = $$('button[data-submit-label-default]', checkout);

        var updatePayment = function () {
            var checked = $('[data-payment-method]:checked', checkout);
            if (!checked) return;
            $$('[data-payment-details]', checkout).forEach(function (el) {
                el.hidden = el.getAttribute('data-payment-details') !== checked.value;
            });
            if (proofFields) proofFields.hidden = checked.getAttribute('data-needs-proof') !== '1';
            submitBtns.forEach(function (btn) {
                var label = $('[data-submit-label]', btn);
                if (label) label.textContent = btn.getAttribute(checked.value === 'stripe' ? 'data-submit-label-card' : 'data-submit-label-default');
            });
        };
        $$('[data-payment-method]', checkout).forEach(function (r) { r.addEventListener('change', updatePayment); });

        // Coupon code: apply or remove without reloading the page.
        var couponBox = $('[data-coupon]', checkout);
        if (couponBox) {
            var entry = $('[data-coupon-entry]', couponBox);
            var applied = $('[data-coupon-applied]', couponBox);
            var couponForm = $('[data-coupon-form]', couponBox);
            var couponInput = $('#coupon_code', couponBox);
            var couponError = $('[data-coupon-error]', couponBox);
            var toggle = $('[data-coupon-toggle]', couponBox);
            var sendCoupon = function (fields) {
                var body = new FormData();
                body.append('_token', $('input[name="_token"]', checkout).value);
                Object.keys(fields).forEach(function (k) { body.append(k, fields[k]); });
                return fetch(couponBox.getAttribute('data-coupon-url'), { method: 'POST', body: body, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); });
            };
            toggle.addEventListener('click', function () {
                couponForm.hidden = !couponForm.hidden;
                toggle.setAttribute('aria-expanded', couponForm.hidden ? 'false' : 'true');
                if (!couponForm.hidden) couponInput.focus();
            });
            couponInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); $('[data-coupon-apply]', couponBox).click(); }
            });
            $('[data-coupon-apply]', couponBox).addEventListener('click', function (e) {
                e.preventDefault();
                var btn = e.currentTarget;
                if (!couponInput.value.trim()) { couponInput.focus(); return; }
                btn.disabled = true;
                sendCoupon({ action: 'apply', code: couponInput.value }).then(function (data) {
                    btn.disabled = false;
                    if (!data.ok) {
                        couponError.textContent = data.message;
                        couponError.hidden = false;
                        return;
                    }
                    couponError.hidden = true;
                    $('[data-coupon-code]', applied).textContent = data.code;
                    $('[data-coupon-label]', applied).textContent = data.label;
                    totalEl.setAttribute('data-discount', data.discount);
                    applied.hidden = false;
                    entry.hidden = true;
                    updateTotals();
                }).catch(function () {
                    btn.disabled = false;
                    couponError.textContent = 'We could not check the code. Please try again.';
                    couponError.hidden = false;
                });
            });
            $('[data-coupon-remove]', couponBox).addEventListener('click', function (e) {
                e.preventDefault();
                sendCoupon({ action: 'remove' }).then(function () {
                    totalEl.setAttribute('data-discount', '0');
                    applied.hidden = true;
                    entry.hidden = false;
                    couponInput.value = '';
                    updateTotals();
                }).catch(function () {});
            });
        }

        // Optional account creation
        var createToggle = $('[data-create-account]', checkout);
        var passwordField = $('[data-account-password]', checkout);
        if (createToggle && passwordField) {
            createToggle.addEventListener('change', function () {
                passwordField.hidden = !createToggle.checked;
                if (createToggle.checked) $('input', passwordField).focus();
            });
        }

        // ---- What is missing: checked before the form is sent, listed at the top and under each field ----
        var errorBox = $('[data-form-errors]', checkout);
        var errorList = $('[data-form-errors-list]', checkout);
        var errorTitle = $('[data-form-errors-title]', checkout);
        var field = function (id) { return document.getElementById(id); };
        var value = function (id) { var el = field(id); return el ? el.value.trim() : ''; };
        var isDelivery = function () {
            var c = $('[data-fulfilment]:checked', checkout) || $('input[name="fulfilment"]', checkout);
            return !!c && c.value === 'delivery';
        };
        var auPhone = function (v) {
            var d = v.replace(/\D+/g, '');
            if (d.indexOf('61') === 0) d = '0' + d.slice(2);
            return /^0[2-478]\d{8}$/.test(d);
        };
        var method = function () { return $('[data-payment-method]:checked', checkout); };
        // key = the server's error name, target = where the list link jumps to.
        var rules = [
            { key: 'name', target: 'name', label: 'Full name', msg: 'Please enter your name.', ok: function () { return value('name') !== ''; } },
            { key: 'email', target: 'email', label: 'Email', ok: function () { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value('email')); },
              msg: function () { return value('email') === '' ? 'Please enter your email address.' : 'This email does not look right, e.g. name@example.com.'; } },
            { key: 'phone', target: 'phone', label: 'Mobile number', ok: function () { return auPhone(value('phone')); },
              msg: function () { return value('phone') === '' ? 'Please enter your mobile number.' : 'Please enter an Australian number, e.g. 0412 345 678.'; } },
            { key: 'account_password', target: 'account_password', label: 'Password',
              when: function () { var c = $('[data-create-account]', checkout); return !!c && c.checked; },
              ok: function () { var el = field('account_password'); return el.value.length >= (parseInt(el.getAttribute('minlength'), 10) || 8); },
              msg: function () { return 'Please choose a password of at least ' + (field('account_password').getAttribute('minlength') || 8) + ' characters.'; } },
            { key: 'fulfilment', target: 'fulfilment-options', label: 'Pickup or delivery', msg: 'Please choose pickup or delivery.',
              when: function () { return !!$('[data-fulfilment]', checkout); }, ok: function () { return !!$('[data-fulfilment]:checked', checkout); } },
            { key: 'pickup_time', target: 'pickup_time', label: 'Pickup date and time',
              when: function () { return !!field('pickup_time') && !isDelivery(); },
              ok: function () { return value('pickup_date') !== '' && value('pickup_time') !== ''; },
              msg: function () { return value('pickup_date') === '' ? 'Please choose a pickup date.' : 'Please choose a pickup time. If no times are listed, try another date.'; } },
            { key: 'delivery_date', target: 'delivery_date', label: 'Delivery date', msg: 'Please choose a delivery date.',
              when: function () { return !!field('delivery_date') && isDelivery(); }, ok: function () { return value('delivery_date') !== ''; } },
            { key: 'delivery_address', target: 'delivery_address', label: 'Street address', msg: 'Please enter the street address.',
              when: function () { return !!field('delivery_address') && isDelivery(); }, ok: function () { return value('delivery_address') !== ''; } },
            { key: 'delivery_suburb', target: 'delivery_suburb', label: 'Suburb', msg: 'Please enter the suburb.',
              when: function () { return !!field('delivery_suburb') && isDelivery(); }, ok: function () { return value('delivery_suburb') !== ''; } },
            { key: 'delivery_postcode', target: 'delivery_postcode', label: 'Postcode', msg: 'Please enter the 4 digit postcode.',
              when: function () { return !!field('delivery_postcode') && isDelivery(); }, ok: function () { return /^\d{4}$/.test(value('delivery_postcode')); } },
            { key: 'payment_method', target: 'payment-options', label: 'Payment method', msg: 'Please choose how you would like to pay.',
              when: function () { return !!$('[data-payment-method]', checkout); }, ok: function () { return !!method(); } },
            { key: 'payment_proof', target: 'payment_proof', label: 'Payment screenshot', msg: 'Please upload a screenshot of your payment.',
              when: function () { var m = method(); return !!m && m.getAttribute('data-needs-proof') === '1' && !!field('payment_proof'); },
              ok: function () { var t = $('input[name="payment_proof_token"]', checkout); var f = field('payment_proof'); return (t && t.value !== '') || (f.files && f.files.length > 0); } },
            { key: 'agree', target: 'agree', label: 'Confirm your order', msg: 'Please tick the box to confirm your order details are correct.',
              when: function () { return !!field('agree'); }, ok: function () { return field('agree').checked; } }
        ];
        var labels = {};
        rules.forEach(function (r) { labels[r.key] = r; });

        // Errors the server found that the browser cannot check (postcode outside the area, a date
        // that just filled up...). Each one stays until its field is changed.
        var serverErrors = {};
        if (errorList) $$('li[data-key]', errorList).forEach(function (li) {
            var a = $('a', li);
            serverErrors[li.getAttribute('data-key')] = { target: a.getAttribute('data-target'), label: $('strong', a).textContent, msg: $('span', a).textContent };
        });
        var attempted = Object.keys(serverErrors).length > 0;

        var inlineError = function (key, target) {
            var el = document.getElementById(key + '-error');
            if (el) return el;
            var anchor = field(target);
            if (!anchor) return null;
            el = document.createElement('p');
            el.className = 'field-error';
            el.id = key + '-error';
            el.hidden = true;
            var box = anchor.closest('.field') || anchor.closest('.check') || anchor;
            if (anchor.id === 'pickup_time' && anchor.closest('.field-row')) box = anchor.closest('.field-row');
            box.parentNode.insertBefore(el, box.nextSibling);
            if (box.classList.contains('field')) box.appendChild(el);
            return el;
        };
        var markField = function (target, key, bad) {
            var el = field(target);
            if (!el) return;
            if (bad) { el.setAttribute('aria-invalid', 'true'); el.setAttribute('aria-describedby', key + '-error'); }
            else if (el.getAttribute('aria-invalid')) { el.removeAttribute('aria-invalid'); }
            var card = el.closest('.file-drop') || (el.classList.contains('option-cards') ? el : null);
            if (card) card.classList.toggle('is-invalid', bad);
        };

        var collect = function () {
            var found = {};
            rules.forEach(function (r) {
                if (r.when && !r.when()) return;
                if (!r.ok()) found[r.key] = { target: r.target, label: r.label, msg: typeof r.msg === 'function' ? r.msg() : r.msg };
            });
            Object.keys(serverErrors).forEach(function (k) { if (!found[k]) found[k] = serverErrors[k]; });
            return found;
        };

        var showErrors = function (found) {
            var keys = Object.keys(found);
            rules.concat(Object.keys(serverErrors).map(function (k) { return { key: k, target: serverErrors[k].target }; })).forEach(function (r) {
                var bad = !!found[r.key];
                var el = bad ? inlineError(r.key, r.target) : document.getElementById(r.key + '-error');
                if (el && el.hasAttribute('data-upload-error')) el = null;
                if (el) { el.hidden = !bad; if (bad) el.textContent = found[r.key].msg; }
                markField(r.target, r.key, bad);
            });
            $$('[data-step]', checkout).forEach(function (step) {
                step.classList.toggle('has-error', keys.some(function (k) { var t = field(found[k].target); return t && step.contains(t); }));
            });
            if (!errorBox) return;
            // Only rebuild the list when it changed, so a tap on a link is never lost mid-click.
            var signature = JSON.stringify(found);
            if (signature === errorBox.getAttribute('data-signature')) return;
            errorBox.setAttribute('data-signature', signature);
            errorBox.hidden = keys.length === 0;
            errorTitle.textContent = keys.length === 1 ? '1 thing needs your attention' : keys.length + ' things need your attention';
            errorList.innerHTML = '';
            keys.forEach(function (k) {
                var li = document.createElement('li');
                li.setAttribute('data-key', k);
                var a = document.createElement('a');
                a.href = '#' + found[k].target;
                a.setAttribute('data-target', found[k].target);
                var strong = document.createElement('strong');
                strong.textContent = found[k].label;
                var span = document.createElement('span');
                span.textContent = found[k].msg;
                a.appendChild(strong);
                a.appendChild(span);
                li.appendChild(a);
                errorList.appendChild(li);
            });
        };

        // A tick on each step once everything in it is filled in.
        var markSteps = function () {
            var found = {};
            rules.forEach(function (r) { if ((!r.when || r.when()) && !r.ok()) found[r.target] = true; });
            $$('[data-step]', checkout).forEach(function (step) {
                var bad = rules.some(function (r) { var t = field(r.target); return found[r.target] && t && step.contains(t); });
                var hasRule = rules.some(function (r) { var t = field(r.target); return (!r.when || r.when()) && t && step.contains(t); });
                step.classList.toggle('is-done', hasRule && !bad);
            });
        };

        var goTo = function (target) {
            var el = field(target);
            if (!el) return;
            if (target === 'coupon_code') {
                var form = $('[data-coupon-form]', checkout);
                if (form) form.hidden = false;
            }
            var focusEl = el.matches('input, select, textarea') ? el : $('input:not([type=hidden]), select, textarea', el) || el;
            var box = el.closest('.field') || el.closest('.check') || el;
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(function () { try { focusEl.focus({ preventScroll: true }); } catch (e) { focusEl.focus(); } }, 350);
        };
        if (errorList) errorList.addEventListener('click', function (e) {
            var a = e.target.closest('a[data-target]');
            if (!a) return;
            e.preventDefault();
            goTo(a.getAttribute('data-target'));
        });

        var refresh = function (e) {
            if (e && e.target && e.target.name) {
                Object.keys(serverErrors).forEach(function (k) {
                    var t = serverErrors[k].target;
                    var el = field(t);
                    if (k === e.target.name || t === e.target.id || (el && el.contains(e.target)) || (k === 'pickup_time' && e.target.id === 'pickup_date')) delete serverErrors[k];
                });
            }
            markSteps();
            if (attempted) showErrors(collect());
        };
        checkout.addEventListener('input', refresh);
        checkout.addEventListener('change', refresh);
        markSteps();
        if (attempted) showErrors(collect());

        checkout.addEventListener('submit', function (e) {
            var submitter = e.submitter;
            if (submitter && submitter.name === 'coupon_action') return;
            var found = collect();
            if (Object.keys(found).length) {
                e.preventDefault();
                attempted = true;
                showErrors(found);
                if (errorBox) {
                    errorBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    try { errorBox.focus({ preventScroll: true }); } catch (err) { errorBox.focus(); }
                    errorBox.classList.remove('is-shaking');
                    void errorBox.offsetWidth;
                    errorBox.classList.add('is-shaking');
                }
                return;
            }
            submitBtns.forEach(function (btn) {
                btn.disabled = true;
                var label = $('[data-submit-label]', btn);
                if (label) label.textContent = 'Please wait...';
            });
        });

        // Phones: a sticky bar with the total and the button, until the real button scrolls into view.
        var bar = $('[data-checkout-bar]', checkout);
        var mainBtn = $('[data-submit-main]', checkout);
        if (bar && mainBtn && 'IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                var r = entries[0];
                bar.hidden = r.isIntersecting || r.boundingClientRect.top < 0;
                document.body.classList.toggle('has-checkout-bar', !bar.hidden);
            }).observe(mainBtn);
        }
    }
})();

/* Custom cake quote: show the amount for the chosen deposit or full price. */
(function () {
    var choices = document.querySelectorAll('[data-quote-amount]');
    if (!choices.length) return;
    var update = function () {
        var picked = document.querySelector('[data-quote-amount]:checked');
        if (!picked) return;
        document.querySelectorAll('[data-pay-amount]').forEach(function (el) { el.textContent = picked.getAttribute('data-quote-amount'); });
    };
    choices.forEach(function (c) { c.addEventListener('change', update); });
})();

/* Product photo gallery: swipe or use the arrows and thumbnails; the magnifier opens a large view. */
(function () {
    var gallery = document.querySelector('[data-gallery]');
    if (!gallery) return;
    var slides = gallery.querySelector('[data-gallery-slides]');
    if (!slides) return;
    var images = Array.prototype.slice.call(slides.querySelectorAll('img'));
    var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('[data-gallery-thumb]'));
    var indexEl = gallery.querySelector('[data-gallery-index]');
    var current = 0;
    var mark = function (i) {
        current = i;
        thumbs.forEach(function (t, k) { t.classList.toggle('is-active', k === i); });
        if (thumbs[i] && thumbs[i].scrollIntoView && thumbs.length > 4) thumbs[i].scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        if (indexEl) indexEl.textContent = i + 1;
    };
    var go = function (i) {
        i = (i + images.length) % images.length;
        slides.scrollTo({ left: i * slides.clientWidth, behavior: 'smooth' });
        mark(i);
    };
    slides.addEventListener('scroll', function () {
        var i = Math.round(slides.scrollLeft / Math.max(1, slides.clientWidth));
        if (i !== current) mark(i);
    }, { passive: true });
    thumbs.forEach(function (t, k) { t.addEventListener('click', function () { go(k); }); });
    var prev = gallery.querySelector('[data-gallery-prev]');
    var next = gallery.querySelector('[data-gallery-next]');
    if (prev) prev.addEventListener('click', function () { go(current - 1); });
    if (next) next.addEventListener('click', function () { go(current + 1); });

    // Large view
    var box = gallery.querySelector('[data-lightbox]');
    var zoom = gallery.querySelector('[data-gallery-zoom]');
    if (!box || !zoom || typeof box.showModal !== 'function') { if (zoom) zoom.hidden = true; return; }
    var big = box.querySelector('[data-lightbox-img]');
    var shown = 0;
    var showBig = function (i) {
        shown = (i + images.length) % images.length;
        big.src = images[shown].currentSrc || images[shown].src;
        big.alt = images[shown].alt;
    };
    var open = function () { showBig(current); box.showModal(); document.documentElement.style.overflow = 'hidden'; };
    zoom.addEventListener('click', open);
    images.forEach(function (img) { img.addEventListener('dblclick', open); });
    box.addEventListener('close', function () { document.documentElement.style.overflow = ''; go(shown); });
    box.querySelector('[data-lightbox-close]').addEventListener('click', function () { box.close(); });
    box.addEventListener('click', function (e) { if (e.target === box) box.close(); });
    var lp = box.querySelector('[data-lightbox-prev]'), ln = box.querySelector('[data-lightbox-next]');
    if (lp) lp.addEventListener('click', function () { showBig(shown - 1); });
    if (ln) ln.addEventListener('click', function () { showBig(shown + 1); });
    box.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') showBig(shown - 1);
        if (e.key === 'ArrowRight') showBig(shown + 1);
    });
    // Swipe in the large view
    var startX = null;
    box.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', function (e) {
        if (startX === null) return;
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) showBig(shown + (dx < 0 ? 1 : -1));
        startX = null;
    }, { passive: true });
})();

/* Phones: a bar with the price and "Add to Cart" while the real button is off screen. */
(function () {
    var bar = document.querySelector('[data-buy-bar]');
    var main = document.querySelector('[data-buy-main]');
    var form = document.querySelector('[data-product-form]');
    if (!bar || !main || !form || !('IntersectionObserver' in window)) return;
    var mq = window.matchMedia('(max-width: 899px)');
    var visible = true;
    var update = function () {
        bar.hidden = !mq.matches || visible;
        document.body.classList.toggle('has-buy-bar', !bar.hidden);
    };
    new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; update(); }).observe(main);
    mq.addEventListener('change', update);
    bar.querySelector('[data-buy-bar-btn]').addEventListener('click', function () {
        if (form.requestSubmit) form.requestSubmit(main); else main.click();
    });
})();

/* Conversation with the shop: replies appear on their own and messages send without reloading. */
(function () {
    var list = document.querySelector('[data-chat]');
    if (!list || !window.fetch) return;
    var form = document.querySelector('[data-chat-form]');
    var url = list.getAttribute('data-chat-url');
    var lastId = parseInt(list.getAttribute('data-last-id'), 10) || 0;
    var timer, busy = false;
    var append = function (messages) {
        var added = null;
        messages.forEach(function (m) {
            if (m.id <= lastId || list.querySelector('[data-msg-id="' + m.id + '"]')) return;
            var empty = list.querySelector('.chat__empty');
            if (empty) empty.remove();
            list.insertAdjacentHTML('beforeend', m.html);
            lastId = Math.max(lastId, m.id);
            added = list.lastElementChild;
            if (m.html.indexOf('chat__msg--shop') !== -1 && window.CCSSound) window.CCSSound.play('message');
        });
        if (added) added.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };
    var schedule = function () { clearTimeout(timer); timer = setTimeout(poll, document.hidden ? 30000 : 5000); };
    var poll = function () {
        if (busy) return schedule();
        busy = true;
        fetch(url + (url.indexOf('?') < 0 ? '?' : '&') + 'poll=1&after=' + lastId, { credentials: 'same-origin', cache: 'no-store', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) { append(d.messages || []); })
            .catch(function () {})
            .then(function () { busy = false; schedule(); });
    };
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
    schedule();

    if (!form) return;
    var error = form.querySelector('[data-chat-error]');
    var textarea = form.querySelector('textarea');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        error.hidden = true;
        fetch(form.getAttribute('action') || location.href, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { error.textContent = d.error || 'Your message could not be sent.'; error.hidden = false; return; }
                textarea.value = '';
                if (window.CCSSound) window.CCSSound.play('sent');
                poll();
            })
            .catch(function () { error.textContent = 'Could not send. Please check your connection and try again.'; error.hidden = false; })
            .then(function () { button.disabled = false; });
    });
})();

/* Round categories: glide past like a news ticker. Hover, focus or a touch pauses it, and
   people can still swipe or scroll it by hand. Without JavaScript it is a normal scroll row. */
(function () {
    document.querySelectorAll('[data-ticker]').forEach(function (ticker) {
        var viewport = ticker.querySelector('[data-ticker-viewport]');
        var track = ticker.querySelector('[data-ticker-track]');
        var first = track && track.firstElementChild;
        if (!viewport || !first) return;
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var speed = parseFloat(getComputedStyle(ticker).getPropertyValue('--ticker-speed')) || 34; // pixels a second
        var pos = 0, last = 0, paused = false, resumeTimer, running = false;
        var loopWidth = function () { return first.offsetWidth; };
        var fits = function () { return loopWidth() <= viewport.clientWidth; };
        var setup = function () {
            // Not enough categories to fill the row (or motion is off): just show them once.
            var still = reduce || fits();
            ticker.classList.toggle('is-static', still);
            if (!still && !running) { running = true; last = 0; requestAnimationFrame(step); }
        };
        var step = function (t) {
            if (ticker.classList.contains('is-static')) { running = false; return; }
            if (!last) last = t;
            var dt = Math.min(64, t - last);
            last = t;
            if (!paused && !document.hidden) {
                pos += speed * dt / 1000;
                var w = loopWidth();
                if (pos >= w) pos -= w;
                viewport.scrollLeft = pos;
            }
            requestAnimationFrame(step);
        };
        var pause = function () { paused = true; clearTimeout(resumeTimer); };
        var resume = function (delay) {
            clearTimeout(resumeTimer);
            resumeTimer = setTimeout(function () { pos = viewport.scrollLeft % loopWidth(); paused = false; }, delay || 0);
        };
        ticker.addEventListener('mouseenter', pause);
        ticker.addEventListener('mouseleave', function () { resume(150); });
        ticker.addEventListener('focusin', pause);
        ticker.addEventListener('focusout', function () { resume(300); });
        viewport.addEventListener('touchstart', pause, { passive: true });
        viewport.addEventListener('touchend', function () { resume(2500); }, { passive: true });
        viewport.addEventListener('wheel', function () { pause(); resume(2500); }, { passive: true });
        window.addEventListener('resize', setup);
        if (document.readyState === 'complete') setup(); else window.addEventListener('load', setup);
    });
})();

/* Product cards with more than one photo: moving the mouse across the photo shows the others
   (left to right), and on phones the photos swipe. The dots follow along. */
(function () {
    document.querySelectorAll('[data-card-photos]').forEach(function (media) {
        var slides = media.querySelector('.product-card__slides');
        var dots = media.querySelectorAll('.product-card__dots i');
        var count = slides ? slides.children.length : 0;
        if (count < 2) return;
        var current = 0;
        var show = function (i) {
            if (i === current) return;
            current = i;
            slides.scrollTo({ left: i * slides.clientWidth, behavior: 'auto' });
            dots.forEach(function (d, k) { d.classList.toggle('is-on', k === i); });
        };
        media.addEventListener('pointermove', function (e) {
            if (e.pointerType !== 'mouse') return;
            var r = media.getBoundingClientRect();
            show(Math.min(count - 1, Math.max(0, Math.floor((e.clientX - r.left) / r.width * count))));
        });
        media.addEventListener('mouseleave', function () { show(0); });
        slides.addEventListener('scroll', function () {
            var i = Math.round(slides.scrollLeft / Math.max(1, slides.clientWidth));
            if (i !== current) { current = i; dots.forEach(function (d, k) { d.classList.toggle('is-on', k === i); }); }
        }, { passive: true });
    });
})();
