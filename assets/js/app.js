let scale = 1;
let panning = false;
let pointX = 0;
let pointY = 0;
let startX = 0;
let startY = 0;

window.lihatFoto = function(src, caption) {
    const img = document.getElementById('imgPreview');
    const cap = document.getElementById('captionPreview');
    
    if (img && cap) {
        img.src = src;
        cap.innerText = caption;
        resetZoom();
        const myModal = new bootstrap.Modal(document.getElementById('modalPreview'));
        myModal.show();
    }
};

window.resetZoom = function() {
    scale = 1;
    pointX = 0;
    pointY = 0;
    updateTransform();
};

function updateTransform() {
    const img = document.getElementById('imgPreview');
    if (img) {
        img.style.transform = `translate(${pointX}px, ${pointY}px) scale(${scale})`;
    }
}

window.adjustZoom = function(delta) {
    scale += delta;
    if (scale < 0.5) scale = 0.5;
    if (scale > 5) scale = 5;
    updateTransform();
};

document.addEventListener("DOMContentLoaded", function() {
    console.log("App Logic Ready.");

    function getCsrfToken() {
        return window.APP_CSRF_TOKEN || '';
    }

    function fixModalBackdrop(modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function () {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    }

    function konfirmasiHapus(url) {
        Swal.fire({
            title: 'Hapus Data?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#cbd5e1',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                const csrfToken = encodeURIComponent(getCsrfToken());
                const separator = url.includes('?') ? '&' : '?';
                window.location.href = `${url}${separator}csrf_token=${csrfToken}`;
            }
        });
    }

    const modalLokasi = document.getElementById('modalLokasi');
    if (modalLokasi) {
        document.body.appendChild(modalLokasi);
        fixModalBackdrop(modalLokasi);
        const modalInstance = new bootstrap.Modal(modalLokasi);

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                document.querySelectorAll('#dataTable tbody tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
                });
            });
        }

        document.body.addEventListener('click', function(e) {
            if (e.target.closest('#btnTambah')) {
                document.getElementById('formLokasi').reset();
                document.getElementById('modalTitle').innerText = "Tambah Lokasi";
                document.getElementById('inputAct').value = 'tambah';
                document.getElementById('inputId').value = '';
                modalInstance.show();
            }
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                const d = btnEdit.dataset;
                document.getElementById('modalTitle').innerText = "Edit Lokasi";
                document.getElementById('inputAct').value = 'edit';
                document.getElementById('inputId').value = d.id;
                document.getElementById('inputNama').value = d.nama;
                document.getElementById('inputLat').value = d.lat;
                document.getElementById('inputLong').value = d.long;
                document.getElementById('inputKet').value = d.ket;
                modalInstance.show();
            }
            const btnDelete = e.target.closest('.btn-delete');
            if (btnDelete) konfirmasiHapus(`proses_master_lokasi.php?act=hapus&id=${btnDelete.dataset.id}`);
        });
    }

