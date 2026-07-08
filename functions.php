<?php
// ============================================
// DATABASE CONFIGURATION (LOCAL ONLY)
// ============================================

$local_host = 'localhost';
$local_user = 'GR02';
$local_pass = 'abc1234';
$local_db   = 'gr02';

// ============================================
// CONNECT TO DATABASE
// ============================================
$conn = null;
$pdo = null;

try {
    // MySQLi local connection
    $conn = new mysqli($local_host, $local_user, $local_pass, $local_db);
    
    if ($conn->connect_error) {
        error_log("❌ Failed to connect to Local MySQLi: " . $conn->connect_error);
        $conn = null;
    } else {
        $conn->set_charset("utf8mb4");
    }
    
    // PDO local connection
    $pdo = new PDO("mysql:host=$local_host;dbname=$local_db;charset=utf8mb4", $local_user, $local_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $conn = null;
    $pdo = null;
    error_log("❌ Exception connecting to local database: " . $e->getMessage());
}

// ============================================
// UTILITY HELPER FUNCTIONS
// ============================================

/**
 * Detects 'upload' or 'uploads' properly to prevent double folder generation
 */
function formatUtemUrl($path) {
    if (empty($path)) return '';
    
    // Clean up whitespace and leading slashes
    $path = ltrim(trim($path), '/');
    
    // If it already starts with upload or uploads, append directly to base link
    if (stripos($path, 'upload') === 0) {
        return 'https://bitp3353.utem.edu.my/2026/all/' . $path;
    }
    
    // Default fallback if no upload directory is specified in the string
    return 'https://bitp3353.utem.edu.my/2026/all/uploads/' . $path;
}

// ============================================
// STUDENT DATA FUNCTIONS (READ ALL GROUPS)
// ============================================

function getStudentsFromLectureDB($group = null) {
    global $conn;
    
    if (!$conn) {
        error_log("❌ getStudentsFromLectureDB: No connection to database");
        return [];
    }
    
    $sql = "SELECT * FROM mmdb2026.vstu ORDER BY group_no ASC, full_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

function getStudentsWithPhotos($group = null) {
    global $conn;
    
    if (!$conn) {
        error_log("❌ getStudentsWithPhotos: No connection to database");
        return [];
    }
    
    $sql = "SELECT * FROM mmdb2026.vstu WHERE photoStu IS NOT NULL AND photoStu != '' ORDER BY group_no ASC, full_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $analysis = getPhotoAnalysis($row['matric_no']);
        if ($analysis) {
            $row = array_merge($row, $analysis);
        }
        
        if (!empty($row['photoStu'])) {
            $row['photoStu'] = formatUtemUrl($row['photoStu']);
        }
        
        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

function getStudentsWithAudio($group = null) {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    $sql = "SELECT v.*, 
                   aa.emotion, aa.emotion_confidence, aa.duration, aa.sample_rate, 
                   aa.speech_to_text, aa.analysis_date as audio_analysis_date
            FROM mmdb2026.vstu v
            LEFT JOIN audio_analysis aa ON v.matric_no = aa.matric_no
            WHERE v.audioStu IS NOT NULL 
              AND v.audioStu != ''
            ORDER BY v.group_no ASC, v.full_name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        
        if (!empty($row['audioStu'])) {
            $row['audioStu'] = formatUtemUrl($row['audioStu']);
        }
        
        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

function getStudentsWithDocuments($group = null) {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    $sql = "SELECT * FROM mmdb2026.vstu WHERE docStu IS NOT NULL AND docStu != '' ORDER BY group_no ASC, full_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $analysis = getDocumentAnalysis($row['matric_no']);
        if ($analysis) {
            $row = array_merge($row, $analysis);
        }
        
        if (!isset($row['language']) || empty($row['language']) || $row['language'] === 'N/A') {
            if (!empty($row['docStu'])) {
                $filename = strtolower(basename($row['docStu']));
                
                if (strpos($filename, 'poem') !== false || strpos($filename, 'lab') !== false) {
                    $row['language'] = 'English';
                } elseif (strpos($filename, 'syair') !== false || strpos($filename, 'lagu') !== false) {
                    $row['language'] = 'Malay';
                } else {
                    $row['language'] = 'N/A'; 
                }
            } else {
                $row['language'] = 'N/A';
            }
        }

        if (!empty($row['docStu'])) {
            $row['docStu'] = formatUtemUrl($row['docStu']);
        }

        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

function getGroupStats($group = null) {
    global $conn;
    
    $stats = [
        'total_members' => 0,
        'total_images' => 0,
        'total_pdfs' => 0,
        'total_audios' => 0,
        'total_files' => 0
    ];
    
    if (!$conn) {
        return $stats;
    }
    
    // Get total members for all groups
    $sql = "SELECT COUNT(*) as total FROM mmdb2026.vstu";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_members'] = $row['total'];
    }
    $stmt->close();
    
    // Get file counts for all groups
    $sql = "SELECT * FROM mmdb2026.vstu";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['photoStu'])) $stats['total_images']++;
        if (!empty($row['docStu'])) $stats['total_pdfs']++;
        if (!empty($row['audioStu'])) $stats['total_audios']++;
    }
    $stmt->close();
    
    $stats['total_files'] = $stats['total_images'] + $stats['total_pdfs'] + $stats['total_audios'];
    return $stats;
}

// ============================================
// ANALYSIS FUNCTIONS (READ FROM LOCAL PDO)
// ============================================

function getPhotoAnalysis($matric_no) {
    global $pdo;
    
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT is_formal, has_glasses, has_smile, quality_score, analysis_date FROM photo_analysis WHERE matric_no = ? ORDER BY analysis_date DESC LIMIT 1");
        $stmt->execute([$matric_no]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function getAudioAnalysis($matric_no) {
    global $pdo;
    
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT emotion, emotion_confidence, duration, sample_rate, speech_to_text, analysis_date FROM audio_analysis WHERE matric_no = ? ORDER BY analysis_date DESC LIMIT 1");
        $stmt->execute([$matric_no]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function getDocumentAnalysis($matric_no) {
    global $pdo;
    
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT language, word_count, page_count, document_type, analysis_date FROM document_analysis WHERE matric_no = ? ORDER BY analysis_date DESC LIMIT 1");
        $stmt->execute([$matric_no]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function getAnalysisStats() {
    global $pdo;
    
    $stats = [
        'photo_analyzed' => 0,
        'audio_analyzed' => 0,
        'document_analyzed' => 0
    ];
    
    if (!$pdo) {
        return $stats;
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(DISTINCT matric_no) as total FROM photo_analysis");
        $stats['photo_analyzed'] = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(DISTINCT matric_no) as total FROM audio_analysis");
        $stats['audio_analyzed'] = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(DISTINCT matric_no) as total FROM document_analysis");
        $stats['document_analyzed'] = $stmt->fetch()['total'] ?? 0;
        
        return $stats;
    } catch (PDOException $e) {
        return $stats;
    }
}

// ============================================
// ANALYSIS STORAGE FUNCTIONS (WRITE TO LOCAL PDO)
// ============================================

/**
 * FIXED: Converted from MySQLi to PDO to avoid binding parameter count/type mismatch bugs.
 */
function savePhotoAnalysis($matric_no, $photo_path, $is_formal, $has_glasses, $has_smile, $face_count, $quality_score) {
    global $pdo; 
    if (!$pdo) return false;

    try {
        $sql = "INSERT INTO photo_analysis (matric_no, photo_path, is_formal, has_glasses, has_smile, face_count, quality_score, analysis_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                photo_path = ?, is_formal = ?, has_glasses = ?, has_smile = ?, face_count = ?, quality_score = ?, analysis_date = NOW()";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $matric_no, $photo_path, $is_formal, $has_glasses, $has_smile, $face_count, $quality_score,
            $photo_path, $is_formal, $has_glasses, $has_smile, $face_count, $quality_score
        ]);
    } catch (PDOException $e) {
        error_log("❌ Error saving photo analysis: " . $e->getMessage());
        return false;
    }
}

function saveAudioAnalysis($matric_no, $audio_path, $emotion, $emotion_confidence, $duration, $sample_rate, $speech_to_text) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO audio_analysis (matric_no, audio_path, emotion, emotion_confidence, duration, sample_rate, speech_to_text, analysis_date) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$matric_no, $audio_path, $emotion, $emotion_confidence, $duration, $sample_rate, $speech_to_text]);
    } catch (PDOException $e) {
        return false;
    }
}

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
        return false;
    }
}

