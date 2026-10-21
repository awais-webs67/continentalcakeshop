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

        checkout.addEventListener('submit', function () {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitLabel.textContent = 'Please wait...';
            }
        });
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

/* Product photo gallery: thumbnails swap the big photo. */
(function () {
    var gallery = document.querySelector('[data-gallery]');
    if (!gallery) return;
    var main = gallery.querySelector('[data-gallery-main]');
    gallery.addEventListener('click', function (e) {
        var thumb = e.target.closest('[data-gallery-thumb]');
        if (!thumb || !main) return;
        main.src = thumb.getAttribute('data-src');
        main.alt = thumb.getAttribute('data-alt');
        gallery.querySelectorAll('[data-gallery-thumb]').forEach(function (t) { t.classList.toggle('is-active', t === thumb); });
    });
})();
