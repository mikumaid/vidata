<?php
// includes/db_sorting.php
?>
<!-- Results Info and Sorting -->
<div class="row mb-3">
    <div class="col-md-6">
        <p class="text-muted">
            Showing <?php echo count($dbData['videos']); ?> of <?php echo $dbData['totalVideos']; ?> videos
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