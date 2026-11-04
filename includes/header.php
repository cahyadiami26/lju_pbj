<?php
/** Header layout (sidebar + topbar). Butuh bootstrap.php sudah dimuat. */
if (session_status() === PHP_SESSION_NONE) require_once __DIR__ . '/bootstrap.php';
$pageTitle = $pageTitle ?? APP_NAME;
$active = $active ?? '';

// Definisi menu dengan struktur kategori dan sub-menu
$menuGroups = [
    [
        'label' => 'Menu Utama',
        'items' => [
            ['dashboard', 'Dashboard', '📊', []]
        ]
    ],
    [
        'label' => 'Pengadaan',
        'key' => 'pengadaan_group',
        'icon' => '📝',
        'items' => [
            ['kebutuhan', 'Pengajuan Kebutuhan', '📝'],
            ['rencana_pengadaan', 'Rencana Pengadaan', '🗂️'],
            ['penerimaan', 'Penerimaan Barang/Jasa', '📦'],
        ]
    ],
    [
        'label' => 'Aset',
        'key' => 'aset_group',
        'icon' => '🏷️',
        'items' => [
            ['inventaris', 'Inventaris & Aset', '🏷️'],
            ['mutasi', 'Mutasi Aset', '🔄'],
            ['penghapusan', 'Penghapusan Aset', '🗑️'],
            ['kerusakan', 'Laporan Kerusakan', '⚠️'],
            ['pemeliharaan', 'Pemeliharaan & Perbaikan', '🛠️'],
            ['fasilitas', 'Fasilitas Kantor', '🏢'],
        ]
    ],
    [
        'label' => 'Perlengkapan / ATK',
        'key' => 'perlengkapan_group',
        'icon' => '🖇️',
        'items' => [
            ['perlengkapan', 'Perlengkapan / ATK', '🖇️'],
            ['stock_opname', 'Stock Opname', '🔍'],
        ]
    ],
    [
        'label' => 'Kendaraan',
        'key' => 'kendaraan_group',
        'icon' => '🚗',
        'items' => [
            ['kendaraan', 'Data Kendaraan', '🚗'],
            ['peminjaman', 'Peminjaman Kendaraan', '🔑'],
            ['perawatan_kendaraan', 'Perawatan Kendaraan', '🔧'],
        ]
    ],
    [
        'label' => 'Laporan & Pengaturan',
        'key' => 'setting_group',
        'icon' => '⚙️',
        'items' => [
            ['laporan', 'Laporan', '📈'],
            ['pegawai', 'Data Pegawai', '👥'],
            ['lokasi', 'Data Lokasi', '📍'],
            ['role_mapping', 'Pemetaan Role', '🎭'],
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> - <?= e(APP_COMPANY) ?></title>
<link rel="icon" href="<?= e(LOGO_BASE_URL) ?>logo.png" type="image/png">
<link rel="stylesheet" href="https://pengadaan.lju.co.id/assets/css/style.css">
<style>
/* Tambahan styling sederhana untuk menu dropdown/accordion group */
.nav-group { margin-bottom: 5px; }
.nav-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 15px;
    color: var(--sidebar-color, #adb5bd);
    cursor: pointer;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}
.nav-group-header:hover { color: #fff; background: rgba(255,255,255,0.05); }
.nav-sub { padding-left: 15px; display: none; }
.nav-group.open .nav-sub { display: block; }
.nav-group-header .arrow { transition: transform 0.2s; }
.nav-group.open .nav-group-header .arrow { transform: rotate(90deg); }
</style>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <img src="<?= e(LOGO_BASE_URL) ?>logo.png" alt="Logo LJU" onerror="this.style.display='none'">
      <div>
        <strong>Pengadaan LJU</strong>
        <small>PT. Lampung Jasa Utama</small>
      </div>
    </div>
    
    <nav class="sidebar-nav">
      <?php foreach ($menuGroups as $group): 
          // Periksa apakah ada minimal satu item dalam grup yang diizinkan untuk diakses user
          $hasVisibleItems = false;
          foreach ($group['items'] as $item) {
              if (can($item[0])) {
                  $hasVisibleItems = true;
                  break;
              }
          }
          if (!$hasVisibleItems) continue;

          // Cek apakah salah satu sub-menu sedang aktif saat ini
          $isGroupActive = false;
          foreach ($group['items'] as $item) {
              if ($active === $item[0]) {
                  $isGroupActive = true;
                  break;
              }
          }
      ?>
        <?php if (count($group['items']) === 1): ?>
          <!-- Menu tunggal tanpa sub-menu (contoh: Dashboard) -->
          <?php $item = $group['items'][0]; ?>
          <?php if (can($item[0])): ?>
            <a href="<?= $item[0] ?>.php" class="nav-item <?= $active === $item[0] ? 'active' : '' ?>">
              <span class="nav-icon"><?= $item[2] ?></span><span><?= e($item[1]) ?></span>
            </a>
          <?php endif; ?>
        <?php else: ?>
          <!-- Menu dengan Grup / Sub-menu -->
          <div class="nav-group <?= $isGroupActive ? 'open' : '' ?>">
            <div class="nav-group-header" onclick="this.parentElement.classList.toggle('open')">
              <div>
                <span class="nav-icon" style="margin-right: 8px;"><?= $group['icon'] ?? '📁' ?></span>
                <span><?= e($group['label']) ?></span>
              </div>
              <span class="arrow">▶</span>
            </div>
            <div class="nav-sub">
              <?php foreach ($group['items'] as [$key, $label, $icon]): if (!can($key)) continue; ?>
                <a href="<?= $key ?>.php" class="nav-item <?= $active === $key ? 'active' : '' ?>">
                  <span class="nav-icon"><?= $icon ?></span><span><?= e($label) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-foot"><small>&copy; <?= date('Y') ?> <?= e(APP_COMPANY) ?></small></div>
  </aside>

  <div class="main-wrap">
    <header class="topbar">
      <button class="btn-icon menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
      <div class="topbar-title"><?= e($pageTitle) ?></div>
      <div class="topbar-right">
        <a class="btn-icon notif-bell" href="<?= can('dashboard') ? 'dashboard.php' : '#' ?>" title="Notifikasi">🔔<?php
            $notifN = can('dashboard') ? notification_badge_count() : (int)($_SESSION['notif_count'] ?? 0);
            if ($notifN > 0) echo '<span class="dot">' . (int)$notifN . '</span>';
        ?></a>
        <div class="user-chip">
          <?= avatar_html($_SESSION['avatar'] ?? null, $_SESSION['user_name'] ?? 'User', 34) ?>
          <div class="user-meta">
            <strong><?= e($_SESSION['user_name'] ?? '') ?></strong>
            <small><?= e(ucwords(str_replace('_', ' ', $_SESSION['app_role'] ?? 'pegawai'))) ?> · <?= e($_SESSION['position'] ?? '') ?></small>
          </div>
        </div>
        <a class="btn btn-outline btn-sm" href="https://lju.co.id/dashboard">Dashboard</a>
      </div>
    </header>

    <main class="content">
    <?= flash() ?>