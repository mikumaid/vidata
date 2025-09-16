<?php
// index.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get navigation parameters
$videoId = isset($_GET['video']) ? intval($_GET['video']) : 0;
$specificVideo = $videoId > 0;

// Get current video
$currentVideo = getCurrentVideo($db, $videoId);

// Get navigation videos
[$prevVideo, $nextVideo] = getNavigationVideos($db, $currentVideo, $specificVideo);

// Generate thumbnail
$thumbnailUrl = getThumbnailUrl($currentVideo);

// Get all video data
$videoData = getVideoData($db, $currentVideo);

// Include template files
include 'includes/header.php';

if ($currentVideo): ?>
    <!-- Progress Bar -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <h5 class="mb-0">Video Tagging</h5>
            <small class="text-muted">
              <?php echo "{$videoData['processedVideos']} of {$videoData['totalVideos']} videos processed"; ?>
              <?php if ($videoData['remainingVideos'] > 0): ?>
                (<?php echo $videoData['remainingVideos']; ?> remaining)
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
            <button class="btn btn-outline-danger btn-sm" id="delete-btn">
              <i class="fa-solid fa-trash"></i> Delete
            </button>
            <?php if ($nextVideo): ?>
              <a href="?video=<?php echo $nextVideo['id']; ?>" class="btn btn-outline-secondary btn-sm">
                Next <i class="fa-solid fa-arrow-right"></i>
              </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar" role="progressbar" 
            style="width: <?php echo $videoData['progressPercent']; ?>%">
          </div>
        </div>
      </div>
    </div>
    
    <?php 
    include 'includes/video_info.php';
    include 'includes/video_player.php';
    include 'includes/tagging_form.php';
    ?>
<?php else: ?>
    <div class="text-center mt-5">
      <i class="fa-solid fa-check-circle fa-3x text-success"></i>
      <h3 class="mt-3">All videos processed!</h3>
      <p class="text-muted">Great job! You've tagged all your videos.</p>
      <a href="database.php" class="btn btn-primary">View Database</a>
    </div>
<?php endif;

include 'includes/footer.php';
?>