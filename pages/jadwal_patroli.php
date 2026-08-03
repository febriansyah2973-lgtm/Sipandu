<link rel="stylesheet" href="assets/css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
require_once 'config/database.php';
$role_user = strtolower($_SESSION['role'] ?? '');
$is_admin = in_array($role_user, ['admin', 'pengawas']);
$upload_path = "assets/uploads/jadwal/";

// Cek apakah sedang di halaman review/validasi
$action = $_GET['action'] ?? 'view';

// --- 1. PROSES HAPUS ---
if (isset($_GET['delete']) && $is_admin && $action != 'review') {
    $target_id = $_GET['delete'];
    $stmt_get = $pdo->prepare("SELECT file_path FROM jadwal_patroli WHERE id_jadwal = ?");
    $stmt_get->execute([$target_id]);
    $file_data = $stmt_get->fetch();

    if ($file_data) {
        if (file_exists($upload_path . $file_data['file_path'])) {
            unlink($upload_path . $file_data['file_path']);
        }
        $stmt_del = $pdo->prepare("DELETE FROM jadwal_patroli WHERE id_jadwal = ?");
        if ($stmt_del->execute([$target_id])) {
            echo "<script>
                Swal.fire('Terhapus!', 'Jadwal telah dihapus.', 'success')
                .then(() => { window.location.href='index.php?page=jadwal_patroli'; });
            </script>";
        }
    }
}

// --- 2. PROSES UPLOAD & LEMPAR KE HALAMAN REVIEW ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_jadwal']) && $is_admin) {
    if (!empty($_FILES['file_jadwal']['name'])) {
        $txt_judul = htmlspecialchars($_POST['judul']);
        $file_ext = strtolower(pathinfo($_FILES['file_jadwal']['name'], PATHINFO_EXTENSION));
        $file_tipe = in_array($file_ext, ['jpg','jpeg','png']) ? 'gambar' : 'pdf';
        $new_name = "JDL_" . time() . "." . $file_ext;

        if (!is_dir($upload_path)) mkdir($upload_path, 0777, true);

        if (move_uploaded_file($_FILES['file_jadwal']['tmp_name'], $upload_path . $new_name)) {
            $stmt_in = $pdo->prepare("INSERT INTO jadwal_patroli (judul, file_path, tipe_file, uploaded_by) VALUES (?, ?, ?, ?)");
            $stmt_in->execute([$txt_judul, $new_name, $file_tipe, $_SESSION['nama_lengkap']]);
            $last_id = $pdo->lastInsertId();
            
            // ALIHKAN KE HALAMAN REVIEW
            echo "<script>window.location.href='index.php?page=jadwal_patroli&action=review&id=$last_id';</script>";
            exit;
        }
    }
}

// --- 3. PROSES SIMPAN VALIDASI & SINKRONISASI PERSONIL ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_validasi']) && $is_admin) {
    $id_jadwal_ref = $_POST['id_jadwal_ref'];
    $user_ids = $_POST['user_id'] ?? [];
    $cabang_ids = $_POST['cabang_id'] ?? [];
    $shift_ids = $_POST['shift_id'] ?? [];
    $tgl_mulais = $_POST['tgl_mulai'] ?? [];
    $tgl_selesais = $_POST['tgl_selesai'] ?? [];

    try {
        $pdo->beginTransaction();

        for ($i = 0; $i < count($user_ids); $i++) {
            $uid = $user_ids[$i];
            
            // Hanya proses jika nama petugas dipilih
            if (!empty($uid)) {
                $cid = !empty($cabang_ids[$i]) ? $cabang_ids[$i] : null;
                $sid = !empty($shift_ids[$i]) ? $shift_ids[$i] : null;
                $tm = $tgl_mulais[$i];
                $ts = $tgl_selesais[$i];

                // A. Simpan ke Riwayat Penugasan (Tabel jadwal_tugas)
                $sql_tugas = "INSERT INTO jadwal_tugas (id_jadwal_ref, user_id, cabang_id, shift_id, tgl_mulai, tgl_selesai) VALUES (?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql_tugas)->execute([$id_jadwal_ref, $uid, $cid, $sid, $tm, $ts]);

                // B. INTEGRASI SUPER: Update langsung ke Master Personil (Tabel users)
                $sql_user = "UPDATE users SET cabang_id = ?, shift_id = ? WHERE id = ?";
                $pdo->prepare($sql_user)->execute([$cid, $sid, $uid]);
            }
        }

        $pdo->commit();
        echo "<script>
            Swal.fire('Validasi Sukses!', 'Jadwal diterbitkan & Data Master Personil otomatis terupdate.', 'success')
            .then(() => { window.location.href='index.php?page=jadwal_patroli'; });
        </script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>Swal.fire('Error Sistem', 'Gagal memproses data: " . addslashes($e->getMessage()) . "', 'error');</script>";
    }
}
?>

