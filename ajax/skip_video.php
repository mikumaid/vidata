<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    $videoId = intval($_POST['video_id'] ?? 0);
    
    if (!$videoId) {
        throw new Exception('Invalid video ID');
    }
    
    // Mark video as processed without other data (skip)
    $stmt = $db->prepare("UPDATE videos SET is_processed = 1 WHERE id = ?");
    $stmt->bindValue(1, $videoId, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>