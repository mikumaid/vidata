<?php
// includes/db_functions.php

function getDatabaseVideos($db, $params) {
    extract($params); // Extract all parameters
    
    // Build query with filters
    $sql = "SELECT v.*, 
                   GROUP_CONCAT(DISTINCT c.category) as categories,
                   GROUP_CONCAT(DISTINCT t.tag) as tags
            FROM videos v
            LEFT JOIN video_categories vc ON v.id = vc.video_id
            LEFT JOIN categories c ON vc.category_id = c.id
            LEFT JOIN video_tags vt ON v.id = vt.video_id
            LEFT JOIN tags t ON vt.tag_id = t.id";
    
    $countSql = "SELECT COUNT(DISTINCT v.id) as total
                 FROM videos v
                 LEFT JOIN video_categories vc ON v.id = vc.video_id
                 LEFT JOIN categories c ON vc.category_id = c.id
                 LEFT JOIN video_tags vt ON v.id = vt.video_id
                 LEFT JOIN tags t ON vt.tag_id = t.id";
    
    $params = [];
    $whereConditions = [];
    $countParams = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(v.file_name LIKE ?)";
        $params[] = '%' . $search . '%';
        $countParams[] = '%' . $search . '%';
    }
    
    if (!empty($categoryFilter)) {
        $whereConditions[] = "c.category = ?";
        $params[] = $categoryFilter;
        $countParams[] = $categoryFilter;
    }
    
    if (!empty($tagFilter)) {
        $whereConditions[] = "t.tag = ?";
        $params[] = $tagFilter;
        $countParams[] = $tagFilter;
    }
    
    if ($flaggedOnly) {
        $whereConditions[] = "v.flagged = 1";
        $countParams[] = 1;
    }
    
    if ($processedOnly === 'yes') {
        $whereConditions[] = "v.is_processed = 1";
        $countParams[] = 1;
    } elseif ($processedOnly === 'no') {
        $whereConditions[] = "v.is_processed = 0";
        $countParams[] = 0;
    }
    
    // Add rating filter
    if (!empty($ratingFilter)) {
        if ($ratingFilter === 'rated') {
            $whereConditions[] = "v.rating > 0";
            $countParams[] = 0;
        } else {
            $whereConditions[] = "v.rating = ?";
            $params[] = $ratingFilter;
            $countParams[] = $ratingFilter;
        }
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
        $countSql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " GROUP BY v.id ORDER BY v.$sortBy $sortOrder LIMIT $perPage OFFSET $offset";
    
    // Get total count for pagination
    $countStmt = $db->prepare($countSql);
    foreach ($countParams as $i => $param) {
        $countStmt->bindValue($i + 1, $param, SQLITE3_TEXT);
    }
    $countResult = $countStmt->execute();
    $totalVideos = $countResult ? $countResult->fetchArray(SQLITE3_ASSOC)['total'] : 0;
    $totalPages = ceil($totalVideos / $perPage);
    
    // Get videos for current page
    $stmt = $db->prepare($sql);
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param, SQLITE3_TEXT);
    }
    $result = $stmt->execute();
    
    $videos = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $videos[] = $row;
    }
    
    return [
        'videos' => $videos,
        'totalVideos' => $totalVideos,
        'totalPages' => $totalPages
    ];
}

function getFilterOptions($db) {
    // Get all categories and tags for filter dropdowns
    $allCategories = [];
    $categoryResult = $db->query('SELECT category FROM categories ORDER BY category');
    while ($row = $categoryResult->fetchArray(SQLITE3_ASSOC)) {
        $allCategories[] = htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8');
    }
    
    $allTags = [];
    $tagResult = $db->query('SELECT tag FROM tags ORDER BY tag');
    while ($row = $tagResult->fetchArray(SQLITE3_ASSOC)) {
        $allTags[] = htmlspecialchars($row['tag'], ENT_QUOTES, 'UTF-8');
    }
    
    return [
        'categories' => $allCategories,
        'tags' => $allTags
    ];
}

function buildPaginationQuery($params) {
    extract($params);
    
    // Build query string for pagination links
    $queryParams = [];
    if ($search) $queryParams['search'] = $search;
    if ($categoryFilter) $queryParams['category'] = $categoryFilter;
    if ($tagFilter) $queryParams['tag'] = $tagFilter;
    if ($flaggedOnly) $queryParams['flagged'] = 1;
    if ($processedOnly) $queryParams['processed'] = $processedOnly;
    if ($ratingFilter) $queryParams['rating'] = $ratingFilter;
    if ($sortBy !== 'id') $queryParams['sort'] = $sortBy;
    if ($sortOrder !== 'DESC') $queryParams['order'] = 'asc';
    
    return http_build_query($queryParams);
}
?>