<style>
    .viewer-container { background: #fff; border-radius: 25px; border: 1px solid #eee; overflow: hidden; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .viewer-header { padding: 20px 30px; background: #fdfdfd; border-bottom: 1px solid #f1f1f1; display: flex; justify-content: space-between; align-items: center; }
    .viewer-body { padding: 0; background: #525659; min-height: 500px; display: flex; justify-content: center; }
    .viewer-body img { width: 100%; height: auto; display: block; }
    .pdf-embed { width: 100%; height: 800px; border: none; }
    .border-dashed { border: 2px dashed #0d6efd; }
</style>

<?php if ($action == 'review' && isset($_GET['id']) && $is_admin): 
    $id_jadwal = $_GET['id'];
    $stmt_rev = $pdo->prepare("SELECT * FROM jadwal_patroli WHERE id_jadwal = ?");
    $stmt_rev->execute([$id_jadwal]);
    $review_data = $stmt_rev->fetch();
    
    // PERBAIKAN 1: Ambil Data Master untuk Form Validasi (Semua personil kecuali Admin)
    $list_users = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role NOT IN ('admin', 'administrator') AND status='Aktif' ORDER BY nama_lengkap ASC")->fetchAll();
    
    $list_cabang = $pdo->query("SELECT id, nama_cabang FROM cabang ORDER BY nama_cabang ASC")->fetchAll();
    $list_shift = $pdo->query("SELECT id, nama_shift FROM shifts")->fetchAll();
?>
<div class="container-fluid animate__animated animate__fadeIn">
    <div class="alert alert-warning border-warning shadow-sm mb-4" style="border-radius: 15px;">
        <div class="d-flex align-items-center">
            <i class="fas fa-robot fa-2x me-3 text-warning"></i>
            <div>
                <h5 class="fw-bold text-dark mb-0">Review & Validasi Penugasan</h5>
                <p class="mb-0 small text-dark">Silakan periksa dokumen di sebelah kiri dan input penugasan personil di sebelah kanan. Data akan otomatis mengupdate Profil Petugas.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="viewer-container mb-0" style="border-radius: 20px;">
                <div class="viewer-header py-3">
                    <h6 class="fw-bold mb-0 text-secondary"><i class="fas fa-file-alt me-2"></i> Dokumen Sumber: <?= htmlspecialchars($review_data['judul']) ?></h6>
                </div>
                <div class="viewer-body" style="min-height: 600px;">
                    <?php if ($review_data['tipe_file'] == 'gambar'): ?>
                        <img src="<?= $upload_path . $review_data['file_path']; ?>" style="object-fit: contain;">
                    <?php else: ?>
                        <iframe src="<?= $upload_path . $review_data['file_path']; ?>#toolbar=0" class="pdf-embed" style="height: 600px;"></iframe>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 20px; height: 100%;">
                <h5 class="fw-bold mb-3 border-bottom pb-3">Form Penugasan Personil</h5>
                <form action="" method="POST" id="formValidasi">
                    
                    <input type="hidden" name="id_jadwal_ref" value="<?= htmlspecialchars($id_jadwal) ?>">
                    
                    <div id="dynamicRowsContainer" style="overflow-y: auto; max-height: 50vh; overflow-x: hidden; padding-right: 5px;">
                        <div class="bg-light p-3 rounded-3 mb-3 border assignment-row" id="rowTemplate">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="small fw-bold text-secondary">NAMA PETUGAS</label>
                                <button type="button" class="btn btn-sm text-danger btn-remove-row d-none" onclick="removeRow(this)"><i class="fas fa-times"></i></button>
                            </div>
                            <select name="user_id[]" class="form-select border-0 fw-bold mb-2 js-select-petugas" required>
                                <option value="">-- Pilih Petugas --</option>
                                <?php foreach($list_users as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small fw-bold text-secondary">CABANG</label>
                                    <select name="cabang_id[]" class="form-select border-0">
                                        <option value="">-- Pusat --</option>
                                        <?php foreach($list_cabang as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama_cabang']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-secondary">SHIFT</label>
                                    <select name="shift_id[]" class="form-select border-0">
                                        <option value="">-- Non Shift --</option>
                                        <?php foreach($list_shift as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_shift']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="small fw-bold text-secondary">TGL MULAI</label>
                                    <input type="date" name="tgl_mulai[]" class="form-control border-0" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-secondary">TGL SELESAI</label>
                                    <input type="date" name="tgl_selesai[]" class="form-control border-0" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="button" id="btnAddRow" class="btn btn-outline-primary btn-sm rounded-pill mb-4 w-100 border-dashed py-2 fw-bold">
                            <i class="fas fa-plus"></i> Tambah Baris Petugas
                        </button>

                        <button type="submit" name="simpan_validasi" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow-sm">
                            <i class="fas fa-check-circle me-2"></i> KONFIRMASI & TERBITKAN
                        </button>
                        <a href="index.php?page=jadwal_patroli" class="btn btn-light w-100 py-2 mt-2 fw-bold rounded-pill text-secondary">Batal & Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi Cerdas: Sembunyikan nama petugas yang sudah dipilih di baris lain
function updateDropdownPetugas() {
    const semuaSelect = document.querySelectorAll('.js-select-petugas');
    const namaTerpilih = [];

    // 1. Kumpulkan semua ID yang sudah terpilih
    semuaSelect.forEach(select => {
        if (select.value !== "") {
            namaTerpilih.push(select.value);
        }
    });

    // 2. Cek masing-masing dropdown dan disable opsi yang sudah terpilih
    semuaSelect.forEach(select => {
        const nilaiSaatIni = select.value;
        Array.from(select.options).forEach(option => {
            if (option.value === "") return; // Abaikan option kosong
            
            if (namaTerpilih.includes(option.value) && option.value !== nilaiSaatIni) {
                option.style.display = 'none';
                option.disabled = true;
            } else {
                option.style.display = 'block';
                option.disabled = false;
            }
        });
    });
}

// Pasang sensor pertama kali
document.querySelector('#rowTemplate .js-select-petugas').addEventListener('change', updateDropdownPetugas);

// JS untuk Fungsi Duplicate Baris Form
document.getElementById('btnAddRow').addEventListener('click', function() {
    var container = document.getElementById('dynamicRowsContainer');
    var template = document.getElementById('rowTemplate');
    var clone = template.cloneNode(true); 
    
    // Hapus ID dari hasil clone
    clone.removeAttribute('id');
    
    // Reset nilai select dan input
    var selects = clone.querySelectorAll('select');
    selects.forEach(sel => sel.selectedIndex = 0);
    
    var inputs = clone.querySelectorAll('input[type="date"]');
    inputs.forEach(inp => {
        if(inp.name === 'tgl_selesai[]') inp.value = ''; 
    });

    // Munculkan tombol silang untuk form hasil clone
    var btnRemove = clone.querySelector('.btn-remove-row');
    btnRemove.classList.remove('d-none');

    // SANGAT PENTING: Pasang event listener ke select yang baru di-clone
    clone.querySelector('.js-select-petugas').addEventListener('change', updateDropdownPetugas);

    container.appendChild(clone);
    container.scrollTop = container.scrollHeight;
    
    // Refresh filter setelah clone
    updateDropdownPetugas();
});

// Fungsi untuk hapus baris
function removeRow(btn) {
    var row = btn.closest('.assignment-row');
    row.remove();
    // Refresh filter setelah hapus
    updateDropdownPetugas();
}
</script>

<?php else: 
    $semua_data = $pdo->query("SELECT * FROM jadwal_patroli ORDER BY created_at DESC")->fetchAll();
?>
<div class="container-fluid animate-fade-up">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Papan Jadwal Patroli</h3>
            <p class="text-secondary small">Jadwal penugasan personil yang berlaku saat ini.</p>
        </div>
        <?php if ($is_admin): ?>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalAdd">
                <i class="fas fa-upload me-2"></i> UPLOAD JADWAL BARU
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($semua_data)): ?>
        <div class="text-center py-5 opacity-50">
            <i class="fas fa-calendar-times fa-5x mb-3"></i>
            <h5>Belum ada jadwal yang ditempel.</h5>
        </div>
    <?php else: foreach ($semua_data as $item): ?>
        <div class="viewer-container">
            <div class="viewer-header">
                <div>
                    <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($item['judul']); ?></h5>
                    <small class="text-muted">Diunggah oleh: <?= htmlspecialchars($item['uploaded_by']); ?> pada <?= date('d M Y', strtotime($item['created_at'])); ?></small>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $upload_path . $item['file_path']; ?>" download class="btn btn-light border rounded-pill px-3 fw-bold btn-sm">
                        <i class="fas fa-download me-1"></i> Download
                    </a>
                    <?php if ($is_admin): ?>
                        <button onclick="konfirmasiHapus('<?= $item['id_jadwal']; ?>')" class="btn btn-danger rounded-pill px-3 fw-bold btn-sm">
                            <i class="fas fa-trash me-1"></i> Hapus
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="viewer-body">
                <?php if ($item['tipe_file'] == 'gambar'): ?>
                    <img src="<?= $upload_path . $item['file_path']; ?>" alt="Jadwal">
                <?php else: ?>
                    <iframe src="<?= $upload_path . $item['file_path']; ?>#toolbar=0" class="pdf-embed"></iframe>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="index.php?page=jadwal_patroli" method="POST" enctype="multipart/form-data" class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold">Input Jadwal Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-3">
                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-1">JUDUL JADWAL</label>
                    <input type="text" name="judul" class="form-control rounded-3 bg-light border-0" placeholder="Misal: Jadwal Shift 18-24 Feb" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-1">FILE (GAMBAR/PDF)</label>
                    <input type="file" name="file_jadwal" class="form-control rounded-3 bg-light border-0" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="upload_jadwal" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">UPLOAD & VALIDASI DATA</button>
            </div>
        </form>
    </div>
</div>

<script>
function konfirmasiHapus(id) {
    Swal.fire({
        title: 'Hapus dari Papan?',
        text: "Jadwal akan hilang dari layar petugas.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'index.php?page=jadwal_patroli&delete=' + id;
        }
    });
}
document.addEventListener('DOMContentLoaded', function() {
    var m = document.getElementById('modalAdd');
    if (m) {
        document.body.appendChild(m);
        m.addEventListener('hidden.bs.modal', function () {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        });
    }
});
</script>
<?php endif; ?>