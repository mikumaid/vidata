<?php
// includes/db_pagination.php
?>
<!-- Pagination -->
<?php if ($dbData['totalPages'] > 1): ?>
  <div class="pagination-container">
    <nav>
      <ul class="pagination">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">
              <i class="fa-solid fa-chevron-left"></i>
            </a>
          </li>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($dbData['totalPages'], $page + 2);
        
        if ($startPage > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?page=1<?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">1</a>
          </li>
          <?php if ($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">
              <?php echo $i; ?>
            </a>
          </li>
        <?php endfor; ?>
        
        <?php if ($endPage < $dbData['totalPages']): ?>
          <?php if ($endPage < $dbData['totalPages'] - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?php echo $dbData['totalPages']; ?><?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">
              <?php echo $dbData['totalPages']; ?>
            </a>
          </li>
        <?php endif; ?>
        
        <?php if ($page < $dbData['totalPages']): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $baseQuery ? '&' . $baseQuery : ''; ?>">
              <i class="fa-solid fa-chevron-right"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
<?php endif; ?>