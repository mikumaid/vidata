<?php
require_once 'config.php';

// Get first unprocessed video
$stmt = $db->prepare('SELECT * FROM videos WHERE is_processed = 0 LIMIT 1');
$result = $stmt->execute();
$currentVideo = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;

// Generate random thumbnail on every page load
$thumbnailUrl = '';
if ($currentVideo) {
    $videoPath = $currentVideo['file_path'];
    
    // Get video duration using ffprobe
    $durationCmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 " . escapeshellarg($videoPath) . " 2>/dev/null";
    $duration = floatval(shell_exec($durationCmd));
    
    if ($duration > 0) {
        // Pick random time (avoid first and last 5%)
        $safeStart = $duration * 0.05;
        $safeEnd = $duration * 0.95;
        $randomTime = $safeStart + (mt_rand() / mt_getrandmax()) * ($safeEnd - $safeStart);
        
        // Create temporary thumbnail URL with timestamp to prevent caching
        $thumbnailUrl = "thumbnail.php?video=" . urlencode($videoPath) . "&time=" . urlencode(sprintf("%.2f", $randomTime)) . "&t=" . time();
    }
}

// Get all categories and tags for autocomplete
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

// Get progress stats
$totalVideos = $db->querySingle('SELECT COUNT(*) FROM videos');
$processedVideos = $db->querySingle('SELECT COUNT(*) FROM videos WHERE is_processed = 1');
$remainingVideos = $totalVideos - $processedVideos;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Collection Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="playerjs.js"></script>
    
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
    </style>
</head>
<body data-bs-theme="dark">
    <div class="container-fluid border-bottom border-2 p-1 m-0">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item">
                <a class="nav-link active" href="#">
                    <i class="fa-solid fa-video"></i> Tagging
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fa-solid fa-database"></i> Database
                </a>
            </li>
        </ul>
    </div>

    <main class="container mt-3">
        <?php if ($currentVideo): ?>
            <div class="row">
                <!-- Progress Bar -->
                <div class="col-12 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Video Tagging</h5>
                            <small class="progress-text">
                                <?php echo "$processedVideos of $totalVideos videos processed ($remainingVideos remaining)"; ?>
                            </small>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary btn-sm" id="skip-btn">
                                <i class="fa-solid fa-forward"></i> Skip
                            </button>
                        </div>
                    </div>
                    <div class="progress mt-2">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?php echo $totalVideos > 0 ? ($processedVideos / $totalVideos * 100) : 0; ?>%">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Video Player -->
                <div class="col-md-4">
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
                <div class="col-md-8">
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

        // Initialize Tagify
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

        // Save functionality
        $('#save-btn').click(function() {
            const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
            if (!videoId) return;
            
            const data = {
                video_id: videoId,
                orientation: $('#video-orientation').val(),
                duration: $('#video-duration').val(),
                quality: $('#video-quality').val(),
                categories: categoryTagify.value.map(tag => tag.value),
                tags: tagsTagify.value.map(tag => tag.value),
                flagged: $('#flagged-switch').is(':checked') ? 1 : 0
            };
            
            $.post('ajax/save_video.php', data, function(response) {
                if (response.success) {
                    window.location.href = 'index.php';
                } else {
                    alert('Error saving video: ' + response.error);
                }
            }).fail(function() {
                alert('Failed to save video');
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
            }).fail(function() {
                alert('Failed to skip video');
            });
        });
    </script>
</body>
</html>