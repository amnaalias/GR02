<?php
session_start();

// 1. Dapatkan nama group daripada URL parameter atau nama folder semasa
if (!isset($_GET['group'])) {
    $group = basename(dirname(__FILE__));
} else {
    $group = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['group']);
}

// 2. Panggil db.php - Use the same method as other files
$db_paths = [
    'db.php',
    '../db.php',
    '../../db.php',
    '../../All/db.php',
    '../All/db.php',
];

$db_found = false;
$conn = null;

foreach ($db_paths as $path) {
    if (file_exists($path)) {
        include $path;
        $db_found = true;
        break;
    }
}

// If db.php not found or connection failed, try direct connection
if (!$db_found || !isset($conn) || $conn->connect_error) {
    try {
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'gr02';
        
        $conn = new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            $conn = new mysqli($host, $username, $password);
            if (!$conn->connect_error) {
                $conn->query("CREATE DATABASE IF NOT EXISTS gr02");
                $conn->select_db('gr02');
            }
        }
        $conn->set_charset("utf8mb4");
    } catch (Exception $e) {
        $conn = null;
    }
}

// 3. Get student data from database
$members = [];
if ($conn && !$conn->connect_error) {
    // First try to get from student table (like other files do)
    $sql = "SELECT full_name, matric_no, group_no FROM student WHERE group_no = ? ORDER BY full_name ASC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $group);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
        $stmt->close();
    }
    $conn->close();
}

// If no students found, try alternative query
if (empty($members)) {
    try {
        // Try to get from mmdb2026.vstu as fallback
        $conn_mmdb = new mysqli('localhost', 'root', '', 'mmdb2026');
        if (!$conn_mmdb->connect_error) {
            $sql_vstu = "SELECT full_name, matric_no, group_no FROM vstu WHERE group_no = ? ORDER BY full_name ASC";
            
            if ($stmt = $conn_mmdb->prepare($sql_vstu)) {
                $stmt->bind_param("s", $group);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $members[] = $row;
                }
                $stmt->close();
            }
            $conn_mmdb->close();
        }
    } catch (Exception $e) {
        // Fallback - continue with empty members
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senarai Ahli Kumpulan | <?php echo htmlspecialchars($group); ?></title>
    <style>
        body { background: #0f0f0f; color: white; font-family: sans-serif; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 40px; flex-wrap: wrap; gap: 15px; }
        .header h1 { font-size: 2rem; }
        .group-badge { border: 1px solid #00d2ff; padding: 10px 25px; font-size: 1.6rem; border-radius: 5px; font-weight: bold; color: #00d2ff; }
        
        .table-container { border: 1px solid #444; border-radius: 12px; overflow: hidden; background: rgba(255,255,255,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        
        th, td { padding: 22px 30px; border-bottom: 1px solid #333; font-size: 1.3rem; }
        th { background: #161616; color: #00d2ff; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px; }
        
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.04); transition: 0.2s; }
        
        .text-break { word-break: break-all; line-height: 1.5; font-size: 1.35rem; font-weight: 500; }
        .matrix-code { color: #00d2ff; font-weight: bold; font-family: monospace; font-size: 1.4rem; }
        .bil-col { font-size: 1.3rem; font-weight: bold; }
        .empty-state { text-align: center; color: #ff4444; padding: 40px; font-size: 1.4rem; }
        
        .action-bar {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .btn-action {
            display: inline-block;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: 0.3s;
            cursor: pointer;
            border: none;
        }
        .btn-back {
            background: #555;
            color: white;
        }
        .btn-back:hover { background: #666; }
        .btn-stylescope {
            background: #007aff;
            color: white;
        }
        .btn-stylescope:hover { background: #0056b3; }
        
        @media (max-width: 768px) {
            body { padding: 20px; }
            th, td { padding: 15px 18px; font-size: 1rem; }
            .header h1 { font-size: 1.4rem; }
            .group-badge { font-size: 1.2rem; padding: 8px 16px; }
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1>SENARAI AHLI KUMPULAN</h1>
        <p style="margin: 8px 0 0; color: #c7c7c7; font-size: 1rem;">Sila semak senarai ahli kumpulan dengan teliti.</p>
    </div>
    <div class="group-badge">
        GROUP: <?php echo htmlspecialchars($group); ?>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="width: 100px;">BIL</th>
                <th>NAMA PENUH</th>
                <th style="width: 350px;">NO. MATRIK</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="3" class="empty-state">
                        <i class="fa-solid fa-user-slash" style="margin-right: 10px;"></i>
                        Tiada data ahli kumpulan ditemui untuk kod group "<?php echo htmlspecialchars($group); ?>".
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($members as $index => $row): ?>
                    <tr>
                        <td class="bil-col"><?php echo $index + 1; ?></td>
                        <td class="text-break" style="text-transform: uppercase;"><?php echo htmlspecialchars($row['full_name'] ?? '-'); ?></td>
                        <td class="matrix-code"><?php echo htmlspecialchars($row['matric_no'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="action-bar">
    <a href="dashboard.php?group=<?php echo urlencode($group); ?>" class="btn-action btn-back">
        <i class="fa-solid fa-arrow-left"></i> BACK TO DASHBOARD
    </a>
    <a href="dashboard.php?group=<?php echo urlencode($group); ?>" class="btn-action btn-stylescope">
        <i class="fa-solid fa-right-to-bracket"></i> ENTER STYLESCOPE
    </a>
</div>


</body>
</html>
