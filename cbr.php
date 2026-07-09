<?php
session_start();
require_once 'functions.php';

// Get group parameter
if (!isset($_GET['group'])) {
    $group = basename(dirname(__FILE__));
} else {
    $group = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['group']);
}

// Get students with photos from lecturer database using SELECT *
$members = getStudentsWithPhotos($group);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBR Detection | Group <?php echo htmlspecialchars($group); ?></title>
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
        
        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            background: #1a1a1a;
            padding: 15px 25px;
            border-radius: 10px;
            border: 1px solid #333;
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            min-width: 150px;
        }
        
        .stat-item i { font-size: 1.8rem; color: #00d2ff; }
        .stat-item .stat-info { display: flex; flex-direction: column; }
        .stat-item .number { font-size: 1.5rem; font-weight: bold; }
        .stat-item .label { color: #888; font-size: 0.85rem; }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .photo-card {
            background: #1a1a1a;
            border-radius: 12px;
            border: 1px solid #333;
            overflow: hidden;
            transition: 0.3s;
        }
        
        .photo-card:hover {
            border-color: #00d2ff;
            transform: translateY(-5px);
        }
        
        .photo-card .photo-image {
            width: 100%;
            height: 200px;
            background: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .photo-card .photo-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-card .photo-image .no-image {
            color: #555;
            font-size: 3rem;
        }
        
        .photo-card .photo-info {
            padding: 15px;
        }
        
        .photo-card .photo-info .name {
            font-weight: bold;
            font-size: 1.1rem;
            color: #00d2ff;
        }
        
        .photo-card .photo-info .matric {
            color: #888;
            font-size: 0.85rem;
            font-family: monospace;
        }
        
        .photo-card .photo-info .analysis-results {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding-top: 10px;
            border-top: 1px solid #333;
        }
        
        .photo-card .photo-info .analysis-results .result-item {
            font-size: 0.8rem;
        }
        
        .photo-card .photo-info .analysis-results .result-item .label {
            color: #888;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .badge-formal { background: #00ff88; color: #000; }
        .badge-informal { background: #ff6b6b; color: #fff; }
        .badge-yes { background: #00ff88; color: #000; }
        .badge-no { background: #ff6b6b; color: #fff; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #555;
        }
        
        .empty-state i {
            font-size: 4rem;
            display: block;
            margin-bottom: 20px;
        }
        
        .action-bar {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
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
        }
        
        .btn-primary { background: #00d2ff; color: #000; }
        .btn-primary:hover { background: #00b8d4; transform: scale(1.02); }
        
        .btn-secondary { background: #333; color: #fff; }
        .btn-secondary:hover { background: #444; transform: translateX(-3px); }
        
        /* Analyze Button */
        .btn-analyze {
            background: #00ff88;
            color: #000;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-analyze:hover {
            background: #00e67a;
            transform: scale(1.05);
        }
        
        .btn-analyze:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(0,0,0,0.3);
            border-radius: 50%;
            border-top-color: #000;
            animation: spin 0.8s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
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
            .photo-grid { grid-template-columns: 1fr 1fr; }
        }
        
        @media (max-width: 500px) {
            .photo-grid { grid-template-columns: 1fr; }
            .stats-bar { flex-direction: column; }
            .stat-item { width: 100%; }
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
        <li><a href="dashboard.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="cbr.php?group=<?php echo urlencode($group); ?>" class="active"><i class="fas fa-image"></i> CBR Detection</a></li>
        <li><a href="tbr.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-file-pdf"></i> TBR Detection</a></li>
        <li><a href="abr.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-music"></i> ABR Detection</a></li>
        <li><a href="index.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-users"></i> Group Members</a></li>
    </ul>
</nav>

<main class="main-content">
    <div class="header">
        <h1>
            <i class="fas fa-image"></i>
            CBR DETECTION
            <span class="group-name">- Content-Based Retrieval</span>
        </h1>
    </div>

    <div class="stats-bar">
        <div class="stat-item">
            <i class="fas fa-image"></i>
            <div class="stat-info">
                <div class="number"><?php echo count($members); ?></div>
                <div class="label">Total Photos</div>
            </div>
        </div>
        <div class="stat-item">
            <i class="fas fa-check-circle"></i>
            <div class="stat-info">
                <div class="number">
                    <?php 
                    $formal_count = 0;
                    foreach ($members as $m) {
                        if (isset($m['is_formal']) && $m['is_formal'] == 1) $formal_count++;
                    }
                    echo $formal_count;
                    ?>
                </div>
                <div class="label">Formal Photos</div>
            </div>
        </div>
        <div class="stat-item">
            <i class="fas fa-smile"></i>
            <div class="stat-info">
                <div class="number">
                    <?php 
                    $smile_count = 0;
                    foreach ($members as $m) {
                        if (isset($m['has_smile']) && $m['has_smile'] == 1) $smile_count++;
                    }
                    echo $smile_count;
                    ?>
                </div>
                <div class="label">With Smile</div>
            </div>
        </div>
        <div class="stat-item">
            <i class="fas fa-glasses"></i>
            <div class="stat-info">
                <div class="number">
                    <?php 
                    $glasses_count = 0;
                    foreach ($members as $m) {
                        if (isset($m['has_glasses']) && $m['has_glasses'] == 1) $glasses_count++;
                    }
                    echo $glasses_count;
                    ?>
                </div>
                <div class="label">With Glasses</div>
            </div>
        </div>
    </div>

    <?php if (empty($members)): ?>
        <div class="empty-state">
            <i class="fas fa-image"></i>
            <h3>No Photos Found</h3>
            <p>No photos have been uploaded or analyzed for this group.</p>
        </div>
    <?php else: ?>
        <div class="photo-grid">
            <?php foreach ($members as $member): ?>
            <?php
            $photoPath = trim($member['photoStu'] ?? '');
            $matricNo = strtolower(trim($member['matric_no'] ?? ''));
            $photoUrl = '';

            if (!empty($photoPath)) {
                if (strpos($photoPath, 'http') === 0) {
                    // Already an absolute URL
                    $photoUrl = $photoPath;
                } elseif (preg_match('/uploads\//i', $photoPath)) {
                    // Has 'uploads/' in it -> convert to full server path
                    $photoUrl = 'https://bitp3353.utem.edu.my/2026/all/' . ltrim($photoPath, '/');
                } else {
                    // Just the file name format -> build it with matric folder dynamically
                    $photoUrl = 'https://bitp3353.utem.edu.my/2026/all/uploads/' . $matricNo . '/' . ltrim($photoPath, '/');
                }
            }
            ?>

            <div class="photo-card">
                <div class="photo-image">
                    <?php if (!empty($photoUrl)): ?>
                        <img src="<?php echo htmlspecialchars($photoUrl); ?>" 
                             alt="<?php echo htmlspecialchars($member['full_name'] ?? 'Student Photo'); ?>"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="no-image" style="display:none;"><i class="fas fa-exclamation-triangle"></i></div>
                    <?php else: ?>
                        <div class="no-image"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                </div>

                <div class="photo-info">
                    <div class="name">
                        <?php echo htmlspecialchars($member['full_name']); ?>
                    </div>

                    <div class="matric">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo htmlspecialchars($member['matric_no']); ?>
                    </div>

                    <div class="analysis-results">
                        <div class="result-item">
                            <span class="label">Type:</span>
                            <?php if (isset($member['is_formal']) && $member['is_formal'] == 1): ?>
                                <span class="badge badge-formal">Formal</span>
                            <?php else: ?>
                                <span class="badge badge-informal">Informal</span>
                            <?php endif; ?>
                        </div>
                        <div class="result-item">
                            <span class="label">Smile:</span>
                            <?php if (isset($member['has_smile']) && $member['has_smile'] == 1): ?>
                                <span class="badge badge-yes">Yes</span>
                            <?php else: ?>
                                <span class="badge badge-no">No</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-top: 10px;">
                        <button class="btn-analyze" 
                                onclick="analyzePhoto(this, '<?php echo htmlspecialchars($member['matric_no']); ?>', '<?php echo htmlspecialchars($photoUrl); ?>')">
                            <i class="fas fa-robot"></i> Analyze Photo
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="action-bar">
        <a href="dashboard.php?group=<?php echo urlencode($group); ?>" class="btn-action btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
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
    
    function analyzePhoto(buttonElement, matricNo, photoPath) {
        const button = buttonElement.closest('.btn-analyze');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="loading-spinner"></span> Analyzing...';
        
        fetch('analyze_photo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'matric_no=' + encodeURIComponent(matricNo) + '&photo_path=' + encodeURIComponent(photoPath)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Analysis completed successfully!');
                location.reload();
            } else {
                alert('❌ Analysis failed: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
</script>

</body>
</html>
