<?php
// config.php
try {
    $db = new SQLite3(__DIR__ . '/collection.db');
    // Create tables
    $db->exec("
        CREATE TABLE IF NOT EXISTS videos (
            id INTEGER PRIMARY KEY,
            file_name TEXT UNIQUE,
            file_path TEXT,
            duration INTEGER,
            width INTEGER,
            height INTEGER,
            orientation TEXT,
            quality TEXT,
            is_processed INTEGER DEFAULT 0,
            flagged INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            rating INTEGER DEFAULT 0
        );
        
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY,
            category TEXT UNIQUE
        );
        
        CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY,
            tag TEXT UNIQUE
        );
        
        CREATE TABLE IF NOT EXISTS video_categories (
            video_id INTEGER,
            category_id INTEGER,
            FOREIGN KEY (video_id) REFERENCES videos(id),
            FOREIGN KEY (category_id) REFERENCES categories(id),
            PRIMARY KEY (video_id, category_id)
        );
        
        CREATE TABLE IF NOT EXISTS video_tags (
            video_id INTEGER,
            tag_id INTEGER,
            FOREIGN KEY (video_id) REFERENCES videos(id),
            FOREIGN KEY (tag_id) REFERENCES tags(id),
            PRIMARY KEY (video_id, tag_id)
        );
    ");
    
} catch (Exception $e) {
    die("Database setup failed: " . $e->getMessage());
}
require_once __DIR__ . '/helpers.php';
?>