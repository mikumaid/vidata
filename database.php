<?php
require_once 'config.php';

// Get parameters
$page = max(1, intval($_GET['page'] ?? 1));
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$tagFilter = $_GET['tag'] ?? '';
$flaggedOnly = isset($_GET['flagged']) ? 1 : 0;
$processedOnly = $_GET['processed'] ?? '';
$ratingFilter = $_GET['rating'] ?? ''; // Add this line
$sortBy = $_GET['sort'] ?? 'id';
$sortOrder = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$perPage = 12; // Videos per page
$offset = ($page - 1) * $perPage;

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

// Add this to your existing whereConditions
if (!empty($ratingFilter)) {
  if ($ratingFilter === 'rated') {
    $whereConditions[] = "v.rating > 0";
    $countParams[] = 0; // For the count query
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

// Build query string for pagination links
$queryParams = [];
if ($search) $queryParams['search'] = $search;
if ($categoryFilter) $queryParams['category'] = $categoryFilter;
if ($tagFilter) $queryParams['tag'] = $tagFilter;
if ($flaggedOnly) $queryParams['flagged'] = 1;
if ($processedOnly) $queryParams['processed'] = $processedOnly;
if ($ratingFilter) $queryParams['rating'] = $ratingFilter; // Add this line
if ($sortBy !== 'id') $queryParams['sort'] = $sortBy;
if ($sortOrder !== 'DESC') $queryParams['order'] = 'asc';

$baseQuery = http_build_query($queryParams);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="shortcut icon" href="/favicon.ico" />
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
  <link rel="manifest" href="/site.webmanifest" />
  <title>Video Database - Collection Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <link href="FA6/css/all.min.css" rel="stylesheet" />
  <style>
    .video-card {
      transition: transform 0.2s;
      height: 100%;
    }
    .video-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .video-thumbnail {
      height: 150px;
      object-fit: cover;
      background: #000;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .pagination-container {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
    .sort-link {
      color: #6c757d;
      text-decoration: none;
    }
    .sort-link:hover {
      color: #fff;
    }
    .sort-link.active {
      color: #0d6efd;
    }
  </style>
</head>
<body data-bs-theme="dark">
  <div class="container-fluid border-bottom border-2 p-1 m-0">
    <ul class="nav nav-pills justify-content-center">
      <li class="nav-item">
        <a class="nav-link" href="index.php">
          <i class="fa-solid fa-video"></i> Tagging
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="database.php">
          <i class="fa-solid fa-database"></i> Database
        </a>
      </li>
    </ul>
  </div>

  <main class="container mt-3">
    <div class="row mb-3">
      <div class="col-12">
        <h3><i class="fa-solid fa-database"></i> Video Database</h3>
        <p class="text-muted">Manage and browse your video collection</p>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Search Videos</label>
            <input type="text" class="form-control" name="search" 
                 value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                 placeholder="Search by filename...">
          </div>
          <div class="col-md-2">
            <label class="form-label">Category</label>
            <select class="form-select" name="category">
              <option value="">All Categories</option>
              <?php foreach ($allCategories as $cat): ?>
                <option value="<?php echo $cat; ?>" 
                    <?php echo ($categoryFilter === $cat) ? 'selected' : ''; ?>>
                  <?php echo $cat; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Tag</label>
            <select class="form-select" name="tag">
              <option value="">All Tags</option>
              <?php foreach ($allTags as $tag): ?>
                <option value="<?php echo $tag; ?>" 
                    <?php echo ($tagFilter === $tag) ? 'selected' : ''; ?>>
                  <?php echo $tag; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Processed</label>
            <select class="form-select" name="processed">
              <option value="">All</option>
              <option value="yes" <?php echo ($processedOnly === 'yes') ? 'selected' : ''; ?>>Processed</option>
              <option value="no" <?php echo ($processedOnly === 'no') ? 'selected' : ''; ?>>Unprocessed</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Rating</label>
            <select class="form-select" name="rating">
              <option value="">All Ratings</option>
              <option value="rated" <?php echo ($ratingFilter === 'rated') ? 'selected' : ''; ?>>Rated</option>
              <option value="5" <?php echo ($ratingFilter === '5') ? 'selected' : ''; ?>>★★★★★ (5 stars)</option>
              <option value="4" <?php echo ($ratingFilter === '4') ? 'selected' : ''; ?>>★★★★☆ (4 stars)</option>
              <option value="3" <?php echo ($ratingFilter === '3') ? 'selected' : ''; ?>>★★★☆☆ (3 stars)</option>
              <option value="2" <?php echo ($ratingFilter === '2') ? 'selected' : ''; ?>>★★☆☆☆ (2 stars)</option>
              <option value="1" <?php echo ($ratingFilter === '1') ? 'selected' : ''; ?>>★☆☆☆☆ (1 star)</option>
              <option value="0" <?php echo ($ratingFilter === '0') ? 'selected' : ''; ?>>Unrated</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">&nbsp;</label>
            <div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="flagged" 
                     id="flagged-check" <?php echo $flaggedOnly ? 'checked' : ''; ?>>
                <label class="form-check-label" for="flagged-check">
                  Flagged Only
                </label>
              </div>
              <button type="submit" class="btn btn-primary btn-sm mt-2">
                <i class="fa-solid fa-filter"></i> Filter
              </button>
              <a href="database.php" class="btn btn-outline-secondary btn-sm mt-2">
                <i class="fa-solid fa-rotate"></i> Reset
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Results Info and Sorting -->
    <div class="row mb-3">
        <div class="col-md-6">
            <p class="text-muted">
                Showing <?php echo count($videos); ?> of <?php echo $totalVideos; ?> videos
            </p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <a href="?<?php echo $baseQuery . ($baseQuery ? '&' : '') . 'sort=id&order=' . ($sortBy === 'id' && $sortOrder === 'ASC' ? 'desc' : 'asc'); ?>" 
                  class="sort-link <?php echo $sortBy === 'id' ? 'active' : ''; ?>">
                    ID <?php if ($sortBy === 'id'): ?><i class="fa-solid fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?>
                </a>
                <a href="?<?php echo $baseQuery . ($baseQuery ? '&' : '') . 'sort=file_name&order=' . ($sortBy === 'file_name' && $sortOrder === 'ASC' ? 'desc' : 'asc'); ?>" 
                  class="sort-link ms-3 <?php echo $sortBy === 'file_name' ? 'active' : ''; ?>">
                    Name <?php if ($sortBy === 'file_name'): ?><i class="fa-solid fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?>
                </a>
                <a href="?<?php echo $baseQuery . ($baseQuery ? '&' : '') . 'sort=created_at&order=' . ($sortBy === 'created_at' && $sortOrder === 'ASC' ? 'desc' : 'asc'); ?>" 
                  class="sort-link ms-3 <?php echo $sortBy === 'created_at' ? 'active' : ''; ?>">
                    Date <?php if ($sortBy === 'created_at'): ?><i class="fa-solid fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?>
                </a>
                <a href="?<?php echo $baseQuery . ($baseQuery ? '&' : '') . 'sort=rating&order=' . ($sortBy === 'rating' && $sortOrder === 'ASC' ? 'desc' : 'asc'); ?>" 
                  class="sort-link ms-3 <?php echo $sortBy === 'rating' ? 'active' : ''; ?>">
                    Rating <?php if ($sortBy === 'rating'): ?><i class="fa-solid fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="row">
      <?php if (empty($videos)): ?>
        <div class="col-12 text-center">
          <div class="alert alert-info">
            <i class="fa-solid fa-circle-info"></i> No videos found matching your criteria.
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($videos as $video): ?>
          <div class="col-md-3 mb-4">
            <div class="card video-card h-100">
              <div class="position-relative">
                <div class="video-thumbnail">
                  <img src="thumbnail.php?video=<?php echo urlencode($video['file_path']); ?>&time=5&t=<?php echo time(); ?>" 
                     class="img-fluid" 
                     alt="Thumbnail"
                     style="max-height: 150px; object-fit: cover;"
                     onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-video fa-3x\'></i>'">
                </div>
                <?php if ($video['flagged']): ?>
                  <span class="position-absolute top-0 start-0 badge bg-danger m-2">
                    <i class="fa-solid fa-flag"></i>
                  </span>
                <?php endif; ?>
                <?php if (!$video['is_processed']): ?>
                  <span class="position-absolute top-0 end-0 badge bg-warning m-2">
                    <i class="fa-solid fa-clock"></i>
                  </span>
                <?php endif; ?>
              </div>
              <div class="card-body">
                <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($video['file_name'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo htmlspecialchars(substr($video['file_name'], 0, 30), ENT_QUOTES, 'UTF-8'); ?>
                  <?php if (strlen($video['file_name']) > 30): ?>...<?php endif; ?>
                </h6>
                <div class="small text-muted">
                  <?php if ($video['categories']): ?>
                    <div class="mb-1">
                      <i class="fa-solid fa-list"></i> 
                      <?php 
                      $cats = explode(',', $video['categories']);
                      echo implode(', ', array_slice($cats, 0, 2));
                      if (count($cats) > 2) echo ' +'.(count($cats)-2).' more';
                      ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($video['tags']): ?>
                    <div>
                      <i class="fa-solid fa-tags"></i> 
                      <?php 
                      $tags = explode(',', $video['tags']);
                      echo implode(', ', array_slice($tags, 0, 3));
                      if (count($tags) > 3) echo ' +'.(count($tags)-3).' more';
                      ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        ID: <?php echo $video['id']; ?>
                        <?php if ($video['rating'] > 0): ?>
                            <span class="ms-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa-solid fa-star <?php echo $i <= $video['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                <?php endfor; ?>
                            </span>
                        <?php endif; ?>
                    </small>
                    <a href="index.php?video=<?php echo $video['id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-edit"></i> Edit
                    </a>
                </div>
            </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination-container">
        <nav>
          <ul class="pagination">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">
                  <i class="fa-solid fa-chevron-left"></i>
                </a>
              </li>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            if ($startPage > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?page=1<?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">1</a>
              </li>
              <?php if ($startPage > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
              <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">
                  <?php echo $i; ?>
                </a>
              </li>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
              <?php if ($endPage < $totalPages - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">
                  <?php echo $totalPages; ?>
                </a>
              </li>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">
                  <i class="fa-solid fa-chevron-right"></i>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>