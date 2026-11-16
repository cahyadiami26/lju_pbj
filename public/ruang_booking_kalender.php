<?php
/**
 * Kalender Pemakaian Ruang - PT. Lampung Jasa Utama
 * Menampilkan peminjaman/pemesanan ruang dalam bentuk kalendar bulanan:
 *  - Highlight hijau/teal : ada pemakaian ruang pada tanggal tersebut
 *  - Highlight MERAH      : ada fasilitas di ruangan terkait yang sedang
 *                           RUSAK / BERMASALAH / PERBAIKAN (facilities) atau
 *                           tiket keluhan fasilitas yang belum SELESAI.
 *  - Klik tanggal untuk melihat detail pemakaian & info fasilitas bermasalah.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Kalender Pemakaian Ruang'; $active = 'ruang_booking_kalender';

$hasTable = false;
try { $hasTable = (bool)q("SHOW TABLES LIKE 'room_bookings'")->fetch(); } catch (Throwable $e) {}

/* ---- Parameter bulan & filter ruangan ---- */
$now = new DateTime('now');
$y = (int)($_GET['y'] ?? $now->format('Y'));
$m = (int)($_GET['m'] ?? $now->format('n'));
if ($y < 2000 || $y > 2100) $y = (int)$now->format('Y');
if ($m < 1 || $m > 12) $m = (int)$now->format('n');
$fRoom = (int)($_GET['room'] ?? 0);

$monthTs     = mktime(0, 0, 0, $m, 1, $y);
$daysInMonth = (int)date('t', $monthTs);
$monthStart  = date('Y-m-01 00:00:00', $monthTs);
$monthEnd    = date('Y-m-t 23:59:59', $monthTs);
$firstDow    = (int)date('w', $monthTs); // 0 = Minggu
$today       = date('Y-m-d');

$dayNames   = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

/* ---- Peta ruangan dengan fasilitas rusak/diperbaiki (highlight merah) ---- */
$brokenRooms = [];
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

$roomNames = [];
try { foreach (q("SELECT id, name FROM locations WHERE type='ruang' ORDER BY name")->fetchAll() as $r) $roomNames[(int)$r['id']] = $r['name']; } catch (Throwable $e) {}

/* ---- Daftar pemesanan yang menyentuh bulan ini ---- */
$eventsByDay = []; // [hari] => list booking
$payload     = []; // data utk modal detail hari (JS)
if ($hasTable) {
    $sql = "SELECT rb.id, rb.booking_no, rb.location_id, rb.start_at, rb.end_at, rb.status, rb.purpose, rb.participants,
                   loc.name AS room_name, uc.name AS pemohon
            FROM room_bookings rb
            JOIN locations loc ON loc.id = rb.location_id
            LEFT JOIN user_cache uc ON uc.user_id = rb.user_id
            WHERE rb.status IN ('MENUNGGU','DISETUJUI','DIGUNAKAN','SELESAI')
              AND rb.start_at <= ? AND rb.end_at >= ?";
    $args = [$monthEnd, $monthStart];
    if ($fRoom) { $sql .= " AND rb.location_id = ?"; $args[] = $fRoom; }
    $sql .= " ORDER BY rb.start_at";
    $rows = q($sql, $args)->fetchAll();

    foreach ($rows as $b) {
        $sTs = strtotime($b['start_at']);
        $eTs = strtotime($b['end_at']);
        $d1 = ($sTs < strtotime($monthStart)) ? 1 : (int)date('j', $sTs);
        $d2 = ($eTs > strtotime($monthEnd)) ? $daysInMonth : (int)date('j', $eTs);
        $broken = $brokenRooms[(int)$b['location_id']] ?? null;
        for ($d = $d1; $d <= $d2; $d++) {
            $eventsByDay[$d][] = $b;
            $payload[$d][] = [
                'no'      => e($b['booking_no']),
                'room'    => e($b['room_name']),
                'time'    => date('H:i', $sTs) . ' s/d ' . date('H:i', $eTs),
                'date'    => date('d/m/Y', $sTs) . ' - ' . date('d/m/Y', $eTs),
                'status'  => e($b['status']),
                'pemohon' => e($b['pemohon'] ?: '-'),
                'purpose' => e(mb_strimwidth($b['purpose'] ?? '', 0, 100, '…')),
                'broken'  => $broken !== null ? e($broken) : null,
                'detail'  => 'ruang_booking_detail.php?id=' . (int)$b['id'],
            ];
        }
    }
}

/* ---- Ruangan yang bisa dipinjam (untuk filter & tombol ajukan) ---- */
$roomsAvail = [];
try {
    $roomsAvail = q("SELECT id, name FROM locations WHERE type='ruang' AND can_book=1 AND is_active=1 ORDER BY name")->fetchAll();
} catch (Throwable $e) {
    try { $roomsAvail = q("SELECT id, name FROM locations WHERE type='ruang' AND is_active=1 ORDER BY name")->fetchAll(); } catch (Throwable $e2) {}
}

