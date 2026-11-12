/*
 * Photos of any size: before a photo is uploaded, the browser makes it smaller (longest side at
 * most 2400 px, WebP, or JPEG where the browser cannot make WebP). A 12 MB phone photo becomes a
 * few hundred KB, so uploads are fast, never pass the server's size limit, and the server then
 * makes its own WebP from it as before. PDFs, small photos and anything the browser cannot read
 * are sent as they are.
 *
 * It works on every <input type="file"> on the page: the first "change" is held back while the
 * photo is made smaller, then fired again with the smaller file, so every script that listens
 * for "change" gets the small photo. A form sent meanwhile waits until it is ready.
 * Other scripts can call window.shrinkImage(file) and get a Promise of the (smaller) file.
 */
(function () {
    'use strict';
    var MAX_SIDE = 2400;
    var KEEP_BELOW_BYTES = 900 * 1024; // smaller photos that fit MAX_SIDE are left as they are
    var QUALITY = 0.9;
    var TYPES = /^image\/(jpeg|png|webp)$/;

    var canReplaceFiles = (function () {
        try { return typeof DataTransfer === 'function' && !!new DataTransfer().items; } catch (e) { return false; }
    })();

    function decode(file) {
        if (window.createImageBitmap) {
            return createImageBitmap(file, { imageOrientation: 'from-image' }).catch(function () { return createImageBitmap(file); });
        }
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('unreadable')); };
            img.src = url;
        });
    }

    function encode(canvas, type) {
        return new Promise(function (resolve) { canvas.toBlob(resolve, type, QUALITY); });
    }

    function shrinkImage(file) {
        if (!file || !TYPES.test(file.type) || !window.Promise || !document.createElement('canvas').toBlob) {
            return Promise.resolve(file);
        }
        return decode(file).then(function (image) {
            var w = image.width, h = image.height;
            if (!w || !h) return file;
            var scale = Math.min(1, MAX_SIDE / Math.max(w, h));
            if (scale === 1 && file.size <= KEEP_BELOW_BYTES) return file;
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(w * scale);
            canvas.height = Math.round(h * scale);
            var ctx = canvas.getContext('2d');
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
            if (image.close) image.close();
            return encode(canvas, 'image/webp').then(function (blob) {
                if (blob && blob.type === 'image/webp') return blob;
                // No WebP in this browser: JPEG, except for PNGs (they may be see-through).
                return file.type === 'image/png' ? null : encode(canvas, 'image/jpeg');
            }).then(function (blob) {
                if (!blob || (blob.size >= file.size && scale === 1)) return file;
                var ext = blob.type === 'image/webp' ? '.webp' : '.jpg';
                var name = (file.name || 'photo').replace(/\.[^.]+$/, '') + ext;
                try { return new File([blob], name, { type: blob.type, lastModified: Date.now() }); } catch (e) { return file; }
            });
        }).catch(function () { return file; });
    }
    window.shrinkImage = shrinkImage;

    if (!canReplaceFiles) return;

    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'file' || !input.files || !input.files.length) return;
        if (input._shrunk) { input._shrunk = false; return; } // our own second event: let it through
        var files = Array.prototype.slice.call(input.files);
        if (!files.some(function (f) { return TYPES.test(f.type); })) return;

        e.stopImmediatePropagation();
        var form = input.form;
        if (form) form._shrinking = (form._shrinking || 0) + 1;
        input.setAttribute('aria-busy', 'true');
        Promise.all(files.map(shrinkImage)).then(function (small) {
            var list = new DataTransfer();
            small.forEach(function (f) { list.items.add(f); });
            input.files = list.files;
        }).catch(function () { /* keep the original files */ }).then(function () {
            input.removeAttribute('aria-busy');
            input._shrunk = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            if (form) {
                form._shrinking -= 1;
                if (!form._shrinking && form._sendWhenReady) {
                    var submitter = form._sendWhenReady;
                    form._sendWhenReady = null;
                    if (form.requestSubmit) form.requestSubmit(submitter instanceof HTMLElement ? submitter : undefined);
                    else form.submit();
                }
            }
        });
    }, true);

    // A form sent while a photo is still being made smaller is sent once it is ready.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form && form._shrinking > 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            form._sendWhenReady = e.submitter || true;
        }
    }, true);
})();
