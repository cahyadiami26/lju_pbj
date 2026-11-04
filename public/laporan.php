<?php
/**
 * Pusat Laporan: rekap kebutuhan, rencana pengadaan, pengadaan per periode,
 * barang belum diterima, inventaris (per unit/lokasi/pegawai/kondisi), mutasi,
 * ATK (stok, masuk/keluar, distribusi, minimum, pemakaian), kerusakan,
 * kendaraan (peminjaman, servis, biaya). Semua dapat dicetak ke PDF (domPDF).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'staff_pengadaan', 'kepala_bidang', 'kepala_divisi', 'pimpinan']);

$JENIS = [
    'pengadaan' => ['Pengadaan', 'Rekap kebutuhan & rencana pengadaan per periode'],
    'belum_diterima' => ['Barang Belum Diterima', 'Rencana dalam proses yang belum ada penerimaannya'],
    'inventaris' => ['Daftar Inventaris & Aset', 'Termasuk rekap per unit / per lokasi / per kondisi'],
    'inventaris_pegawai' => ['Inventaris per Pegawai', 'Aset yang dikuasai tiap pegawai'],
    'mutasi' => ['Mutasi Aset', 'Histori perpindahan unit/lokasi/pengguna'],
    'riwayat_aset' => ['Riwayat Pemeliharaan Aset', 'Semua perbaikan tercatat beserta biaya'],
    'atk_stok' => ['Stok ATK & Perlengkapan', 'Termasuk peringatan stok minimum'],
    'atk_transaksi' => ['Barang Masuk / Keluar ATK', 'Riwayat transaksi stok per periode'],
    'atk_distribusi' => ['Distribusi ATK per Unit', 'Permintaan & serah terima per pemohon/unit'],
    'atk_neraca_transaksi' => ['Neraca Perlengkapan/ATK', 'Neraca Pergerakan Stok Perlengkapan/ATK'],
    'kerusakan' => ['Laporan Kerusakan', 'Per unit, per aset, selesai/belum'],
    'kendaraan' => ['Kendaraan & Peminjaman', 'Daftar, status, dan riwayat peminjaman'],
    'kendaraan_servis' => ['Servis & Biaya Perawatan Kendaraan', 'Riwayat servis dan total biaya'],
];

$from = $_GET['from'] ?? date('Y-01-01');
$to   = $_GET['to'] ?? date('Y-m-d');
$j    = $_GET['jenis'] ?? 'pengadaan';
if (!isset($JENIS[$j])) $j = 'pengadaan';
// Tambahkan parameter filter item jika ada
$itemId = (int)($_GET['item_id'] ?? 0);
$supsList = q("SELECT id, name, code FROM supplies ORDER BY name")->fetchAll();


$pageTitle = 'Laporan'; $active = 'laporan';
require __DIR__ . '/../includes/header.php';
//$pdfUrl = "laporan_pdf.php?jenis=$j&from=$from&to=$to";
$pdfUrl = "laporan_pdf.php?jenis=$j&from=$from&to=$to&item_id=$itemId";

?>
<div class="grid grid-2" style="margin-bottom:16px">
  <div class="card">
    <h3>📈 Pilih Jenis Laporan</h3>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ($JENIS as $key => [$label, $desc]): ?>
      <a class="btn btn-sm <?= $key === $j ? 'btn-primary' : 'btn-outline' ?>" style="justify-content:flex-start;text-align:left"
         href="laporan.php?jenis=<?= $key ?>&from=<?= $from ?>&to=<?= $to ?>&item_id=<?= $itemId ?>">
        <span><b><?= e($label) ?></b><br><small class="muted"><?= e($desc) ?></small></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px">
    <!-- Card Form Filter terpisah -->
    <div class="card">
      <h3>🗓️ Filter &amp; Periode Laporan</h3>
      <form method="get">
        <input type="hidden" name="jenis" value="<?= e($j) ?>">
        
        <div class="form-row" style="display:flex;gap:10px;margin-bottom:12px">
          <div class="form-group" style="flex:1">
            <label>Dari Tanggal</label>
            <input type="date" name="from" value="<?= e($from) ?>" style="width:100%">
          </div>
          <div class="form-group" style="flex:1">
            <label>Sampai Tanggal</label>
            <input type="date" name="to" value="<?= e($to) ?>" style="width:100%">
          </div>
        </div>

        <?php if (in_array($j, ['atk_neraca_transaksi'])): ?>
        <div class="form-group" style="margin-bottom:12px">
          <label>Filter Item Barang</label>
          <select name="item_id" style="width:100%" onchange="this.form.submit()">
            <option value="">-- Semua Barang --</option>
            <?php foreach ($supsList as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= $itemId === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['code'] . ' - ' . $s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <!-- Tombol diletakkan di bawah field tanggal -->
        <div style="display:flex;gap:10px;margin-top:8px">
          <button type="submit" class="btn btn-primary" style="flex:1">🔍 Terapkan</button>
          <a class="btn btn-teal" href="<?= e($pdfUrl) ?>" target="_blank" style="flex:1;text-align:center">📄 Cetak PDF</a>
        </div>
      </form>
    </div>

    <!-- Card Ringkasan Cepat terpisah -->
    <div class="card">
      <h3 style="font-size:14px;margin-bottom:10px">📊 Ringkasan Cepat</h3>
      <table class="tbl">
        <tr><th>Total Aset Aktif</th><td class="num"><?= count_rows("SELECT COUNT(*) FROM assets WHERE status='AKTIF'") ?></td></tr>
        <tr><th>Nilai Aset (Aktif)</th><td class="num"><?= rupiah(q("SELECT IFNULL(SUM(value),0) FROM assets WHERE status='AKTIF'")->fetchColumn()) ?></td></tr>
        <tr><th>Kebutuhan Periode Ini</th><td class="num"><?= count_rows("SELECT COUNT(*) FROM needs_requests WHERE DATE(created_at) BETWEEN ? AND ?", [$from, $to]) ?></td></tr>
        <tr><th>Rencana Pengadaan Periode Ini</th><td class="num"><?= count_rows("SELECT COUNT(*) FROM procurement_plans WHERE plan_date BETWEEN ? AND ?", [$from, $to]) ?></td></tr>
        <tr><th>Estimasi Nilai Rencana (Rp)</th><td class="num"><?= rupiah(q("SELECT IFNULL(SUM(i.qty*i.est_price),0) FROM procurement_plan_items i JOIN procurement_plans p ON p.id=i.plan_id WHERE p.plan_date BETWEEN ? AND ?", [$from, $to])->fetchColumn()) ?></td></tr>
        <tr><th>Transaksi ATK Masuk/Keluar</th><td class="num"><?= count_rows("SELECT COUNT(*) FROM supply_transactions WHERE DATE(created_at) BETWEEN ? AND ?", [$from, $to]) ?></td></tr>
        <tr><th>Laporan Kerusakan Selesai</th><td class="num"><?= count_rows("SELECT COUNT(*) FROM damage_reports WHERE status='SELESAI' AND DATE(report_date) BETWEEN ? AND ?", [$from, $to]) ?></td></tr>
        <tr><th>Biaya Servis Kendaraan</th><td class="num"><?= rupiah(q("SELECT IFNULL(SUM(cost),0) FROM vehicle_services WHERE service_date BETWEEN ? AND ?", [$from, $to])->fetchColumn()) ?></td></tr>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <h3>📋 Pratinjau: <?= e($JENIS[$j][0]) ?> <span class="muted" style="font-weight:normal;font-size:12px">(<?= tgl($from) ?> — <?= tgl($to) ?>)</span>
    <a class="btn btn-sm btn-teal" href="<?= e($pdfUrl) ?>" target="_blank">Buka PDF Full</a>
  </h3>
  <iframe src="<?= e($pdfUrl) ?>" style="width:100%;height:70vh;border:1px solid var(--line);border-radius:8px;background:#fff"></iframe>
  <p class="muted" style="margin-top:8px">Pratinjau dirender oleh domPDF. Gunakan tombol “Cetak PDF” untuk mengunduh.</p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
