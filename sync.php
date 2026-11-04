<?php
// Konfigurasi Repository & Autentikasi
$gitBin = '"C:\Program Files\Git\cmd\git.exe"';
$repoHostPath = 'github.com/cahyadiami26/pengadaan.git'; // Tanpa https://
$token        = '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'; // Personal Access Token GitHub
$branch       = 'main'; // Branch target sinkronisasi
$targetDir    = __DIR__; // Direktori tujuan penyimpanan source code

// Format URL dengan Token untuk autentikasi HTTPS
$authenticatedUrl = "https://{$token}@{$repoHostPath}";

// Pastikan direktori target valid
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}
chdir($targetDir);

// Ambil pilihan strategi konflik dari parameter URL (Contoh: ?strategy=reset, ?strategy=stash, atau default)
// Jika diakses via CLI, bisa diatur lewat argumen
$strategy = isset($_GET['strategy']) ? $_GET['strategy'] : 'safe'; 

$output = [];
$result = "";

// Cek apakah folder sudah menjadi Git repository
if (is_dir($targetDir . '/.git')) {
    
    // Penanganan berdasarkan strategi yang dipilih
    switch ($strategy) {
        case 'reset':
            // Strategi 1: Paksa timpa semua file lokal dengan versi GitHub (Hard Reset)
            $command = "git fetch origin {$branch} 2>&1 && git reset --hard origin/{$branch} 2>&1";
            $result .= "[INFO] Menjalankan strategi: Timpa Semua (Hard Reset ke origin/{$branch})\n\n";
            break;
            
        case 'stash':
            // Strategi 2: Simpan dulu perubahan lokal (stash), pull, lalu coba pop/gabungkan kembali
            $command = "git stash 2>&1 && git pull {$authenticatedUrl} {$branch} 2>&1 && git stash pop 2>&1";
            $result .= "[INFO] Menjalankan strategi: Simpan Perubahan Lokal (Stash & Pull)\n\n";
            break;
            
        case 'safe':
        default:
            // Strategi 3: Pull standar (Akan gagal otomatis jika ada konflik)
            $command = "git pull {$authenticatedUrl} {$branch} 2>&1";
            $result .= "[INFO] Menjalankan strategi: Aman (Default Pull)\n\n";
            break;
    }
    
    // Eksekusi perintah
    $result .= shell_exec($command);

} else {
    // Jika belum ada repo, lakukan clone
    $command = "git clone -b {$branch} {$authenticatedUrl} . 2>&1";
    $result = "[INFO] Direktori belum memiliki Git. Melakukan Clone...\n\n" . shell_exec($command);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>GitHub Sync Tool</title>
    <style>
        body { font-family: sans-serif; margin: 30px; background: #f9f9f9; color: #333; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 15px; margin-right: 5px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn:hover { opacity: 0.9; }
        pre { background: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Pilih Strategi Sinkronisasi Git</h2>
        <p>Silakan pilih metode penanganan file lokal sebelum melakukan pull dari GitHub:</p>
        <a href="?strategy=safe" class="btn">1. Aman (Default Pull)</a>
        <a href="?strategy=stash" class="btn btn-warning">2. Stash Perubahan Lokal (Simpan & Pull)</a>
        <a href="?strategy=reset" class="btn btn-danger" onclick="return confirm('PERINGATAN: Semua perubahan lokal yang belum di-commit akan HILANG. Yakin ingin menimpa?')">3. Timpa Total (Hard Reset)</a>
    </div>

    <div class="card">
        <h3>Log Hasil Eksekusi:</h3>
        <pre><?php echo htmlspecialchars($result); ?></pre>
    </div>

</body>
</html>