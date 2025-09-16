<?php
require_once 'includes/config.php';

// Get overall statistics
$totalVideos = $db->querySingle('SELECT COUNT(*) FROM videos');
$processedVideos = $db->querySingle('SELECT COUNT(*) FROM videos WHERE is_processed = 1');
$unprocessedVideos = $totalVideos - $processedVideos;
$flaggedVideos = $db->querySingle('SELECT COUNT(*) FROM videos WHERE flagged = 1');
$ratedVideos = $db->querySingle('SELECT COUNT(*) FROM videos WHERE rating > 0');

// Get rating distribution
$ratingDistribution = [];
$result = $db->query("SELECT rating, COUNT(*) as count FROM videos WHERE rating > 0 GROUP BY rating ORDER BY rating DESC");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
  $ratingDistribution[$row['rating']] = $row['count'];
}

// Get top categories
$topCategories = [];
$result = $db->query("
  SELECT c.category, COUNT(vc.video_id) as count 
  FROM categories c 
  JOIN video_categories vc ON c.id = vc.category_id 
  GROUP BY c.id, c.category 
  ORDER BY count DESC 
  LIMIT 10
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
  $topCategories[] = $row;
}

// Get top tags
$topTags = [];
$result = $db->query("
  SELECT t.tag, COUNT(vt.video_id) as count 
  FROM tags t 
  JOIN video_tags vt ON t.id = vt.tag_id 
  GROUP BY t.id, t.tag 
  ORDER BY count DESC 
  LIMIT 10
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
  $topTags[] = $row;
}

// Get video processing trend (last 30 days)
$processingTrend = [];
$result = $db->query("
  SELECT DATE(created_at) as date, COUNT(*) as count 
  FROM videos 
  WHERE created_at >= DATE('now', '-30 days') 
  GROUP BY DATE(created_at) 
  ORDER BY date
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
  $processingTrend[] = $row;
}

// Get average rating
$averageRating = $db->querySingle('SELECT AVG(rating) FROM videos WHERE rating > 0');

// Get video size statistics
$totalSize = $db->querySingle('SELECT SUM(size) FROM (
  SELECT 
    CASE 
      WHEN file_path LIKE "media/%" THEN 
        (SELECT size FROM (
          SELECT file_path, 
                 CASE 
                   WHEN file_path LIKE "media/%" THEN LENGTH(file_path) 
                   ELSE 0 
                 END as size
          FROM videos 
          WHERE file_path LIKE "media/%" 
          LIMIT 1
        ))
      ELSE 0 
    END as size
  FROM videos
)');

