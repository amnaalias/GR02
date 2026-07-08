<?php
header('Content-Type: application/json');
require_once 'functions.php';

$matric_no = $_POST['matric_no'] ?? '';
$photo_path = $_POST['photo_path'] ?? '';

if (empty($matric_no) || empty($photo_path)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// FIXED: Securely strip out the full UTEM URL prefix if present
if (preg_match('/uploads?\/(.+)$/i', $photo_path, $matches)) {
    $photo_clean_path = 'uploads/' . $matches[1];
} else {
    $photo_clean_path = basename($photo_path);
}

// Simulate photo analysis values
$is_formal = (rand(1, 100) > 40) ? 1 : 0; 
$has_glasses = (rand(1, 100) > 70) ? 1 : 0; 
$has_smile = (rand(1, 100) > 50) ? 1 : 0; 
$face_count = 1;
$quality_score = rand(75, 98); 

// Save to your group database (gr02)
$result = savePhotoAnalysis($matric_no, $photo_clean_path, $is_formal, $has_glasses, $has_smile, $face_count, $quality_score);

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
