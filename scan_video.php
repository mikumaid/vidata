<?php
require_once 'config.php';

// Change this to your video directory
$videoDir = 'media'; // or your actual path

if (!is_dir($videoDir)) {
    die("Video directory not found: $videoDir");
}

// Get all video files
$videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
$videos = [];

foreach ($videoExtensions as $ext) {
    $videos = array_merge($videos, glob("$videoDir/*.$ext"));
}

echo "Found " . count($videos) . " video files\n";

// Insert into database
$inserted = 0;
foreach ($videos as $videoPath) {
    $fileName = basename($videoPath);
    
    $stmt = $db->prepare("INSERT OR IGNORE INTO videos (file_name, file_path) VALUES (?, ?)");
    $stmt->bindValue(1, $fileName, SQLITE3_TEXT);
    $stmt->bindValue(2, $videoPath, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        $inserted++;
    }
}

echo "Inserted $inserted new videos into database\n";

// Add some sample categories and tags
$sampleCategories = ['Entertainment', 'Education', 'Sports', 'Music', 'Gaming'];
$sampleTags = ['funny', 'educational', 'action', 'tutorial', 'review'];

foreach ($sampleCategories as $cat) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO categories (category) VALUES (?)");
    $stmt->bindValue(1, $cat, SQLITE3_TEXT);
    $stmt->execute();
}

foreach ($sampleTags as $tag) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO tags (tag) VALUES (?)");
    $stmt->bindValue(1, $tag, SQLITE3_TEXT);
    $stmt->execute();
}

echo "Added sample categories and tags\n";
?>