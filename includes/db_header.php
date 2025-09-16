<?php
// includes/db_header.php
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
  <title>Video Database - Collection Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <link href="FA6/css/all.min.css" rel="stylesheet" />
  <style>
    .video-card {
      transition: transform 0.2s;
      height: 100%;
    }
    .video-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .video-thumbnail {
      height: 150px;
      object-fit: cover;
      background: #000;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .pagination-container {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
    .sort-link {
      color: #6c757d;
      text-decoration: none;
    }
    .sort-link:hover {
      color: #fff;
    }
    .sort-link.active {
      color: #0d6efd;
    }
  </style>
</head>
<body data-bs-theme="dark">
  <div class="container-fluid border-bottom border-2 p-1 m-0">
    <ul class="nav nav-pills justify-content-center">
      <li class="nav-item">
        <a class="nav-link" href="index.php">
          <i class="fa-solid fa-video"></i> Tagging
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="database.php">
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