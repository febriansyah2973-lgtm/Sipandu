<link rel="stylesheet" href="assets/css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
require_once 'config/database.php';
$uid_skrg = $_SESSION['id_user'] ?? 0;

// 1. QUERY BARANG (Status: Dipinjam)
$q_barang = "SELECT pb.*, b.nama_barang, u.nama_lengkap 
             FROM pinjam_barang pb 
             JOIN barang b ON pb.barang_id = b.id 
             JOIN users u ON pb.user_id = u.id 
             WHERE pb.status = 'Dipinjam'";
$list_barang = $pdo->query($q_barang)->fetchAll();

// 2. QUERY KENDARAAN (Status: Dipakai)
$q_mobil = "SELECT pk.*, k.merk_model, k.no_polisi, u.nama_lengkap 
            FROM pinjam_kendaraan pk 
            JOIN kendaraan k ON pk.kendaraan_id = k.id 
            JOIN users u ON pk.user_id = u.id 
            WHERE pk.status = 'Dipakai'";
$list_mobil = $pdo->query($q_mobil)->fetchAll();
?>

<div class="container-fluid animate-fade-up">
    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-1">Serah Terima Shift</h3>
        <p class="text-secondary small">Integrasi aset kendaraan & barang inventaris secara real-time.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
        <div class="mb-4">
            <i class="fas fa-exchange-alt fa-5x text-primary opacity-25"></i>
        </div>
        <h5 class="fw-bold">Laporan Pergantian Tugas</h5>
        <p class="text-muted small mb-4">Pastikan Anda memverifikasi aset yang sedang dipinjam sebelum menyerahkan shift.</p>
        <button class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalHandover">
            <i class="fas fa-file-signature me-2"></i> BUAT LAPORAN SERAH TERIMA
        </button>
    </div>
</div>

<div class="modal fade" id="modalHandover" tabindex="-1" aria-labelledby="labelModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="proses_serah_terima.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold" id="labelModal">Laporan Digital Serah Terima</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 pt-3">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="small fw-bold text-secondary mb-2">Petugas Keluar (Keluar)</label>
                        <input type="text" class="form-control bg-light border-0" value="<?= $_SESSION['nama_lengkap'] ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-secondary mb-2">Petugas Penerima (Masuk)</label>
                        <select name="user_id_masuk" class="form-select border-0 bg-light" required>
                            <option value="">-- Pilih Petugas Baru --</option>
                            <?php
                            $st_u = $pdo->query("SELECT id, nama_lengkap FROM users WHERE status='Aktif' AND id != $uid_skrg");
                            while($u = $st_u->fetch()) echo "<option value='{$u['id']}'>{$u['nama_lengkap']}</option>";
                            ?>
                        </select>
                    </div>
                </div>

                <div class="p-4 rounded-4 mb-4 border" style="background-color: #fcfcfc;">
                    <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-boxes me-2"></i>Daftar Peminjaman Aktif</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="small text-secondary text-uppercase">
                                <tr>
                                    <th>Aset</th>
                                    <th>Pemegang</th>
                                    <th>Waktu Pinjam</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php foreach($list_barang as $b): ?>
                                <tr class="border-bottom">
                                    <td><span class="badge bg-info-subtle text-info me-1">B</span> <strong><?= $b['nama_barang'] ?></strong></td>
                                    <td><?= $b['nama_lengkap'] ?></td>
                                    <td><?= date('d/m H:i', strtotime($b['tgl_pinjam'])) ?></td>
                                </tr>
                                <?php endforeach; ?>

                                <?php foreach($list_mobil as $k): ?>
                                <tr class="border-bottom">
                                    <td><span class="badge bg-warning-subtle text-warning me-1">K</span> <strong><?= $k['merk_model'] ?></strong> <small>(<?= $k['no_polisi'] ?>)</small></td>
                                    <td><?= $k['nama_lengkap'] ?></td>
                                    <td><?= date('d/m H:i', strtotime($k['tgl_ambil'])) ?></td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if(empty($list_barang) && empty($list_mobil)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted"><em>Tidak ada peminjaman aktif.</em></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">Catatan Situasi Terakhir</label>
                    <textarea name="catatan" class="form-control bg-light border-0" rows="3" placeholder="Contoh: Patroli aman, kunci gerbang lengkap..." required></textarea>
                </div>
            </div>

            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow">
                    KIRIM LAPORAN SERAH TERIMA
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// --- JURUS PAMUNGKAS FIX BACKDOOR ---
document.addEventListener('DOMContentLoaded', function() {
    var mHandover = document.getElementById('modalHandover');
    if (mHandover) {
        // PINDAHKAN MODAL KE BODY PALING LUAR
        document.body.appendChild(mHandover);
        
        // Pastikan scroll normal saat modal ditutup
        mHandover.addEventListener('hidden.bs.modal', function () {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
        });
    }
});
</script>