<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="shortcut icon" href="/favicon.ico" />
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
  <link rel="manifest" href="/site.webmanifest" />
  <title>Video Collection Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="playerjs.js"></script>
  <link href="FA6/css/all.min.css" rel="stylesheet" />
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
    #delete-btn:hover {
      background-color: #dc3545 !important;
      color: white !important;
      border-color: #dc3545 !important;
    }
    .rating-stars .btn {
        padding: 0.25rem 0.5rem;
        border: none;
    }
    .rating-stars .btn:hover {
        background-color: #ffc107 !important;
        color: #000 !important;
    }
    .rating-stars .btn.active {
        background-color: #ffc107 !important;
        color: #000 !important;
        border-color: #ffc107 !important;
    }
    .rating-text {
        font-weight: 500;
    }
  </style>
</head>
<body data-bs-theme="dark">
  <div class="container-fluid border-bottom border-2 p-1 m-0">
    <ul class="nav nav-pills justify-content-center">
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
          <i class="fa-solid fa-video"></i> Tagging
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'database.php' ? 'active' : ''; ?>" href="database.php">
          <i class="fa-solid fa-database"></i> Database
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="dashboard.php">
          <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
      </li>
    </ul>
  </div>
  <main class="container mt-3">