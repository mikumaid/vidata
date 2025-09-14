<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    // Get POST data
    $videoId = intval($_POST['video_id'] ?? 0);
    $orientation = $_POST['orientation'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $quality = $_POST['quality'] ?? '';
    $categories = $_POST['categories'] ?? [];
    $tags = $_POST['tags'] ?? [];
    $flagged = intval($_POST['flagged'] ?? 0);
    
    if (!$videoId) {
        throw new Exception('Invalid video ID');
    }
    
    // Start transaction
    $db->exec('BEGIN TRANSACTION');
    
    // Update video
    $stmt = $db->prepare("
        UPDATE videos 
        SET orientation = ?, duration = ?, quality = ?, flagged = ?, is_processed = 1 
        WHERE id = ?
    ");
    $stmt->bindValue(1, $orientation, SQLITE3_TEXT);
    $stmt->bindValue(2, $duration, SQLITE3_TEXT);
    $stmt->bindValue(3, $quality, SQLITE3_TEXT);
    $stmt->bindValue(4, $flagged, SQLITE3_INTEGER);
    $stmt->bindValue(5, $videoId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Clear existing relationships
    $stmt = $db->prepare("DELETE FROM video_categories WHERE video_id = ?");
    $stmt->bindValue(1, $videoId, SQLITE3_INTEGER);
    $stmt->execute();
    
    $stmt = $db->prepare("DELETE FROM video_tags WHERE video_id = ?");
    $stmt->bindValue(1, $videoId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Handle categories
    if (is_array($categories)) {
        foreach ($categories as $category) {
            $category = trim($category);
            if (!empty($category)) {
                // Insert category if not exists
                $stmt = $db->prepare("INSERT OR IGNORE INTO categories (category) VALUES (?)");
                $stmt->bindValue(1, $category, SQLITE3_TEXT);
                $stmt->execute();
                
                // Get category ID using prepared statement
                $stmt = $db->prepare("SELECT id FROM categories WHERE category = ?");
                $stmt->bindValue(1, $category, SQLITE3_TEXT);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);
                $categoryId = $row['id'];
                
                // Insert relationship
                $stmt = $db->prepare("INSERT OR IGNORE INTO video_categories (video_id, category_id) VALUES (?, ?)");
                $stmt->bindValue(1, $videoId, SQLITE3_INTEGER);
                $stmt->bindValue(2, $categoryId, SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
    }
    
    // Handle tags
    if (is_array($tags)) {
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                // Insert tag if not exists
                $stmt = $db->prepare("INSERT OR IGNORE INTO tags (tag) VALUES (?)");
                $stmt->bindValue(1, $tag, SQLITE3_TEXT);
                $stmt->execute();
                
                // Get tag ID using prepared statement
                $stmt = $db->prepare("SELECT id FROM tags WHERE tag = ?");
                $stmt->bindValue(1, $tag, SQLITE3_TEXT);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);
                $tagId = $row['id'];
                
                // Insert relationship
                $stmt = $db->prepare("INSERT OR IGNORE INTO video_tags (video_id, tag_id) VALUES (?, ?)");
                $stmt->bindValue(1, $videoId, SQLITE3_INTEGER);
                $stmt->bindValue(2, $tagId, SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
    }
    
    // Commit transaction
    $db->exec('COMMIT');
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>