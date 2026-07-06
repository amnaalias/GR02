<?php
header('Content-Type: application/json');
require_once 'functions.php';

$matric_no = $_POST['matric_no'] ?? '';
$photo_path = $_POST['photo_path'] ?? '';

if (empty($matric_no) || empty($photo_path)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Clean up the photo path before saving to the database if it contains the full server URL prefix
if (strpos($photo_path, 'uploads/') !== false) {
    $photo_path = 'uploads/' . explode('uploads/', $photo_path)[1];
}

// Simulate photo analysis values
$is_formal = (rand(1, 100) > 40) ? 1 : 0; // 60% chance formal
$has_glasses = (rand(1, 100) > 70) ? 1 : 0; // 30% chance glasses
$has_smile = (rand(1, 100) > 50) ? 1 : 0; // 50% chance smile
$face_count = 1;
$quality_score = rand(75, 98); // Whole number quality percentage score

// Save to your group database (gr02)
$result = savePhotoAnalysis($matric_no, $photo_path, $is_formal, $has_glasses, $has_smile, $face_count, $quality_score);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Photo analysis saved successfully',
        'data' => [
            'is_formal' => $is_formal,
            'has_glasses' => $has_glasses,
            'has_smile' => $has_smile,
            'face_count' => $face_count,
            'quality_score' => $quality_score
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to write record to gr02 database']);
}
?>
