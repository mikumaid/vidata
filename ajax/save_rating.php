<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    $videoId = intval($_POST['video_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    
    if (!$videoId) {
        throw new Exception('Invalid video ID');
    }
    
    if ($rating < 0 || $rating > 5) {
        throw new Exception('Invalid rating value');
    }
    
    $stmt = $db->prepare("UPDATE videos SET rating = ? WHERE id = ?");
    $stmt->bindValue(1, $rating, SQLITE3_INTEGER);
    $stmt->bindValue(2, $videoId, SQLITE3_INTEGER);
    $stmt->execute();
    
    if ($db->changes() == 0) {
        throw new Exception('Video not found');
    }
    
    echo json_encode(['success' => true, 'rating' => $rating]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>