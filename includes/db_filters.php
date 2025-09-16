<?php
// includes/db_filters.php
?>
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
          <?php foreach ($filterOptions['categories'] as $cat): ?>
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
          <?php foreach ($filterOptions['tags'] as $tag): ?>
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