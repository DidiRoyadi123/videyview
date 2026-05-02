<?php

/**
 * VideyView Thumbnail Generator (Portable)
 * 
 * Usage: php generate_thumbnails.php [path_to_ffmpeg]
 * Example: php generate_thumbnails.php C:\ffmpeg\bin\ffmpeg.exe
 */

// Robust .env parser
function getEnvValue($key, $default = null) {
    if (!file_exists('.env')) return $default;
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        if (trim($name) == $key) {
            return trim($value, " \t\n\r\0\x0B\"'");
        }
    }
    return $default;
}

$dbHost = getEnvValue('DB_HOST', '127.0.0.1');
$dbName = getEnvValue('DB_DATABASE', 'videyview');
$dbUser = getEnvValue('DB_USERNAME', 'root');
$dbPass = getEnvValue('DB_PASSWORD', '');

echo "Connecting to database: $dbName...\n";

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}

$ffmpeg = $argv[1] ?? 'ffmpeg';

// Check ffmpeg
exec("\"$ffmpeg\" -version", $out, $res);
if ($res !== 0) {
    echo "ERROR: FFmpeg not found at '$ffmpeg'.\n";
    echo "Please download FFmpeg (https://ffmpeg.org/download.html) and provide the path to ffmpeg.exe.\n";
    exit(1);
}

// Ensure directory exists
$thumbDir = __DIR__ . '/public/storage/thumbnails';
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0777, true);
}

// DNS Bypass Logic for FFmpeg
function getDirectIp($host) {
    echo "Resolving $host via DoH...\n";
    $ch = curl_init("https://cloudflare-dns.com/dns-query?name=$host&type=A");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/dns-json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    return $data['Answer'][0]['data'] ?? '172.67.73.18';
}

// Fetch videos
$stmt = $pdo->query("SELECT id, url, title FROM videos WHERE thumbnail_url IS NULL");
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($videos) . " videos needing thumbnails.\n";

foreach ($videos as $video) {
    $id = $video['id'];
    $url = $video['url'];
    $host = parse_url($url, PHP_URL_HOST);
    $path = parse_url($url, PHP_URL_PATH);
    
    $ip = getDirectIp($host);
    $directUrl = "https://$ip$path";

    $filename = "thumb_" . $id . "_" . time() . ".jpg";
    $outputPath = $thumbDir . '/' . $filename;
    
    echo "Processing: " . $video['title'] . " (Bypassing ISP for $host)...\n";
    
    // FFmpeg with Host header and direct IP + SSL verification skip if needed
    // Using -headers to pass the original Host for Cloudflare
    $headers = "Host: $host\r\nUser-Agent: Mozilla/5.0";
    $cmd = "\"$ffmpeg\" -headers \"$headers\" -ss 00:00:01 -i \"$directUrl\" -vframes 1 -q:v 2 \"$outputPath\" -y 2>&1";
    
    exec($cmd, $output, $resultCode);
    
    if ($resultCode === 0 && file_exists($outputPath)) {
        $publicUrl = '/storage/thumbnails/' . $filename;
        $update = $pdo->prepare("UPDATE videos SET thumbnail_url = ? WHERE id = ?");
        $update->execute([$publicUrl, $id]);
        echo "SUCCESS: $publicUrl\n";
    } else {
        echo "FAILED: Capture failed. ISP might still be blocking IP $ip or SSL mismatch.\n";
        // Optionally print output for debug
        // echo implode("\n", $output) . "\n";
    }
}

echo "Done.\n";
