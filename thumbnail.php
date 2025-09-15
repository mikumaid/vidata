<?php
// thumbnail.php
if (!isset($_GET['video']) || !isset($_GET['time'])) {
    http_response_code(400);
    exit('Missing parameters');
}

$relativePath = urldecode($_GET['video']); // This is the relative path from DB
$time = floatval($_GET['time']);

// Convert relative path to system path for FFmpeg
$systemPath = __DIR__ . '/' . $relativePath;

// Security check - ensure path is within allowed directory
$allowedDir = __DIR__ . '/media';
if (strpos(realpath(dirname($systemPath)), realpath($allowedDir)) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

if (!file_exists($systemPath)) {
    http_response_code(404);
    exit('Video not found');
}

// Generate thumbnail using FFmpeg
$tempFile = tempnam(sys_get_temp_dir(), 'thumb_') . '.jpg';
$ffmpegCmd = "ffmpeg -ss " . escapeshellarg($time) . " -i " . escapeshellarg($systemPath) . " -frames:v 1 -vf scale=640:360:force_original_aspect_ratio=decrease -q:v 2 " . escapeshellarg($tempFile) . " 2>&1";
shell_exec($ffmpegCmd);

if (file_exists($tempFile) && filesize($tempFile) > 0) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tempFile);
    unlink($tempFile);
} else {
    // Serve default placeholder
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');
}
exit;
?>