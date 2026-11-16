-- =====================================================================
-- Migrasi: Modul Peminjaman/Pemesanan Ruang (idempotent, aman dijalankan
-- berulang). Jalankan pada database aplikasi (pengadaan_lju):
--   mysql -u USER -p pengadaan_lju < database/migrate_room_bookings.sql
-- Atau lewat phpMyAdmin: import file ini.
-- Isi:
--   1) Tabel room_bookings       : pengajuan peminjaman/pemesanan ruang
--   2) Tabel room_booking_changes: riwayat perubahan jadwal & pembatalan
--   3) Kolom locations.can_book  : penanda ruangan bisa dipinjam/booking
-- =====================================================================

-- 1) Tabel pemesanan ruang
CREATE TABLE IF NOT EXISTS room_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_no VARCHAR(40) NOT NULL UNIQUE,           -- RU-YYYY-0001
  location_id INT NOT NULL,                         -- ruangan (locations.type='ruang' + can_book=1)
  user_id INT NOT NULL,                             -- pemohon
  purpose TEXT NOT NULL,                            -- keperluan penggunaan ruang
  participants INT NULL,                            -- jumlah orang (opsional)
  note TEXT,                                        -- catatan pemohon
  start_at DATETIME NOT NULL,                       -- mulai pemakaian
  end_at DATETIME NOT NULL,                         -- selesai pemakaian
  status ENUM('MENUNGGU','DISETUJUI','DIGUNAKAN','SELESAI','DIBATALKAN','DITOLAK') NOT NULL DEFAULT 'MENUNGGU',
  approved_by INT NULL, approve_note TEXT, rejected_at DATETIME NULL,
  cancelled_by INT NULL, cancelled_at DATETIME NULL, cancel_reason TEXT,
  completed_at DATETIME NULL,
  reschedule_count INT NOT NULL DEFAULT 0,          -- berapa kali jadwal diubah
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (location_id) REFERENCES locations(id)
) ENGINE=InnoDB;

-- 2) Riwayat perubahan jadwal (RESCHEDULE) dan pembatalan (BATALKAN)
CREATE TABLE IF NOT EXISTS room_booking_changes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  change_type ENUM('RESCHEDULE','BATALKAN') NOT NULL DEFAULT 'RESCHEDULE',
  old_start_at DATETIME NULL,
  old_end_at DATETIME NULL,
  new_start_at DATETIME NULL,
  new_end_at DATETIME NULL,
  note TEXT,                                        -- alasan/keterangan
  changed_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES room_bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3) Kolom penanda ruangan bisa dipinjam pada tabel locations
--    (MySQL lama tidak punya ADD COLUMN IF NOT EXISTS -> pakai prosedur)
DROP PROCEDURE IF EXISTS fix_room_booking;
DELIMITER $$
CREATE PROCEDURE fix_room_booking()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = 'locations'
                   AND column_name = 'can_book') THEN
    ALTER TABLE locations ADD COLUMN can_book TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;
  END IF;
END$$
DELIMITER ;

CALL fix_room_booking();
DROP PROCEDURE IF EXISTS fix_room_booking;

-- 4) Aktifkan peminjaman untuk contoh ruangan bawaan (idempotent)
UPDATE locations SET can_book = 1 WHERE type = 'ruang' AND is_active = 1 AND can_book = 0;

SELECT 'Migrasi room_bookings selesai. Struktur:' AS info;
SHOW COLUMNS FROM room_bookings;
SHOW COLUMNS FROM room_booking_changes;
