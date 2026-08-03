<?php
$filter_id = $_GET['id'] ?? '';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Detail Aktivitas Titik Patroli</h1>
    <a href="index.php?page=checkpoint" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali ke Status Pos
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="titik_patroli">
            
            <div class="col-md-6">
                <label class="form-label fw-bold">Pilih Lokasi / Pos</label>
                <select name="id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Lokasi --</option>
                    <?php
                    $list_pos = $pdo->query("SELECT id, nama_lokasi FROM checkpoints ORDER BY nama_lokasi ASC");
                    while($pos = $list_pos->fetch()) {
                        $selected = ($filter_id == $pos['id']) ? 'selected' : '';
                        echo "<option value='".$pos['id']."' $selected>".$pos['nama_lokasi']."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <p class="text-muted small mb-2">
                    <i class="fas fa-info-circle"></i> Filter ini membantu Anda melihat log spesifik pada satu titik penjagaan.
                </p>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Waktu Scan</th>
                        <th>Petugas</th>
                        <th>Lokasi</th>
                        <th>Kondisi</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.*, u.nama_lengkap, c.nama_lokasi 
                              FROM patroli p 
                              JOIN users u ON p.user_id = u.id 
                              JOIN checkpoints c ON p.checkpoint_id = c.id 
                              WHERE p.waktu_scan >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                    if (!empty($filter_id)) {
                        $query .= " AND p.checkpoint_id = " . intval($filter_id);
                    }

                    $query .= " ORDER BY p.waktu_scan DESC";
                    $stmt = $pdo->query($query);

                    if ($stmt->rowCount() == 0) {
                        echo "<tr><td colspan='5' class='text-center py-4 text-muted'>Tidak ada histori scan dalam 90 hari terakhir.</td></tr>";
                    }

                    while ($row = $stmt->fetch()) {
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= date('d/m/Y', strtotime($row['waktu_scan'])) ?></div>
                            <small class="text-muted"><?= date('H:i:s', strtotime($row['waktu_scan'])) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['nama_lokasi']) ?></span></td>
                        <td>
                            <?php if($row['status_temuan'] == 'Aman'): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> Aman</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> <?= $row['status_temuan'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?= htmlspecialchars($row['keterangan'] ?: '-') ?></small></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>