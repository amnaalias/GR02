<?php
header('Content-Type: application/json');
require_once 'functions.php';

$matric_no = $_POST['matric_no'] ?? '';
$audio_path = $_POST['audio_path'] ?? '';

if (empty($matric_no) || empty($audio_path)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Simulate audio emotion analysis
$emotions = ['happy', 'sad', 'angry', 'neutral', 'surprise', 'fear', 'disgust'];
$emotion = $emotions[array_rand($emotions)];
$emotion_confidence = rand(60, 95) / 100;
$duration = rand(30, 300) / 10;

// Save to database
function saveAudioAnalysisEmotionOnly($matric_no, $audio_path, $emotion, $emotion_confidence, $duration) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO audio_analysis 
                               (matric_no, audio_path, emotion, emotion_confidence, duration, analysis_date) 
                               VALUES (?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$matric_no, $audio_path, $emotion, $emotion_confidence, $duration]);
    } catch (PDOException $e) {
        error_log("Error saving audio analysis: " . $e->getMessage());
        return false;
    }
}

$result = saveAudioAnalysisEmotionOnly($matric_no, $audio_path, $emotion, $emotion_confidence, $duration);

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
    echo json_encode(['success' => false, 'message' => 'Failed to save analysis']);
}
?>
