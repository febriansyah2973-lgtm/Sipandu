<div class="container-fluid px-0">
    <div class="mb-4 animate-fade-up">
        <h3 class="fw-bold text-dark mb-1">Broadcast Pengumuman ??</h3>
        <p class="text-secondary small">Kirim pesan massal ke seluruh personil secara real-time.</p>
    </div>

    <div class="row animate-fade-up">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-3 px-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-bullhorn fa-2x me-3 opacity-50"></i>
                        <div>
                            <h5 class="fw-bold mb-0">Buat Pesan Baru</h5>
                            <small class="opacity-75">Pesan akan muncul di lonceng notifikasi semua user.</small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <form action="proses_broadcast.php" method="POST">
                        <input type="hidden" name="act" value="send_broadcast">

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">KATEGORI PESAN</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="kategori" id="cat_info" value="info" checked>
                                    <label class="btn btn-outline-primary w-100 py-3 rounded-4" for="cat_info">
                                        <i class="fas fa-info-circle d-block mb-2 fa-lg"></i>
                                        Informasi Umum
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="kategori" id="cat_warn" value="warning">
                                    <label class="btn btn-outline-warning w-100 py-3 rounded-4" for="cat_warn">
                                        <i class="fas fa-exclamation-triangle d-block mb-2 fa-lg"></i>
                                        Peringatan
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="kategori" id="cat_alert" value="alert">
                                    <label class="btn btn-outline-danger w-100 py-3 rounded-4" for="cat_alert">
                                        <i class="fas fa-bolt d-block mb-2 fa-lg"></i>
                                        Darurat / SOS
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">JUDUL PENGUMUMAN</label>
                            <input type="text" name="judul" class="form-control form-control-lg border-0 bg-light rounded-3" placeholder="Contoh: Pemberitahuan Apel Pagi" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">ISI PESAN BROADCAST</label>
                            <textarea name="pesan" class="form-control border-0 bg-light rounded-3" rows="5" placeholder="Tuliskan instruksi atau informasi di sini..." required></textarea>
                        </div>

                        <div class="alert alert-info border-0 rounded-4 d-flex align-items-center">
                            <i class="fas fa-question-circle me-3 fa-lg"></i>
                            <small>Pesan ini akan dikirimkan ke <strong>seluruh personil</strong> yang berstatus Aktif di sistem SIPANDU.</small>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-dark py-3 rounded-pill fw-bold shadow">
                                <i class="fas fa-paper-plane me-2"></i> SEBARKAN PENGUMUMAN SEKARANG
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white p-4 mb-4">
                <h5 class="fw-bold"><i class="fas fa-lightbulb me-2"></i>Tips Broadcast</h5>
                <ul class="small ps-3 mt-3 mb-0" style="line-height: 1.8;">
                    <li>Gunakan kategori <strong>Darurat</strong> hanya untuk situasi kritis.</li>
                    <li>Sampaikan pesan dengan singkat dan padat agar mudah dibaca di HP.</li>
                    <li>Jangan lupa cek ejaan sebelum mengirim pesan masal.</li>
                </ul>
            </div>
        </div>
    </div>
</div>