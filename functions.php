// ============================================
// STUDENT DATA FUNCTIONS (READ ALL GROUPS)
// ============================================

function getStudentsFromLectureDB($group = null) {
    global $conn;
    
    if (!$conn) {
        error_log("❌ getStudentsFromLectureDB: No connection to database");
        return [];
    }
    
    // CHANGED: Load all students if no specific group filter is passed
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
    
    // CHANGED: Load all students from all groups who have a photo
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
    
    // CHANGED: Removed group constraint to fetch all audio files globally
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
    
    // CHANGED: Removed group filter constraint to list all documents globally
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
