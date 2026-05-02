<?php

/**
 * VideyView Thumbnail Fixer
 * This script scans the videos directory and generates missing thumbnails.
 */

$root = __DIR__;
$ffmpeg = $root . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg.exe';
$videoDir = $root . '/storage/app/public/videos';
$thumbDir = $root . '/storage/app/public/thumbnails';

if (!file_exists($ffmpeg)) {
    die("Error: FFmpeg not found at $ffmpeg\n");
}

if (!file_exists($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

$videos = glob("$videoDir/*.mp4");
echo "Found " . count($videos) . " videos.\n";

$count = 0;
$fixed = 0;
$failed = 0;

foreach ($videos as $videoPath) {
    $filename = basename($videoPath);
    $slug = pathinfo($filename, PATHINFO_FILENAME);
    $thumbPath = "$thumbDir/$slug.jpg";

    if (!file_exists($thumbPath) || filesize($thumbPath) == 0) {
        echo "Generating thumbnail for $slug... ";
        
        $cmd = "\"$ffmpeg\" -y -ss 0.1 -i \"$videoPath\" -vframes 1 -q:v 2 \"$thumbPath\" 2>&1";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0) {
            echo "DONE\n";
            $fixed++;
        } else {
            echo "FAILED\n";
            echo "  Error: " . implode("\n  ", array_slice($output, -2)) . "\n";
            $failed++;
        }
    }
    $count++;
}

echo "\nSummary:\n";
echo "Processed: $count\n";
echo "Generated: $fixed\n";
echo "Failed:    $failed\n";
echo "Already existed: " . ($count - $fixed - $failed) . "\n";
