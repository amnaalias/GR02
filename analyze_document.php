<?php
header('Content-Type: application/json');
require_once 'functions.php';

$matric_no = $_POST['matric_no'] ?? '';
$doc_path = $_POST['doc_path'] ?? '';

if (empty($matric_no) || empty($doc_path)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// 1. DYNAMIC LANGUAGE DETECTION BASED ON FILE PATH / NAME
// Standardizing to lower-case for keyword matching (e.g., 'poem', 'syair', 'lagu', 'lab')
$filename = strtolower(basename($doc_path));
$language = 'English'; // Default fallback language

if (strpos($filename, 'syair') !== false || strpos($filename, 'lagu') !== false) {
    $language = 'Malay';
} elseif (strpos($filename, 'poem') !== false || strpos($filename, 'lab') !== false) {
    $language = 'English';
} else {
    // Optional random pool if it matches none of the standard image signatures
    $fallback_languages = ['English', 'Malay', 'Chinese', 'Arabic'];
    $language = $fallback_languages[array_rand($fallback_languages)];
}

// 2. SIMULATE REMAINING METADATA
$word_count = rand(500, 5000);
$page_count = rand(1, 20);

// Detect format or fallback to a standard document classification
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$document_type = ($ext === 'docx' || $ext === 'doc') ? 'Word Document' : 'PDF';

// 3. SAVE ANALYSIS RESULT TO THE DATABASE
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
