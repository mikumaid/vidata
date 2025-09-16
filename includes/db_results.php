<?php
// includes/db_results.php
?>
<!-- Results -->
<div class="row">
  <?php if (empty($dbData['videos'])): ?>
    <div class="col-12 text-center">
      <div class="alert alert-info">
        <i class="fa-solid fa-circle-info"></i> No videos found matching your criteria.
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($dbData['videos'] as $video): ?>
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