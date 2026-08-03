document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('searchPinjam');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#tablePinjam tbody tr');
            
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    const selectBarang = document.getElementById('pilih_barang');
    if(selectBarang) {
        selectBarang.addEventListener('change', cekStok);
    }
});

function cekStok() {
    var select = document.getElementById('pilih_barang');
    var selectedOption = select.options[select.selectedIndex];
    var stokTersedia = parseInt(selectedOption.getAttribute('data-stok')) || 0;
    var inputJumlah = document.getElementById('jumlah_pinjam');
    var infoStok = document.getElementById('stok_info');
    var btnSubmit = document.getElementById('btnSubmitPinjam');
    inputJumlah.setAttribute('max', stokTersedia);
    
    if (stokTersedia > 0) {
        infoStok.className = "form-text text-success small mt-1 fw-bold";
        infoStok.innerHTML = `<i class="fas fa-check-circle me-1"></i>Tersedia: ${stokTersedia} unit`;
        
        inputJumlah.value = 1;
        inputJumlah.disabled = false;
        
        if(btnSubmit) btnSubmit.disabled = false;
    } else {
        if(select.value === "") {
            infoStok.innerHTML = "";
            inputJumlah.value = "";
        } else {
            infoStok.className = "form-text text-danger small mt-1 fw-bold";
            infoStok.innerHTML = `<i class="fas fa-times-circle me-1"></i>Stok Habis / Kosong!`;
            inputJumlah.value = 0;
        }
        
        inputJumlah.disabled = true;
        if(btnSubmit) btnSubmit.disabled = true;
    }
}