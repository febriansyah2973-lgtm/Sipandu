document.addEventListener('DOMContentLoaded', function() {
    // 1. Notifikasi Status dengan SweetAlert 2 Premium Style
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');

    if (status === 'updated') {
        Swal.fire({
            icon: 'success',
            title: 'Profil Diperbarui!',
            text: 'Data lo udah aman tersimpan di sistem.',
            showConfirmButton: false,
            timer: 2000,
            background: '#fff',
            customClass: { popup: 'rounded-4 shadow' }
        });
    } else if (status === 'missmatch') {
        Swal.fire({
            icon: 'error',
            title: 'Password Salah!',
            text: 'Konfirmasi password barunya nggak cocok, cek lagi ya.',
            confirmButtonColor: '#4338ca'
        });
    }

    // 2. Real-time Image Preview
    const inputFoto = document.getElementById('inputFoto');
    const previewFoto = document.getElementById('previewFoto');

    if (inputFoto) {
        inputFoto.onchange = function() {
            const [file] = inputFoto.files;
            if (file) {
                previewFoto.src = URL.createObjectURL(file);
                // Tambahkan efek kilat saat ganti foto
                previewFoto.style.opacity = '0.5';
                setTimeout(() => { previewFoto.style.opacity = '1'; }, 300);
            }
        };
    }
});