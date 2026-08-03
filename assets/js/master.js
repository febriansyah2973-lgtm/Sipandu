document.addEventListener('DOMContentLoaded', function() {
    
    // =========================================================
    // 0. FITUR PENCARIAN GLOBAL (BERLAKU DI SEMUA MASTER)
    // =========================================================
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll('#dataTable tbody tr').forEach(tr => {
                tr.style.display = tr.innerText.toLowerCase().includes(val) ? '' : 'none';
            });
        });
    }

    // =========================================================
    // 1. PINDAH SEMUA MODAL KE BODY (ANTI BACKDROP HITAM)
    // =========================================================
    const allModals = [
        'modalBarang', 'modalDetailBarang', 
        'modalAkun', 'modalDetailAkun', 
        'modalPersonil', 'modalDetailPersonil', 'modalMutasiPersonil',
        'modalTambahCabang', 'modalEditCabang', 'modalDetailCabang',
        'modalKendaraan', 'modalDetailKendaraan', 'modalPreview',
        'modalLokasi', 'modalDetailLokasi',
        'modalShift', 'modalDetailShift'
    ];
    
    allModals.forEach(id => {
        const m = document.getElementById(id);
        if (m) document.body.appendChild(m);
    });

    // =========================================================
    // 2. EVENT DELEGATION GLOBAL (1 UNTUK SEMUA)
    // =========================================================
    document.body.addEventListener('click', function(e) {

        // --- HANDLER LOGOUT (Agar Tombol Logout Lancar & Keren) ---
        const btnLogout = e.target.closest('.btn-logout') || e.target.closest('#btnLogout');
        if (btnLogout) {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: "Apakah Anda yakin ingin keluar dari sistem?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
            return; // Berhenti di sini
        }
        
        // ---------------------------------------------------------
        // A. MASTER BARANG
        // ---------------------------------------------------------
        const btnDetailBarang = e.target.closest('.btn-detail-barang');
        if (btnDetailBarang) {
            const d = btnDetailBarang.dataset;
            document.getElementById('detailBarangNama').innerText = d.nama;
            document.getElementById('detailBarangKode').innerText = d.kode;
            document.getElementById('detailBarangTotal').innerText = d.total;
            document.getElementById('detailBarangBaik').innerText = d.baik;
            document.getElementById('detailBarangDipinjam').innerText = d.dipinjam;
            document.getElementById('detailBarangRusak').innerText = d.rusak;
            document.getElementById('detailBarangHilang').innerText = d.hilang;
            document.getElementById('detailBarangLokasi').innerText = d.lokasi || 'Belum diisi';

            const imgEl = document.getElementById('detailBarangFoto');
            const initEl = document.getElementById('detailBarangInitials');
            if (d.foto && d.foto !== '') {
                imgEl.src = 'assets/img/barang/' + d.foto;
                imgEl.style.display = 'block';
                initEl.style.display = 'none';
            } else {
                imgEl.style.display = 'none';
                initEl.innerText = d.nama.charAt(0).toUpperCase();
                initEl.style.display = 'flex';
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailBarang')).show();
        }

        const btnEditBarang = e.target.closest('.btn-edit');
        if (btnEditBarang && document.getElementById('formBarang')) {
            const d = btnEditBarang.dataset;
            document.getElementById('modalTitle').innerText = 'Edit Barang';
            document.getElementById('inputAct').value = 'edit';
            document.getElementById('inputId').value = d.id;
            document.getElementById('inputKode').value = d.kode;
            document.getElementById('inputNama').value = d.nama;
            document.getElementById('inputBaik').value = d.baik;
            document.getElementById('inputRusak').value = d.rusak;
            document.getElementById('inputHilang').value = d.hilang;
            document.getElementById('inputLokasi').value = d.lokasi;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBarang')).show();
        }

        const btnTambahBarang = e.target.closest('#btnTambah');
        if (btnTambahBarang && document.getElementById('formBarang')) { 
            document.getElementById('modalTitle').innerText = 'Tambah Barang';
            document.getElementById('formBarang').reset();
            document.getElementById('inputAct').value = 'tambah';
            document.getElementById('inputKode').value = btnTambahBarang.dataset.nextKode;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBarang')).show();
        }

        const btnDeleteBarang = e.target.closest('.btn-delete');
        if (btnDeleteBarang && document.getElementById('formBarang')) {
            const id = btnDeleteBarang.dataset.id;
            Swal.fire({
                title: 'Hapus Barang?',
                text: "Riwayat peminjaman barang ini akan dihapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'proses_master_barang.php';
                    form.innerHTML = `<input type="hidden" name="act" value="hapus"><input type="hidden" name="id" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // ---------------------------------------------------------
        // B. MASTER AKUN
        // ---------------------------------------------------------
        const btnTambahAkun = e.target.closest('#btnTambahAkun');
        if (btnTambahAkun) {
            document.getElementById('form-act').value = 'tambah';
            document.getElementById('form-id').value = '';
            document.getElementById('form-nama').value = '';
            document.getElementById('form-username').value = '';
            document.getElementById('modalLabel').innerText = 'Tambah Akun Baru';
            document.getElementById('pass-note').style.display = 'none';
            document.getElementById('form-password').required = true;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAkun')).show();
        }

        const btnDetailAkun = e.target.closest('.btn-detail-akun');
        if (btnDetailAkun) {
            const d = btnDetailAkun.dataset;
            document.getElementById('detailAkunNama').innerText = d.nama;
            document.getElementById('detailAkunUsername').innerText = '@' + d.username;
            
            let roleTeks = (d.role || '').toUpperCase();
            if(d.jabatan) roleTeks += ' (' + d.jabatan + ')';
            document.getElementById('detailAkunRole').innerText = roleTeks;
            document.getElementById('detailAkunEmail').innerText = d.email || 'Belum diisi';
            document.getElementById('detailAkunHp').innerText = d.hp || 'Belum diisi';
            
            const badge = document.getElementById('detailAkunStatus');
            if (d.status === 'Aktif') {
                badge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3';
                badge.innerText = 'Aktif';
            } else {
                badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3';
                badge.innerText = 'Nonaktif';
            }

            const imgEl = document.getElementById('detailAkunFoto');
            const initEl = document.getElementById('detailAkunInitials');
            if (d.foto && d.foto !== 'default.png') {
                imgEl.src = 'uploads/profil/' + d.foto;
                imgEl.onerror = function() { this.src = 'assets/img/users/' + d.foto; this.onerror = null; };
                imgEl.style.display = 'block';
                initEl.style.display = 'none';
            } else {
                imgEl.style.display = 'none';
                initEl.innerText = d.nama.charAt(0).toUpperCase();
                initEl.style.display = 'flex';
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailAkun')).show();
        }

        const btnEditAkun = e.target.closest('.btn-edit-akun');
        if (btnEditAkun) {
            const d = btnEditAkun.dataset;
            document.getElementById('form-act').value = 'edit';
            document.getElementById('form-id').value = d.id;
            document.getElementById('form-nama').value = d.nama;
            document.getElementById('form-username').value = d.username;
            document.getElementById('form-role').value = d.role;
            document.getElementById('form-status').value = d.status;
            document.getElementById('modalLabel').innerText = 'Edit Akun: ' + d.username;
            document.getElementById('pass-note').style.display = 'block';
            document.getElementById('form-password').required = false;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAkun')).show();
        }

        const btnDeleteAkun = e.target.closest('.btn-delete-akun');
        if (btnDeleteAkun) {
            const id = btnDeleteAkun.dataset.id;
            Swal.fire({
                title: 'Yakin hapus akun?',
                text: "Data ini akan hilang permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php?page=master_akun&delete_id=' + id;
                }
            });
        }

        // ---------------------------------------------------------
        // C. MASTER PERSONIL
        // ---------------------------------------------------------
        const btnTambahPersonil = e.target.closest('#btnTambahPersonil');
        if (btnTambahPersonil) {
            document.getElementById('formPersonil').reset();
            document.getElementById('modalTitlePersonil').innerText = 'Tambah Personil';
            document.getElementById('inputActPersonil').value = 'tambah';
            document.getElementById('inputIdPersonil').value = '';
            document.getElementById('passwordHelpPersonil').classList.add('d-none');
            document.getElementById('inputPasswordPersonil').required = true;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersonil')).show();
        }

        const btnDetailPersonil = e.target.closest('.btn-detail-personil');
        if (btnDetailPersonil) {
            const d = btnDetailPersonil.dataset;
            document.getElementById('detailPersonilNama').innerText = d.nama;
            document.getElementById('detailPersonilUsername').innerText = '@' + d.username;
            document.getElementById('detailPersonilRole').innerText = (d.role || '').toUpperCase();
            document.getElementById('detailPersonilCabang').innerText = d.cabang;
            document.getElementById('detailPersonilShift').innerText = d.shift;
            
            const badge = document.getElementById('detailPersonilStatus');
            if(d.status === 'Aktif') {
                badge.className = 'badge bg-success-subtle text-success rounded-pill px-3';
                badge.innerText = 'Aktif';
            } else {
                badge.className = 'badge bg-danger-subtle text-danger rounded-pill px-3';
                badge.innerText = 'Nonaktif';
            }

            const imgEl = document.getElementById('detailPersonilFoto');
            const initEl = document.getElementById('detailPersonilInitials');
            if (d.foto && d.foto !== '') {
                imgEl.src = d.foto;
                imgEl.style.display = 'block';
                initEl.style.display = 'none';
            } else {
                imgEl.style.display = 'none';
                initEl.innerText = d.nama.charAt(0).toUpperCase();
                initEl.style.display = 'flex';
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailPersonil')).show();
        }

        const btnEditPersonil = e.target.closest('.btn-edit-personil');
        if (btnEditPersonil) {
            const d = btnEditPersonil.dataset;
            document.getElementById('modalTitlePersonil').innerText = 'Edit Personil: ' + d.nama;
            document.getElementById('inputActPersonil').value = 'edit';
            document.getElementById('inputIdPersonil').value = d.id;
            document.getElementById('inputNamaPersonil').value = d.nama;
            document.getElementById('inputUsernamePersonil').value = d.username;
            document.getElementById('inputRolePersonil').value = d.role;
            document.getElementById('inputStatusPersonil').value = d.status;
            document.getElementById('inputCabangPersonil').value = d.cabang;
            document.getElementById('inputShiftPersonil').value = d.shift;
            
            document.getElementById('passwordHelpPersonil').classList.remove('d-none');
            document.getElementById('inputPasswordPersonil').required = false;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersonil')).show();
        }

        const btnMutasiPersonil = e.target.closest('.btn-mutasi-personil');
        if (btnMutasiPersonil) {
            const d = btnMutasiPersonil.dataset;
            document.getElementById('mutasiIdUser').value = d.id;
            document.getElementById('mutasiNamaPersonil').innerText = d.nama;
            document.getElementById('mutasiCabangAsalId').value = d.cabang_asal_id;
            document.getElementById('mutasiCabangAsalNama').innerText = d.cabang_asal_nama;
            
            const targetSelect = document.getElementById('mutasiCabangTujuan');
            targetSelect.value = ''; 
            const namaAsal = (d.cabang_asal_nama || '').trim().toLowerCase();

            Array.from(targetSelect.options).forEach(option => {
                if (option.value === "") return;
                const namaOption = option.text.trim().toLowerCase();

                if (option.value === d.cabang_asal_id || namaOption === namaAsal) {
                    option.style.display = 'none';
                    option.disabled = true;
                } else {
                    option.style.display = 'block';
                    option.disabled = false;
                }
            });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMutasiPersonil')).show();
        }

        const btnDeletePersonil = e.target.closest('.btn-delete-personil');
        if (btnDeletePersonil) {
            const id = btnDeletePersonil.dataset.id;
            Swal.fire({
                title: 'Hapus Personil?',
                text: "Data tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'proses_master_personil.php?act=hapus&id=' + id;
                }
            });
        }

        // ---------------------------------------------------------
        // D. MASTER CABANG
        // ---------------------------------------------------------
        const btnTambahCabang = e.target.closest('#btnTambahCabang');
        if (btnTambahCabang) {
            const modalEl = document.getElementById('modalTambahCabang');
            if(modalEl) {
                const form = modalEl.querySelector('form');
                if(form) form.reset();
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        }

        const btnDetailCabang = e.target.closest('.btn-detail-cabang');
        if (btnDetailCabang) {
            const d = btnDetailCabang.dataset;
            document.getElementById('detailCabangNama').innerText = d.nama;
            document.getElementById('detailCabangAlamat').innerText = d.alamat || 'Belum ada data alamat.';
            document.getElementById('detailCabangKontak').innerText = d.kontak || 'Belum ada data kontak.';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailCabang')).show();
        }

        const btnEditCabang = e.target.closest('.btn-edit-cabang');
        if (btnEditCabang) {
            const d = btnEditCabang.dataset;
            document.getElementById('editCabangId').value = d.id;
            document.getElementById('editCabangNama').value = d.nama;
            document.getElementById('editCabangAlamat').value = d.alamat;
            document.getElementById('editCabangKontak').value = d.kontak;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditCabang')).show();
        }

        const btnDeleteCabang = e.target.closest('.btn-delete-cabang');
        if (btnDeleteCabang) {
            const id = btnDeleteCabang.dataset.id;
            Swal.fire({
                title: 'Hapus Cabang?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'proses_cabang.php?hapus=' + id;
                }
            });
        }

        // ---------------------------------------------------------
        // E. MASTER KENDARAAN
        // ---------------------------------------------------------
        const btnTambahKendaraan = e.target.closest('#btnTambahKendaraan');
        if (btnTambahKendaraan && document.getElementById('formKendaraan')) {
            document.getElementById('modalTitleKendaraan').innerText = 'Tambah Kendaraan';
            document.getElementById('formKendaraan').reset();
            document.getElementById('inputActKendaraan').value = 'tambah';
            document.getElementById('inputIdKendaraan').value = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKendaraan')).show();
        }

        const btnDetailKendaraan = e.target.closest('.btn-detail-kendaraan');
        if (btnDetailKendaraan) {
            const d = btnDetailKendaraan.dataset;
            document.getElementById('detailKendaraanMerk').innerText = d.merk;
            document.getElementById('detailKendaraanNopol').innerText = d.nopol;
            document.getElementById('detailKendaraanJenis').innerText = d.jenis;
            document.getElementById('detailKendaraanKet').innerText = d.ket || 'Tidak ada keterangan tambahan.';
            
            const badge = document.getElementById('detailKendaraanStatus');
            if (d.status === 'Siap') {
                badge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3';
                badge.innerHTML = '<i class="fas fa-check-circle me-1"></i> Siap Operasi';
            } else if (d.status === 'Servis') {
                badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3';
                badge.innerHTML = '<i class="fas fa-tools me-1"></i> Sedang Servis';
            } else {
                badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3';
                badge.innerHTML = '<i class="fas fa-times-circle me-1"></i> Rusak';
            }

            const imgEl = document.getElementById('detailKendaraanFoto');
            const initEl = document.getElementById('detailKendaraanInitials');
            if (d.foto && d.foto !== '') {
                imgEl.src = 'assets/img/kendaraan/' + d.foto;
                imgEl.style.display = 'block';
                initEl.style.display = 'none';
            } else {
                imgEl.style.display = 'none';
                initEl.innerHTML = '<i class="fas fa-car-side"></i>';
                initEl.style.display = 'flex';
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailKendaraan')).show();
        }

        const btnEditKendaraan = e.target.closest('.btn-edit-kendaraan');
        if (btnEditKendaraan && document.getElementById('formKendaraan')) {
            const d = btnEditKendaraan.dataset;
            document.getElementById('modalTitleKendaraan').innerText = 'Edit Kendaraan: ' + d.nopol;
            document.getElementById('inputActKendaraan').value = 'edit';
            document.getElementById('inputIdKendaraan').value = d.id;
            document.getElementById('inputNopolKendaraan').value = d.nopol;
            document.getElementById('inputJenisKendaraan').value = d.jenis;
            document.getElementById('inputMerkKendaraan').value = d.merk;
            document.getElementById('inputStatusKendaraan').value = d.status;
            document.getElementById('inputKetKendaraan').value = d.ket;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKendaraan')).show();
        }

        const btnDeleteKendaraan = e.target.closest('.btn-delete-kendaraan');
        if (btnDeleteKendaraan && document.getElementById('formKendaraan')) {
            const id = btnDeleteKendaraan.dataset.id;
            Swal.fire({
                title: 'Hapus Kendaraan?',
                text: "Data armada ini akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'proses_master_kendaraan.php';
                    form.innerHTML = `<input type="hidden" name="act" value="hapus"><input type="hidden" name="id" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // ---------------------------------------------------------
        // F. MASTER LOKASI (CHECKPOINT)
        // ---------------------------------------------------------
        const btnTambahLokasi = e.target.closest('#btnTambahLokasi');
        if (btnTambahLokasi && document.getElementById('formLokasi')) {
            document.getElementById('modalTitleLokasi').innerText = 'Tambah Lokasi';
            document.getElementById('formLokasi').reset();
            document.getElementById('inputActLokasi').value = 'tambah';
            document.getElementById('inputIdLokasi').value = '';
            
            const btnGPS = document.getElementById('btnGetGPS');
            if (btnGPS) {
                btnGPS.disabled = false;
                btnGPS.innerHTML = '<i class="fas fa-location-arrow me-1"></i> Dapatkan Lokasi';
                btnGPS.className = 'btn btn-success btn-sm rounded-pill shadow-sm fw-bold px-3';
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLokasi')).show();
        }

        const btnDetailLokasi = e.target.closest('.btn-detail-lokasi');
        if (btnDetailLokasi) {
            const d = btnDetailLokasi.dataset;
            document.getElementById('detailLokasiNama').innerText = d.nama;
            document.getElementById('detailLokasiLat').innerText = d.lat || '-';
            document.getElementById('detailLokasiLong').innerText = d.long || '-';
            document.getElementById('detailLokasiQrCode').innerText = d.qr;
            document.getElementById('detailLokasiKet').innerText = d.ket || 'Tidak ada keterangan.';
            document.getElementById('detailLokasiQrImage').src = d.qrurl;
            
            const mapLink = document.getElementById('detailLokasiMapLink');
            if(d.lat && d.long) {
                // PERBAIKAN: Gunakan Template Literals yang benar
                mapLink.href = `https://www.google.com/maps?q=${d.lat},${d.long}`;
                mapLink.style.display = 'block';
            } else {
                mapLink.style.display = 'none';
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailLokasi')).show();
        }

        const btnEditLokasi = e.target.closest('.btn-edit-lokasi');
        if (btnEditLokasi && document.getElementById('formLokasi')) {
            const d = btnEditLokasi.dataset;
            document.getElementById('modalTitleLokasi').innerText = 'Edit Lokasi';
            document.getElementById('inputActLokasi').value = 'edit';
            document.getElementById('inputIdLokasi').value = d.id;
            document.getElementById('inputNamaLokasi').value = d.nama;
            document.getElementById('inputLatLokasi').value = d.lat;
            document.getElementById('inputLongLokasi').value = d.long;
            document.getElementById('inputKetLokasi').value = d.ket;
            
            const btnGPS = document.getElementById('btnGetGPS');
            if (btnGPS) {
                btnGPS.innerHTML = '<i class="fas fa-check me-1"></i> Tersimpan';
                btnGPS.className = 'btn btn-outline-success btn-sm rounded-pill shadow-sm fw-bold px-3';
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLokasi')).show();
        }

        const btnDeleteLokasi = e.target.closest('.btn-delete-lokasi');
        if (btnDeleteLokasi) {
            const id = btnDeleteLokasi.dataset.id;
            Swal.fire({
                title: 'Hapus Lokasi Checkpoint?',
                text: "Riwayat scan pada lokasi ini mungkin akan terpengaruh!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'proses_master_lokasi.php?act=hapus&id=' + id;
                }
            });
        }

        const btnGetGPS = e.target.closest('#btnGetGPS');
        if (btnGetGPS) {
            btnGetGPS.disabled = true;
            btnGetGPS.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mencari...';

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    document.getElementById('inputLatLokasi').value = pos.coords.latitude;
                    document.getElementById('inputLongLokasi').value = pos.coords.longitude;
                    
                    btnGetGPS.disabled = false;
                    btnGetGPS.innerHTML = '<i class="fas fa-check me-1"></i> Terkunci';
                    btnGetGPS.className = 'btn btn-outline-success btn-sm rounded-pill fw-bold px-3';
                    
                    Swal.fire({
                        icon: 'success', title: 'Lokasi Terkunci!', text: 'Koordinat GPS berhasil diambil.', timer: 1500, showConfirmButton: false
                    });
                }, (err) => {
                    btnGetGPS.disabled = false;
                    btnGetGPS.innerHTML = '<i class="fas fa-location-arrow me-1"></i> Dapatkan Lokasi';
                    Swal.fire('Gagal', 'Nyalakan GPS HP dan berikan izin akses lokasi!', 'error');
                }, { enableHighAccuracy: true });
            } else {
                Swal.fire('Error', 'Browser tidak mendukung fitur GPS.', 'error');
            }
        }

        // ---------------------------------------------------------
        // G. MASTER SHIFT
        // ---------------------------------------------------------
        const btnTambahShift = e.target.closest('#btnTambahShift');
        if (btnTambahShift && document.getElementById('formShift')) {
            document.getElementById('modalTitleShift').innerText = 'Shift Baru';
            document.getElementById('formShift').reset();
            document.getElementById('inputActShift').value = 'tambah';
            document.getElementById('inputIdShift').value = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalShift')).show();
        }

        const btnDetailShift = e.target.closest('.btn-detail-shift');
        if (btnDetailShift) {
            const d = btnDetailShift.dataset;
            document.getElementById('detailShiftNama').innerText = d.nama;
            document.getElementById('detailShiftMasuk').innerText = d.masuk;
            document.getElementById('detailShiftPulang').innerText = d.pulang;
            document.getElementById('detailShiftDurasi').innerText = d.durasi;
            
            const iconBox = document.getElementById('detailShiftIconBox');
            const icon = document.getElementById('detailShiftIcon');
            
            iconBox.className = `mx-auto mb-3 d-flex align-items-center justify-content-center bg-${d.color}-subtle text-${d.color}`;
            icon.className = `fas fa-${d.icon}`;

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailShift')).show();
        }

        const btnEditShift = e.target.closest('.btn-edit-shift');
        if (btnEditShift && document.getElementById('formShift')) {
            const d = btnEditShift.dataset;
            document.getElementById('modalTitleShift').innerText = 'Edit Shift';
            document.getElementById('inputActShift').value = 'edit';
            document.getElementById('inputIdShift').value = d.id;
            document.getElementById('inputNamaShift').value = d.nama;
            document.getElementById('inputMasukShift').value = d.masuk;
            document.getElementById('inputPulangShift').value = d.pulang;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalShift')).show();
        }

        const btnDeleteShift = e.target.closest('.btn-delete-shift');
        if (btnDeleteShift && document.getElementById('formShift')) {
            const id = btnDeleteShift.dataset.id;
            Swal.fire({
                title: 'Hapus Shift?',
                text: "Jadwal yang menggunakan shift ini mungkin akan terpengaruh!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'proses_master_shift.php';
                    form.innerHTML = `<input type="hidden" name="act" value="hapus"><input type="hidden" name="id" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    });
});