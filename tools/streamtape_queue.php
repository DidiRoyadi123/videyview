<?php

/**
 * Streamtape Pending Queue Video Mover
 * 
 * Script ini berfungsi untuk memisahkan file video yang belum berhasil 
 * di-upload ke Streamtape ke dalam folder terpisah (`streamtape_pending`).
 * Berguna saat ingin upload bulk lewat Streamtape Desktop/Web uploader, 
 * lalu memindahkannya kembali setelah selesai.
 */

// 1. Bootstrap Laravel Native Environment
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Video;
use Illuminate\Support\Facades\File;

// 2. Define Directories
$sourceDir = __DIR__ . '/public/storage/videos';
$targetDir = __DIR__ . '/public/storage/streamtape_pending';

// Ensure Target Directory Exists
if (!File::exists($targetDir)) {
    File::makeDirectory($targetDir, 0755, true);
    echo "Dibuat folder baru: $targetDir\n";
}

// Ensure Source Directory Exists
if (!File::exists($sourceDir)) {
    die("Error: Direktori sumber tidak ditemukan ($sourceDir)!\n");
}

echo "Memeriksa file di $sourceDir...\n";

// 3. Scan Files
$files = File::files($sourceDir);
$movedCount = 0;
$skippedCount = 0;

foreach ($files as $file) {
    if ($file->getExtension() !== 'mp4') {
        continue;
    }

    $fileName = $file->getFilename();
    $baseName = pathinfo($fileName, PATHINFO_FILENAME); // e.g. "video-v66b1a1a-L42BA"

    // Cari di database berdasarkan slug yang menyerupai nama file
    $video = Video::where('slug', 'LIKE', '%' . $baseName . '%')->first();

    $needsUpload = false;

    if (!$video) {
        // Jika tidak ada di DB, asumsikan belum diupload
        $needsUpload = true;
    } else {
        // Cek status hosting Streamtape
        $status = is_string($video->hosting_status) ? json_decode($video->hosting_status, true) : $video->hosting_status;
        
        if (!isset($status['streamtape']) || $status['streamtape'] !== 'success') {
            $needsUpload = true;
        }
    }

    // 4. Action Move File
    if ($needsUpload) {
        $sourcePath = $file->getPathname();
        $targetPath = $targetDir . '/' . $fileName;

        if (!file_exists($sourcePath)) {
            continue; // File was already moved by another process
        }

        if (rename($sourcePath, $targetPath)) {
            echo "[DIPINDAH] $fileName -> (Belum di Streamtape)\n";
            $movedCount++;
        } else {
            echo "[GAGAL PINDAH] $fileName\n";
        }
    } else {
        $skippedCount++;
    }
}

echo "\n============================================\n";
echo "PROSES SELESAI!\n";
echo "Total Video dipindah ke pending : $movedCount\n";
echo "Total Video aman (Sudah di Streamtape) : $skippedCount\n";
echo "Lokasi Folder Pending: $targetDir\n";
echo "============================================\n";
echo "Catatan: Setelah selesai diupload manual, Anda bisa memindahkan ('Cut & Paste') kembali\nsemua file dari folder 'streamtape_pending' ke folder 'videos' agar terbaca normal di aplikasi lokal Anda.\n";
