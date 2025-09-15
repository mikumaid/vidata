<?php
require_once 'config.php';

// Get scan mode from command line argument
$syncMode = isset($argv[1]) && $argv[1] === '--sync';

$videoDir = __DIR__ . '/media'; // System path for scanning
$webPathPrefix = 'media/'; // Relative path for web access

if (!is_dir($videoDir)) {
    die("Video directory not found: $videoDir\n");
}

// Get all video files
$videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv', 'wmv'];
$videos = [];

foreach ($videoExtensions as $ext) {
    $videos = array_merge($videos, glob("$videoDir/*.$ext"));
}

echo "Found " . count($videos) . " video files\n";

$inserted = 0;
$updated = 0;

// Insert or update videos with relative paths
foreach ($videos as $videoPath) {
    $fileName = basename($videoPath);
    
    // Store relative path for web access
    $relativePath = $webPathPrefix . $fileName;
    
    if ($syncMode) {
        // Sync mode: Update file path if it changed
        $stmt = $db->prepare("INSERT OR REPLACE INTO videos (file_name, file_path, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $stmt->bindValue(1, $fileName, SQLITE3_TEXT);
        $stmt->bindValue(2, $relativePath, SQLITE3_TEXT); // Use relative path
        if ($stmt->execute()) {
            // Check if it was updated or inserted
            if ($db->changes() > 0) {
                $updated++;
            }
        }
    } else {
        // Append mode: Only insert new videos
        $stmt = $db->prepare("INSERT OR IGNORE INTO videos (file_name, file_path) VALUES (?, ?)");
        $stmt->bindValue(1, $fileName, SQLITE3_TEXT);
        $stmt->bindValue(2, $relativePath, SQLITE3_TEXT); // Use relative path
        if ($stmt->execute() && $db->changes() > 0) {
            $inserted++;
        }
    }
}

if ($syncMode) {
    echo "Synced database: $updated videos updated/added\n";
    
    // Optional: Remove videos that no longer exist (be careful!)
    if (isset($argv[2]) && $argv[2] === '--cleanup') {
        echo "Cleaning up orphaned entries...\n";
        $removed = 0;
        
        // Get all videos from database
        $result = $db->query("SELECT id, file_name, file_path FROM videos");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Convert relative path to system path for file existence check
            $systemPath = __DIR__ . '/' . $row['file_path'];
            if (!file_exists($systemPath)) {
                $stmt = $db->prepare("DELETE FROM videos WHERE id = ?");
                $stmt->bindValue(1, $row['id'], SQLITE3_INTEGER);
                $stmt->execute();
                
                // Also remove relationships
                $db->exec("DELETE FROM video_categories WHERE video_id = " . $row['id']);
                $db->exec("DELETE FROM video_tags WHERE video_id = " . $row['id']);
                
                $removed++;
                echo "Removed: " . $row['file_name'] . "\n";
            }
        }
        echo "Cleaned up $removed orphaned videos\n";
    }
} else {
    echo "Added $inserted new videos to database\n";
}

echo "Sample categories and tags added\n";
echo "Usage:\n";
echo "  php scan_videos.php          # Add new videos only\n";
echo "  php scan_videos.php --sync   # Sync all videos (update paths)\n";
echo "  php scan_videos.php --sync --cleanup # Sync and remove missing videos\n";
?>