<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

try {
    $videoId = intval($_POST['video_id'] ?? 0);
    
    error_log("=== DELETE DEBUG ===");
    error_log("Video ID received: $videoId");
    
    if (!$videoId) {
        throw new Exception('Invalid video ID: ' . $videoId);
    }
    
    // First, check if video exists
    $checkStmt = $db->prepare("SELECT id, file_name, file_path FROM videos WHERE id = ?");
    $checkStmt->bindValue(1, $videoId, SQLITE3_INTEGER);
    $checkResult = $checkStmt->execute();
    $video = $checkResult ? $checkResult->fetchArray(SQLITE3_ASSOC) : null;
    
    error_log("Video exists check: " . ($video ? 'YES' : 'NO'));
    if ($video) {
        error_log("Video data: " . print_r($video, true));
    }
    
    if (!$video) {
        // Let's check what videos actually exist
        $allVideos = $db->query("SELECT id, file_name FROM videos LIMIT 10");
        $videosList = [];
        while ($row = $allVideos->fetchArray(SQLITE3_ASSOC)) {
            $videosList[] = $row;
        }
        error_log("Existing videos: " . print_r($videosList, true));
        throw new Exception('Video not found in database. Available IDs: ' . implode(', ', array_column($videosList, 'id')));
    }
    
    // Start transaction
    $db->exec('BEGIN TRANSACTION');
    error_log("Transaction started");
    
    // Delete relationships first
    $stmt1 = $db->prepare("DELETE FROM video_categories WHERE video_id = ?");
    $stmt1->bindValue(1, $videoId, SQLITE3_INTEGER);
    $stmt1->execute();
    $catChanges = $db->changes();
    error_log("Deleted $catChanges video_categories");
    
    $stmt2 = $db->prepare("DELETE FROM video_tags WHERE video_id = ?");
    $stmt2->bindValue(1, $videoId, SQLITE3_INTEGER);
    $stmt2->execute();
    $tagChanges = $db->changes();
    error_log("Deleted $tagChanges video_tags");
    
    // Delete video from database
    $stmt3 = $db->prepare("DELETE FROM videos WHERE id = ?");
    $stmt3->bindValue(1, $videoId, SQLITE3_INTEGER);
    $result = $stmt3->execute();
    
    error_log("DELETE statement executed: " . ($result ? 'SUCCESS' : 'FAILED'));
    
    // Check how many rows were affected
    $videoChanges = $db->changes();
    error_log("Video rows affected: $videoChanges");
    
    if ($videoChanges == 0) {
        throw new Exception("No video rows deleted. Video ID $videoId exists in check but not in delete. This might be a database locking issue.");
    }
    
    // Delete actual file
    $filePath = __DIR__ . '/../' . $video['file_path'];
    $deletedFile = false;
    
    error_log("File path to delete: $filePath");
    error_log("File exists before delete: " . (file_exists($filePath) ? 'YES' : 'NO'));
    
    if (file_exists($filePath)) {
        $deletedFile = unlink($filePath);
        error_log("File deletion result: " . ($deletedFile ? 'SUCCESS' : 'FAILED'));
        if (!$deletedFile) {
            error_log("Failed to delete file: " . $filePath);
        }
    } else {
        $deletedFile = true;
        error_log("File already doesn't exist");
    }
    
    // Commit transaction
    $db->exec('COMMIT');
    error_log("Transaction committed");
    
    echo json_encode([
        'success' => true,
        'message' => 'Video deleted successfully',
        'file_deleted' => $deletedFile,
        'rows_affected' => $videoChanges,
        'debug' => [
            'cat_changes' => $catChanges,
            'tag_changes' => $tagChanges,
            'video_changes' => $videoChanges
        ]
    ]);
    
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    error_log("Delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>