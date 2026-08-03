document.addEventListener("DOMContentLoaded", function() {
    console.log("Patroli Scan System v4.0 - Anti-Fraud & Selfie Enabled");

    // ==========================================
    // 1. VARIABEL & KONFIGURASI
    // ==========================================
    let html5QrCode = null;
    let cameras = [];
    let currentCameraIdx = 0;
    let isScanning = false;
    let isManualMode = false;

    const readerWrapper = document.getElementById('reader-wrapper');
    const manualWrapper = document.getElementById('manual-input-wrapper');
    const btnToggle     = document.getElementById('btnToggleMode');
    const btnSubmit     = document.getElementById('btnSubmitManual');
    const btnSwitch     = document.getElementById('btnSwitchCam');
    const inputManual   = document.getElementById('inputKodeLokasi');
    const badge         = document.getElementById('status-badge');

    initCamera();

    // ==========================================
    // 2. LOGIKA SCANNER QR
    // ==========================================
    function initCamera() {
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                cameras = devices;
                currentCameraIdx = (cameras.length > 1) ? cameras.length - 1 : 0;
                startScanner(cameras[currentCameraIdx].id);
            } else {
                Swal.fire("Error", "Kamera tidak ditemukan.", "error");
            }
        }).catch(err => {
            Swal.fire("Izin Ditolak", "Mohon izinkan akses kamera.", "error");
        });
    }

    function startScanner(cameraId) {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().then(() => {
                isScanning = false;
                runScanner(cameraId);
            });
        } else {
            runScanner(cameraId);
        }
    }

    function runScanner(cameraId) {
        html5QrCode = new Html5Qrcode("reader");
        html5QrCode.start(cameraId, { fps: 15, qrbox: 250 }, (txt) => {
            handleAction(txt, false);
        }).then(() => { isScanning = true; });
    }

    // ==========================================
    // 3. LOGIKA GPS & ANTI-FAKE GPS
    // ==========================================
    function handleAction(content, isManual) {
        if(html5QrCode && !isManual) html5QrCode.pause();

        Swal.fire({
            title: 'Memverifikasi Lokasi...',
            text: 'Mencari koordinat paling akurat',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    // --- PROTEKSI 1: CEK AKURASI (ANTI FAKE GPS) ---
                    const accuracy = position.coords.accuracy;
                    if (accuracy <= 1) {
                        Swal.fire("Peringatan Keamanan", "Aplikasi manipulasi lokasi (Fake GPS) terdeteksi!", "error");
                        if(html5QrCode) html5QrCode.resume();
                        return;
                    }

                    const coord = position.coords.latitude + "," + position.coords.longitude;
                    verifyWithServer(content, coord, isManual);
                },
                (error) => {
                    Swal.fire("GPS Error", "Aktifkan GPS HP Anda!", "error");
                    if(html5QrCode) html5QrCode.resume();
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    }

    // ==========================================
    // 4. VERIFIKASI KE SERVER
    // ==========================================
    function verifyWithServer(qr, coord, manual) {
        const fd = new FormData();
        fd.append('act', 'scan');
        fd.append('qr_content', qr);
        fd.append('koordinat', coord);
        fd.append('is_manual', manual ? '1' : '0');

        fetch('proses_patroli.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'confirm_condition') {
                Swal.fire({
                    title: 'Verifikasi Berhasil',
                    html: `Lokasi: <b>${data.nama_lokasi}</b><br>Kondisi aman? Silakan ambil selfie.`,
                    icon: 'success',
                    confirmButtonText: '<i class="fas fa-camera"></i> Ambil Selfie',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // --- LANJUT KE PROSES SELFIE ---
                        triggerSelfie(data.checkpoint_id, coord);
                    } else {
                        if(html5QrCode) html5QrCode.resume();
                    }
                });
            } else {
                Swal.fire("Gagal", data.message, "error").then(() => {
                    if(html5QrCode) html5QrCode.resume();
                });
            }
        });
    }

// ==========================================
// 5. FITUR WAJIB SELFIE (LIVE CAMERA - FIXED)
// ==========================================
function triggerSelfie(cp_id, coord) {
    let localStream = null;

    Swal.fire({
        title: 'Selfie Kehadiran',
        html: `
            <div style="position: relative; background: #000; border-radius: 15px; overflow: hidden; line-height: 0;">
                <video id="vSelfie" width="100%" height="auto" autoplay playsinline 
                       style="transform: scaleX(-1); border: 2px solid #6366f1; border-radius: 15px;"></video>
                <canvas id="cSelfie" class="d-none"></canvas>
            </div>
            <p class="small text-muted mt-2">Posisikan wajah di tengah area kamera.</p>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-camera"></i> Jepret & Simpan',
        cancelButtonText: 'Batal',
        allowOutsideClick: false,
        didOpen: () => {
            const video = document.getElementById('vSelfie');
            
            // Minta akses kamera depan (user)
            navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: "user",
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }, 
                audio: false 
            })
            .then(stream => {
                localStream = stream;
                video.srcObject = stream;
                // Paksa putar video
                video.onloadedmetadata = () => { video.play(); };
            })
            .catch(err => {
                console.error("Kamera Error: ", err);
                Swal.showValidationMessage(`Gagal akses kamera: ${err.name}. Pastikan koneksi HTTPS!`);
            });
        },
        willClose: () => {
            // Matikan kamera saat modal ditutup (Batal atau Berhasil)
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
        },
        preConfirm: () => {
            const video = document.getElementById('vSelfie');
            const canvas = document.getElementById('cSelfie');
            
            if (!video.srcObject) {
                Swal.showValidationMessage('Kamera belum siap!');
                return false;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            
            // Efek Mirror untuk hasil foto (biar sama dengan tampilan di layar)
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            return canvas.toDataURL('image/jpeg', 0.7); // Kompres kualitas 70% agar upload cepat
        }
    }).then((res) => {
        if (res.isConfirmed) {
            saveFinalReport(cp_id, coord, res.value);
        } else {
            // Jika batal, nyalakan lagi scanner QR
            if(html5QrCode) html5QrCode.resume();
        }
    });
}

    function saveFinalReport(id, coord, imgBase64) {
        Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });

        const fd = new FormData();
        fd.append('act', 'simpan_aman');
        fd.append('checkpoint_id', id);
        fd.append('koordinat', coord);
        fd.append('foto_selfie', imgBase64);

        fetch('proses_patroli.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(d => {
            if(d.status === 'success') {
                Swal.fire('Berhasil!', 'Patroli tersimpan dengan foto selfie.', 'success')
                .then(() => location.reload());
            } else {
                Swal.fire('Gagal', d.message, 'error');
            }
        });
    }

    // --- Switch & Toggle Event Listeners ---
    if(btnSwitch) btnSwitch.addEventListener('click', () => {
        currentCameraIdx = (currentCameraIdx + 1) % cameras.length;
        startScanner(cameras[currentCameraIdx].id);
    });

    if(btnToggle) btnToggle.addEventListener('click', () => {
        isManualMode = !isManualMode;
        readerWrapper.classList.toggle('d-none');
        manualWrapper.classList.toggle('d-none');
        if(isManualMode && html5QrCode) html5QrCode.pause();
        else if(html5QrCode) html5QrCode.resume();
    });

    if(btnSubmit) btnSubmit.addEventListener('click', () => {
        const kode = inputManual.value.trim().toUpperCase();
        if(kode) handleAction(kode, true);
    });
});