<?php
// includes/video_player.php
?>
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