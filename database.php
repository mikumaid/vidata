<?php
// database.php
require_once 'includes/config.php';
require_once 'includes/db_functions.php';

// Get parameters
$page = max(1, intval($_GET['page'] ?? 1));
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$tagFilter = $_GET['tag'] ?? '';
$flaggedOnly = isset($_GET['flagged']) ? 1 : 0;
$processedOnly = $_GET['processed'] ?? '';
$ratingFilter = $_GET['rating'] ?? '';
$sortBy = $_GET['sort'] ?? 'id';
$sortOrder = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Package parameters for functions
$params = compact('page', 'search', 'categoryFilter', 'tagFilter', 'flaggedOnly', 
                 'processedOnly', 'ratingFilter', 'sortBy', 'sortOrder', 'perPage', 'offset');

// Get database data
$dbData = getDatabaseVideos($db, $params);

// Get filter options
$filterOptions = getFilterOptions($db);

// Build pagination query
$baseQuery = buildPaginationQuery($params);

// Include template files
include 'includes/db_header.php';
?>

<div class="row mb-3">
  <div class="col-12">
    <h3><i class="fa-solid fa-database"></i> Video Database</h3>
    <p class="text-muted">Manage and browse your video collection</p>
  </div>
</div>

<?php
include 'includes/db_filters.php';
include 'includes/db_sorting.php';
include 'includes/db_results.php';
include 'includes/db_pagination.php';
include 'includes/db_footer.php';
?>