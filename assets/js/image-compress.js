/**
 * Kompresi gambar client-side (Canvas murni, tanpa library).
 * compressImage(file, { maxSize: 450*1024, maxDimension: 1600 }) -> Promise<File>
 */
(function (global) {
    function loadBitmap(file) {
        if (global.createImageBitmap) {
            // imageOrientation 'from-image' menerapkan rotasi EXIF dari kamera HP
            return createImageBitmap(file, { imageOrientation: 'from-image' });
        }
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Gambar tidak dapat dibaca.')); };
            img.src = url;
        });
    }

    function toBlob(canvas, quality) {
        return new Promise((resolve, reject) => {
            canvas.toBlob(b => b ? resolve(b) : reject(new Error('Gagal mengompres gambar.')), 'image/jpeg', quality);
        });
    }

    async function compressImage(file, opts = {}) {
        const maxSize = opts.maxSize || 450 * 1024;
        const maxDim = opts.maxDimension || 1600;

        if (!file.type.startsWith('image/')) throw new Error('File bukan gambar.');
        if (file.size <= maxSize && file.type === 'image/jpeg') return file;

        const bmp = await loadBitmap(file);
        const w0 = bmp.width, h0 = bmp.height;
        let scale = Math.min(1, maxDim / Math.max(w0, h0));

        const canvas = document.createElement('canvas');
        let blob = null;

        // Turunkan kualitas dulu; jika masih besar, perkecil dimensi.
        for (let attempt = 0; attempt < 4; attempt++) {
            canvas.width = Math.round(w0 * scale);
            canvas.height = Math.round(h0 * scale);
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff'; // PNG transparan -> latar putih
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(bmp, 0, 0, canvas.width, canvas.height);

            let q = 0.85;
            blob = await toBlob(canvas, q);
            while (blob.size > maxSize && q > 0.5) {
                q -= 0.1;
                blob = await toBlob(canvas, q);
            }
            if (blob.size <= maxSize) break;
            scale *= 0.8;
        }
        if (bmp.close) bmp.close();

        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
        return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
    }

    global.compressImage = compressImage;
})(window);
