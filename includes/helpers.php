<?php
// includes/helpers.php

function getVideoMetadata($videoPath) {
    $metadata = [
        'duration' => 0,
        'width' => 0,
        'height' => 0,
        'size' => 0,
        'orientation' => 'unknown'
    ];
    
    if (!file_exists($videoPath)) {
        return $metadata;
    }
    
    try {
        // Get file size
        $metadata['size'] = filesize($videoPath);
        
        // Use ffprobe to get video metadata
        $ffprobeCmd = "ffprobe -v quiet -show_entries format=duration:stream=width,height -of json " . escapeshellarg($videoPath) . " 2>/dev/null";
        $output = shell_exec($ffprobeCmd);
        
        if ($output) {
            $data = json_decode($output, true);
            
            // Get duration
            if (isset($data['format']['duration'])) {
                $metadata['duration'] = floatval($data['format']['duration']);
            }
            
            // Get video stream info
            if (isset($data['streams']) && is_array($data['streams'])) {
                foreach ($data['streams'] as $stream) {
                    if (isset($stream['codec_type']) && $stream['codec_type'] === 'video') {
                        if (isset($stream['width'])) {
                            $metadata['width'] = intval($stream['width']);
                        }
                        if (isset($stream['height'])) {
                            $metadata['height'] = intval($stream['height']);
                        }
                        break;
                    }
                }
            }
        }
        
        // Determine orientation
        if ($metadata['width'] > 0 && $metadata['height'] > 0) {
            $ratio = $metadata['width'] / $metadata['height'];
            if ($ratio > 1.2) {
                $metadata['orientation'] = 'landscape';
            } elseif ($ratio < 0.8) {
                $metadata['orientation'] = 'portrait';
            } else {
                $metadata['orientation'] = 'square';
            }
        }
        
    } catch (Exception $e) {
        error_log("Error getting video metadata for $videoPath: " . $e->getMessage());
    }
    
    return $metadata;
}

function formatDuration($seconds) {
    if ($seconds <= 0) return 'Unknown';
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = floor($seconds % 60);
    
    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    } else {
        return sprintf('%d:%02d', $minutes, $secs);
    }
}

function formatFileSize($bytes) {
    if ($bytes <= 0) return 'Unknown';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

function getVideoAspectRatio($width, $height) {
    if ($width <= 0 || $height <= 0) return 'Unknown';
    
    // Simplify the ratio
    $gcd = function($a, $b) use (&$gcd) {
        return $b ? $gcd($b, $a % $b) : $a;
    };
    
    $divisor = $gcd($width, $height);
    $ratioWidth = $width / $divisor;
    $ratioHeight = $height / $divisor;
    
    return $ratioWidth . ':' . $ratioHeight;
}
?>