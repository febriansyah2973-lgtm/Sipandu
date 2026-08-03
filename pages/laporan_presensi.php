<style>
    /* UI Gaya Gambar Kedua (Putih & Ikon) */
    .stat-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        height: 100%;
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 15px;
    }
    .icon-hadir { background: #e6f4ea; color: #1e7e34; }
    .icon-lembur { background: #fff4e5; color: #f0ad4e; }
    .icon-izin { background: #e3f2fd; color: #0275d8; }
    .icon-alpha { background: #fce8e8; color: #d9534f; }
    
    .stat-value { font-size: 28px; font-weight: 700; line-height: 1; margin-bottom: 5px; }
    .stat-label { font-size: 14px; color: #6c757d; }
    
    .form-control-pill { border-radius: 8px; border: 1px solid #ced4da; padding: 10px 15px; }
    .live-badge { font-size: 10px; vertical-align: middle; margin-left: 10px; }
</style>

<?php
require_once 'config/database.php';

$mulai = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');
$search_name = $_GET['search_name'] ?? '';

$sql = "SELECT p.*, u.nama_lengkap 
        FROM presensi p 
        JOIN users u ON p.id_user = u.id 
        WHERE (p.tgl_presensi BETWEEN :mulai AND :selesai)";

$params = ['mulai' => $mulai, 'selesai' => $selesai];
if (!empty($search_name)) {
    $sql .= " AND u.nama_lengkap LIKE :search_name";
    $params['search_name'] = "%$search_name%";
}
$sql .= " ORDER BY p.tgl_presensi ASC, p.jam_masuk ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

$h = 0; $l = 0; $i = 0; $a = 0;
foreach($data as $r) {
    if($r['status'] == 'Hadir' || $r['status'] == 'Terlambat') $h++;
    if(!empty($r['jam_lembur_mulai'])) $l++;
    if(in_array($r['status'], ['Izin', 'Sakit', 'Cuti'])) $i++;
    if($r['status'] == 'Alpha') $a++;
}
?>

<div class="animate-fade-up px-2">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h5 class="text-secondary mb-1">Laporan</h5>
            <h3 class="fw-bold text-dark">Rekapitulasi Presensi <span class="badge bg-danger rounded-pill live-badge animate-pulse">LIVE</span></h3>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-success rounded-pill px-4 dropdown-toggle shadow-sm fw-bold" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-file-export me-2"></i>Export Laporan
            </button>
            <ul class="dropdown-menu shadow border-0 mt-2">
                <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="exportToExcel()"><i class="fas fa-file-excel me-2 text-success"></i>Format Excel (.xlsx)</a></li>
                <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="exportToPDF()"><i class="fas fa-file-pdf me-2 text-danger"></i>Format PDF (.pdf)</a></li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 no-print" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="laporan_presensi">
                <div class="col-md-3">
                    <label class="small fw-bold text-secondary mb-2">Dari Tanggal</label>
                    <input type="date" id="f_mulai" name="mulai" class="form-control form-control-pill" value="<?= $mulai ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-secondary mb-2">Sampai Tanggal</label>
                    <input type="date" id="f_selesai" name="selesai" class="form-control form-control-pill" value="<?= $selesai ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-secondary mb-2">Cari Nama Personil</label>
                    <input type="text" id="f_search" name="search_name" class="form-control form-control-pill" placeholder="Nama..." value="<?= htmlspecialchars($search_name) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm">
                        <i class="fas fa-filter me-2"></i>Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4 no-print">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon icon-hadir"><i class="fas fa-user-check"></i></div>
                <div><div class="stat-value" id="val-h"><?= $h ?></div><div class="stat-label">Hadir</div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon icon-lembur"><i class="fas fa-moon"></i></div>
                <div><div class="stat-value" id="val-l"><?= $l ?></div><div class="stat-label">Lembur</div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon icon-izin"><i class="fas fa-file-medical"></i></div>
                <div><div class="stat-value" id="val-i"><?= $i ?></div><div class="stat-label">Izin/Sakit</div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon icon-alpha"><i class="fas fa-user-times"></i></div>
                <div><div class="stat-value" id="val-a"><?= $a ?></div><div class="stat-label">Belum Absen</div></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableExport">
                <thead class="bg-light text-secondary small text-uppercase fw-bold">
                    <tr class="text-center border-0">
                        <th width="5%" class="py-3">No</th>
                        <th>Tanggal</th>
                        <th class="text-start">Nama Personil</th>
                        <th>Masuk</th>
                        <th>Pulang</th>
                        <th>Lembur</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody class="small" id="table-body">
                    <?php if(empty($data)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>
                    <?php else: $no=1; foreach($data as $row): ?>
                        <tr class="text-center">
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/Y', strtotime($row['tgl_presensi'])) ?></td>
                            <td class="text-start">
                                <div class="fw-bold text-dark"><?= $row['nama_lengkap'] ?></div>
                                <small class="text-muted">ID: PEG-<?= $row['id_user'] ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $row['jam_masuk'] ? substr($row['jam_masuk'], 0, 5) : '-' ?></span></td>
                            <td><span class="badge bg-light text-dark border"><?= $row['jam_pulang'] ? substr($row['jam_pulang'], 0, 5) : '-' ?></span></td>
                            <td class="text-primary fw-bold"><?= $row['jam_lembur_mulai'] ? substr($row['jam_lembur_mulai'], 0, 5) . " - " . substr($row['jam_lembur_selesai'], 0, 5) : '-' ?></td>
                            <td>
                                <?php $st = $row['status']; $bg = ($st == 'Hadir' ? 'success' : 'warning'); ?>
                                <span class="badge bg-<?= $bg ?> rounded-pill px-3"><?= strtoupper($st) ?></span>
                            </td>
                            <td class="text-muted small"><?= $row['keterangan'] ?: '-' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<script>
// --- FUNGSI AUTO REFRESH (TAMBAHAN) ---
function refreshData() {
    const mulai = document.getElementById('f_mulai').value;
    const selesai = document.getElementById('f_selesai').value;
    const search = document.getElementById('f_search').value;

    fetch(`ajax/fetch_presensi_realtime.php?mulai=${mulai}&selesai=${selesai}&search_name=${search}`)
    .then(res => res.json())
    .then(res => {
        // Update Statistik
        document.getElementById('val-h').innerText = res.stats.h;
        document.getElementById('val-l').innerText = res.stats.l;
        document.getElementById('val-i').innerText = res.stats.i;
        document.getElementById('val-a').innerText = res.stats.a;

        // Update Tabel
        let html = "";
        if(res.data.length === 0) {
            html = '<tr><td colspan="8" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>';
        } else {
            res.data.forEach((row, index) => {
                const badgeBg = (row.status === 'Hadir' ? 'success' : 'warning');
                html += `<tr class="text-center">
                    <td>${index + 1}</td>
                    <td>${formatDate(row.tgl_presensi)}</td>
                    <td class="text-start">
                        <div class="fw-bold text-dark">${row.nama_lengkap}</div>
                        <small class="text-muted">ID: PEG-${row.id_user}</small>
                    </td>
                    <td><span class="badge bg-light text-dark border">${row.jam_masuk ? row.jam_masuk.substring(0,5) : '-'}</span></td>
                    <td><span class="badge bg-light text-dark border">${row.jam_pulang ? row.jam_pulang.substring(0,5) : '-'}</span></td>
                    <td class="text-primary fw-bold">${row.jam_lembur_mulai ? row.jam_lembur_mulai.substring(0,5) + " - " + row.jam_lembur_selesai.substring(0,5) : '-'}</td>
                    <td><span class="badge bg-${badgeBg} rounded-pill px-3">${row.status.toUpperCase()}</span></td>
                    <td class="text-muted small">${row.keterangan || '-'}</td>
                </tr>`;
            });
        }
        document.getElementById('table-body').innerHTML = html;
    });
}

function formatDate(dateString) {
    const d = new Date(dateString);
    return `${("0" + d.getDate()).slice(-2)}/${("0" + (d.getMonth() + 1)).slice(-2)}/${d.getFullYear()}`;
}

// Jalankan refresh setiap 10 detik
setInterval(refreshData, 10000);

// --- FUNGSI ASLI BAPAK ---
function exportToExcel() {
    let wb = XLSX.utils.table_to_book(document.getElementById("tableExport"), {sheet: "Presensi"});
    XLSX.writeFile(wb, "Laporan_Presensi_<?= $mulai ?>.xlsx");
}

function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt', 'a4');
    doc.text("LAPORAN REKAPITULASI PRESENSI - SIPANDU", 40, 30);
    doc.autoTable({ html: '#tableExport', startY: 50, theme: 'grid', styles: { fontSize: 8 } });
    doc.save("Laporan_Presensi_<?= $mulai ?>.pdf");
}
</script>