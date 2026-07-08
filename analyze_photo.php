<?php
// Prevent PHP from dumping raw HTML errors into your JSON stream
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'functions.php';

// Clean out any accidental pre-existing buffers
if (ob_get_length()) ob_clean();

$matric_no = $_POST['matric_no'] ?? '';
$photo_path = $_POST['photo_path'] ?? '';

if (empty($matric_no) || empty($photo_path)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Safely extract just the relative uploads path
if (preg_match('/uploads?\/(.+)$/i', $photo_path, $matches)) {
    $photo_clean_path = 'uploads/' . $matches[1];
} else {
    $photo_clean_path = basename($photo_path);
}

// Generate the strict, correct absolute URL to send back to the frontend
$absolute_photo_url = formatUtemUrl($photo_clean_path);

// Simulate photo analysis values
$is_formal = (rand(1, 100) > 40) ? 1 : 0; 
$has_glasses = (rand(1, 100) > 70) ? 1 : 0; 
$has_smile = (rand(1, 100) > 50) ? 1 : 0; 
$face_count = 1;
$quality_score = rand(75, 98); 

// Save to your group database
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
            'quality_score' => $quality_score,
            'photo_url' => $absolute_photo_url // <-- The frontend can now use this exact link directly
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to write record to database']);
}
exit;
?>