// For actual file sizes, we'd need to scan the files, but for now we'll use a simpler approach
$fileCount = $db->querySingle('SELECT COUNT(*) FROM videos');
$avgFileSize = $fileCount > 0 ? "N/A" : "N/A";
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
  <title>Dashboard - Video Collection Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <link href="FA6/css/all.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .stat-card {
      transition: transform 0.2s;
    }
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .progress {
      height: 10px;
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
        <a class="nav-link" href="database.php">
          <i class="fa-solid fa-database"></i> Database
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="dashboard.php">
          <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
      </li>
    </ul>
  </div>

  <main class="container mt-3">
    <div class="row mb-3">
      <div class="col-12">
        <h3><i class="fa-solid fa-chart-line"></i> Collection Dashboard</h3>
        <p class="text-muted">Statistics and insights about your video collection</p>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="card stat-card h-100">
          <div class="card-body text-center">
            <h3><?php echo $totalVideos; ?></h3>
            <p class="text-muted mb-0">Total Videos</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card stat-card h-100">
          <div class="card-body text-center">
            <h3><?php echo $processedVideos; ?></h3>
            <div class="progress mt-2">
              <div class="progress-bar bg-success" role="progressbar" 
                style="width: <?php echo $totalVideos > 0 ? ($processedVideos / $totalVideos * 100) : 0; ?>%"></div>
            </div>
            <p class="text-muted mb-0 mt-2">Processed</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card stat-card h-100">
          <div class="card-body text-center">
            <h3><?php echo $ratedVideos; ?></h3>
            <div class="progress mt-2">
              <div class="progress-bar bg-info" role="progressbar" 
                style="width: <?php echo $totalVideos > 0 ? ($ratedVideos / $totalVideos * 100) : 0; ?>%"></div>
            </div>
            <p class="text-muted mb-0 mt-2">Rated</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card stat-card h-100">
          <div class="card-body text-center">
            <h3><?php echo $flaggedVideos; ?></h3>
            <p class="text-muted mb-0">Flagged</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Rating Distribution -->
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-star"></i> Rating Distribution</h5>
          </div>
          <div class="card-body">
            <?php if (empty($ratingDistribution)): ?>
              <p class="text-muted text-center">No ratings yet</p>
            <?php else: ?>
              <div class="row">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <div class="col-12 mb-2">
                    <div class="d-flex align-items-center">
                      <div class="me-2" style="width: 25%;">
                        <?php for ($j = 1; $j <= 5; $j++): ?>
                          <i class="fa-solid fa-star <?php echo $j <= $i ? 'text-warning' : 'text-muted'; ?>"></i>
                        <?php endfor; ?>
                      </div>
                      <div class="flex-grow-1">
                        <div class="progress" style="height: 20px;">
                          <?php $count = $ratingDistribution[$i] ?? 0; ?>
                          <div class="progress-bar bg-warning" role="progressbar" 
                            style="width: <?php echo $ratedVideos > 0 ? ($count / $ratedVideos * 100) : 0; ?>%">
                            <?php echo $count; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endfor; ?>
              </div>
              <div class="text-center mt-3">
                <small class="text-muted">
                  Average Rating: <?php echo $averageRating ? number_format($averageRating, 1) : 'N/A'; ?> stars
                </small>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Top Categories -->
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-list"></i> Top Categories</h5>
          </div>
          <div class="card-body">
            <?php if (empty($topCategories)): ?>
              <p class="text-muted text-center">No categories yet</p>
            <?php else: ?>
              <div class="list-group">
                <?php foreach ($topCategories as $index => $category): ?>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                      <?php echo htmlspecialchars($category['category'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="badge bg-primary rounded-pill">
                      <?php echo $category['count']; ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Top Tags -->
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-tags"></i> Top Tags</h5>
          </div>
          <div class="card-body">
            <?php if (empty($topTags)): ?>
              <p class="text-muted text-center">No tags yet</p>
            <?php else: ?>
              <div class="list-group">
                <?php foreach ($topTags as $index => $tag): ?>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                      <?php echo htmlspecialchars($tag['tag'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="badge bg-secondary rounded-pill">
                      <?php echo $tag['count']; ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Processing Progress -->
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-tasks"></i> Processing Progress</h5>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
              <span>Progress</span>
              <span><?php echo $totalVideos > 0 ? round(($processedVideos / $totalVideos) * 100, 1) : 0; ?>%</span>
            </div>
            <div class="progress mb-3">
              <div class="progress-bar" role="progressbar" 
                style="width: <?php echo $totalVideos > 0 ? ($processedVideos / $totalVideos * 100) : 0; ?>%"></div>
            </div>
            <div class="row text-center">
              <div class="col-6">
                <h4><?php echo $processedVideos; ?></h4>
                <small class="text-muted">Processed</small>
              </div>
              <div class="col-6">
                <h4><?php echo $unprocessedVideos; ?></h4>
                <small class="text-muted">Remaining</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-bolt"></i> Quick Actions</h5>
          </div>
          <div class="card-body">
            <div class="btn-group" role="group">
              <a href="index.php" class="btn btn-primary">
                <i class="fa-solid fa-play"></i> Start Tagging
              </a>
              <a href="database.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-database"></i> Browse Database
              </a>
              <a href="database.php?processed=no" class="btn btn-outline-warning">
                <i class="fa-solid fa-clock"></i> Unprocessed Videos
              </a>
              <a href="database.php?rating=0" class="btn btn-outline-info">
                <i class="fa-solid fa-star"></i> Unrated Videos
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
