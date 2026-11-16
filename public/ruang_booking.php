<?php
/**
 * Peminjaman / Pemesanan Ruang - PT. Lampung Jasa Utama
 * Alur (serupa Peminjaman Kendaraan):
 *   MENUNGGU -> (persetujuan Bagian/staff Pengadaan) DISETUJUI -> DIGUNAKAN -> SELESAI
 *   atau DITOLAK / DIBATALKAN.
 * Pemohon dapat MEMBATALKAN (selama MENUNGGU/DISETUJUI dan belum mulai) serta
 * MENGUBAH JADWAL (reschedule, tercatat di room_booking_changes; bila jadwal yang
 * sudah disetujui diubah oleh pemohon maka kembali MENUNGGU untuk persetujuan ulang).
 * Ruangan yang bisa dipilih berasal dari Data Lokasi (type='ruang' + can_book=1).
 * Data pemakaian ruang dalam bentuk kalender: ruang_booking_kalender.php.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Peminjaman Ruang'; $active = 'ruang_booking';

$canManage = is_in_role(['admin', 'staff_pengadaan']);

/* ---- Deteksi kesiapan struktur DB (migrasi database/migrate_room_bookings.sql) ---- */
$hasTable = false;
$hasCanBook = false;
try { $hasTable = (bool)q("SHOW TABLES LIKE 'room_bookings'")->fetch(); } catch (Throwable $e) {}
try { $hasCanBook = (bool)q("SHOW COLUMNS FROM locations LIKE 'can_book'")->fetch(); } catch (Throwable $e) {}

