<?php
session_start();
require_once 'functions.php';

// Get group parameter
if (!isset($_GET['group'])) {
    $group = basename(dirname(__FILE__));
} else {
    $group = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['group']);
}

// Get stats from lecturer database
$group_stats = getGroupStats($group);
$analysis_stats = getAnalysisStats();

$total_members = $group_stats['total_members'] ?? 0;
$total_images = $group_stats['total_images'] ?? 0;
$total_pdfs = $group_stats['total_pdfs'] ?? 0;
$total_audios = $group_stats['total_audios'] ?? 0;
$total_files = $group_stats['total_files'] ?? 0;

$photo_analyzed = $analysis_stats['photo_analyzed'] ?? 0;
$audio_analyzed = $analysis_stats['audio_analyzed'] ?? 0;
$document_analyzed = $analysis_stats['document_analyzed'] ?? 0;
$total_analyses = $photo_analyzed + $audio_analyzed + $document_analyzed;

// Calculate percentages
$image_percent = $total_files > 0 ? round(($total_images / $total_files) * 100) : 0;
$pdf_percent = $total_files > 0 ? round(($total_pdfs / $total_files) * 100) : 0;
$audio_percent = $total_files > 0 ? round(($total_audios / $total_files) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #0f0f0f;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #1a1a1a;
            border-right: 1px solid #333;
            padding: 20px 0;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 0 20px 30px 20px;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }
        
        .sidebar-brand h2 {
            color: #00d2ff;
            font-size: 1.3rem;
        }
        
        .sidebar-brand h2 i { margin-right: 10px; }
        .sidebar-brand small { color: #888; font-size: 0.8rem; display: block; margin-top: 5px; }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            padding: 0 15px;
            margin-bottom: 2px;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #aaa;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
            font-size: 0.95rem;
        }
        
        .sidebar-menu li a:hover {
            background: rgba(0, 210, 255, 0.1);
            color: #fff;
        }
        
        .sidebar-menu li a.active {
            background: rgba(0, 210, 255, 0.15);
            color: #00d2ff;
        }
        
        .sidebar-menu li a i { width: 20px; text-align: center; font-size: 1.1rem; }
        
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 200;
            background: #1a1a1a;
            border: 1px solid #333;
            color: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
            flex: 1;
            min-height: 100vh;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 i { color: #00d2ff; }
        .header .group-name { color: #888; font-size: 1rem; font-weight: normal; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #333;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        
        .stat-card:hover {
            border-color: #00d2ff;
            transform: translateY(-3px);
        }
        
        .stat-card .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .stat-icon.blue { background: rgba(0, 210, 255, 0.15); color: #00d2ff; }
        .stat-icon.green { background: rgba(0, 255, 136, 0.15); color: #00ff88; }
        .stat-icon.orange { background: rgba(255, 159, 74, 0.15); color: #ff9f4a; }
        .stat-icon.purple { background: rgba(108, 92, 231, 0.15); color: #6c5ce7; }
        .stat-icon.red { background: rgba(255, 71, 87, 0.15); color: #ff4757; }
        .stat-icon.teal { background: rgba(0, 206, 209, 0.15); color: #00ced1; }
        
        .stat-card .stat-info { flex: 1; }
        .stat-card .stat-number { font-size: 1.8rem; font-weight: bold; }
        .stat-card .stat-label { color: #888; font-size: 0.85rem; }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 10px;
        }
        
        @media (max-width: 992px) {
            .content-grid { grid-template-columns: 1fr; }
        }
        
        .card {
            background: #1a1a1a;
            border-radius: 12px;
            border: 1px solid #333;
            padding: 25px;
            transition: 0.3s;
        }
        
        .card:hover { border-color: #00d2ff; }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #00d2ff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i { font-size: 1.2rem; }
        .card-title .badge-count {
            margin-left: auto;
            font-size: 0.8rem;
            background: #00d2ff;
            color: #000;
            padding: 2px 12px;
            border-radius: 20px;
            font-weight: normal;
        }
        
        .file-type-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        
        .file-type-item {
            text-align: center;
            padding: 15px;
            background: #111;
            border-radius: 8px;
            border: 1px solid #333;
        }
        
        .file-type-item .icon { font-size: 2rem; margin-bottom: 8px; }
        .file-type-item .count { font-size: 1.5rem; font-weight: bold; }
        .file-type-item .label { color: #888; font-size: 0.8rem; }
        .file-type-item .percent { font-size: 0.75rem; color: #00d2ff; margin-top: 5px; }
        
        .stat-bar {
            width: 100%;
            height: 4px;
            background: #222;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        .stat-bar .bar-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 1s ease;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #111;
            border-radius: 10px;
            border: 1px solid #333;
            color: #fff;
            text-decoration: none;
            transition: 0.3s;
            gap: 8px;
        }
        
        .quick-action-btn:hover {
            border-color: #00d2ff;
            transform: translateY(-3px);
            background: #161616;
        }
        
        .quick-action-btn i {
            font-size: 1.8rem;
            color: #00d2ff;
        }
        
        .quick-action-btn .action-label { font-size: 0.85rem; font-weight: 500; }
        .quick-action-btn .action-sub { font-size: 0.7rem; color: #555; }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #555;
        }
        .empty-state i { font-size: 2.5rem; display: block; margin-bottom: 10px; }
        
        .action-bar {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
            flex-direction: row;
        }
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1rem;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary { background: #00d2ff; color: #000; }
        .btn-primary:hover { background: #00b8d4; transform: scale(1.02); }
        .btn-secondary { background: #333; color: #fff; }
        .btn-secondary:hover { background: #444; transform: translateX(-3px); }
        .btn-success { background: #00ff88; color: #000; }
        .btn-success:hover { background: #00e67a; transform: scale(1.02); }
        .btn-warning { background: #ff9f4a; color: #000; }
        .btn-warning:hover { background: #f08c33; transform: scale(1.02); }
        .btn-danger { background: #ff4757; color: #fff; }
        .btn-danger:hover { background: #e03a4a; transform: scale(1.02); }
        
        @media (max-width: 768px) {
            .sidebar-toggle { display: block; }
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .sidebar.open { transform: translateX(0); }
            .main-content {
                margin-left: 0;
                padding: 15px;
                padding-top: 70px;
            }
            .header h1 { font-size: 1.4rem; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .stat-card { padding: 15px; }
            .file-type-grid { grid-template-columns: 1fr; }
            .quick-actions { grid-template-columns: 1fr 1fr; }
            .action-bar { flex-direction: column; }
            .btn-action { justify-content: center; }
        }
        
        @media (max-width: 500px) {
            .stats-grid { grid-template-columns: 1fr; }
            .quick-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h2><i class="fas fa-flask"></i> Research Archive</h2>
        <small>Data Management System</small>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php?group=<?php echo urlencode($group); ?>" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="cbr.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-image"></i> CBR Detection</a></li>
        <li><a href="tbr.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-file-pdf"></i> TBR Detection</a></li>
        <li><a href="abr.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-music"></i> ABR Detection</a></li>
        <li><a href="index.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-users"></i> Group Members</a></li>
    </ul>
</nav>

<main class="main-content">
    <div class="header">
        <h1>
            <i class="fas fa-tachometer-alt"></i>
            DASHBOARD
            <span class="group-name">- <?php echo htmlspecialchars($group); ?></span>
        </h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_members); ?></div>
                <div class="stat-label">Group Members</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-camera"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($photo_analyzed); ?></div>
                <div class="stat-label">Photo Analysis Done</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-microphone"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($audio_analyzed); ?></div>
                <div class="stat-label">Audio Analysis Done</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-file-pdf"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($document_analyzed); ?></div>
                <div class="stat-label">Document Analysis Done</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_analyses); ?></div>
                <div class="stat-label">Total Analyses</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-folder-open"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_files); ?></div>
                <div class="stat-label">Total Files</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 30px;">
        <div class="card-title">
            <i class="fas fa-chart-pie"></i> 
            File Type Distribution
            <span class="badge-count"><?php echo htmlspecialchars($total_files); ?> total</span>
        </div>
        <div class="file-type-grid">
            <div class="file-type-item" style="border-left: 4px solid #00d2ff;">
                <div class="icon" style="color: #00d2ff;"><i class="fas fa-image"></i></div>
                <div class="count" style="color: #00d2ff;"><?php echo htmlspecialchars($total_images); ?></div>
                <div class="label">Images</div>
                <div class="percent"><?php echo htmlspecialchars($image_percent); ?>% of files</div>
                <div class="stat-bar">
                    <div class="bar-fill" style="width: <?php echo htmlspecialchars($image_percent); ?>%; background: #00d2ff;"></div>
                </div>
            </div>
            <div class="file-type-item" style="border-left: 4px solid #00ff88;">
                <div class="icon" style="color: #00ff88;"><i class="fas fa-file-pdf"></i></div>
                <div class="count" style="color: #00ff88;"><?php echo htmlspecialchars($total_pdfs); ?></div>
                <div class="label">PDF Documents</div>
                <div class="percent"><?php echo htmlspecialchars($pdf_percent); ?>% of files</div>
                <div class="stat-bar">
                    <div class="bar-fill" style="width: <?php echo htmlspecialchars($pdf_percent); ?>%; background: #00ff88;"></div>
                </div>
            </div>
            <div class="file-type-item" style="border-left: 4px solid #ff9f4a;">
                <div class="icon" style="color: #ff9f4a;"><i class="fas fa-music"></i></div>
                <div class="count" style="color: #ff9f4a;"><?php echo htmlspecialchars($total_audios); ?></div>
                <div class="label">Audio Files</div>
                <div class="percent"><?php echo htmlspecialchars($audio_percent); ?>% of files</div>
                <div class="stat-bar">
                    <div class="bar-fill" style="width: <?php echo htmlspecialchars($audio_percent); ?>%; background: #ff9f4a;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="card">
            <div class="card-title">
                <i class="fas fa-bolt"></i>
                QUICK ACTIONS
            </div>
            <div class="quick-actions">
                <a href="cbr.php?group=<?php echo urlencode($group); ?>" class="quick-action-btn">
                    <i class="fas fa-image"></i>
                    <span class="action-label">CBR Detection</span>
                    <span class="action-sub">Photo analysis</span>
                </a>
                <a href="tbr.php?group=<?php echo urlencode($group); ?>" class="quick-action-btn">
                    <i class="fas fa-file-pdf"></i>
                    <span class="action-label">TBR Detection</span>
                    <span class="action-sub">Document analysis</span>
                </a>
                <a href="abr.php?group=<?php echo urlencode($group); ?>" class="quick-action-btn">
                    <i class="fas fa-music"></i>
                    <span class="action-label">ABR Detection</span>
                    <span class="action-sub">Audio analysis</span>
                </a>
                <a href="index.php?group=<?php echo urlencode($group); ?>" class="quick-action-btn">
                    <i class="fas fa-users"></i>
                    <span class="action-label">Group Members</span>
                    <span class="action-sub">View all members</span>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-title">
                <i class="fas fa-clock"></i>
                RECENT ACTIVITY
                <span class="badge-count"><?php echo htmlspecialchars($total_analyses); ?></span>
            </div>
            <div class="empty-state">
                <?php if ($total_analyses > 0): ?>
                    <i class="fas fa-check-circle" style="color: #00ff88;"></i>
                    <span style="color: #aaa;">Analysis tasks are actively recorded in gr02 database.</span>
                <?php else: ?>
                    <i class="fas fa-inbox"></i>
                    No data processed yet
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <a href="index.php?group=<?php echo urlencode($group); ?>" class="btn-action btn-primary">
            <i class="fas fa-users"></i> BACK TO MAIN DASHBOARD
        </a>
        <a href="cbr.php?group=<?php echo urlencode($group); ?>" class="btn-action btn-warning">
            <i class="fas fa-image"></i> CBR
        </a>
        <a href="tbr.php?group=<?php echo urlencode($group); ?>" class="btn-action btn-danger">
            <i class="fas fa-file-pdf"></i> TBR
        </a>
        <a href="abr.php?group=<?php echo urlencode($group); ?>" class="btn-action btn-secondary">
            <i class="fas fa-music"></i> ABR
        </a>
    </div>
</main>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('open');
    }
    
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        const bars = document.querySelectorAll('.bar-fill');
        bars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 300);
        });
    });
</script>

</body>
</html>