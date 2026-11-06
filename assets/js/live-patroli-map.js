/**
 * Live monitoring patroli (Leaflet).
 * Butuh: leaflet.js, elemen #map, dan endpoint ajax/patroli_route.php
 */
(function () {
    const map = L.map('map').setView([-6.2, 106.816], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const personIcon = L.divIcon({
        className: '',
        html: '<div style="font-size:26px;color:#0d6efd;text-shadow:0 0 3px #fff"><i class="fas fa-person-walking"></i></div>',
        iconSize: [26, 26], iconAnchor: [13, 26]
    });
    const colors = ['#e63946', '#2a9d8f', '#f4a261', '#6a4c93', '#1d3557'];
    const layer = L.layerGroup().addTo(map);
    let fitted = false;

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // Selisih menit antar dua waktu "YYYY-MM-DD HH:mm:ss"
    function selisihMenit(a, b) {
        const t = s => new Date(s.replace(' ', 'T')).getTime();
        return Math.round((t(b) - t(a)) / 60000);
    }

    function draw(res) {
        layer.clearLayers();
        const bounds = [];

        res.rute.forEach((p, idx) => {
            const color = colors[idx % colors.length];
            const pts = p.titik;
            const latlngs = pts.map(t => [t.lat, t.lng]);
            if (latlngs.length > 1) L.polyline(latlngs, { color, weight: 4, opacity: 0.8 }).addTo(layer);

            pts.forEach((t, i) => {
                L.circleMarker([t.lat, t.lng], { radius: 6, color, fillColor: '#fff', fillOpacity: 1, weight: 3 })
                    .bindPopup(`<b>${i + 1}. ${esc(t.lokasi)}</b><br>${esc(p.nama)}<br>${esc(t.waktu)}`)
                    .addTo(layer);
                bounds.push([t.lat, t.lng]);

                // Label durasi di tengah segmen Titik i-1 -> i
                if (i > 0) {
                    const prev = pts[i - 1];
                    const mnt = selisihMenit(prev.waktu, t.waktu);
                    L.marker([(prev.lat + t.lat) / 2, (prev.lng + t.lng) / 2], {
                        icon: L.divIcon({
                            className: '',
                            html: `<span style="background:${color};color:#fff;padding:2px 6px;border-radius:10px;font-size:11px;white-space:nowrap">${mnt} mnt</span>`
                        }),
                        interactive: false
                    }).addTo(layer);
                }
            });
        });

        res.posisi.forEach(u => {
            L.marker([+u.latitude, +u.longitude], { icon: personIcon })
                .bindPopup(`<b>${esc(u.nama_lengkap)}</b><br>Terakhir: ${esc(u.last_seen)}`)
                .addTo(layer);
            bounds.push([+u.latitude, +u.longitude]);
        });

        if (bounds.length && !fitted) { map.fitBounds(bounds, { padding: [40, 40] }); fitted = true; }
    }

    function load() {
        fetch('ajax/patroli_route.php')
            .then(r => r.json())
            .then(res => { if (res.status === 'success') draw(res); })
            .catch(() => {});
    }

    load();
    setInterval(load, 10000);
})();
