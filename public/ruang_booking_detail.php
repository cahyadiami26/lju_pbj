<?php
/**
 * Detail Pemesanan/Peminjaman Ruang: informasi pemesanan, persetujuan
 * (staff/admin pengadaan), pembatalan, dan pengubahan jadwal (reschedule).
 * Semua aksi diposting ke ruang_booking.php (handler terpusat) dengan
 * parameter `back` agar kembali ke halaman detail ini.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$b = q("SELECT rb.*, loc.name AS room_name, loc.code AS room_code, loc.description AS room_desc,
               uc.name AS pemohon, uc.avatar AS pemohon_avatar, uc.position AS pemohon_jab, uc.nama_bidang,
               ab.name AS approver, cb.name AS canceller
        FROM room_bookings rb
        JOIN locations loc ON loc.id = rb.location_id
        LEFT JOIN user_cache uc ON uc.user_id = rb.user_id
        LEFT JOIN user_cache ab ON ab.user_id = rb.approved_by
        LEFT JOIN user_cache cb ON cb.user_id = rb.cancelled_by
        WHERE rb.id=?", [$id])->fetch();
if (!$b) {
    flash_set('danger', 'Pemesanan ruang tidak ditemukan.');
    header('Location: ruang_booking.php'); exit;
}

$canManage = is_in_role(['admin', 'staff_pengadaan']);
$isOwner   = (int)$b['user_id'] === (int)$_SESSION['user_id'];

/* Hak aksi sesuai status & waktu mulai */
$canCancel  = in_array($b['status'], ['MENUNGGU','DISETUJUI','DIGUNAKAN'], true)
              && ($canManage || ($isOwner && in_array($b['status'], ['MENUNGGU','DISETUJUI'], true) && strtotime($b['start_at']) > time()));
$canResched = in_array($b['status'], ['MENUNGGU','DISETUJUI'], true)
              && ($canManage || ($isOwner && strtotime($b['start_at']) > time()));

