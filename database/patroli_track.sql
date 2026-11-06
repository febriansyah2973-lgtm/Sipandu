-- Log titik GPS petugas (jejak rute). Dicatat berkala oleh ajax/update_lokasi_petugas.php.
CREATE TABLE IF NOT EXISTS patroli_track (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    latitude    DECIMAL(10,7) NOT NULL,
    longitude   DECIMAL(10,7) NOT NULL,
    accuracy_m  FLOAT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_time (user_id, recorded_at),
    CONSTRAINT fk_track_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Titik patroli yang dilewati sudah ada di tabel `patroli`
-- (user_id, checkpoint_id, waktu_scan, koordinat) dan dipakai untuk polyline + durasi antar checkpoint.
