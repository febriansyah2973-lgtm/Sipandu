<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Styling ala Talenta */
    .talenta-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .btn-lembur-choice { 
        border-radius: 24px; border: 2px solid #f0f2f5; background: #fff;
        padding: 40px 20px; width: 100%; transition: all 0.3s;
    }
    .btn-lembur-choice:hover { transform: translateY(-8px); border-color: #ffc107; }
    
    /* Z-Index Fix: Memaksa Modal di atas Backdrop */
    .modal { z-index: 1060 !important; }
    .modal-backdrop { z-index: 1050 !important; }
    .select2-container { z-index: 1070 !important; } /* Select2 harus paling depan */
    .flatpickr-calendar { z-index: 1080 !important; }

    .select2-container--default .select2-selection--single {
        border: none !important; background-color: #f8f9fa !important;
        height: 45px !important; border-radius: 12px !important; padding: 8px !important;
    }
</style>

<div class="animate-fade-up px-2">
    <div class="mb-4">
        <h5 class="text-secondary mb-1">Halo, <strong><?= explode(' ', $_SESSION['nama_lengkap'] ?? 'User')[0] ?></strong> 👋</h5>
        <h3 class="fw-bold text-dark">Pengajuan Overtime</h3>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <button type="button" class="btn btn-lembur-choice shadow-sm" onclick="openLemburModal()">
                <div class="mb-3 text-warning"><i class="fas fa-moon fa-3x"></i></div>
                <h5 class="fw-bold mb-1">BUAT PENGAJUAN LEMBUR</h5>
                <p class="text-muted small mb-0">Input jadwal lembur (Format 24 Jam).</p>
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLembur" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold text-warning">Form Pengajuan Overtime</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_presensi.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="ajukan_lembur_delegate">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-secondary text-uppercase mb-2 d-block">Pilih Pemberi Persetujuan</label>
                            <select name="id_p_tujuan" id="select_delegasi" class="form-control" style="width: 100%" required>
                                <option value="">Cari nama delegasi...</option>
                                <?php
                                require_once 'config/database.php';
                                $my_role = strtolower($_SESSION['role'] ?? ''); 
                                $my_id   = $_SESSION['id_user'] ?? 0;

                                if (in_array($my_role, ['petugas', 'petugas lapangan', 'satpam'])) {
                                    $target = "'pengawas'";
                                } elseif ($my_role == 'pengawas') {
                                    $target = "'administrator', 'admin'";
                                } else {
                                    $target = "'administrator', 'admin'";
                                }

                                $sql = "SELECT id, nama_lengkap, role FROM users WHERE LOWER(role) IN ($target) AND status = 'Aktif' AND id != $my_id ORDER BY nama_lengkap ASC";
                                $stmtP = $pdo->query($sql);
                                while($p = $stmtP->fetch()) {
                                    echo "<option value='{$p['id']}'>{$p['nama_lengkap']} (" . ucfirst($p['role']) . ")</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="small fw-bold text-secondary text-uppercase">Tanggal Lembur</label>
                            <input type="date" name="tgl_lembur" class="form-control border-0 bg-light py-2" style="border-radius:12px;" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-6">
                            <label class="small fw-bold text-secondary text-uppercase">Jam Mulai</label>
                            <input type="text" name="jam_mulai" id="jam_mulai" class="form-control border-0 bg-light py-2 timepicker" placeholder="00:00" style="border-radius:12px;" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-secondary text-uppercase">Jam Selesai</label>
                            <input type="text" name="jam_selesai" id="jam_selesai" class="form-control border-0 bg-light py-2 timepicker" placeholder="00:00" style="border-radius:12px;" required>
                        </div>

                        <div class="col-12">
                            <label class="small fw-bold text-secondary text-uppercase">Deskripsi Tugas</label>
                            <textarea name="keterangan" class="form-control border-0 bg-light" rows="3" style="border-radius: 12px;" placeholder="Tuliskan tugas..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-warning text-white w-100 py-3 rounded-pill fw-bold shadow-sm">KIRIM PENGAJUAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Pindahkan modal ke body (Fix Backdrop)
    const modalEl = document.getElementById('modalLembur');
    document.body.appendChild(modalEl);

    // Inisialisasi Flatpickr: Mendukung Ketik Manual & 24 Jam
    $(".timepicker").flatpickr({
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,     // Memastikan format 24 Jam (Tanpa AM/PM)
        allowInput: true,    // INI KUNCINYA: Membolehkan personil ketik jam manual
        minuteIncrement: 1,
        disableMobile: "true" // Menonaktifkan keyboard native HP agar Flatpickr tetap muncul rapi
    });

    // Inisialisasi Select2 untuk Delegasi
    $('#select_delegasi').select2({
        dropdownParent: $('#modalLembur'),
        placeholder: "Cari nama pengawas...",
        allowClear: true
    });
});

    function openLemburModal() {
        var myModal = new bootstrap.Modal(document.getElementById('modalLembur'));
        myModal.show();
    }
</script>