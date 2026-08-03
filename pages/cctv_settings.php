<?php
$dirConfig  = 'config';
$fileConfig = $dirConfig . '/cctv_config.json';
if (!is_dir($dirConfig)) {
    mkdir($dirConfig, 0777, true);
}
if (!file_exists($fileConfig)) {
    $defaultData = [
        "ip"   => "192.168.1.3",
        "user" => "admin",
        "pass" => "admin123",
        "port" => "80"
    ];
    file_put_contents($fileConfig, json_encode($defaultData, JSON_PRETTY_PRINT));
}
$pesan = "";
if (isset($_POST['simpan_cctv'])) {
    $dataBaru = [
        "ip"   => $_POST['ip'],
        "user" => $_POST['user'],
        "pass" => $_POST['pass'],
        "port" => $_POST['port']
    ];
    if(file_put_contents($fileConfig, json_encode($dataBaru, JSON_PRETTY_PRINT))){
        $pesan = "<div class='alert alert-success alert-dismissible fade show'>
                    <i class='fas fa-check-circle me-2'></i>Pengaturan Berhasil Disimpan!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    } else {
        $pesan = "<div class='alert alert-danger'>Gagal menyimpan file. Cek izin folder 'config'.</div>";
    }
}
$cfg = json_decode(file_get_contents($fileConfig), true);
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Pengaturan CCTV Dahua</h5>
            </div>
            <div class="card-body">
                
                <?= $pesan ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">IP Address NVR</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-network-wired"></i></span>
                            <input type="text" name="ip" class="form-control" value="<?= $cfg['ip'] ?>" placeholder="192.168.1.xxx" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="user" class="form-control" value="<?= $cfg['user'] ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" name="pass" id="passInput" class="form-control" value="<?= $cfg['pass'] ?>" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePass()">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">HTTP Port (Web)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-door-open"></i></span>
                            <input type="number" name="port" class="form-control" value="<?= $cfg['port'] ?? 80 ?>">
                        </div>
                        <small class="text-muted fst-italic">Default port biasanya 80.</small>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <button type="submit" name="simpan_cctv" class="btn btn-primary fw-bold px-4">
                            <i class="fas fa-save me-2"></i> SIMPAN
                        </button>
                        
                        <a href="cctv_proxy.php?ch=1&test=1" target="_blank" class="btn btn-outline-dark">
                            <i class="fas fa-external-link-alt me-2"></i> Tes Gambar
                        </a>
                    </div>
                </form>

            </div>
        </div>
        
        <div class="alert alert-info mt-3 small border-0 shadow-sm">
            <i class="fas fa-info-circle me-1"></i> 
            Data pengaturan ini disimpan di file: <code>sipandu/config/cctv_config.json</code>
        </div>
    </div>
</div>

<script>
function togglePass() {
    var x = document.getElementById("passInput");
    if (x.type === "password") { x.type = "text"; } else { x.type = "password"; }
}
</script>