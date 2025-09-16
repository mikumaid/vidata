<?php
// includes/functions.php

function getCurrentVideo($db, $videoId) {
    $specificVideo = $videoId > 0;
    
    if ($specificVideo) {
        $stmt = $db->prepare('SELECT * FROM videos WHERE id = ?');
        $stmt->bindValue(1, $videoId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
    } else {
        $stmt = $db->prepare('SELECT * FROM videos WHERE is_processed = 0 LIMIT 1');
        $result = $stmt->execute();
        $currentVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
        
        if (!$currentVideo) {
            $stmt = $db->prepare('SELECT * FROM videos ORDER BY id ASC LIMIT 1');
            $result = $stmt->execute();
            $currentVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
        }
        return $currentVideo;
    }
}

function getNavigationVideos($db, $currentVideo, $specificVideo) {
    $prevVideo = null;
    $nextVideo = null;
    
    if ($currentVideo) {
        // Get ANY previous video
        $stmt = $db->prepare('SELECT * FROM videos WHERE id < ? ORDER BY id DESC LIMIT 1');
        $stmt->bindValue(1, $currentVideo['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $prevVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
        
        if ($specificVideo) {
            // For specific video, get ANY next video
            $stmt = $db->prepare('SELECT * FROM videos WHERE id > ? ORDER BY id ASC LIMIT 1');
            $stmt->bindValue(1, $currentVideo['id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $nextVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
        } else {
            // For default view, try next unprocessed first
            $stmt = $db->prepare('SELECT * FROM videos WHERE id > ? AND is_processed = 0 ORDER BY id ASC LIMIT 1');
            $stmt->bindValue(1, $currentVideo['id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $nextVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
            
            if (!$nextVideo) {
                $stmt = $db->prepare('SELECT * FROM videos WHERE id > ? ORDER BY id ASC LIMIT 1');
                $stmt->bindValue(1, $currentVideo['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                $nextVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
            }
        }
    }
    
    return [$prevVideo, $nextVideo];
}

function getThumbnailUrl($currentVideo) {
    $thumbnailUrl = '';
    if ($currentVideo) {
        $videoPath = $currentVideo['file_path'];
        if (file_exists($videoPath)) {
            $durationCmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 " . escapeshellarg($videoPath) . " 2>/dev/null";
            $duration = floatval(shell_exec($durationCmd));
            if ($duration > 0) {
                $safeStart = $duration * 0.05;
                $safeEnd = $duration * 0.95;
                $randomTime = $safeStart + (mt_rand() / mt_getrandmax()) * ($safeEnd - $safeStart);
                $thumbnailUrl = "thumbnail.php?video=" . urlencode($videoPath) . "&time=" . urlencode(sprintf("%.2f", $randomTime)) . "&t=" . time();
            }
        }
    }
    return $thumbnailUrl;
}

function getVideoData($db, $currentVideo) {
    // Get video metadata
    $videoMetadata = [];
    if ($currentVideo) {
        $videoMetadata = getVideoMetadata($currentVideo['file_path']);
        if (empty($currentVideo['orientation']) && $videoMetadata['orientation'] !== 'unknown') {
            $currentVideo['orientation'] = $videoMetadata['orientation'];
        }
    }
    
    // Get all categories and tags
    $categories = [];
    $tags = [];
    
    $categoryResult = $db->query('SELECT category FROM categories');
    while ($row = $categoryResult->fetchArray(SQLITE3_ASSOC)) {
        $categories[] = htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8');
    }
    
    $tagResult = $db->query('SELECT tag FROM tags');
    while ($row = $tagResult->fetchArray(SQLITE3_ASSOC)) {
        $tags[] = htmlspecialchars($row['tag'], ENT_QUOTES, 'UTF-8');
    }
    
    // Get existing tags/categories for current video
    $videoCategories = [];
    $videoTags = [];
    
    if ($currentVideo) {
        $stmt = $db->prepare("SELECT c.category FROM categories c JOIN video_categories vc ON c.id = vc.category_id WHERE vc.video_id = ?");
        $stmt->bindValue(1, $currentVideo['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $videoCategories[] = htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8');
        }
        
        $stmt = $db->prepare("SELECT t.tag FROM tags t JOIN video_tags vt ON t.id = vt.tag_id WHERE vt.video_id = ?");
        $stmt->bindValue(1, $currentVideo['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $videoTags[] = htmlspecialchars($row['tag'], ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Progress stats
    $totalVideos = $db->querySingle('SELECT COUNT(*) FROM videos');
    $processedVideos = $db->querySingle('SELECT COUNT(*) FROM videos WHERE is_processed = 1');
    $remainingVideos = $totalVideos - $processedVideos;
    $progressPercent = $totalVideos > 0 ? ($processedVideos / $totalVideos * 100) : 0;
    
    return [
        'videoMetadata' => $videoMetadata,
        'categories' => $categories,
        'tags' => $tags,
        'videoCategories' => $videoCategories,
        'videoTags' => $videoTags,
        'totalVideos' => $totalVideos,
        'processedVideos' => $processedVideos,
        'remainingVideos' => $remainingVideos,
        'progressPercent' => $progressPercent
    ];
}
?>