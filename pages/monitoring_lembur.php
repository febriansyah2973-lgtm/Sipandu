<?php
/**
 * MONITORING & APPROVAL LEMBUR (OVT)
 * Fitur Isolasi Data (Kacamata Kuda) terintegrasi
 */
require_once 'config/database.php';

$id_login   = $_SESSION['id_user'] ?? $_SESSION['user_id'];
$my_role    = strtolower($_SESSION['role'] ?? '');
$filter_tgl = $_GET['tgl'] ?? date('Y-m-d');

try {
    // LOGIKA ISOLASI DATA
    if ($my_role == 'administrator' || $my_role == 'admin') {
        // 1. Admin bisa lihat semua
        $where = "WHERE l.tgl_lembur = :tgl";
        $params = ['tgl' => $filter_tgl];
    } elseif ($my_role == 'pengawas') {
        // 2. Pengawas lihat lemburan bawahan (id_p_tujuan) ATAU lemburan miliknya sendiri (id_user)
        $where = "WHERE l.tgl_lembur = :tgl AND (l.id_p_tujuan = :id_login OR l.id_user = :id_login)";
        $params = ['tgl' => $filter_tgl, 'id_login' => $id_login];
    } else {
        // 3. Petugas hanya lihat lemburan miliknya sendiri
        $where = "WHERE l.tgl_lembur = :tgl AND l.id_user = :id_login";
        $params = ['tgl' => $filter_tgl, 'id_login' => $id_login];
    }

    $sql = "SELECT l.*, u.nama_lengkap, u.role as jabatan_user
            FROM lembur l JOIN users u ON l.id_user = u.id 
            $where ORDER BY l.tgl_input DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data_lembur = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>");
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .status-pending { background-color: #E1EFFE !important; color: #1E429F !important; }
    .status-approved { background-color: #DEF7EC !important; color: #03543F !important; }
    .status-rejected { background-color: #FDE8E8 !important; color: #9B1C1C !important; }
    .card-ovt { border: 1px solid #F1F5F9; border-radius: 16px; transition: 0.2s; background: #FFF; }
    .card-ovt:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.05); transform: translateY(-2px); }
    .avatar-box { width: 42px; height: 42px; background: #F8F9FA; color: #6366F1; border-radius: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .btn-action-detail { background: #F3F4F6; color: #374151; border: none; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; }
    .badge-soft { font-size: 10px; font-weight: 800; padding: 5px 12px; border-radius: 6px; text-transform: uppercase; }
</style>

<div class="animate-fade-up px-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">
                <?= ($my_role == 'admin' || $my_role == 'administrator' || $my_role == 'pengawas') ? 'Approval Lembur' : 'Riwayat Pengajuan Lembur' ?>
            </h4>
            <p class="text-muted small mb-0">
                <?= ($my_role == 'admin' || $my_role == 'administrator' || $my_role == 'pengawas') ? 'Rincian pengajuan lembur berjenjang.' : 'Pantau status persetujuan lembur Anda di sini.' ?>
            </p>
        </div>
        <input type="date" class="form-control w-auto border-0 shadow-sm rounded-3 fw-bold" value="<?= $filter_tgl ?>" onchange="location.href='index.php?page=monitoring_lembur&tgl='+this.value">
    </div>

    <?php if (empty($data_lembur)): ?>
        <div class="card card-ovt py-5 text-center text-muted small">Belum ada data lembur pada tanggal ini.</div>
    <?php else: foreach ($data_lembur as $l): 
        $cls = "status-pending"; $txt = "Pending";
        if($l['status_lembur'] == 1) { $cls = "status-approved"; $txt = "Approve Pengawas"; }
        if($l['status_lembur'] == 2) { $cls = "status-approved"; $txt = "Final Approved"; }
        if($l['status_lembur'] == 9) { $cls = "status-rejected"; $txt = "Rejected"; }
    ?>
    <div class="card card-ovt mb-3 shadow-sm">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-box me-3"><?= strtoupper(substr($l['nama_lengkap'], 0, 1)) ?></div>
                        <div>
                            <div class="fw-bold text-dark mb-0" style="font-size: 14px;">
                                <?= $l['nama_lengkap'] ?> 
                                <?= ($l['id_user'] == $id_login) ? '<span class="text-primary" style="font-size:10px;">(SAYA)</span>' : '' ?>
                            </div>
                            <div class="text-muted small text-uppercase" style="font-size: 10px;"><?= $l['jabatan_user'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-center">
                    <div class="text-muted small mb-1">Jadwal OVT</div>
                    <div class="fw-bold text-dark"><?= date('H:i', strtotime($l['jam_mulai'])) ?> - <?= date('H:i', strtotime($l['jam_selesai'])) ?></div>
                </div>
                <div class="col-md-2 text-center">
                    <div class="text-muted small mb-1">Status</div>
                    <span class="badge-soft <?= $cls ?>"><?= $txt ?></span>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn-action-detail me-1" onclick="showOvtDetail(<?= htmlspecialchars(json_encode($l)) ?>)">LIHAT DETAIL</button>
                    
                    <?php 
                    // Pengawas hanya bisa approve jika status 0 DAN yang lembur BUKAN dirinya sendiri
                    if($l['status_lembur'] == 0 && $my_role == 'pengawas' && $l['id_user'] != $id_login): 
                    ?>
                        <button onclick="handleApproval(<?= $l['id_lembur'] ?>, 1)" class="btn btn-success btn-sm rounded-3 px-3 fw-bold">SETUJU</button>
                        <button onclick="handleApproval(<?= $l['id_lembur'] ?>, 9)" class="btn btn-danger btn-sm rounded-3 px-3 fw-bold ms-1">TOLAK</button>
                        
                    <?php 
                    // Admin melakukan Final Approve
                    elseif($l['status_lembur'] == 1 && ($my_role == 'admin' || $my_role == 'administrator')): 
                    ?>
                        <button onclick="handleApproval(<?= $l['id_lembur'] ?>, 2)" class="btn btn-primary btn-sm rounded-3 px-3 fw-bold">FINAL APPROVE</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if(!empty($l['komentar_pengawas'])): ?>
                <div class="mt-2 p-2 bg-light rounded-3 small text-muted border-start border-3 border-info">
                    <i class="fas fa-comment-dots me-1 text-info"></i> <b>Catatan:</b> <?= htmlspecialchars($l['komentar_pengawas']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<script>
/**
 * DETAIL LENGKAP: Menampilkan semua informasi pengajuan
 */
function showOvtDetail(data) {
    Swal.fire({
        title: '<div class="text-start fw-bold" style="font-size: 18px;">Rincian Pengajuan Lembur</div>',
        html: `
            <div class="text-start" style="font-size: 13px; line-height: 2;">
                <div class="d-flex justify-content-between border-bottom pb-1 mb-2">
                    <span class="text-muted">Nama Personil</span>
                    <span class="fw-bold text-dark">${data.nama_lengkap}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-1 mb-2">
                    <span class="text-muted">Jabatan</span>
                    <span class="fw-bold text-uppercase">${data.jabatan_user}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-1 mb-2">
                    <span class="text-muted">Tanggal Lembur</span>
                    <span class="fw-bold">${data.tgl_lembur}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-1 mb-2">
                    <span class="text-muted">Waktu (24 Jam)</span>
                    <span class="fw-bold text-primary">${data.jam_mulai} s/d ${data.jam_selesai} WIB</span>
                </div>
                <div class="mt-3">
                    <div class="text-muted mb-1">Keterangan / Tugas Pekerjaan:</div>
                    <div class="p-3 rounded-3 bg-light" style="font-style: italic; color: #4B5563; border: 1px solid #E5E7EB;">
                        "${data.keterangan || 'Tidak ada keterangan tambahan.'}"
                    </div>
                </div>
            </div>
        `,
        confirmButtonText: 'Tutup Detail',
        confirmButtonColor: '#6366F1',
        customClass: { popup: 'rounded-4' }
    });
}

/**
 * APPROVAL DENGAN INPUT KOMENTAR
 */
async function handleApproval(id, status) {
    const isOk = (status !== 9);
    const { value: text } = await Swal.fire({
        title: isOk ? 'Setujui Lembur?' : 'Tolak Lembur?',
        input: 'textarea',
        inputLabel: 'Berikan Catatan Pengawas (Opsional)',
        inputPlaceholder: isOk ? 'Contoh: Pekerjaan selesai dengan baik.' : 'Alasan penolakan...',
        showCancelButton: true,
        confirmButtonColor: isOk ? '#10B981' : '#EF4444',
        confirmButtonText: isOk ? 'Ya, Setujui' : 'Ya, Tolak',
        cancelButtonText: 'Batal',
        reverseButtons: true
    });

    if (text !== undefined) {
        const note = encodeURIComponent(text);
        window.location.href = `proses_presensi.php?act=approve_lembur&id=${id}&status=${status}&note=${note}`;
    }
}
</script>