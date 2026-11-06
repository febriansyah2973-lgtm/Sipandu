<?php
/**
 * FITUR PENGAJUAN IZIN & SAKIT - SIPANDU
 * Frontend (Tampilan)
 */
require_once 'config/database.php';
require_once 'config/csrf.php';
date_default_timezone_set('Asia/Jakarta');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$csrf_token = csrf_token_value();

$id_user = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? null;
$today = date('Y-m-d');
?>

<style>
    /* Style ala Talenta */
    .talenta-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .btn-izin-choice { 
        border-radius: 24px; border: 2px solid #f0f2f5; background: #fff;
        padding: 40px 20px; width: 100%; transition: all 0.3s;
    }
    .btn-izin-choice:hover { transform: translateY(-8px); border-color: #0d6efd; }
</style>

<div class="animate-fade-up px-2">
    <div class="mb-4">
        <h5 class="text-secondary mb-1">Halo, <strong><?= explode(' ', $_SESSION['nama_lengkap'] ?? 'User')[0] ?></strong> 👋</h5>
        <h3 class="fw-bold text-dark">Pengajuan Izin & Sakit</h3>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <button type="button" class="btn btn-izin-choice shadow-sm" onclick="openTalentaModal('Izin')">
                <div class="icon-circle bg-primary-subtle text-primary mb-3"><i class="fas fa-envelope-open-text fa-2x"></i></div>
                <h5 class="fw-bold mb-1">IZIN</h5>
                <p class="text-muted small mb-0">Klik untuk keperluan pribadi/keluarga</p>
            </button>
        </div>
        <div class="col-md-6">
            <button type="button" class="btn btn-izin-choice shadow-sm" onclick="openTalentaModal('Sakit')">
                <div class="icon-circle bg-danger-subtle text-danger mb-3"><i class="fas fa-briefcase-medical fa-2x"></i></div>
                <h5 class="fw-bold mb-1">SAKIT</h5>
                <p class="text-muted small mb-0">Klik untuk lapor sakit (wajib surat dokter)</p>
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTalenta" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold" id="titleTalenta">Form Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="proses_izin.php" method="POST" enctype="multipart/form-data" onsubmit="gabungkanAlasan()">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <input type="hidden" name="jenis_izin" id="val_status"> 
                    
                    <div class="mb-4 p-3 rounded-3 text-center fw-bold" id="banner_status"></div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">KATEGORI DETAIL</label>
                            <select id="kategori_detail" class="form-select border-0 bg-light py-2" style="border-radius: 12px;">
                                <option value="">-- Pilih Alasan --</option>
                                <option value="Keluarga">Urusan Keluarga</option>
                                <option value="Duka">Duka Cita</option>
                                <option value="Nikah">Pernikahan</option>
                                <option value="Medis">Sakit / Rawat Inap</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">MULAI TANGGAL</label>
                            <input type="date" name="tgl_mulai" id="tgl_mulai" class="form-control border-0 bg-light" value="<?= $today ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">SAMPAI TANGGAL</label>
                            <input type="date" name="tgl_selesai" id="tgl_selesai" class="form-control border-0 bg-light" value="<?= $today ?>" required>
                        </div>
                        
                        <div class="col-12 text-center my-2">
                            <div class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2" id="total_hari_display">Total: 1 Hari Kerja</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">DETAIL KETERANGAN</label>
                            <textarea id="keterangan_input" class="form-control border-0 bg-light" rows="3" style="border-radius: 12px;" placeholder="Jelaskan secara singkat..." required></textarea>
                            <input type="hidden" name="alasan" id="final_alasan">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">LAMPIRAN BUKTI (FOTO/PDF)</label>
                            <input type="file" name="lampiran" class="form-control border-0 bg-light">
                            <div class="form-text text-muted small">* Wajib untuk sakit (Surat Dokter)</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-paper-plane me-2"></i> KIRIM PENGAJUAN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Pindahkan modal ke body agar aman dari z-index
    document.addEventListener("DOMContentLoaded", function() {
        document.body.appendChild(document.getElementById('modalTalenta'));
        
        // Hitung Hari Otomatis
        const tgl1 = document.getElementById('tgl_mulai');
        const tgl2 = document.getElementById('tgl_selesai');
        
        function hitungHari() {
            const start = new Date(tgl1.value);
            const end = new Date(tgl2.value);
            
            if (end < start) {
                // Jangan alert terus menerus, cukup reset tgl2
                tgl2.value = tgl1.value;
                return;
            }
            
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('total_hari_display').innerText = "Total: " + diffDays + " Hari Kerja";
        }

        tgl1.addEventListener('change', hitungHari);
        tgl2.addEventListener('change', hitungHari);
    });

    function openTalentaModal(jenis) {
        document.getElementById('val_status').value = jenis;
        document.getElementById('titleTalenta').innerText = "Pengajuan " + jenis;
        
        const banner = document.getElementById('banner_status');
        if (jenis === 'Izin') {
            banner.className = "mb-4 p-3 rounded-3 bg-primary-subtle text-primary fw-bold";
        } else {
            banner.className = "mb-4 p-3 rounded-3 bg-danger-subtle text-danger fw-bold";
        }
        banner.innerText = "Anda mengajukan " + jenis.toUpperCase();
        
        new bootstrap.Modal(document.getElementById('modalTalenta')).show();
    }

    // Fungsi menggabungkan Dropdown Kategori + Textarea agar masuk ke database rapi
    function gabungkanAlasan() {
        const kat = document.getElementById('kategori_detail').value;
        const ket = document.getElementById('keterangan_input').value;
        // Hasil: "[Keluarga] Izin mengantar anak sunatan"
        document.getElementById('final_alasan').value = "[" + kat + "] " + ket;
    }
</script>