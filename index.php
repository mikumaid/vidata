<?php
require_once 'config.php';

// Get navigation parameters
$videoId = isset($_GET['video']) ? intval($_GET['video']) : 0;
$specificVideo = $videoId > 0;

// Get specific video or first unprocessed
if ($specificVideo) {
    $stmt = $db->prepare('SELECT * FROM videos WHERE id = ?');
    $stmt->bindValue(1, $videoId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $currentVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
} else {
    $stmt = $db->prepare('SELECT * FROM videos WHERE is_processed = 0 LIMIT 1');
    $result = $stmt->execute();
    $currentVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
    
    // If no unprocessed videos, get first video
    if (!$currentVideo) {
        $stmt = $db->prepare('SELECT * FROM videos ORDER BY id ASC LIMIT 1');
        $result = $stmt->execute();
        $currentVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
    }
}

// Get previous and next videos for navigation
$prevVideo = null;
$nextVideo = null;

if ($currentVideo) {
    // Get ANY previous video (processed or unprocessed)
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
        // For default view, try next unprocessed first, then any next
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

// Generate thumbnail
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

// Get video metadata if video exists
$videoMetadata = [];
if ($currentVideo) {
  $videoMetadata = getVideoMetadata($currentVideo['file_path']);
  
  // Auto-detect orientation if not set
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
  // Get existing categories for this video
  $stmt = $db->prepare("
    SELECT c.category 
    FROM categories c 
    JOIN video_categories vc ON c.id = vc.category_id 
    WHERE vc.video_id = ?
  ");
  $stmt->bindValue(1, $currentVideo['id'], SQLITE3_INTEGER);
  $result = $stmt->execute();
  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $videoCategories[] = htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8');
  }
  
  // Get existing tags for this video
  $stmt = $db->prepare("
    SELECT t.tag 
    FROM tags t 
    JOIN video_tags vt ON t.id = vt.tag_id 
    WHERE vt.video_id = ?
  ");
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
  <title>Video Collection Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="playerjs.js"></script>
  <link href="FA6/css/all.min.css" rel="stylesheet" />
  <!-- Tagify -->
  <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
  <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />
  
  <style>
    .tagify {
      --tags-border-color: #495057;
      --tags-hover-border-color: #6c757d;
      --tags-focus-border-color: #86b7fe;
      --tags-background-color: #212529;
      --tags-text-color: #f8f9fa;
      --tag-bg: #343a40;
      --tag-hover: #495057;
      --tag-text-color: #f8f9fa;
      --tag-remove-bg: #dc3545;
      --tag-remove-btn-color: #fff;
      --placeholder-color: #adb5bd;
    }

    .tagify__dropdown,
    .tagify__dropdown__wrapper {
      background: #212529 !important;
      border: 1px solid #495057 !important;
      color: #f8f9fa !important;
    }

    .tagify__dropdown__item {
      color: #f8f9fa !important;
      background: #212529 !important;
    }

    .tagify__dropdown__item--active {
      background: #495057 !important;
      color: #f8f9fa !important;
    }
    
    #video-player {
      width: 100%;
      height: auto;
      background: #000;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
    }
    
    .progress-text {
      font-size: 0.9rem;
      color: #6c757d;
    }
    #delete-btn:hover {
      background-color: #dc3545 !important;
      color: white !important;
      border-color: #dc3545 !important;
    }
    .rating-stars .btn {
        padding: 0.25rem 0.5rem;
        border: none;
    }

    .rating-stars .btn:hover {
        background-color: #ffc107 !important;
        color: #000 !important;
    }

    .rating-stars .btn.active {
        background-color: #ffc107 !important;
        color: #000 !important;
        border-color: #ffc107 !important;
    }

    .rating-text {
        font-weight: 500;
    }
  </style>
</head>
<body data-bs-theme="dark">
  <div class="container-fluid border-bottom border-2 p-1 m-0">
    <ul class="nav nav-pills justify-content-center">
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
          <i class="fa-solid fa-video"></i> Tagging
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'database.php' ? 'active' : ''; ?>" href="database.php">
          <i class="fa-solid fa-database"></i> Database
        </a>
      </li>
    </ul>
  </div>

  <main class="container mt-3">
    <?php if ($currentVideo): ?>
        <!-- Progress Bar -->
        <div class="row mb-3">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <h5 class="mb-0">Video Tagging</h5>
                <small class="text-muted">
                  <?php echo "$processedVideos of $totalVideos videos processed"; ?>
                  <?php if ($remainingVideos > 0): ?>
                    (<?php echo $remainingVideos; ?> remaining)
                  <?php endif; ?>
                </small>
              </div>
              <div class="btn-group">
              <?php if ($prevVideo): ?>
                <a href="?video=<?php echo $prevVideo['id']; ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="fa-solid fa-arrow-left"></i> Previous
                </a>
              <?php endif; ?>
              <button class="btn btn-outline-warning btn-sm" id="skip-btn">
                <i class="fa-solid fa-forward"></i> Skip
              </button>
              <?php if ($currentVideo): ?>
                <button class="btn btn-outline-danger btn-sm" id="delete-btn">
                  <i class="fa-solid fa-trash"></i> Delete
                </button>
              <?php endif; ?>
              <?php if ($nextVideo): ?>
                <a href="?video=<?php echo $nextVideo['id']; ?>" class="btn btn-outline-secondary btn-sm">
                  Next <i class="fa-solid fa-arrow-right"></i>
                </a>
              <?php endif; ?>
            </div>
            </div>
            <div class="progress" style="height: 8px;">
              <div class="progress-bar" role="progressbar" 
                style="width: <?php echo $progressPercent; ?>%">
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Video Info -->
        <?php if ($currentVideo): ?>
        <div class="row mb-3">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="w-50">
                    <h6 class="mb-1">
                      <i class="fa-solid fa-file-video"></i>
                      <?php echo htmlspecialchars($currentVideo['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </h6>
                    <div class="row mt-2">
                      <div class="col-md-6">
                        <small class="text-muted">
                          <div><i class="fa-solid fa-hashtag"></i> ID: <?php echo $currentVideo['id']; ?></div>
                          <div><i class="fa-solid fa-weight-scale"></i> Size: <span><?php echo formatFileSize($videoMetadata['size'] ?? 0); ?></span></div>
                          <div><i class="fa-solid fa-clock"></i> Duration: <?php echo formatDuration($videoMetadata['duration'] ?? 0); ?></div>
                        </small>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted">
                          <?php if ($videoMetadata['width'] > 0 && $videoMetadata['height'] > 0): ?>
                            <div><i class="fa-solid fa-expand"></i> Resolution: <?php echo $videoMetadata['width']; ?>×<?php echo $videoMetadata['height']; ?></div>
                            <div><i class="fa-solid fa-ratio"></i> Aspect Ratio: <?php echo getVideoAspectRatio($videoMetadata['width'], $videoMetadata['height']); ?></div>
                          <?php endif; ?>
                          <?php if (!empty($currentVideo['orientation']) && $currentVideo['orientation'] !== 'unknown'): ?>
                            <div><i class="fa-solid fa-rotate"></i> Orientation: <?php echo ucfirst($currentVideo['orientation']); ?></div>
                          <?php endif; ?>
                        </small>
                      </div>
                    </div>
                  </div>
                  <div class="p-0 m-0">
                    <?php if ($currentVideo['is_processed']): ?>
                      <span class="badge bg-success">Processed</span>
                    <?php else: ?>
                      <span class="badge bg-warning">Unprocessed</span>
                    <?php endif; ?>
                    <?php if ($currentVideo['flagged']): ?>
                      <span class="badge bg-danger ms-1">Flagged</span>
                    <?php endif; ?>
                    <?php if ($currentVideo['duration']): ?>
                      <span class="badge bg-secondary ms-1"><?php echo ucfirst($currentVideo['duration']); ?></span>
                    <?php endif; ?>
                    <?php if ($currentVideo['quality']): ?>
                      <span class="badge bg-info ms-1"><?php echo ucfirst($currentVideo['quality']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <!-- Add this after the video info card -->
        <?php if ($currentVideo): ?>
        <div class="row mb-3">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <h6 class="card-title mb-3">
                  <i class="fa-solid fa-star"></i> Rate This Video
                </h6>
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <div class="rating-stars">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" 
                            class="btn btn-outline-warning rating-star <?php echo ($currentVideo['rating'] >= $i) ? 'active' : ''; ?>" 
                            data-rating="<?php echo $i; ?>">
                          <i class="fa-solid fa-star"></i>
                        </button>
                      <?php endfor; ?>
                    </div>
                  </div>
                  <div>
                    <span class="rating-text">
                      <?php 
                      $ratingText = ['Unrated', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                      echo $currentVideo['rating'] > 0 ? $ratingText[$currentVideo['rating']] : 'Not rated';
                      ?>
                    </span>
                    <?php if ($currentVideo['rating'] > 0): ?>
                      <button class="btn btn-sm btn-outline-danger ms-2" id="clear-rating">
                        <i class="fa-solid fa-xmark"></i> Clear
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

      <div class="row">
        <!-- Video Player -->
        <div class="col-md-3">
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="fa-solid fa-play"></i> 
                <?php echo htmlspecialchars($currentVideo['file_name'], ENT_QUOTES, 'UTF-8'); ?>
              </h6>
            </div>
            <div class="card-body p-0">
              <div id="video-player">
                <div class="text-center">
                  <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
                  <p class="mt-2">Loading video...</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tagging Form -->
        <div class="col-md-9">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Video Details</span>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="flagged-switch">
                <label class="form-check-label" for="flagged-switch">
                  <i class="fa-solid fa-flag"></i> Flagged
                </label>
              </div>
            </div>
            
            <div class="card-body">
              <!-- Video Properties -->
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">
                    <i class="fa-solid fa-rotate"></i> Orientation
                  </label>
                  <select class="form-select" id="video-orientation">
                    <option value="">Select...</option>
                    <option value="portrait">Portrait</option>
                    <option value="landscape">Landscape</option>
                    <option value="square">Square</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">
                    <i class="fa-solid fa-clock"></i> Duration
                  </label>
                  <select class="form-select" id="video-duration">
                    <option value="">Select...</option>
                    <option value="quick">Quick (<1 min)</option>
                    <option value="short">Short (1-2 min)</option>
                    <option value="medium">Medium (2-10 min)</option>
                    <option value="long">Long (>10 min)</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">
                    <i class="fa-solid fa-high-definition"></i> Quality
                  </label>
                  <select class="form-select" id="video-quality">
                    <option value="">Select...</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                  </select>
                </div>
              </div>

              <!-- Categories and Tags -->
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    <i class="fa-solid fa-list"></i> Categories
                  </label>
                  <input id="category-input" class="form-control" placeholder="Add categories..."/>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    <i class="fa-solid fa-tags"></i> Tags
                  </label>
                  <input id="tags-input" class="form-control" placeholder="Add tags..."/>
                </div>
              </div>
            </div>
            
            <div class="card-footer">
              <div class="d-flex justify-content-between">
                <button class="btn btn-secondary" id="prev-btn" disabled>
                  <i class="fa-solid fa-arrow-left"></i> Previous
                </button>
                <div>
                  <button class="btn btn-success" id="save-btn">
                    <i class="fa-solid fa-save"></i> Save & Next
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="text-center mt-5">
        <i class="fa-solid fa-check-circle fa-3x text-success"></i>
        <h3 class="mt-3">All videos processed!</h3>
        <p class="text-muted">Great job! You've tagged all your videos.</p>
        <a href="#" class="btn btn-primary">View Database</a>
      </div>
    <?php endif; ?>
  </main>

  <script>
    // Initialize video player
    <?php if ($currentVideo): ?>
    var player = new Playerjs({
      id: "video-player",
      file: "<?php echo htmlspecialchars($currentVideo['file_path'], ENT_QUOTES, 'UTF-8'); ?>",
      width: 100,
      poster: "<?php echo $thumbnailUrl; ?>"
    });
    <?php endif; ?>

    // Initialize Tagify with existing values
  const categoryInput = document.querySelector('#category-input');
  const tagsInput = document.querySelector('#tags-input');
  
  const categoryTagify = new Tagify(categoryInput, {
    whitelist: <?php echo json_encode($categories); ?>,
    dropdown: {
      enabled: 0,
      maxItems: 10,
      closeOnSelect: false
    }
  });
  
  const tagsTagify = new Tagify(tagsInput, {
    whitelist: <?php echo json_encode($tags); ?>,
    dropdown: {
      enabled: 0,
      maxItems: 10,
      closeOnSelect: false
    }
  });
  
  // Set existing values if editing a processed video
  <?php if (!empty($videoCategories)): ?>
    categoryTagify.addTags(<?php echo json_encode($videoCategories); ?>);
  <?php endif; ?>
  
  <?php if (!empty($videoTags)): ?>
    tagsTagify.addTags(<?php echo json_encode($videoTags); ?>);
  <?php endif; ?>
  
  // Set existing form values
  <?php if ($currentVideo): ?>
    $('#video-orientation').val('<?php echo $currentVideo['orientation'] ?? ''; ?>');
    $('#video-duration').val('<?php echo $currentVideo['duration'] ?? ''; ?>');
    $('#video-quality').val('<?php echo $currentVideo['quality'] ?? ''; ?>');
    $('#flagged-switch').prop('checked', <?php echo ($currentVideo['flagged'] ?? 0) ? 'true' : 'false'; ?>);
  <?php endif; ?>

  // Save functionality
  $('#save-btn').click(function() {
    const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
    if (!videoId) return;
    
    const data = {
      video_id: videoId,
      orientation: $('#video-orientation').val(),
      duration: $('#video-duration').val(),
      quality: $('#video-quality').val(),
      categories: categoryTagify.value.map(tag => {
        return typeof tag === 'object' ? tag.value : tag;
      }),
      tags: tagsTagify.value.map(tag => {
        return typeof tag === 'object' ? tag.value : tag;
      }),
      flagged: $('#flagged-switch').is(':checked') ? 1 : 0
    };
    
    $.post('ajax/save_video.php', data, function(response) {
      if (response.success) {
        window.location.href = 'index.php';
      } else {
        alert('Error saving video: ' + response.error);
      }
    }).fail(function(xhr, status, error) {
      alert('Failed to save video: ' + error);
    });
  });

  // Skip functionality
  $('#skip-btn').click(function() {
    const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
    if (!videoId) return;
    
    $.post('ajax/skip_video.php', {video_id: videoId}, function(response) {
      if (response.success) {
        window.location.href = 'index.php';
      } else {
        alert('Error skipping video: ' + response.error);
      }
    }).fail(function(xhr, status, error) {
      alert('Failed to skip video: ' + error);
    });
  });

  // Delete functionality
  $('#delete-btn').click(function() {
    const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
    if (!videoId) return;
    
    // Confirm deletion
    if (!confirm('Are you sure you want to delete this video?\nThis will remove both the database entry and the actual file!')) {
      return;
    }
    
    $.post('ajax/delete_video.php', {video_id: videoId}, function(response) {
      if (response.success) {
        alert('Video deleted successfully!');
        // Go to next video or reload
        window.location.href = 'index.php';
      } else {
        alert('Error deleting video: ' + response.error);
      }
    }).fail(function(xhr, status, error) {
      alert('Failed to delete video: ' + error);
    });
  });
  // Rating functionality
    $('.rating-star').click(function() {
        const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
        const rating = $(this).data('rating');
        
        if (!videoId) return;
        
        $.post('ajax/save_rating.php', {
            video_id: videoId,
            rating: rating
        }, function(response) {
            if (response.success) {
                // Update UI
                $('.rating-star').removeClass('active');
                $('.rating-star').each(function() {
                    if ($(this).data('rating') <= response.rating) {
                        $(this).addClass('active');
                    }
                });
                
                // Update rating text
                const ratingText = ['Unrated', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                $('.rating-text').text(ratingText[response.rating]);
                
                // Show clear button
                if (response.rating > 0 && $('#clear-rating').length === 0) {
                    $('.rating-text').after('<button class="btn btn-sm btn-outline-danger ms-2" id="clear-rating"><i class="fa-solid fa-xmark"></i> Clear</button>');
                }
            } else {
                alert('Error saving rating: ' + response.error);
            }
        }).fail(function() {
            alert('Failed to save rating');
        });
    });
    
    // Clear rating
    $(document).on('click', '#clear-rating', function() {
        const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
        
        if (!videoId) return;
        
        $.post('ajax/save_rating.php', {
            video_id: videoId,
            rating: 0
        }, function(response) {
            if (response.success) {
                // Update UI
                $('.rating-star').removeClass('active');
                $('.rating-text').text('Not rated');
                $('#clear-rating').remove();
            } else {
                alert('Error clearing rating: ' + response.error);
            }
        }).fail(function() {
            alert('Failed to clear rating');
        });
    });
  </script>
</body>
</html>