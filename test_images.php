<?php
header('Content-Type: text/plain');
$upload_dir = 'D:/xampp/htdocs/missing-persons-reporting-system/uploads/';

// Test directory accessibility
echo "Directory exists: " . (file_exists($upload_dir) ? 'Yes' : 'No') . "\n";
echo "Directory readable: " . (is_readable($upload_dir) ? 'Yes' : 'No') . "\n";

// Test first 5 images
$stmt = $pdo->query("SELECT picture FROM approved_cases WHERE picture IS NOT NULL LIMIT 5");
$images = $stmt->fetchAll();

foreach ($images as $img) {
    $path = $upload_dir . $img['picture'];
    echo "Image: {$img['picture']} - " . (file_exists($path) ? 'Exists' : 'Missing') . "\n";
}