function saveDocumentAnalysis($matric_no, $document_path, $language, $word_count, $page_count, $document_type) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO document_analysis (matric_no, doc_path, language, word_count, page_count, document_type, analysis_date) 
                               VALUES (?, ?, ?, ?, ?, ?, NOW())
                               ON DUPLICATE KEY UPDATE 
                               doc_path = ?, language = ?, word_count = ?, page_count = ?, document_type = ?, analysis_date = NOW()");
        return $stmt->execute([
            $matric_no, $document_path, $language, $word_count, $page_count, $document_type,
            $document_path, $language, $word_count, $page_count, $document_type
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function extractTextFromDocx($filePath) {
    if (!file_exists($filePath)) return '';
    $striped_content = '';
    $zip = new ZipArchive();
    if ($zip->open($filePath) === true) {
        if (($index = $zip->locateName('word/document.xml')) !== false) {
            $data = $zip->getFromIndex($index);
            $striped_content = strip_tags($data);
        }
        $zip->close();
    }
    return $striped_content;
}

function extractTextFromPdf($filePath) {
    if (!file_exists($filePath)) return '';
    $content = file_get_contents($filePath);
    
    if (preg_match_all("/\((.*?)\)\s*TJ/s", $content, $matches)) {
        return implode(' ', $matches[1]);
    }
    if (preg_match_all("/\[\((.*?)\)\]\s*TJ/s", $content, $matches)) {
        return implode(' ', $matches[1]);
    }
    return strip_tags($content); 
}
?>
