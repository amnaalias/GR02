<?php
// Ensure no error messages or notices display inline, messing up JSON strings
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'functions.php';

// Clear out any accidental white-space characters generated prior to encoding
if (ob_get_length()) ob_clean();

$matric_no = $_POST['matric_no'] ?? '';
$audio_path = $_POST['audio_path'] ?? '';

if (empty($matric_no) || empty($audio_path)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Clean path structure configuration
$local_audio_path = $audio_path;
if (preg_match('/uploads?\/(.+)$/i', $audio_path, $matches)) {
    $local_audio_path = 'uploads/' . $matches[1];
}

// Simulate audio emotion analysis parameters
$emotions = ['happy', 'sad', 'angry', 'neutral', 'surprise', 'fear', 'disgust'];
$emotion = $emotions[array_rand($emotions)];
$emotion_confidence = rand(60, 95) / 100;
$duration = rand(30, 300) / 10;

// Call the function directly from functions.php (Do not redeclare it here!)
$result = saveAudioAnalysisEmotionOnly($matric_no, $local_audio_path, $emotion, $emotion_confidence, $duration);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Audio emotion analysis saved successfully',
        'data' => [
            'emotion' => $emotion,
            'emotion_confidence' => $emotion_confidence,
            'duration' => $duration
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save analysis inside target local framework']);
}
exit;
?>