/* Navigasi bulan sebelumnya / berikutnya */
$prev = ['y' => (int)date('Y', mktime(0,0,0,$m-1,1,$y)), 'm' => (int)date('n', mktime(0,0,0,$m-1,1,$y))];
$next = ['y' => (int)date('Y', mktime(0,0,0,$m+1,1,$y)), 'm' => (int)date('n', mktime(0,0,0,$m+1,1,$y))];
$isCurrentMonth = ($y === (int)$now->format('Y') && $m === (int)$now->format('n'));
/* Ruangan terfilter sedang memiliki fasilitas rusak/diperbaiki -> highlight merah pada seluruh kalender */
$filterRoomBroken = ($fRoom && isset($brokenRooms[$fRoom]));
$qs = function (int $yy, int $mm) use ($fRoom): string {
    return 'ruang_booking_kalender.php?y=' . $yy . '&m=' . $mm . ($fRoom ? '&room=' . $fRoom : '');
};

require __DIR__ . '/../includes/header.php';
?>
<?php if (!$hasTable): ?>
<div class="alert alert-warning">⚠️ Tabel <i>room_bookings</i> belum ada. Jalankan migrasi <b>database/migrate_room_bookings.sql</b> terlebih dahulu.</div>
<?php endif; ?>

<?php /* Ringkasan ruangan dengan fasilitas rusak/diperbaiki (penjelas highlight merah) */ ?>
<div class="card">
  <h3>⚠️ Ruang dengan Fasilitas Rusak / Sedang Diperbaiki
    <a class="btn btn-sm btn-outline" href="fasilitas.php">Kelola Fasilitas</a>
  </h3>
  <?php if (!$brokenRooms): ?>
    <div class="empty-state"><div class="big">✅</div>Semua fasilitas ruangan dalam kondisi baik.</div>
  <?php else: ?>
    <div class="table-wrap"><table class="tbl">
      <tr><th>Ruangan</th><th>Fasilitas Bermasalah</th></tr>
      <?php foreach ($brokenRooms as $lid => $names): ?>
      <tr><td><b><?= e($roomNames[(int)$lid] ?? ('Lokasi #' . (int)$lid)) ?></b></td>
          <td><span class="badge badge-danger">⚠</span> <?= e($names) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
    <p class="muted" style="font-size:12.5px;margin-top:8px">Tanggal dengan pemakaian di ruangan-ruangan di atas akan ditandai <b>highlight merah</b> pada kalender.</p>
  <?php endif; ?>
</div>

