<?php
/**
 * AJAX Handler: Mengambil riwayat pergerakan stok (Buku Besar / Ledger) per item barang
 * Menghitung saldo awal dan saldo berjalan (running balance) layaknya neraca kas/stok.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

// Proteksi akses: hanya pengguna yang sudah login dengan hak akses sesuai
require_role(['admin', 'staff_pengadaan', 'kepala_bidang', 'kepala_divisi', 'pimpinan']);

$supplyId = (int)($_GET['id'] ?? 0);

if (!$supplyId) {
    echo '<tr><td colspan="9" class="center muted">ID Barang tidak valid.</td></tr>';
    exit;
}

// 1. Ambil data barang
$supply = q("SELECT * FROM supplies WHERE id = ?", [$supplyId])->fetch();
if (!$supply) {
    echo '<tr><td colspan="9" class="center muted">Data barang tidak ditemukan.</td></tr>';
    exit;
}

// 2. Ambil semua riwayat transaksi barang ini diurutkan berdasarkan waktu tercepat (ASC)
$txs = q("SELECT t.*, uc.name AS pencatat 
          FROM supply_transactions t
          LEFT JOIN user_cache uc ON uc.user_id = t.created_by
          WHERE t.supply_id = ? 
          ORDER BY t.created_at ASC, t.id ASC", [$supplyId])->fetchAll();

if (!$txs) {
    echo '<tr><td colspan="9" class="center muted">Belum ada riwayat transaksi pergerakan stok untuk barang ini.</td></tr>';
    exit;
}

// 3. Hitung saldo berjalan (Running Balance)
$runningBalance = 0;
foreach ($txs as $t): 
    $qty = (int)$t['qty'];
    $type = $t['type'];
    
    if ($type === 'MASUK') {
        $runningBalance += $qty;
        $masukStr = '<b style="color:var(--success, #28a745)">+' . $qty . '</b>';
        $keluarStr = '-';
    } elseif ($type === 'KELUAR') {
        $runningBalance -= $qty;
        $masukStr = '-';
        $keluarStr = '<b style="color:var(--danger, #dc3545)">-' . $qty . '</b>';
    } else {
        // MUTASI atau penyesuaian stok lainnya
        $masukStr = $qty > 0 ? '+' . $qty : '-';
        $keluarStr = $qty < 0 ? (string)$qty : '-';
    }
?>
<tr>
  <td style="white-space:nowrap"><?= tgl($t['created_at'], true) ?></td>
  <td>
    <?php if ($type === 'MASUK'): ?>
      <span class="badge badge-success">MASUK</span>
    <?php elseif ($type === 'KELUAR'): ?>
      <span class="badge badge-danger">KELUAR</span>
    <?php else: ?>
      <span class="badge badge-info">MUTASI</span>
    <?php endif; ?>
  </td>
  <td class="num"><?= $masukStr ?></td>
  <td class="num"><?= $keluarStr ?></td>
  <td class="num"><b><?= $runningBalance ?></b> <small class="muted"><?= e($supply['unit']) ?></small></td>
  <td><small><?= e($t['from_unit'] ?: '-') ?> → <?= e($t['to_unit'] ?: '-') ?></small></td>
  <td><small><?= e($t['ref_type'] ?: '-') ?><?= $t['ref_id'] ? ' #' . (int)$t['ref_id'] : '' ?></small></td>
  <td><?= e($t['note'] ?: '-') ?></td>
  <td><small><?= e($t['pencatat'] ?? user_name_of($t['created_by'])) ?></small></td>
</tr>
<?php endforeach; ?>