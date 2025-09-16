<?php
// includes/video_info.php
?>
<!-- Enhanced Video Info -->
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
                  <div><i class="fa-solid fa-weight-scale"></i> Size: <span><?php echo formatFileSize($videoData['videoMetadata']['size'] ?? 0); ?></span></div>
                  <div><i class="fa-solid fa-clock"></i> Duration: <?php echo formatDuration($videoData['videoMetadata']['duration'] ?? 0); ?></div>
                </small>
              </div>
              <div class="col-md-6">
                <small class="text-muted">
                  <?php if ($videoData['videoMetadata']['width'] > 0 && $videoData['videoMetadata']['height'] > 0): ?>
                    <div><i class="fa-solid fa-expand"></i> Resolution: <?php echo $videoData['videoMetadata']['width']; ?>×<?php echo $videoData['videoMetadata']['height']; ?></div>
                    <div><i class="fa-solid fa-expand-wide"></i> Aspect Ratio: <?php echo getVideoAspectRatio($videoData['videoMetadata']['width'], $videoData['videoMetadata']['height']); ?></div>
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