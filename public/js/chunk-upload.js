(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('chunk-upload');
        if (!root) return;

        const cfg = {
            urlStatus: root.dataset.urlStatus,
            urlChunk:  root.dataset.urlChunk,
            urlFinish: root.dataset.urlFinish,
            urlCancel: root.dataset.urlCancel,
            chunkSize: parseInt(root.dataset.chunkSize, 10),
            maxSize:   parseInt(root.dataset.maxSize, 10),
        };
        const MAX_RETRY = 5;
        const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png'];

        const picker      = document.getElementById('file_dokumen_picker');
        const hiddenToken = document.getElementById('uploaded_file');
        const hiddenName  = document.getElementById('uploaded_file_name');
        const wrap        = document.getElementById('upload-progress-wrapper');
        const bar         = document.getElementById('upload-progress-bar');
        const statusText  = document.getElementById('upload-status-text');
        const speedText   = document.getElementById('upload-speed-text');
        const btnPause    = document.getElementById('btn-upload-pause');
        const btnResume   = document.getElementById('btn-upload-resume');
        const btnCancel   = document.getElementById('btn-upload-cancel');
        const doneBox     = document.getElementById('upload-done');
        const doneName    = document.getElementById('upload-done-name');
        const btnRemove   = document.getElementById('btn-upload-remove');
        const form        = picker.closest('form');

        const csrf =
            (document.querySelector('meta[name="csrf-token"]') || {}).content ||
            (form.querySelector('input[name="_token"]') || {}).value;

        const state = {
            file: null, uploadId: null, totalChunks: 0,
            uploaded: new Set(), doneBytes: 0,
            sessionBytes: 0, sessionStart: 0,
            uploading: false, paused: false, xhr: null,
        };

        // ---------- helper ----------
        const show  = el => el.classList.remove('d-none');
        const hide  = el => el.classList.add('d-none');
        const sleep = ms => new Promise(r => setTimeout(r, ms));

        function formatBytes(b) {
            if (b < 1024) return b + ' B';
            if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
            return (b / 1048576).toFixed(1) + ' MB';
        }

        function formatTime(s) {
            if (!isFinite(s)) return '-';
            s = Math.round(s);
            const m = Math.floor(s / 60);
            return m > 0 ? `${m} mnt ${s % 60} dtk` : `${s} dtk`;
        }

        function chunkBytes(i) {
            return Math.min(cfg.chunkSize, state.file.size - i * cfg.chunkSize);
        }

        function storageKey(file) {
            return 'arsip-upload:' + file.name + ':' + file.size + ':' + file.lastModified;
        }

        function getUploadId(file) {
            let id = null;
            try { id = localStorage.getItem(storageKey(file)); } catch (e) {}
            if (!id) {
                id = (window.crypto && crypto.randomUUID)
                    ? crypto.randomUUID()
                    : Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
                try { localStorage.setItem(storageKey(file), id); } catch (e) {}
            }
            return id;
        }

        function forgetUploadId(file) {
            try { localStorage.removeItem(storageKey(file)); } catch (e) {}
        }

        function postForm(url, data) {
            const fd = new FormData();
            Object.keys(data).forEach(k => fd.append(k, data[k]));
            return fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: fd,
                credentials: 'same-origin',
            }).then(async r => {
                const json = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const err = new Error(json.message || ('HTTP ' + r.status));
                    err.status = r.status;
                    throw err;
                }
                return json;
            });
        }

        function setButtons() {
            if (state.uploading) {
                show(btnPause); hide(btnResume); show(btnCancel);
            } else if (state.paused) {
                hide(btnPause); show(btnResume); show(btnCancel);
            } else {
                hide(btnPause); hide(btnResume); hide(btnCancel);
            }
        }

        function setBarError(isError) {
            bar.classList.toggle('bg-danger', isError);
            bar.classList.toggle('progress-bar-animated', !isError);
        }

        function updateProgress(currentLoaded) {
            const done = state.doneBytes + currentLoaded;
            const pct  = state.file.size ? Math.min(100, Math.floor(done / state.file.size * 100)) : 0;
            bar.style.width = pct + '%';
            bar.textContent = pct + '%';
            statusText.textContent = `${formatBytes(done)} / ${formatBytes(state.file.size)}`;

            const elapsed = (Date.now() - state.sessionStart) / 1000;
            const sent    = state.sessionBytes + currentLoaded;
            if (elapsed > 1 && sent > 0) {
                const speed = sent / elapsed;
                speedText.textContent = `${formatBytes(speed)}/s • sisa ± ${formatTime((state.file.size - done) / speed)}`;
            }
        }

        // ---------- kirim 1 potongan ----------
        function sendChunk(index) {
            return new Promise((resolve, reject) => {
                const start = index * cfg.chunkSize;
                const blob  = state.file.slice(start, Math.min(start + cfg.chunkSize, state.file.size));

                const fd = new FormData();
                fd.append('upload_id', state.uploadId);
                fd.append('chunk_index', index);
                fd.append('total_chunks', state.totalChunks);
                fd.append('file_name', state.file.name);
                fd.append('file_size', state.file.size);
                fd.append('chunk', blob, 'chunk.part');

                const xhr = new XMLHttpRequest();
                state.xhr = xhr;
                xhr.open('POST', cfg.urlChunk);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.onprogress = e => { if (e.lengthComputable) updateProgress(e.loaded); };

                xhr.onload = () => {
                    state.xhr = null;
                    if (xhr.status >= 200 && xhr.status < 300) return resolve();
                    let msg = null;
                    try { msg = JSON.parse(xhr.responseText).message; } catch (e) {}
                    if (xhr.status === 413) msg = 'Potongan ditolak server (413). Naikkan batas upload server.';
                    if (xhr.status === 419) msg = 'Sesi login kedaluwarsa. Muat ulang halaman lalu pilih file yang sama.';
                    const err = new Error(msg || ('HTTP ' + xhr.status));
                    err.status = xhr.status;
                    reject(err);
                };
                xhr.onerror = () => { state.xhr = null; reject(new Error('Koneksi terputus')); };
                xhr.onabort = () => { state.xhr = null; const e = new Error('aborted'); e.aborted = true; reject(e); };

                xhr.send(fd);
            });
        }

        // ---------- proses utama ----------
        async function startUpload() {
            state.uploading = true;
            state.paused = false;
            setButtons();
            setBarError(false);
            show(wrap);

            try {
                statusText.textContent = 'Mengecek bagian yang sudah terupload...';
                speedText.textContent = '';

                const st = await postForm(cfg.urlStatus, { upload_id: state.uploadId });
                state.uploaded = new Set(st.uploaded_chunks || []);
                state.doneBytes = [...state.uploaded].reduce((sum, i) => sum + chunkBytes(i), 0);
                state.sessionBytes = 0;
                state.sessionStart = Date.now();
                updateProgress(0);

                for (let i = 0; i < state.totalChunks; i++) {
                    if (state.uploaded.has(i)) continue;
                    if (state.paused) return;

                    let attempt = 0;
                    while (true) {
                        try {
                            await sendChunk(i);
                            break;
                        } catch (err) {
                            if (err.aborted) return; // dijeda / dibatalkan
                            // Error 4xx (validasi, sesi habis) tidak perlu diulang
                            if (err.status && err.status < 500 && err.status !== 408 && err.status !== 429) throw err;

                            attempt++;
                            if (attempt > MAX_RETRY) {
                                throw new Error(`Gagal mengirim bagian ${i + 1} setelah ${MAX_RETRY} percobaan. Klik "Lanjutkan" untuk mencoba lagi.`);
                            }
                            const wait = Math.min(30, Math.pow(2, attempt));
                            statusText.textContent = `Koneksi bermasalah, coba lagi dalam ${wait} dtk (${attempt}/${MAX_RETRY})...`;
                            await sleep(wait * 1000);
                            if (state.paused) return;
                        }
                    }

                    state.uploaded.add(i);
                    state.doneBytes += chunkBytes(i);
                    state.sessionBytes += chunkBytes(i);
                    updateProgress(0);
                }

                statusText.textContent = 'Menyatukan file di server...';
                speedText.textContent = '';
                hide(btnPause);

                const res = await postForm(cfg.urlFinish, { upload_id: state.uploadId });

                forgetUploadId(state.file);
                hiddenToken.value = res.token;
                hiddenName.value  = state.file.name;
                doneName.textContent = state.file.name + ' (' + formatBytes(res.size) + ')';

                state.uploading = false;
                state.file = null;
                picker.value = '';
                hide(picker);
                hide(wrap);
                show(doneBox);
                picker.classList.remove('is-invalid');
            } catch (err) {
                state.uploading = false;
                state.paused = true;
                setBarError(true);
                statusText.textContent = 'Upload terhenti: ' + err.message;
                speedText.textContent = '';
                setButtons();
            }
        }

        // ---------- event ----------
        picker.addEventListener('change', function () {
            const file = picker.files[0];
            if (!file) return;

            if (state.xhr) state.xhr.abort();
            state.paused = true;

            const ext = file.name.split('.').pop().toLowerCase();
            if (!ALLOWED_EXT.includes(ext)) {
                alert('Format file harus PDF, JPG, JPEG, atau PNG.');
                picker.value = '';
                return;
            }
            if (file.size === 0) {
                alert('File kosong.');
                picker.value = '';
                return;
            }
            if (file.size > cfg.maxSize) {
                alert('Ukuran file maksimal ' + formatBytes(cfg.maxSize) + '.');
                picker.value = '';
                return;
            }

            state.file = file;
            state.uploadId = getUploadId(file);
            state.totalChunks = Math.ceil(file.size / cfg.chunkSize);
            hiddenToken.value = '';
            hiddenName.value = '';
            hide(doneBox);

            startUpload();
        });

        btnPause.addEventListener('click', function () {
            state.paused = true;
            state.uploading = false;
            if (state.xhr) state.xhr.abort();
            statusText.textContent = 'Dijeda. Klik "Lanjutkan" untuk meneruskan.';
            speedText.textContent = '';
            setButtons();
        });

        btnResume.addEventListener('click', function () {
            if (state.file) startUpload();
        });

        btnCancel.addEventListener('click', function () {
            if (!confirm('Batalkan upload file ini?')) return;
            state.paused = true;
            state.uploading = false;
            if (state.xhr) state.xhr.abort();
            if (state.uploadId) postForm(cfg.urlCancel, { upload_id: state.uploadId }).catch(() => {});
            if (state.file) forgetUploadId(state.file);
            state.file = null;
            picker.value = '';
            hide(wrap);
            setButtons();
        });

        btnRemove.addEventListener('click', function () {
            hiddenToken.value = '';
            hiddenName.value = '';
            hide(doneBox);
            picker.value = '';
            show(picker);
        });

        // Hapus file lama (halaman edit)
        const btnHapusLama = document.getElementById('btn-hapus-file-lama');
        if (btnHapusLama) {
            btnHapusLama.addEventListener('click', function () {
                if (!confirm('Apakah Anda yakin ingin menghapus file ini?')) return;
                document.getElementById('hapus_file').value = '1';
                document.getElementById('file-lama-wrapper').style.display = 'none';
            });
        }

        // Cegah simpan saat upload belum selesai
        form.addEventListener('submit', function (e) {
            if (state.file && !hiddenToken.value) {
                e.preventDefault();
                alert('Upload file belum selesai. Tunggu hingga 100% atau batalkan upload terlebih dahulu.');
            }
        });

        // Peringatan bila menutup tab saat upload berjalan
        window.addEventListener('beforeunload', function (e) {
            if (state.uploading) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });
})();