const modalPersonil = document.getElementById('modalPersonil');
if (modalPersonil) {
    document.body.appendChild(modalPersonil);
    fixModalBackdrop(modalPersonil);
    const modalInstance = new bootstrap.Modal(modalPersonil);

    document.body.addEventListener('click', function(e) {
        if (e.target.closest('#btnTambah')) {
            document.getElementById('formPersonil').reset();
            document.getElementById('modalTitle').innerText = "Tambah Personil";
            document.getElementById('inputAct').value = 'tambah';
            document.getElementById('inputId').value = '';
            
            const passInput = document.getElementById('inputPassword');
            passInput.required = true;
            document.getElementById('passwordHelp').classList.add('d-none');
            
            modalInstance.show();
        }
        const btnEdit = e.target.closest('.btn-edit');
        if (btnEdit) {
            const d = btnEdit.dataset;
            document.getElementById('modalTitle').innerText = "Edit Personil";
            document.getElementById('inputAct').value = 'edit';
            document.getElementById('inputId').value = d.id;
            document.getElementById('inputNama').value = d.nama;
            document.getElementById('inputUsername').value = d.username;
            document.getElementById('inputRole').value = d.role;
            document.getElementById('inputStatus').value = d.status;

            // --- TAMBAHAN KODE: Mengisi dropdown Cabang dan Shift saat edit ---
            if(document.getElementById('inputCabang')) document.getElementById('inputCabang').value = d.cabang;
            if(document.getElementById('inputShift')) document.getElementById('inputShift').value = d.shift;
            // ------------------------------------------------------------------

            const passInput = document.getElementById('inputPassword');
            passInput.value = '';
            passInput.required = false; //
            document.getElementById('passwordHelp').classList.remove('d-none');

            modalInstance.show();
        }
        const btnDelete = e.target.closest('.btn-delete');
        if (btnDelete) {
            konfirmasiHapus(`proses_master_personil.php?act=hapus&id=${btnDelete.dataset.id}`);
        }
    });
}

    const modalBarang = document.getElementById('modalBarang');
    if (modalBarang) {
        document.body.appendChild(modalBarang);
        fixModalBackdrop(modalBarang);
        const modalInstance = new bootstrap.Modal(modalBarang);

        const searchInput = document.getElementById('searchInput');
        const filterKondisi = document.getElementById('filterKondisi');

        function filterBarang() {
            const txt = searchInput ? searchInput.value.toLowerCase() : "";
            const kondisi = filterKondisi ? filterKondisi.value.toLowerCase() : "";

            document.querySelectorAll('#dataTable tbody tr').forEach(row => {
                const rowText = row.textContent.toLowerCase();
                let matchKondisi = true;
                if (kondisi !== "") {
                    const stokWrapper = row.querySelector('.filter-kondisi-wrapper'); 
                    if (stokWrapper) {
                        matchKondisi = stokWrapper.textContent.toLowerCase().includes(kondisi);
                    }
                }
                
                const matchSearch = rowText.includes(txt);
                row.style.display = (matchSearch && matchKondisi) ? "" : "none";
            });
        }

        if (searchInput) searchInput.addEventListener('keyup', filterBarang);
        if (filterKondisi) filterKondisi.addEventListener('change', filterBarang);

        document.body.addEventListener('click', function(e) {
            if (e.target.closest('#btnTambah')) {
                document.getElementById('formBarang').reset();
                document.getElementById('modalTitle').innerText = "Tambah Barang";
                document.getElementById('inputAct').value = 'tambah';
                document.getElementById('inputId').value = '';
                
                document.getElementById('inputBaik').value = '0';
                document.getElementById('inputRusak').value = '0';
                document.getElementById('inputHilang').value = '0';
                
                modalInstance.show();
            }
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                const d = btnEdit.dataset;
                document.getElementById('modalTitle').innerText = "Edit Barang";
                document.getElementById('inputAct').value = 'edit';
                document.getElementById('inputId').value = d.id;
                document.getElementById('inputKode').value = d.kode;
                document.getElementById('inputNama').value = d.nama;
                document.getElementById('inputBaik').value = d.baik;
                document.getElementById('inputRusak').value = d.rusak;
                document.getElementById('inputHilang').value = d.hilang;
                
                document.getElementById('inputLokasi').value = d.lokasi;
                modalInstance.show();
            }
            const btnDelete = e.target.closest('.btn-delete');
            if (btnDelete) konfirmasiHapus(`proses_master_barang.php?act=hapus&id=${btnDelete.dataset.id}`);
        });
    }
    const zoomContainer = document.getElementById('zoomContainer');
    if (zoomContainer) {
        zoomContainer.addEventListener('wheel', function(e) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            window.adjustZoom(delta);
        });

        zoomContainer.addEventListener('mousedown', function(e) {
            e.preventDefault();
            startX = e.clientX - pointX;
            startY = e.clientY - pointY;
            panning = true;
            zoomContainer.style.cursor = 'grabbing';
        });

        window.addEventListener('mouseup', function() {
            panning = false;
            if(zoomContainer) zoomContainer.style.cursor = 'grab';
        });

        window.addEventListener('mousemove', function(e) {
            if (!panning) return;
            e.preventDefault();
            pointX = e.clientX - startX;
            pointY = e.clientY - startY;
            updateTransform();
        });
    }

    const modalPreview = document.getElementById('modalPreview');
    if (modalPreview) {
        modalPreview.addEventListener('hidden.bs.modal', function () {
            window.resetZoom();
        });
    }

    const modalKendaraan = document.getElementById('modalKendaraan');
    if (modalKendaraan) {
        document.body.appendChild(modalKendaraan);
        fixModalBackdrop(modalKendaraan);
        const modalInstance = new bootstrap.Modal(modalKendaraan);

        const searchInput = document.getElementById('searchInput');
        const filterStatus = document.getElementById('filterStatus');

        function filterKendaraan() {
            const txt = searchInput ? searchInput.value.toLowerCase() : "";
            const stat = filterStatus ? filterStatus.value.toLowerCase() : "";

            document.querySelectorAll('#dataTable tbody tr').forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const rowStatus = row.querySelector('.filter-status') ? row.querySelector('.filter-status').textContent.toLowerCase().trim() : "";
                
                const matchSearch = rowText.includes(txt);
                const matchStatus = stat === "" || rowStatus === stat;
                
                row.style.display = (matchSearch && matchStatus) ? "" : "none";
            });
        }

        if (searchInput) searchInput.addEventListener('keyup', filterKendaraan);
        if (filterStatus) filterStatus.addEventListener('change', filterKendaraan);

        document.body.addEventListener('click', function(e) {
            if (e.target.closest('#btnTambah')) {
                document.getElementById('formKendaraan').reset();
                document.getElementById('modalTitle').innerText = "Tambah Kendaraan";
                document.getElementById('inputAct').value = 'tambah';
                document.getElementById('inputId').value = '';
                modalInstance.show();
            }
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                const d = btnEdit.dataset;
                document.getElementById('modalTitle').innerText = "Edit Kendaraan";
                document.getElementById('inputAct').value = 'edit';
                document.getElementById('inputId').value = d.id;
                document.getElementById('inputNopol').value = d.nopol;
                document.getElementById('inputJenis').value = d.jenis;
                document.getElementById('inputMerk').value = d.merk;
                document.getElementById('inputStatus').value = d.status; // Pastikan value option sesuai (Siap, Servis, Rusak)
                document.getElementById('inputKet').value = d.ket;
                modalInstance.show();
            }
            const btnDelete = e.target.closest('.btn-delete');
            if (btnDelete) konfirmasiHapus(`proses_master_kendaraan.php?act=hapus&id=${btnDelete.dataset.id}`);
        });
    }

    const modalAkun = document.getElementById('modalAkun');
    if (modalAkun) {
        document.body.appendChild(modalAkun);
        fixModalBackdrop(modalAkun);
        const modalInstance = new bootstrap.Modal(modalAkun);

        const searchInput = document.getElementById('searchInput');
        const filterRole = document.getElementById('filterRole');

        function filterUsers() {
            const txt = searchInput ? searchInput.value.toLowerCase() : "";
            const role = filterRole ? filterRole.value.toLowerCase() : "";

            document.querySelectorAll('#dataTable tbody tr').forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const rowRole = row.querySelector('.filter-role') ? row.querySelector('.filter-role').textContent.toLowerCase().trim() : "";
                
                const matchSearch = rowText.includes(txt);
                const matchRole = role === "" || rowRole === role;
                
                row.style.display = (matchSearch && matchRole) ? "" : "none";
            });
        }

        if (searchInput) searchInput.addEventListener('keyup', filterUsers);
        if (filterRole) filterRole.addEventListener('change', filterUsers);

        document.body.addEventListener('click', function(e) {
            const btnToggle = e.target.closest('.btn-toggle-status');
            
            if (btnToggle) {
                const id = btnToggle.dataset.id;
                const statusSekarang = btnToggle.dataset.status;
                
                const statusBaru = (statusSekarang === 'Aktif') ? 'Nonaktif' : 'Aktif';
                const aksiTeks = (statusBaru === 'Nonaktif') ? 'Nonaktifkan / Kunci' : 'Aktifkan / Buka Kunci';
                const warnaKonfirmasi = (statusBaru === 'Nonaktif') ? '#d33' : '#10b981';

                Swal.fire({
                    title: 'Ubah Status Akun?',
                    text: `Yakin ingin ${aksiTeks} akun ini?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: warnaKonfirmasi,
                    confirmButtonText: 'Ya, Ubah!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('act', 'update_status');
                        formData.append('id', id);
                        formData.append('status_baru', statusBaru);
                        formData.append('csrf_token', getCsrfToken());

                        fetch('proses_master_akun.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            if (data.trim() === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: 'Status akun diperbarui!',
                                    timer: 1000,
                                    showConfirmButton: false
                                });

                                if (statusBaru === 'Aktif') {
                                    btnToggle.className = 'btn-action btn-toggle-status text-success';
                                    btnToggle.innerHTML = '<i class="fas fa-unlock"></i>';
                                    btnToggle.dataset.status = 'Aktif';
                                    btnToggle.title = 'Nonaktifkan Akun';
                                } else {
                                    btnToggle.className = 'btn-action btn-toggle-status text-danger';
                                    btnToggle.innerHTML = '<i class="fas fa-lock"></i>';
                                    btnToggle.dataset.status = 'Nonaktif';
                                    btnToggle.title = 'Aktifkan Akun';
                                }

                                const badge = document.getElementById(`badge-status-${id}`);
                                if (badge) {
                                    if (statusBaru === 'Aktif') {
                                        badge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3';
                                        badge.innerHTML = '<i class="fas fa-check-circle small me-1"></i> Aktif';
                                    } else {
                                        badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3';
                                        badge.innerHTML = '<i class="fas fa-ban small me-1"></i> Nonaktif';
                                    }
                                }
                                
                                const btnEdit = btnToggle.parentElement.querySelector('.btn-edit');
                                if(btnEdit) {
                                    btnEdit.dataset.status = statusBaru;
                                }

                            } else {
                                Swal.fire('Error', 'Gagal update database.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
                        });
                    }
                });
            }

            if (e.target.closest('#btnTambah')) {
                document.getElementById('formAkun').reset();
                document.getElementById('modalTitle').innerText = "Tambah Akun";
                document.getElementById('inputAct').value = 'tambah';
                document.getElementById('inputId').value = '';

                const passInput = document.getElementById('inputPassword');
                passInput.required = true;
                document.getElementById('passwordHelp').classList.add('d-none');
                
                modalInstance.show();
            }
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                const d = btnEdit.dataset;
                document.getElementById('modalTitle').innerText = "Edit Akun";
                document.getElementById('inputAct').value = 'edit';
                document.getElementById('inputId').value = d.id;
                document.getElementById('inputNama').value = d.nama;
                document.getElementById('inputUsername').value = d.username;
                document.getElementById('inputRole').value = d.role;
                document.getElementById('inputStatus').value = d.status;
                const passInput = document.getElementById('inputPassword');
                passInput.value = '';
                passInput.required = false; 
                document.getElementById('passwordHelp').classList.remove('d-none');

                modalInstance.show();
            }
            const btnDelete = e.target.closest('.btn-delete');
            if (btnDelete) konfirmasiHapus(`proses_master_akun.php?act=hapus&id=${btnDelete.dataset.id}`);
        });
    }

    const modalShift = document.getElementById('modalShift');
    if (modalShift) {
        document.body.appendChild(modalShift);
        fixModalBackdrop(modalShift);
        const modalInstance = new bootstrap.Modal(modalShift);

        document.body.addEventListener('click', function(e) {
            if (e.target.closest('#btnTambah')) {
                document.getElementById('formShift').reset();
                document.getElementById('modalTitle').innerText = "Shift Baru";
                document.getElementById('inputAct').value = 'tambah';
                document.getElementById('inputId').value = '';
                modalInstance.show();
            }
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                const d = btnEdit.dataset;
                document.getElementById('modalTitle').innerText = "Edit Shift";
                document.getElementById('inputAct').value = 'edit';
                document.getElementById('inputId').value = d.id;
                document.getElementById('inputNama').value = d.nama;
                document.getElementById('inputMasuk').value = d.masuk;
                document.getElementById('inputPulang').value = d.pulang;
                modalInstance.show();
            }
            const btnDelete = e.target.closest('.btn-delete');
            if (btnDelete) konfirmasiHapus(`proses_master_shift.php?act=hapus&id=${btnDelete.dataset.id}`);
        });
    }
});