/* ---- Ruangan yang dapat dipinjam: type='ruang' + can_book=1 + aktif ---- */
$roomsAvail = [];
if ($hasCanBook) {
    try {
        $roomsAvail = q("SELECT id, name, code, description FROM locations
                         WHERE type='ruang' AND can_book=1 AND is_active=1 ORDER BY name")->fetchAll();
    } catch (Throwable $e) {}
}

/* ---- Peta ruangan dengan fasilitas rusak/diperbaiki (untuk peringatan & highlight) ---- */
$brokenRooms = []; // location_id => 'nama fasilitas (status), ...'
try {
    foreach (q("SELECT location_id, GROUP_CONCAT(CONCAT(name,' (',status,')') SEPARATOR ', ') AS nm
                FROM facilities WHERE location_id IS NOT NULL
                  AND status IN ('RUSAK','BERMASALAH','PERBAIKAN') GROUP BY location_id")->fetchAll() as $r) {
        $brokenRooms[(int)$r['location_id']] = $r['nm'];
    }
    foreach (q("SELECT location_id, GROUP_CONCAT(CONCAT(problem_type,' (',status,')') SEPARATOR ', ') AS nm
                FROM facility_tickets WHERE location_id IS NOT NULL AND status <> 'SELESAI'
                GROUP BY location_id")->fetchAll() as $r) {
        $lid = (int)$r['location_id'];
        $brokenRooms[$lid] = isset($brokenRooms[$lid]) ? $brokenRooms[$lid] . ' · Tiket: ' . $r['nm'] : 'Tiket keluhan: ' . $r['nm'];
    }
} catch (Throwable $e) { /* tabel fasilitas/tiket belum ada */ }

/* ================= POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$hasTable) { flash_set('warning', 'Tabel room_bookings belum ada. Jalankan migrasi database/migrate_room_bookings.sql terlebih dahulu.'); header('Location: ruang_booking.php'); exit; }

    $act  = $_POST['action'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);
    $back = (int)($_POST['back'] ?? 0); // id booking bila diposting dari halaman detail
    $redirect = $back ? 'ruang_booking_detail.php?id=' . $back : 'ruang_booking.php';

    /* ---- Ajukan pemesanan ruang (semua pegawai) ---- */
    if ($act === 'ajukan') {
        $locId        = (int)($_POST['location_id'] ?? 0);
        $startAt      = norm_datetime($_POST['start_at'] ?? '');
        $endAt        = norm_datetime($_POST['end_at'] ?? '');
        $purpose      = trim($_POST['purpose'] ?? '');
        $participants = ($_POST['participants'] ?? '') !== '' ? (int)$_POST['participants'] : null;
        $note         = trim($_POST['note'] ?? '');
        $errors = [];

        $room = $hasCanBook
            ? q("SELECT * FROM locations WHERE id=? AND type='ruang' AND can_book=1 AND is_active=1", [$locId])->fetch()
            : null;
        if (!$room) $errors[] = 'Ruangan tidak ditemukan / tidak tersedia untuk dipinjam. Aktifkan checkbox "Bisa Dipinjam" pada menu Data Lokasi.';
        if (!$startAt || !$endAt) $errors[] = 'Tanggal & jam pemakaian wajib diisi.';
        elseif (strtotime($endAt) <= strtotime($startAt)) $errors[] = 'Waktu selesai harus setelah waktu mulai.';
        if ($purpose === '') $errors[] = 'Keperluan/penggunaan ruangan wajib diisi.';

        /* Cek bentrok jadwal pada ruangan yang sama */
        if (!$errors) {
            $conflict = q("SELECT booking_no, start_at, end_at FROM room_bookings
                           WHERE location_id=? AND status IN ('MENUNGGU','DISETUJUI','DIGUNAKAN')
                             AND start_at < ? AND end_at > ? ORDER BY start_at LIMIT 1",
                          [$locId, $endAt, $startAt])->fetch();
            if ($conflict) {
                $errors[] = 'Ruangan sudah terpesan pada rentang waktu tersebut (pemesanan ' . $conflict['booking_no'] . ': '
                          . tgl($conflict['start_at'], true) . ' s/d ' . tgl($conflict['end_at'], true) . '). Silakan pilih waktu lain.';
            }
        }

        if (!$errors) {
            $no = gen_no('room_bookings', 'booking_no', 'RU');
            q("INSERT INTO room_bookings (booking_no, location_id, user_id, purpose, participants, note, start_at, end_at, status)
               VALUES (?,?,?,?,?,?,?,?,'MENUNGGU')",
              [$no, $locId, $_SESSION['user_id'], $purpose, $participants, $note, $startAt, $endAt]);
            refresh_notification_cache();
            flash_set('success', 'Pengajuan pemesanan ruang ' . $no . ' terkirim. Menunggu persetujuan Bagian/staff Pengadaan.');
            if (isset($brokenRooms[$locId])) {
                flash_set('warning', 'Perhatian: di ruangan ini terdapat fasilitas rusak/sedang diperbaiki (' . $brokenRooms[$locId] . ').');
            }
            header('Location: ruang_booking.php'); exit;
        }
        flash_set('danger', implode("\n", $errors));
        header('Location: ruang_booking.php#mAjukan'); exit;
    }
    /* ---- Persetujuan / penolakan (staff pengadaan / admin) ---- */
    if ($act === 'approve' || $act === 'reject') {
        require_role(['admin', 'staff_pengadaan']);
        $b = q("SELECT * FROM room_bookings WHERE id=?", [$id])->fetch();
        if ($b && $b['status'] === 'MENUNGGU') {
            if ($act === 'approve') {
                q("UPDATE room_bookings SET status='DISETUJUI', approved_by=?, approve_note=?, rejected_at=NULL WHERE id=?",
                  [$_SESSION['user_id'], trim($_POST['approve_note'] ?? ''), $id]);
                flash_set('success', 'Pemesanan ruang <b>' . e($b['booking_no']) . '</b> disetujui.');
            } else {
                $reason = trim($_POST['reject_reason'] ?? '');
                if ($reason === '') $reason = 'Ditolak oleh Bagian/staff Pengadaan';
                q("UPDATE room_bookings SET status='DITOLAK', approved_by=?, approve_note=?, rejected_at=NOW() WHERE id=?",
                  [$_SESSION['user_id'], $reason, $id]);
                flash_set('info', 'Pemesanan ruang <b>' . e($b['booking_no']) . '</b> ditolak.');
            }
            refresh_notification_cache();
        } else {
            flash_set('warning', 'Pemesanan tidak berada pada status MENUNGGU (mungkin sudah diproses sebelumnya).');
        }
        header('Location: ' . $redirect); exit;
    }

    /* ---- Pembatalan (pemohon selama MENUNGGU/DISETUJUI & belum mulai; staff/admin kapan pun sebelum selesai) ---- */
    if ($act === 'batalkan') {
        $b = q("SELECT * FROM room_bookings WHERE id=?", [$id])->fetch();
        $isOwner = $b && (int)$b['user_id'] === (int)$_SESSION['user_id'];
        $reason  = trim($_POST['cancel_reason'] ?? '');
        if ($reason === '') $reason = $canManage && !$isOwner ? 'Dibatalkan oleh Bagian/staff Pengadaan' : 'Dibatalkan oleh pemohon';

        if ($b && in_array($b['status'], ['MENUNGGU','DISETUJUI','DIGUNAKAN'], true)
              && ($canManage || ($isOwner && in_array($b['status'], ['MENUNGGU','DISETUJUI'], true) && strtotime($b['start_at']) > time()))) {
            q("UPDATE room_bookings SET status='DIBATALKAN', cancelled_by=?, cancelled_at=NOW(), cancel_reason=? WHERE id=?",
              [$_SESSION['user_id'], $reason, $id]);
            q("INSERT INTO room_booking_changes (booking_id, change_type, old_start_at, old_end_at, note, changed_by)
               VALUES (?, 'BATALKAN', ?, ?, ?, ?)",
              [$id, $b['start_at'], $b['end_at'], $reason, $_SESSION['user_id']]);
            refresh_notification_cache();
            flash_set('info', 'Pemesanan <b>' . e($b['booking_no']) . '</b> dibatalkan.');
        } else {
            flash_set('danger', 'Pemesanan tidak dapat dibatalkan (hanya selama MENUNGGU/DISETUJUI dan belum melewati waktu mulai).');
        }
        header('Location: ' . $redirect); exit;
    }

    /* ---- Pengubahan jadwal (reschedule) oleh pemohon atau staff ---- */
    if ($act === 'ubah_jadwal') {
        $b        = q("SELECT * FROM room_bookings WHERE id=?", [$id])->fetch();
        $isOwner  = $b && (int)$b['user_id'] === (int)$_SESSION['user_id'];
        $newStart = norm_datetime($_POST['new_start_at'] ?? '');
        $newEnd   = norm_datetime($_POST['new_end_at'] ?? '');
        $reason   = trim($_POST['reschedule_reason'] ?? '');
        $errors = [];

        if (!$b) $errors[] = 'Pemesanan tidak ditemukan.';
        else {
            if (!in_array($b['status'], ['MENUNGGU','DISETUJUI'], true)) $errors[] = 'Jadwal hanya dapat diubah selama status MENUNGGU atau DISETUJUI.';
            if (!$canManage && !($isOwner && strtotime($b['start_at']) > time())) $errors[] = 'Anda tidak berhak mengubah jadwal pemesanan ini.';
            if (!$newStart || !$newEnd) $errors[] = 'Tanggal & jam baru wajib diisi.';
            elseif (strtotime($newEnd) <= strtotime($newStart)) $errors[] = 'Waktu selesai baru harus setelah waktu mulai baru.';
            if ($reason === '') $errors[] = 'Alasan perubahan jadwal wajib diisi.';
        }

        /* Cek bentrok jadwal baru (pemesanan lain pada ruangan yang sama) */
        if (!$errors) {
            $conflict = q("SELECT booking_no, start_at, end_at FROM room_bookings
                           WHERE location_id=? AND id<>? AND status IN ('MENUNGGU','DISETUJUI','DIGUNAKAN')
                             AND start_at < ? AND end_at > ? ORDER BY start_at LIMIT 1",
                          [$b['location_id'], $id, $newEnd, $newStart])->fetch();
            if ($conflict) {
                $errors[] = 'Ruangan sudah terpesan pada rentang waktu baru (pemesanan ' . $conflict['booking_no'] . ': '
                          . tgl($conflict['start_at'], true) . ' s/d ' . tgl($conflict['end_at'], true) . '). Silakan pilih waktu lain.';
            }
        }

        if (!$errors) {
            /* Jadwal yang sudah DISETUJUI diubah oleh pemohon -> kembali MENUNGGU untuk persetujuan ulang.
               Perubahan oleh staff/admin mempertahankan status pemesanan. */
            $backToWaiting = (!$canManage && $b['status'] === 'DISETUJUI');
            q("UPDATE room_bookings SET start_at=?, end_at=?, status=?, approve_note=?, reschedule_count=reschedule_count+1 WHERE id=?",
              [$newStart, $newEnd,
               $backToWaiting ? 'MENUNGGU' : $b['status'],
               $backToWaiting ? 'Jadwal diubah oleh pemohon - menunggu persetujuan ulang.' : $b['approve_note'],
               $id]);
            q("INSERT INTO room_booking_changes (booking_id, change_type, old_start_at, old_end_at, new_start_at, new_end_at, note, changed_by)
               VALUES (?, 'RESCHEDULE', ?, ?, ?, ?, ?, ?)",
              [$id, $b['start_at'], $b['end_at'], $newStart, $newEnd, $reason, $_SESSION['user_id']]);
            refresh_notification_cache();
            flash_set('success', 'Jadwal pemesanan <b>' . e($b['booking_no']) . '</b> berhasil diubah. '
                     . ($backToWaiting ? 'Status kembali MENUNGGU untuk persetujuan ulang.' : ''));
        }
        if ($errors) flash_set('danger', implode("\n", $errors));
        header('Location: ' . $redirect); exit;
    }

    /* ---- Mulai penggunaan ruang (DISETUJUI -> DIGUNAKAN, staff/admin) ---- */
    if ($act === 'mulai') {
        require_role(['admin', 'staff_pengadaan']);
        $b = q("SELECT * FROM room_bookings WHERE id=?", [$id])->fetch();
        if ($b && $b['status'] === 'DISETUJUI') {
            q("UPDATE room_bookings SET status='DIGUNAKAN' WHERE id=?", [$id]);
            flash_set('success', 'Status pemesanan <b>' . e($b['booking_no']) . '</b> menjadi DIGUNAKAN.');
        } else flash_set('warning', 'Pemesanan tidak berada pada status DISETUJUI.');
        header('Location: ' . $redirect); exit;
    }

    /* ---- Selesaikan penggunaan ruang (DISETUJUI/DIGUNAKAN -> SELESAI, staff/admin) ---- */
    if ($act === 'selesaikan') {
        require_role(['admin', 'staff_pengadaan']);
        $b = q("SELECT * FROM room_bookings WHERE id=?", [$id])->fetch();
        if ($b && in_array($b['status'], ['DISETUJUI','DIGUNAKAN'], true)) {
            q("UPDATE room_bookings SET status='SELESAI', completed_at=NOW() WHERE id=?", [$id]);
            refresh_notification_cache();
            flash_set('success', 'Pemakaian ruang <b>' . e($b['booking_no']) . '</b> selesai.');
        } else flash_set('warning', 'Pemesanan tidak dapat diselesaikan.');
        header('Location: ' . $redirect); exit;
    }

    flash_set('danger', 'Aksi tidak dikenali.');
    header('Location: ruang_booking.php'); exit;
}

/* ================= DATA ================= */
$fStatus = trim($_GET['status'] ?? '');
$fRoom   = (int)($_GET['room'] ?? 0);

$sql = "SELECT rb.*, loc.name AS room_name, loc.code AS room_code,
               uc.name AS pemohon, uc.avatar AS pemohon_avatar, uc.nama_bidang,
               ab.name AS approver
        FROM room_bookings rb
        JOIN locations loc ON loc.id = rb.location_id
        LEFT JOIN user_cache uc ON uc.user_id = rb.user_id
        LEFT JOIN user_cache ab ON ab.user_id = rb.approved_by
        WHERE 1";
$args = [];
if ($fStatus !== '') { $sql .= " AND rb.status = ?"; $args[] = $fStatus; }
if ($fRoom)          { $sql .= " AND rb.location_id = ?"; $args[] = $fRoom; }
if (!$canManage && !is_in_role(['kepala_bidang','kepala_divisi','pimpinan'])) {
    $sql .= " AND rb.user_id = ?"; $args[] = $_SESSION['user_id'];
}
$sql .= " ORDER BY rb.id DESC LIMIT 200";

$rows = [];
$cntWaiting = $cntUpcoming = $cntUsed = 0;
$allRooms = [];
if ($hasTable) {
    $rows = q($sql, $args)->fetchAll();
    $cntWaiting  = count_rows("SELECT COUNT(*) FROM room_bookings WHERE status='MENUNGGU'");
    $cntUpcoming = count_rows("SELECT COUNT(*) FROM room_bookings WHERE status='DISETUJUI' AND end_at >= NOW()");
    $cntUsed     = count_rows("SELECT COUNT(*) FROM room_bookings WHERE status='DIGUNAKAN'");
    $allRooms = q("SELECT id, name FROM locations WHERE type='ruang' ORDER BY name")->fetchAll();
}
$cntRooms = count($roomsAvail);

require __DIR__ . '/../includes/header.php';
?>
<?php if (!$hasTable || !$hasCanBook): ?>
<div class="alert alert-warning">
  ⚠️ Struktur database untuk modul Peminjaman Ruang belum lengkap. Jalankan migrasi
  <b>database/migrate_room_bookings.sql</b> (membuat tabel <i>room_bookings</i>, <i>room_booking_changes</i>,
  dan kolom <i>locations.can_book</i>).
</div>
<?php endif; ?>

<div class="card">
  <h3>📅 Peminjaman / Pemesanan Ruang
    <span style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn btn-sm btn-teal" href="ruang_booking_kalender.php">🗓️ Kalender Pemakaian</a>
      <button class="btn btn-sm btn-primary" onclick="openModal('mAjukan')" <?= $roomsAvail ? '' : 'disabled' ?>>➕ Ajukan Pemesanan Ruang</button>
    </span>
  </h3>
  <div class="grid grid-3" style="margin-bottom:14px">
    <div class="stat-card"><div class="stat-value"><?= $cntWaiting ?></div><div class="stat-label">Menunggu Persetujuan</div></div>
    <div class="stat-card"><div class="stat-value"><?= $cntUpcoming ?></div><div class="stat-label">Disetujui (Mendatang)</div></div>
    <div class="stat-card"><div class="stat-value"><?= $cntUsed ?></div><div class="stat-label">Sedang Digunakan</div></div>
    <div class="stat-card"><div class="stat-value"><?= $cntRooms ?></div><div class="stat-label">Ruang Tersedia untuk Dipinjam</div></div>
    <div class="stat-card"><div class="stat-value" style="color:<?= $brokenRooms ? 'var(--danger)' : 'var(--success)' ?>"><?= count($brokenRooms) ?></div><div class="stat-label">Ruang dgn Fasilitas Rusak/Diperbaiki</div></div>
  </div>
  <form class="filters" method="get">
    <div class="form-group"><label>Ruangan</label>
      <select name="room" onchange="this.form.submit()">
        <option value="">Semua Ruangan</option>
        <?php foreach ($allRooms as $r): ?>
        <option value="<?= (int)$r['id'] ?>" <?= $fRoom === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Status</label>
      <select name="status" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <?php foreach (['MENUNGGU','DISETUJUI','DIGUNAKAN','SELESAI','DIBATALKAN','DITOLAK'] as $s): ?>
        <option value="<?= $s ?>" <?= $fStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <noscript><button class="btn btn-sm">Terapkan</button></noscript>
  </form>
  <div class="table-wrap"><table class="tbl">
    <tr><th>No. Booking</th><th>Ruangan</th><th>Pemohon</th><th>Keperluan</th>
        <th>Waktu Pemakaian</th><th class="num">Jumlah Orang</th><th>Status</th><th></th></tr>
    <?php foreach ($rows as $b): $broken = $brokenRooms[(int)$b['location_id']] ?? null; ?>
    <tr>
      <td><b><a href="ruang_booking_detail.php?id=<?= (int)$b['id'] ?>"><?= e($b['booking_no']) ?></a></b>
          <?php if ((int)$b['reschedule_count'] > 0): ?><br><small class="muted">🔄 jadwal diubah <?= (int)$b['reschedule_count'] ?>x</small><?php endif; ?></td>
      <td><b><?= e($b['room_name']) ?></b><?= $b['room_code'] ? ' <small class="muted">(' . e($b['room_code']) . ')</small>' : '' ?>
          <?php if ($broken): ?><br><span class="badge badge-danger" title="<?= e($broken) ?>">⚠ fasilitas rusak</span><?php endif; ?></td>
      <td><?= avatar_html($b['pemohon_avatar'] ?? null, $b['pemohon'] ?? '?', 24) ?> <b><?= e($b['pemohon'] ?? '-') ?></b><br><small class="muted"><?= e($b['nama_bidang'] ?? '') ?></small></td>
      <td><?= nl2br(e(mb_strimwidth($b['purpose'] ?? '', 0, 60, '…'))) ?></td>
      <td><small><?= date('d/m H:i', strtotime($b['start_at'])) ?> →<br><?= date('d/m H:i', strtotime($b['end_at'])) ?></small></td>
      <td class="num"><?= $b['participants'] !== null ? (int)$b['participants'] : '-' ?></td>
      <td><?= status_badge($b['status']) ?></td>
      <td>
        <?php if ($canManage && $b['status'] === 'MENUNGGU'): ?>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-sm btn-primary" title="Setujui">✔</button>
            </form>
            <form method="post" style="display:inline" onsubmit="return confirm('Tolak pengajuan <?= e($b['booking_no']) ?>?')">
              <input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <input type="hidden" name="reject_reason" value="Ditolak oleh Bagian/staff Pengadaan">
              <button class="btn btn-sm btn-danger" title="Tolak">✖</button>
            </form>
            <a class="btn btn-sm btn-teal" href="ruang_booking_detail.php?id=<?= (int)$b['id'] ?>">Detail</a>
          </div>
        <?php elseif ($canManage && $b['status'] === 'DIGUNAKAN'): ?>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="selesaikan"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-sm btn-warning" title="Tandai selesai">✔ Selesaikan</button>
            </form>
            <a class="btn btn-sm btn-teal" href="ruang_booking_detail.php?id=<?= (int)$b['id'] ?>">Detail</a>
          </div>
        <?php elseif ($canManage && $b['status'] === 'DISETUJUI'): ?>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="mulai"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-sm btn-primary" title="Tandai sedang digunakan">▶ Mulai</button>
            </form>
            <a class="btn btn-sm btn-teal" href="ruang_booking_detail.php?id=<?= (int)$b['id'] ?>">Detail</a>
          </div>
        <?php else: ?>
          <?php
            $isOwnerRow = (int)$b['user_id'] === (int)$_SESSION['user_id'];
            $canCancelRow  = $isOwnerRow && in_array($b['status'], ['MENUNGGU','DISETUJUI'], true) && strtotime($b['start_at']) > time();
            $canReschedRow = $isOwnerRow && in_array($b['status'], ['MENUNGGU','DISETUJUI'], true) && strtotime($b['start_at']) > time();
          ?>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a class="btn btn-sm btn-teal" href="ruang_booking_detail.php?id=<?= (int)$b['id'] ?>">Detail</a>
            <?php if ($canReschedRow): ?><a class="btn btn-sm btn-outline" href="ruang_booking_detail.php?id=<?= (int)$b['id'] ?>#ubahjadwal">🔄 Ubah Jadwal</a><?php endif; ?>
            <?php if ($canCancelRow): ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Batalkan pemesanan <?= e($b['booking_no']) ?>?')">
              <input type="hidden" name="action" value="batalkan"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <input type="hidden" name="cancel_reason" value="Dibatalkan oleh pemohon">
              <button class="btn btn-sm btn-outline">Batal</button>
            </form>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; if (!$rows): ?>
    <tr><td colspan="8"><div class="empty-state"><div class="big">📅</div>Belum ada data pemesanan ruang.</div></td></tr><?php endif; ?>
  </table></div>
</div>

<!-- Dialog pengajuan dibuat di level dokumen (di luar .card) agar rendering stabil -->
<div class="modal-backdrop" id="mAjukan"><div class="modal" style="max-width:720px">
  <h3>🚪 Ajukan Pemesanan / Peminjaman Ruang</h3>
  <?php if (!$roomsAvail): ?>
    <div class="alert alert-warning">Belum ada ruangan yang bisa dipinjam. Tandai ruangan pada menu <b>Data Lokasi</b> (checkbox "Bisa Dipinjam").</div>
  <?php endif; ?>
  <div class="alert alert-danger" id="roomBrokenWarn" style="display:none"></div>
  <form method="post">
    <input type="hidden" name="action" value="ajukan">
    <div class="form-row">
      <div class="form-group" style="flex:2"><label>Ruangan yang Dipinjam *</label>
        <select name="location_id" id="selRoom" required>
          <option value="">- Pilih Ruangan -</option>
          <?php foreach ($roomsAvail as $r): $broken = $brokenRooms[(int)$r['id']] ?? null; ?>
          <option value="<?= (int)$r['id'] ?>" data-broken="<?= e($broken ?? '') ?>">
            <?= e($r['name']) ?><?= $r['code'] ? ' (' . e($r['code']) . ')' : '' ?><?= $broken ? ' ⚠ ada fasilitas rusak' : '' ?>
          </option>
          <?php endforeach; ?>
        </select>
        <small class="muted">Hanya ruangan yang ditandai "Bisa Dipinjam" pada Data Lokasi yang muncul di sini.</small>
      </div>
      <div class="form-group"><label>Jumlah Orang</label><input type="number" name="participants" min="1" placeholder="mis. 12"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Mulai Pemakaian (tgl &amp; jam) *</label><input type="datetime-local" name="start_at" required></div>
      <div class="form-group"><label>Selesai Pemakaian (tgl &amp; jam) *</label><input type="datetime-local" name="end_at" required></div>
    </div>
    <div class="form-group"><label>Keperluan / Penggunaan Ruangan *</label><textarea name="purpose" rows="2" required placeholder="Contoh: Rapat evaluasi bulanan Divisi Operasional"></textarea></div>
    <div class="form-group"><label>Catatan</label><input type="text" name="note" placeholder="Opsional: kebutuhan proyektor, sound system, dll."></div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button type="button" class="btn btn-outline" onclick="closeModal('mAjukan')">Batal</button>
      <button class="btn btn-primary" <?= $roomsAvail ? '' : 'disabled' ?>>📮 Kirim Pengajuan</button>
    </div>
  </form>
</div></div>
<script>
(function () {
  var selRoom = document.getElementById('selRoom');
  var warnBox = document.getElementById('roomBrokenWarn');
  if (!selRoom) return;

  // Peringatan bila ruangan terpilih memiliki fasilitas rusak/diperbaiki
  function syncBrokenWarn() {
    var b = selRoom.options[selRoom.selectedIndex] ? selRoom.options[selRoom.selectedIndex].getAttribute('data-broken') : null;
    if (b) {
      warnBox.textContent = '⚠️ Ada fasilitas di ruangan ini yang rusak / sedang diperbaiki: ' + b + '.';
      warnBox.style.display = 'block';
    } else {
      warnBox.style.display = 'none';
    }
  }
  selRoom.addEventListener('change', syncBrokenWarn);

  // Auto-buka dialog & pilih ruangan jika datang dari halaman lain (?room=ID)
  // atau setelah error validasi (redirect ke ruang_booking.php#mAjukan)
  var rid = new URLSearchParams(location.search).get('room');
  if (rid && selRoom.querySelector('option[value="' + rid + '"]')) {
    selRoom.value = rid;
    selRoom.dispatchEvent(new Event('change'));
    openModal('mAjukan');
  } else if (location.hash === '#mAjukan') {
    openModal('mAjukan');
    syncBrokenWarn();
  } else {
    syncBrokenWarn();
  }
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