$changes = q("SELECT c.*, uc.name AS changer FROM room_booking_changes c
              LEFT JOIN user_cache uc ON uc.user_id = c.changed_by
              WHERE c.booking_id=? ORDER BY c.id DESC", [$id])->fetchAll();

/* Fasilitas ruangan yang sedang rusak/diperbaiki */
$brokenNames = [];
try {
    foreach (q("SELECT name, status FROM facilities WHERE location_id=? AND status IN ('RUSAK','BERMASALAH','PERBAIKAN')", [$b['location_id']])->fetchAll() as $f) {
        $brokenNames[] = $f['name'] . ' (' . $f['status'] . ')';
    }
    foreach (q("SELECT problem_type, status FROM facility_tickets WHERE location_id=? AND status <> 'SELESAI'", [$b['location_id']])->fetchAll() as $t) {
        $brokenNames[] = 'Tiket ' . $t['problem_type'] . ' (' . $t['status'] . ')';
    }
} catch (Throwable $e) {}

/* Riwayat pemakaian ruangan terkait */
$roomRecent = q("SELECT rb.*, uc.name AS pemohon FROM room_bookings rb
                 LEFT JOIN user_cache uc ON uc.user_id = rb.user_id
                 WHERE rb.location_id=? ORDER BY rb.id DESC LIMIT 10", [$b['location_id']])->fetchAll();

$pageTitle = 'Pemesanan Ruang ' . $b['booking_no']; $active = 'ruang_booking';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($brokenNames): ?>
<div class="alert alert-danger">⚠️ <b>Perhatian:</b> terdapat fasilitas di ruangan <b><?= e($b['room_name']) ?></b> yang sedang rusak/diperbaiki: <?= e(implode(', ', $brokenNames)) ?>. Tanggal pemakaian di ruangan ini akan di-highlight merah pada kalender.</div>
<?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <h3>🚪 <?= e($b['booking_no']) ?> <?= status_badge($b['status']) ?></h3>
    <table class="tbl">
      <tr><th>Ruangan</th><td><b><?= e($b['room_name']) ?></b><?= $b['room_code'] ? ' (' . e($b['room_code']) . ')' : '' ?>
        <?php if ($b['room_desc']): ?><br><small class="muted"><?= e($b['room_desc']) ?></small><?php endif; ?></td></tr>
      <tr><th>Pemohon</th><td><?= avatar_html($b['pemohon_avatar'] ?? null, $b['pemohon'] ?? '?', 28) ?> <b><?= e($b['pemohon'] ?? '-') ?></b>
        <br><small class="muted"><?= e($b['pemohon_jab'] ?? '') ?><?= $b['nama_bidang'] ? ' · ' . e($b['nama_bidang']) : '' ?></small></td></tr>
      <tr><th>Keperluan</th><td><?= nl2br(e($b['purpose'])) ?></td></tr>
      <tr><th>Jumlah Orang</th><td><?= $b['participants'] !== null ? (int)$b['participants'] . ' orang' : '-' ?></td></tr>
      <tr><th>Waktu Pemakaian</th><td><b><?= tgl($b['start_at'], true) ?></b> → <b><?= tgl($b['end_at'], true) ?></b></td></tr>
      <tr><th>Diajukan</th><td><?= tgl($b['created_at'], true) ?></td></tr>
      <?php if ($b['status'] !== 'MENUNGGU' && $b['approver']): ?>
      <tr><th>Persetujuan</th><td><b><?= e($b['approver']) ?></b><?= $b['approve_note'] ? ' — ' . e($b['approve_note']) : '' ?></td></tr>
      <?php endif; ?>
      <?php if ((int)$b['reschedule_count'] > 0): ?>
      <tr><th>Jadwal Diubah</th><td><?= (int)$b['reschedule_count'] ?>x (lihat riwayat di bawah)</td></tr>
      <?php endif; ?>
      <?php if ($b['status'] === 'DIBATALKAN'): ?>
      <tr><th>Dibatalkan</th><td><?= $b['cancelled_at'] ? tgl($b['cancelled_at'], true) : '-' ?> oleh <b><?= e($b['canceller'] ?? '-') ?></b>
        <?= $b['cancel_reason'] ? '<br><small class="muted">Alasan: ' . e($b['cancel_reason']) . '</small>' : '' ?></td></tr>
      <?php endif; ?>
      <?php if ($b['completed_at']): ?>
      <tr><th>Selesai</th><td><?= tgl($b['completed_at'], true) ?></td></tr>
      <?php endif; ?>
      <?php if ($b['note']): ?>
      <tr><th>Catatan Pemohon</th><td><?= nl2br(e($b['note'])) ?></td></tr>
      <?php endif; ?>
    </table>
    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn btn-sm btn-teal" href="ruang_booking_kalender.php?room=<?= (int)$b['location_id'] ?>&y=<?= date('Y', strtotime($b['start_at'])) ?>&m=<?= date('n', strtotime($b['start_at'])) ?>">🗓️ Lihat di Kalender</a>
      <a class="btn btn-sm btn-outline" href="ruang_booking.php">← Kembali ke Daftar</a>
    </div>
  </div>

  <div class="card">
    <h3>⚡ Aksi Persetujuan &amp; Penggunaan</h3>
    <?php if ($canManage && $b['status'] === 'MENUNGGU'): ?>
      <p class="muted" style="font-size:13px">Setujui atau tolak pengajuan pemesanan ruang ini (Bagian/staff Pengadaan).</p>
      <form method="post" action="ruang_booking.php" style="margin-bottom:10px">
        <input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="back" value="<?= (int)$id ?>">
        <div class="form-group"><label>Catatan Persetujuan</label><input type="text" name="approve_note" placeholder="Opsional"></div>
        <button class="btn btn-primary">✔ Setujui Pemesanan</button>
      </form>
      <form method="post" action="ruang_booking.php" onsubmit="return confirm('Tolak pengajuan <?= e($b['booking_no']) ?>?')">
        <input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="back" value="<?= (int)$id ?>">
        <div class="form-group"><label>Alasan Penolakan</label><input type="text" name="reject_reason" placeholder="Wajib bila menolak" required></div>
        <button class="btn btn-danger">✖ Tolak Pengajuan</button>
      </form>
    <?php elseif ($canManage && $b['status'] === 'DISETUJUI'): ?>
      <p class="muted" style="font-size:13px">Pemesanan sudah disetujui. Gunakan tombol berikut saat ruangan mulai/selesai dipakai.</p>
      <form method="post" action="ruang_booking.php" style="display:inline-block">
        <input type="hidden" name="action" value="mulai"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="back" value="<?= (int)$id ?>">
        <button class="btn btn-primary">▶ Tandai DIGUNAKAN</button>
      </form>
      <form method="post" action="ruang_booking.php" style="display:inline-block">
        <input type="hidden" name="action" value="selesaikan"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="back" value="<?= (int)$id ?>">
        <button class="btn btn-warning">✔ Selesaikan (SELESAI)</button>
      </form>
    <?php elseif ($canManage && $b['status'] === 'DIGUNAKAN'): ?>
      <p class="muted" style="font-size:13px">Ruangan sedang digunakan. Tandai selesai bila pemakaian telah berakhir.</p>
      <form method="post" action="ruang_booking.php" style="display:inline-block">
        <input type="hidden" name="action" value="selesaikan"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="back" value="<?= (int)$id ?>">
        <button class="btn btn-warning">✔ Selesaikan (SELESAI)</button>
      </form>
    <?php elseif ($b['status'] === 'MENUNGGU' && !$canManage): ?>
      <p class="muted" style="font-size:13px">Pengajuan sedang <b>menunggu persetujuan Bagian/staff Pengadaan</b>. Anda akan menerima status setelah diproses.</p>
    <?php elseif ($b['status'] === 'DISETUJUI'): ?>
      <p class="muted" style="font-size:13px">Pemesanan <b>disetujui</b>. Harap gunakan ruangan sesuai jadwal; laporkan ke Bagian Umum bila ada perubahan.</p>
    <?php else: ?>
      <div class="empty-state"><div class="big">ℹ️</div>Tidak ada aksi yang tersedia untuk status ini.</div>
    <?php endif; ?>
  </div>
</div>

<?php if ($canResched): $st = strtotime($b['start_at']); $en = strtotime($b['end_at']); ?>
<div class="card" id="ubahjadwal" style="max-width:760px">
  <h3>🔄 Ubah Jadwal Pemesanan</h3>
  <p class="muted" style="font-size:13px">
    Jadwal saat ini: <b><?= tgl($b['start_at'], true) ?> → <?= tgl($b['end_at'], true) ?></b>.
    <?php if (!$canManage && $b['status'] === 'DISETUJUI'): ?>
      Karena pemesanan sudah disetujui, perubahan jadwal oleh pemohon akan <b>kembali MENUNGGU persetujuan ulang</b>.
    <?php else: ?>
      Sistem akan menolak jadwal baru bila bentrok dengan pemesanan lain pada ruangan yang sama.
    <?php endif; ?>
  </p>
  <form method="post" action="ruang_booking.php">
    <input type="hidden" name="action" value="ubah_jadwal"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="back" value="<?= (int)$id ?>">
    <div class="form-row">
      <div class="form-group"><label>Mulai Baru (tgl &amp; jam) *</label>
        <input type="datetime-local" name="new_start_at" required value="<?= date('Y-m-d\TH:i', $st) ?>"></div>
      <div class="form-group"><label>Selesai Baru (tgl &amp; jam) *</label>
        <input type="datetime-local" name="new_end_at" required value="<?= date('Y-m-d\TH:i', $en) ?>"></div>
    </div>
    <div class="form-group"><label>Alasan Perubahan Jadwal *</label>
      <textarea name="reschedule_reason" rows="2" required placeholder="Contoh: jadwal rapat digeser karena agenda pimpinan"></textarea></div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <a class="btn btn-outline" href="ruang_booking_detail.php?id=<?= (int)$id ?>">Batal</a>
      <button class="btn btn-primary">💾 Simpan Jadwal Baru</button>
    </div>
  </form>
</div>
<?php endif; ?>

<?php if ($canCancel): ?>
<div class="card" id="batalkan" style="max-width:760px">
  <h3>🚫 Batalkan Pemesanan</h3>
  <p class="muted" style="font-size:13px">
    <?php if ($isOwner): ?>Pemesanan dapat dibatalkan selama status masih MENUNGGU/DISETUJUI dan belum melewati waktu mulai.
    <?php else: ?>Sebagai Bagian/staff Pengadaan, Anda dapat membatalkan pemesanan ini selama belum selesai.<?php endif; ?>
  </p>
  <form method="post" action="ruang_booking.php" onsubmit="return confirm('Yakin membatalkan pemesanan <?= e($b['booking_no']) ?>?')">
    <input type="hidden" name="action" value="batalkan"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="back" value="<?= (int)$id ?>">
    <div class="form-group"><label>Alasan Pembatalan</label><input type="text" name="cancel_reason" placeholder="Contoh: rapat dibatalkan"></div>
    <button class="btn btn-danger">🚫 Batalkan Pemesanan</button>
  </form>
</div>
<?php endif; ?>

<div class="card" style="max-width:980px">
  <h3>📜 Riwayat Perubahan Jadwal / Pembatalan</h3>
  <div class="table-wrap"><table class="tbl">
    <tr><th>Tanggal</th><th>Jenis</th><th>Jadwal Lama</th><th>Jadwal Baru</th><th>Keterangan</th><th>Oleh</th></tr>
    <?php foreach ($changes as $c): ?>
    <tr>
      <td><?= tgl($c['created_at'], true) ?></td>
      <td><?= $c['change_type'] === 'RESCHEDULE' ? '<span class="badge badge-info">🔄 UBAH JADWAL</span>' : '<span class="badge badge-dark">🚫 BATALKAN</span>' ?></td>
      <td><small><?= $c['old_start_at'] ? tgl($c['old_start_at'], true) : '-' ?> →<br><?= $c['old_end_at'] ? tgl($c['old_end_at'], true) : '-' ?></small></td>
      <td><small><?= $c['new_start_at'] ? tgl($c['new_start_at'], true) : '-' ?> →<br><?= $c['new_end_at'] ? tgl($c['new_end_at'], true) : '-' ?></small></td>
      <td><?= e($c['note'] ?: '-') ?></td>
      <td><?= e($c['changer'] ?? 'User #' . (int)$c['changed_by']) ?></td>
    </tr>
    <?php endforeach; if (!$changes): ?>
    <tr><td colspan="6"><div class="empty-state">Belum ada perubahan jadwal/pembatalan.</div></td></tr>
    <?php endif; ?>
  </table></div>
</div>

<div class="card" style="max-width:980px">
  <h3>🗓️ Riwayat Pemakaian Ruangan <?= e($b['room_name']) ?></h3>
  <div class="table-wrap"><table class="tbl">
    <tr><th>No. Booking</th><th>Pemohon</th><th>Keperluan</th><th>Waktu</th><th>Status</th></tr>
    <?php foreach ($roomRecent as $r): ?>
    <tr>
      <td><a href="ruang_booking_detail.php?id=<?= (int)$r['id'] ?>"><?= e($r['booking_no']) ?></a></td>
      <td><?= e($r['pemohon'] ?? '-') ?></td>
      <td><?= e(mb_strimwidth($r['purpose'], 0, 50, '…')) ?></td>
      <td><small><?= tgl($r['start_at'], true) ?> → <?= tgl($r['end_at'], true) ?></small></td>
      <td><?= status_badge($r['status']) ?></td>
    </tr>
    <?php endforeach; if (!$roomRecent): ?>
    <tr><td colspan="5"><div class="empty-state">Belum ada riwayat untuk ruangan ini.</div></td></tr>
    <?php endif; ?>
  </table></div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
