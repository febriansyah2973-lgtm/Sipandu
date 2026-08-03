function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
}
document.addEventListener("DOMContentLoaded", function() {
    const overlay = document.getElementById('overlay');
    if(overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
});