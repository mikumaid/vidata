<?php
// thumbnail.php - Generates thumbnail on demand
if (!isset($_GET['video']) || !isset($_GET['time'])) {
    http_response_code(400);
    exit('Missing parameters');
}

$videoPath = urldecode($_GET['video']);
$time = floatval($_GET['time']);

// Security check - ensure video path is within allowed directory
$allowedDir = 'media';
if (strpos(realpath(dirname($videoPath)), realpath($allowedDir)) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

if (!file_exists($videoPath)) {
    http_response_code(404);
    exit('Video not found');
}

// Generate thumbnail using FFmpeg
$tempFile = tempnam(sys_get_temp_dir(), 'thumb_') . '.jpg';
$ffmpegCmd = "ffmpeg -ss " . escapeshellarg($time) . " -i " . escapeshellarg($videoPath) . " -frames:v 1 -q:v 2 " . escapeshellarg($tempFile) . " 2>&1";
shell_exec($ffmpegCmd);

// Check if thumbnail was created
if (file_exists($tempFile) && filesize($tempFile) > 0) {
    // Serve the image
    header('Content-Type: image/jpeg');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tempFile);
    
    // Clean up temporary file
    unlink($tempFile);
} else {
    // Fallback to a default image or error
    http_response_code(500);
    echo "Failed to generate thumbnail";
}

exit;
?>