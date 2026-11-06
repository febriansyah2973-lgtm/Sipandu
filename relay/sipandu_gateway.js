const WebSocket = require('ws');
const { spawn } = require('child_process');
const mysql = require('mysql2');

const db = mysql.createPool({
    host: 'localhost', user: 'root', password: '', database: 'db_sipandu',
    waitForConnections: true, connectionLimit: 10
});

const wss = new WebSocket.Server({ port: 8000 });
console.log("?? GATEWAY SIPANDU: MODE MULTIPLEXER (HEMAT CPU & STABIL 25 FPS)");

// WADAH PENYIMPANAN: 1 Kamera = 1 FFmpeg = Banyak Penonton
const streams = {};

wss.on('connection', (ws, req) => {
    const camId = req.url.replace('/', '');
    console.log(`?? Client masuk minta CH/ID: ${camId}`);

    // 1. Jika wadah kamera ini belum ada, buatkan
    if (!streams[camId]) {
        streams[camId] = {
            process: null,
            clients: new Set() // Menyimpan daftar siapa saja yang nonton
        };
    }

    // 2. Masukkan client baru ini ke daftar penonton
    streams[camId].clients.add(ws);

    // 3. CEK FFMPEG: Kalau FFmpeg belum jalan, nyalakan! Kalau sudah jalan, Nebeng!
    if (!streams[camId].process) {
        console.log(`?? Menyalakan FFmpeg CH/ID: ${camId} (Penonton Pertama)`);
        startFFmpeg(camId);
    } else {
        console.log(`?? Nebeng FFmpeg CH/ID: ${camId} (Total Penonton: ${streams[camId].clients.size})`);
    }

    // 4. JIKA ADA PENONTON KELUAR (Tutup Browser)
    ws.on('close', () => {
        if (streams[camId]) {
            // Hapus client dari daftar
            streams[camId].clients.delete(ws);
            console.log(`?? Client keluar. Sisa penonton CH/ID ${camId}: ${streams[camId].clients.size}`);

            // FITUR PENGHEMAT CPU: Jika yang nonton sudah habis (0), matikan FFmpeg-nya!
            if (streams[camId].clients.size === 0) {
                console.log(`?? Tidak ada penonton CH/ID: ${camId}. FFmpeg DIMATIKAN untuk hemat CPU!`);
                if (streams[camId].process) {
                    streams[camId].process.kill();
                }
                delete streams[camId]; // Bersihkan dari memori
            }
        }
    });
});

function startFFmpeg(camId) {
    db.query('SELECT * FROM cctv_list WHERE id = ?', [camId], (err, results) => {
        // Cek apakah kamera ada dan MASIH ada penonton yang menunggu
        if (err || results.length === 0 || !streams[camId]) return;
        
        const cam = results[0];
        const user = encodeURIComponent(cam.username.trim());
        const pass = encodeURIComponent(cam.password.trim());
        const ip = cam.ip_address.trim();
        
        const rtspUrl = `rtsp://${user}:${pass}@${ip}:554/cam/realmonitor?channel=${cam.channel}&subtype=1`;

        // MENGGUNAKAN SETTINGAN FFMPEG BAPAK YANG SUDAH TERBUKTI STABIL
        const ffmpeg = spawn('ffmpeg', [
            '-rtsp_transport', 'tcp',
            '-i', `"${rtspUrl}"`,
            '-f', 'mpegts', 
            '-codec:v', 'mpeg1video',
            '-s', '640x480', 
            '-b:v', '800k', 
            '-r', '25', // <--- Angka Keramat Bapak
            '-bf', '0',
            '-an', '-'
        ], { 
            shell: true, 
            windowsHide: true 
        });

        // Simpan mesin FFmpeg ini ke dalam wadah
        streams[camId].process = ffmpeg;

        // BROADCAST: Fotokopi gambar video dan kirim ke SEMUA penonton yang ada di daftar
        ffmpeg.stdout.on('data', (data) => {
            if (streams[camId] && streams[camId].clients) {
                streams[camId].clients.forEach((client) => {
                    if (client.readyState === WebSocket.OPEN) {
                        client.send(data);
                    }
                });
            }
        });

        // JIKA FFMPEG MATI SENDIRI (Misal NVR Restart / Kabel Putus)
        ffmpeg.on('exit', (code) => {
            if (code !== 0 && code !== null) {
                console.log(`?? [ID:${camId}] FFmpeg Exit Code: ${code}.`);
            }
            
            if (streams[camId]) {
                streams[camId].process = null;
                // Selama masih ada penonton yang buka halaman, usahakan terus untuk reconnect!
                if (streams[camId].clients.size > 0) {
                    console.log(`?? Re-connecting CH/ID ${camId} dalam 5 detik...`);
                    setTimeout(() => startFFmpeg(camId), 5000);
                }
            }
        });
    });
}