<div class="card">
  <div class="cal-toolbar">
    <span class="cal-title">🗓️ <?= e($monthNames[$m-1]) ?> <?= $y ?></span>
    <a class="btn btn-sm btn-outline" href="<?= e($qs($prev['y'], $prev['m'])) ?>">‹ Sebelumnya</a>
    <a class="btn btn-sm btn-outline" href="<?= $isCurrentMonth ? '#' : e($qs((int)$now->format('Y'), (int)$now->format('n'))) ?>">Bulan Ini</a>
    <a class="btn btn-sm btn-outline" href="<?= e($qs($next['y'], $next['m'])) ?>">Berikutnya ›</a>
    <?php if ($filterRoomBroken): ?>
      <span class="badge badge-danger">⚠ Ada fasilitas rusak/sedang diperbaiki di ruangan ini: <?= e($brokenRooms[$fRoom]) ?></span>
    <?php endif; ?>
    <form method="get" class="filters" style="margin-bottom:0;margin-left:auto">
      <input type="hidden" name="y" value="<?= $y ?>"><input type="hidden" name="m" value="<?= $m ?>">
      <div class="form-group"><label>Ruangan</label>
        <select name="room" onchange="this.form.submit()">
          <option value="">Semua Ruangan</option>
          <?php foreach ($roomNames as $rid => $rname): ?>
          <option value="<?= (int)$rid ?>" <?= $fRoom === (int)$rid ? 'selected' : '' ?>><?= e($rname) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <noscript><button class="btn btn-sm">Terapkan</button></noscript>
    </form>
    <?php if (can('ruang_booking') && $roomsAvail): ?>
    <a class="btn btn-sm btn-primary" href="ruang_booking.php<?= $fRoom ? '?room=' . $fRoom : '' ?>">➕ Ajukan Pemesanan</a>
    <?php endif; ?>
  </div>

  <div class="cal-legend">
    <span><span class="dot-swatch" style="background:#e0f2fe"></span>Ada pemakaian ruang</span>
    <span><span class="dot-swatch" style="background:#dcfce7"></span>DISETUJUI</span>
    <span><span class="dot-swatch" style="background:#e2e8f0"></span>MENUNGGU</span>
    <span><span class="dot-swatch" style="background:#dbeafe"></span>DIGUNAKAN</span>
    <span><span class="dot-swatch" style="background:#f1f5f9"></span>SELESAI</span>
    <span><span class="dot-swatch" style="background:#fee2e2;border:1px solid #fca5a5"></span>⚠ Fasilitas ruangan rusak/diperbaiki (highlight merah)</span>
    <span><span class="dot-swatch" style="background:#fff;border:2px solid var(--teal)"></span>Hari ini</span>
  </div>

  <div class="cal<?= $filterRoomBroken ? ' room-broken' : '' ?>">
    <?php foreach ($dayNames as $dn): ?><div class="cal-head"><?= $dn ?></div><?php endforeach; ?>
    <?php for ($i = 0; $i < $firstDow; $i++): ?><div class="cal-day out"></div><?php endfor; ?>
    <?php for ($d = 1; $d <= $daysInMonth; $d++):
        $evs = $eventsByDay[$d] ?? [];
        $hasBroken = false;
        foreach ($evs as $b) { if (isset($brokenRooms[(int)$b['location_id']])) { $hasBroken = true; break; } }
        $cls = 'cal-day';
        if ($evs) $cls .= ' has-event';
        if ($hasBroken) $cls .= ' day-broken';
        if (date('Y-m-d', mktime(0,0,0,$m,$d,$y)) === $today) $cls .= ' today';
    ?>
    <div class="<?= $cls ?>" onclick="showDay(<?= $d ?>)" title="Klik untuk detail pemakaian">
      <div class="cal-num"><?= $d ?></div>
      <?php foreach (array_slice($evs, 0, 2) as $b): $broken = $brokenRooms[(int)$b['location_id']] ?? null;
             $stCls = ['MENUNGGU'=>'wait','DISETUJUI'=>'ok','DIGUNAKAN'=>'use','SELESAI'=>'done'][$b['status']] ?? 'wait'; ?>
      <div class="cal-ev <?= $stCls ?><?= $broken ? ' broken' : '' ?>">
        <?= date('H:i', strtotime($b['start_at'])) ?> <?= e($b['room_name']) ?><?= $broken ? ' ⚠' : '' ?>
      </div>
      <?php endforeach; ?>
      <?php if (count($evs) > 2): ?><div class="cal-more">+<?= count($evs) - 2 ?> lainnya</div><?php endif; ?>
    </div>
    <?php endfor; ?>
  </div>
</div>

<!-- Modal detail pemakaian per tanggal -->
<div class="modal-backdrop" id="mDay"><div class="modal" style="max-width:680px">
  <h3 id="dayTitle">Detail Pemakaian Ruang</h3>
  <div id="dayBody"></div>
  <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px">
    <button type="button" class="btn btn-outline" onclick="closeModal('mDay')">Tutup</button>
  </div>
</div></div>

<script>
var CAL = <?= json_encode($payload, JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>;
var CAL_TITLE = <?= json_encode($monthNames[$m-1] . ' ' . $y, JSON_HEX_APOS | JSON_HEX_QUOT) ?: "''" ?>;
var ST_BADGE = { 'MENUNGGU': 'badge-secondary', 'DISETUJUI': 'badge-success', 'DIGUNAKAN': 'badge-primary', 'SELESAI': 'badge-success', 'DIBATALKAN': 'badge-dark', 'DITOLAK': 'badge-danger' };

function showDay(d) {
  var evs = CAL[d] || [];
  var html = '';
  if (!evs.length) {
    html = '<div class="empty-state"><div class="big">📭</div>Tidak ada pemakaian ruang tercatat pada tanggal ini.</div>';
  } else {
    html = '<div class="table-wrap"><table class="tbl"><tr><th>Waktu</th><th>Ruangan</th><th>Keperluan / Pemohon</th><th>Status</th></tr>';
    evs.forEach(function (ev) {
      html += '<tr>';
      html += '<td><small><b>' + ev.time + '</b><br>' + ev.date + '</small></td>';
      html += '<td><b>' + ev.room + '</b>';
      if (ev.broken) html += '<br><span class="badge badge-danger">⚠ fasilitas rusak</span><br><small style="color:#991b1b">Rusak/diperbaiki: ' + ev.broken + '</small>';
      html += '</td>';
      html += '<td>' + ev.purpose + '<br><small class="muted">👤 ' + ev.pemohon + ' · ' + ev.no + '</small></td>';
      html += '<td><span class="badge ' + (ST_BADGE[ev.status] || 'badge-secondary') + '">' + ev.status + '</span></td>';
      html += '</tr>';
    });
    html += '</table></div>';
  }
  document.getElementById('dayBody').innerHTML = html;
  document.getElementById('dayTitle').textContent = '📅 Pemakaian Ruang — ' + d + ' ' + CAL_TITLE;
  openModal('mDay');
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
