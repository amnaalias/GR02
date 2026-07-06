<?php
header('Content-Type: application/json');
require_once 'functions.php';

$matric_no = $_POST['matric_no'] ?? '';
$doc_path = $_POST['doc_path'] ?? '';

if (empty($matric_no) || empty($doc_path)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Simulate document language detection
// In production, you would use libraries like Tesseract OCR, Google Cloud Document AI, etc.
$languages = ['English', 'Malay', 'Chinese', 'Arabic', 'Spanish', 'French'];
$language = $languages[array_rand($languages)];
$word_count = rand(500, 5000);
$page_count = rand(1, 20);
$document_types = ['PDF', 'Text', 'Report', 'Essay', 'Research', 'Thesis'];
$document_type = $document_types[array_rand($document_types)];

// Save to your database (gr02)
$result = saveDocumentAnalysis($matric_no, $doc_path, $language, $word_count, $page_count, $document_type);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Document analysis saved successfully',
        'data' => [
            'language' => $language,
            'word_count' => $word_count,
            'page_count' => $page_count,
            'document_type' => $document_type
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save analysis']);
}
?>
