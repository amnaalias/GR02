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
// In production, you would use AI services like Google Cloud Speech-to-Text, AWS Transcribe, etc.
$emotions = ['happy', 'sad', 'angry', 'neutral', 'surprise'];
$emotion = $emotions[array_rand($emotions)];
$emotion_confidence = rand(60, 95) / 100;
$duration = rand(30, 300) / 10;
$sample_rate = 44100;
$languages = ['English', 'Malay', 'Chinese', 'Arabic', 'Spanish'];
$language = $languages[array_rand($languages)];
$speech_to_text = 'Sample transcribed text from the audio file for demonstration purposes.';

// Save to your database (gr02)
$result = saveAudioAnalysis($matric_no, $audio_path, $emotion, $emotion_confidence, $duration, $sample_rate, $language, $speech_to_text);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Audio analysis saved successfully',
        'data' => [
            'emotion' => $emotion,
            'emotion_confidence' => $emotion_confidence,
            'duration' => $duration,
            'sample_rate' => $sample_rate,
            'language' => $language,
            'speech_to_text' => $speech_to_text
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save analysis']);
}
?>
