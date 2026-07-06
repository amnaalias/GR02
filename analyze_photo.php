<?php
header('Content-Type: application/json');
require_once 'functions.php';

$matric_no = $_POST['matric_no'] ?? '';
$photo_path = $_POST['photo_path'] ?? '';

if (empty($matric_no) || empty($photo_path)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Simulate photo analysis
// In production, you would use AI/ML services like Google Vision API, AWS Rekognition, etc.
$is_formal = rand(0, 1) ? 1 : 0;
$has_glasses = rand(0, 1) ? 1 : 0;
$has_smile = rand(0, 1) ? 1 : 0;
$face_count = rand(1, 3);
$quality_score = rand(60, 95) / 100;

// Save to your database (gr02)
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
    echo json_encode(['success' => false, 'message' => 'Failed to save analysis']);
}
?>
