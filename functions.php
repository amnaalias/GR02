<?php
// ============================================
// CONNECT TO CLOUD DATABASES
// ============================================

// Cloud server details
$cloud_host = 'bitp3353.utem.edu.my';  // Your lecturer's server
$cloud_user = 'GR02';
$cloud_pass = 'abc1234';

// ============================================
// CONNECT TO YOUR DATABASE (gr02)
// ============================================
$conn = null;
$pdo = null;

try {
    // MySQLi connection
    $conn = new mysqli($cloud_host, $cloud_user, $cloud_pass, 'gr02');
    
    if ($conn->connect_error) {
        // Try with different host
        $conn = new mysqli('www.' . $cloud_host, $cloud_user, $cloud_pass, 'gr02');
        if ($conn->connect_error) {
            $conn = null;
        }
    }
    
    if ($conn) {
        $conn->set_charset("utf8mb4");
        error_log("✅ Connected to gr02 database");
    }
    
    // PDO connection
    $pdo = new PDO("mysql:host=$cloud_host;dbname=gr02;charset=utf8mb4", $cloud_user, $cloud_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    error_log("✅ PDO connected to gr02 database");
    
} catch (Exception $e) {
    $conn = null;
    $pdo = null;
    error_log("❌ Failed to connect to gr02: " . $e->getMessage());
}

// ============================================
// CONNECT TO LECTURER'S DATABASE (mmdb2026)
// ============================================
$lecture_conn = null;

try {
    $lecture_conn = new mysqli($cloud_host, $cloud_user, $cloud_pass, 'mmdb2026');
    
    if ($lecture_conn->connect_error) {
        // Try with different host
        $lecture_conn = new mysqli('www.' . $cloud_host, $cloud_user, $cloud_pass, 'mmdb2026');
        if ($lecture_conn->connect_error) {
            $lecture_conn = null;
        }
    }
    
    if ($lecture_conn) {
        $lecture_conn->set_charset("utf8mb4");
        error_log("✅ Connected to mmdb2026 database");
    } else {
        error_log("❌ Failed to connect to mmdb2026");
    }
} catch (Exception $e) {
    $lecture_conn = null;
    error_log("❌ Exception connecting to mmdb2026: " . $e->getMessage());
}

// ============================================
// LECTURER DATABASE FUNCTIONS (READ ONLY - mmdb2026.vstu)
// ============================================

function getStudentsFromLectureDB($group) {
    global $lecture_conn;
    
    if (!$lecture_conn) {
        error_log("❌ getStudentsFromLectureDB: No connection to mmdb2026");
        return [];
    }
    
    $sql = "SELECT * FROM vstu WHERE group_no = ? ORDER BY full_name ASC";
    $stmt = $lecture_conn->prepare($sql);
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

function getStudentsWithPhotos($group) {
    global $lecture_conn;
    
    if (!$lecture_conn) {
        error_log("❌ getStudentsWithPhotos: No connection to mmdb2026");
        return [];
    }
    
    $sql = "SELECT * FROM vstu WHERE group_no = ? AND photoStu IS NOT NULL AND photoStu != '' ORDER BY full_name ASC";
    $stmt = $lecture_conn->prepare($sql);
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $analysis = getPhotoAnalysis($row['matric_no']);
        if ($analysis) {
            $row = array_merge($row, $analysis);
        }
        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

function getStudentsWithAudio($group) {
    global $lecture_conn;
    
    if (!$lecture_conn) {
        return [];
    }
    
    $sql = "SELECT * FROM vstu WHERE group_no = ? AND audioStu IS NOT NULL AND audioStu != '' ORDER BY full_name ASC";
    $stmt = $lecture_conn->prepare($sql);
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $analysis = getAudioAnalysis($row['matric_no']);
        if ($analysis) {
            $row = array_merge($row, $analysis);
        }
        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

function getStudentsWithDocuments($group) {
    global $lecture_conn;
    
    if (!$lecture_conn) {
        return [];
    }
    
    $sql = "SELECT * FROM vstu WHERE group_no = ? AND docStu IS NOT NULL AND docStu != '' ORDER BY full_name ASC";
    $stmt = $lecture_conn->prepare($sql);
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $analysis = getDocumentAnalysis($row['matric_no']);
        if ($analysis) {
            $row = array_merge($row, $analysis);
        }
        
        // --- START OF LANGUAGE DETECTION FROM docStu ---
        // If language isn't set by your analyzer yet, fallback to checking the file path strings seen in image_c3a098.png
        if (!isset($row['language']) || empty($row['language']) || $row['language'] === 'N/A') {
            if (!empty($row['docStu'])) {
                $filename = strtolower(basename($row['docStu']));
                
                if (strpos($filename, 'poem') !== false || strpos($filename, 'lab') !== false) {
                    $row['language'] = 'English';
                } elseif (strpos($filename, 'syair') !== false || strpos($filename, 'lagu') !== false) {
                    $row['language'] = 'Malay';
                } else {
                    $row['language'] = 'N/A'; // Default fallback if no keyword matches
                }
            } else {
                $row['language'] = 'N/A';
            }
        }
        // --- END OF LANGUAGE DETECTION ---

        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

function getGroupStats($group) {
    global $lecture_conn;
    
    $stats = [
        'total_members' => 0,
        'total_images' => 0,
        'total_pdfs' => 0,
        'total_audios' => 0,
        'total_files' => 0
    ];
    
    if (!$lecture_conn) {
        error_log("❌ getGroupStats: No connection to mmdb2026");
        return $stats;
    }
    
    // Get total members
    $sql = "SELECT COUNT(*) as total FROM vstu WHERE group_no = ?";
    $stmt = $lecture_conn->prepare($sql);
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_members'] = $row['total'];
    }
    $stmt->close();
    
    // Get file counts using SELECT *
    $sql = "SELECT * FROM vstu WHERE group_no = ?";
    $stmt = $lecture_conn->prepare($sql);
    $stmt->bind_param("s", $group);
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
// ANALYSIS FUNCTIONS (YOUR DATABASE - gr02)
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
        $stmt = $pdo->prepare("SELECT emotion, emotion_confidence, duration, language, speech_to_text, analysis_date FROM audio_analysis WHERE matric_no = ? ORDER BY analysis_date DESC LIMIT 1");
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
// ANALYSIS STORAGE FUNCTIONS (WRITE TO gr02)
// ============================================

function savePhotoAnalysis($matric_no, $photo_path, $is_formal, $has_glasses, $has_smile, $face_count, $quality_score) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO photo_analysis (matric_no, photo_path, is_formal, has_glasses, has_smile, face_count, quality_score, analysis_date) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$matric_no, $photo_path, $is_formal, $has_glasses, $has_smile, $face_count, $quality_score]);
    } catch (PDOException $e) {
        return false;
    }
}

function saveAudioAnalysis($matric_no, $audio_path, $emotion, $emotion_confidence, $duration, $sample_rate, $language, $speech_to_text) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO audio_analysis (matric_no, audio_path, emotion, emotion_confidence, duration, sample_rate, language, speech_to_text, analysis_date) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$matric_no, $audio_path, $emotion, $emotion_confidence, $duration, $sample_rate, $language, $speech_to_text]);
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
        $stmt = $pdo->prepare("INSERT INTO document_analysis (matric_no, document_path, language, word_count, page_count, document_type, analysis_date) 
                               VALUES (?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$matric_no, $document_path, $language, $word_count, $page_count, $document_type]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Extracts plain text from a .docx file
 */
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

/**
 * Extracts plain text from a simple text-based .pdf file
 * Note: For advanced/scanned PDFs, tools like pdftotext or an OCR API are required.
 */
function extractTextFromPdf($filePath) {
    if (!file_exists($filePath)) return '';
    $content = file_get_contents($filePath);
    // Basic regex pattern to extract printable text strings from PDF objects
    if (preg_match_all("/\((.*?)\)\s*TJ/s", $content, $matches)) {
        return implode(' ', $matches[1]);
    }
    // Secondary basic PDF fallback parser
    if (preg_match_all("/\[\((.*?)\)\]\s*TJ/s", $content, $matches)) {
        return implode(' ', $matches[1]);
    }
    return strip_tags($content); // Ultimate crude fallback
}
?>
