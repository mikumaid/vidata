<?php
// includes/tagging_form.php
?>
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
          <div class="d-flex align-items-center gap-3">
            <div class="rating-stars">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" 
                    class="btn btn-outline-warning rating-star <?php echo ($currentVideo['rating'] >= $i) ? 'active' : ''; ?>" 
                    data-rating="<?php echo $i; ?>">
                  <i class="fa-solid fa-star"></i>
                </button>
              <?php endfor; ?>
            </div>
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