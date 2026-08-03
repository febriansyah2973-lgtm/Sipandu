<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Laporan Bulanan</h1>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 border-top-primary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-4"><i class="fas fa-print"></i> Cetak Laporan Patroli</h5>
                
                <form action="cetak_laporan.php" method="GET" target="_blank">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Periode Bulan</label>
                        <input type="month" name="periode" class="form-control form-control-lg" 
                               value="<?= date('Y-m') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status Temuan</label>
                        <select name="status" class="form-select">
                            <option value="Semua">-- Semua Status --</option>
                            <option value="Aman">Hanya yang Aman</option>
                            <option value="Rusak">Hanya Temuan Rusak/Insiden</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-file-export"></i> Preview & Cetak PDF
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="alert alert-info">
            <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Petunjuk</h5>
            <p>Fitur ini akan mencetak seluruh riwayat patroli berdasarkan bulan yang dipilih.</p>
            <hr>
            <p class="mb-0">
                Gunakan browser <strong>Google Chrome</strong> atau <strong>Edge</strong> untuk hasil cetak PDF terbaik. 
                Saat jendela baru muncul, pilih <strong>Destination: Save as PDF</strong>.
            </p>
        </div>
    </div>
</div>

<style>
    .border-top-primary {
        border-top: 5px solid #0d6efd !important;
    }
</style>