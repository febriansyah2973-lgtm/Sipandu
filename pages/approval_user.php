<?php
require_once 'config/database.php';

// --- PROSES APPROVAL (SETUJUI) ---
if (isset($_POST['approve_id'])) {
    $id = $_POST['approve_id'];
    
    // UPDATE SEMUA KOLOM STATUS SEKALIGUS (FIX SINKRONISASI)
    $stmt = $pdo->prepare("UPDATE users SET is_active = 1, status = 'Aktif', status_akun = 'Aktif' WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        echo "<script>
            Swal.fire({
                title: 'Akun Diaktifkan!',
                text: 'User kini resmi aktif dan bisa login.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => { window.location='index.php?page=approval_user'; });
        </script>";
    }
}

// --- PROSES TOLAK/HAPUS ---
if (isset($_POST['reject_id'])) {
    $id = $_POST['reject_id'];
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    echo "<script>
        Swal.fire('Terhapus!', 'Data pendaftaran ditolak.', 'success')
        .then(() => { window.location='index.php?page=approval_user'; });
    </script>";
}

/**
 * --- PERBAIKAN QUERY (ANTI GHOST DATA) ---
 * Hanya tampilkan user yang:
 * 1. is_active = 0 (Benar-benar mati)
 * 2. DAN statusnya BUKAN 'Aktif' (Mencegah user aktif nyasar ke sini)
 */
$stmt = $pdo->query("SELECT * FROM users WHERE is_active = 0 AND status != 'Aktif' ORDER BY created_at DESC");
$pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-4 animate-fade-up">
    <h3 class="fw-bold text-dark mb-1">Approval & Validasi User</h3>
    <p class="text-secondary mb-0 small">Validasi pendaftar baru yang menunggu akses login.</p>
</div>

<div class="card-master border-top border-3 border-primary">
    <div class="card-header bg-white p-3 border-bottom">
        <h6 class="fw-bold text-primary mb-0">
            <i class="fas fa-user-clock me-2"></i> Menunggu Persetujuan
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-3">Personil</th>
                    <th>Role Request</th>
                    <th>Kontak</th>
                    <th>Waktu Daftar</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pending_users)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" alt="Empty" style="width: 60px; opacity: 0.5;" class="mb-3 d-block mx-auto">
                            <span class="fw-bold">Tidak ada antrean.</span><br>
                            <small>Semua akun sudah diproses.</small>
                        </td>
                    </tr>
                <?php else: foreach ($pending_users as $u): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-initial me-3 bg-primary-subtle text-primary fw-bold rounded-circle" style="width:35px; height:35px; display:flex; align-items:center; justify-content:center;">
                                    <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">@<?= htmlspecialchars($u['username']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill" style="font-size: 0.7rem;">
                                <?= htmlspecialchars($u['role'] ?? 'Anggota') ?>
                            </span>
                        </td>
                        <td>
                            <div class="small text-dark"><?= htmlspecialchars($u['no_hp'] ?? '-') ?></div>
                            <div class="small text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($u['email'] ?? '-') ?></div>
                        </td>
                        <td class="small text-secondary">
                            <?= date('d M, H:i', strtotime($u['created_at'])) ?>
                        </td>
                        <td class="text-center">
                            <form method="POST" class="d-flex justify-content-center gap-2">
                                <button type="button" onclick="confirmApprove(<?= $u['id'] ?>)" class="btn btn-sm btn-primary py-1 px-3 rounded-pill shadow-sm" style="font-size: 0.75rem;">
                                    <i class="fas fa-check me-1"></i> Terima
                                </button>
                                <button type="button" onclick="confirmReject(<?= $u['id'] ?>)" class="btn btn-sm btn-outline-danger py-1 px-3 rounded-pill" style="font-size: 0.75rem;">
                                    <i class="fas fa-times me-1"></i> Tolak
                                </button>
                                
                                <input type="hidden" name="approve_id" id="approve_input_<?= $u['id'] ?>" disabled>
                                <input type="hidden" name="reject_id" id="reject_input_<?= $u['id'] ?>" disabled>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmApprove(id) {
    Swal.fire({
        title: 'Setujui Akun?',
        text: "User akan langsung bisa login ke sistem.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Setujui',
        confirmButtonColor: '#0d6efd'
    }).then((result) => {
        if (result.isConfirmed) {
            let i = document.getElementById('approve_input_' + id);
            i.disabled = false;
            i.value = id;
            i.form.submit();
        }
    })
}

function confirmReject(id) {
    Swal.fire({
        title: 'Hapus Data?',
        text: "Pendaftaran akan dihapus permanen.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            let i = document.getElementById('reject_input_' + id);
            i.disabled = false;
            i.value = id;
            i.form.submit();
        }
    })
}
</script>