<?php
session_start();
require_once 'functions.php';

// Get group parameter
if (!isset($_GET['group'])) {
    $group = basename(dirname(__FILE__));
} else {
    $group = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['group']);
}

// Get students with documents from lecturer database
$members = getStudentsWithDocuments($group);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TBR Detection | Group <?php echo htmlspecialchars($group); ?></title>
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
        
        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .document-card {
            background: #1a1a1a;
            border-radius: 12px;
            border: 1px solid #333;
            padding: 20px;
            transition: 0.3s;
        }
        
        .document-card:hover {
            border-color: #00d2ff;
            transform: translateY(-5px);
        }
        
        .document-card .doc-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        
        .document-card .doc-header .doc-icon {
            font-size: 2.5rem;
            color: #ff4757;
        }
        
        .document-card .doc-header .name {
            font-weight: bold;
            font-size: 1.1rem;
            color: #00d2ff;
        }
        
        .document-card .doc-header .matric {
            color: #888;
            font-size: 0.85rem;
            font-family: monospace;
        }
        
        .document-card .doc-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        .document-card .doc-details .detail-item {
            background: #111;
            padding: 10px;
            border-radius: 6px;
        }
        
        .document-card .doc-details .detail-item .label {
            color: #888;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        
        .document-card .doc-details .detail-item .value {
            font-size: 1rem;
            font-weight: bold;
            margin-top: 3px;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .badge-language {
            background: #6c5ce7;
            color: #fff;
        }
        
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
        
        .btn-analyze {
            background: #6c5ce7;
            color: #fff;
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
            background: #5a4bd1;
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
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
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
            .document-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 500px) {
            .stats-bar { flex-direction: column; }
            .stat-item { width: 100%; }
            .document-card .doc-details { grid-template-columns: 1fr; }
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
        <li><a href="cbr.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-image"></i> CBR Detection</a></li>
        <li><a href="tbr.php?group=<?php echo urlencode($group); ?>" class="active"><i class="fas fa-file-pdf"></i> TBR Detection</a></li>
        <li><a href="abr.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-music"></i> ABR Detection</a></li>
        <li><a href="index.php?group=<?php echo urlencode($group); ?>"><i class="fas fa-users"></i> Group Members</a></li>
    </ul>
</nav>

<main class="main-content">
    <div class="header">
        <h1>
            <i class="fas fa-file-pdf"></i>
            TBR DETECTION
            <span class="group-name">- Text-Based Retrieval</span>
        </h1>
    </div>

    <div class="stats-bar">
        <div class="stat-item">
            <i class="fas fa-file-pdf"></i>
            <div class="stat-info">
                <div class="number"><?php echo count($members); ?></div>
                <div class="label">Total Documents</div>
            </div>
        </div>
        <div class="stat-item">
            <i class="fas fa-language"></i>
            <div class="stat-info">
                <div class="number">
                    <?php 
                    $langs = [];
                    foreach ($members as $m) {
                        if (isset($m['language']) && !empty($m['language'])) {
                            $langs[] = $m['language'];
                        }
                    }
                    echo count(array_unique($langs));
                    ?>
                </div>
                <div class="label">Languages Detected</div>
            </div>
        </div>
        <div class="stat-item">
            <i class="fas fa-file-word"></i>
            <div class="stat-info">
                <div class="number">
                    <?php 
                    $total_words = 0;
                    foreach ($members as $m) {
                        $total_words += isset($m['word_count']) ? $m['word_count'] : 0;
                    }
                    echo number_format($total_words);
                    ?>
                </div>
                <div class="label">Total Words</div>
            </div>
        </div>
        <div class="stat-item">
            <i class="fas fa-file"></i>
            <div class="stat-info">
                <div class="number">
                    <?php 
                    $total_pages = 0;
                    foreach ($members as $m) {
                        $total_pages += isset($m['page_count']) ? $m['page_count'] : 0;
                    }
                    echo $total_pages;
                    ?>
                </div>
                <div class="label">Total Pages</div>
            </div>
        </div>
    </div>

    <?php if (empty($members)): ?>
        <div class="empty-state">
            <i class="fas fa-file-pdf"></i>
            <h3>No Documents Found</h3>
            <p>No documents have been uploaded or analyzed for this group.</p>
        </div>
    <?php else: ?>
        <div class="document-grid">
            <?php foreach ($members as $member): ?>
                <div class="document-card">
                    <div class="doc-header">
                        <div class="doc-icon"><i class="fas fa-file-pdf"></i></div>
                        <div>
                            <div class="name"><?php echo htmlspecialchars($member['full_name']); ?></div>
                            <div class="matric"><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($member['matric_no']); ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($member['docStu'])): ?>
                        <div style="font-size:0.85rem;color:#888;margin-bottom:10px;">
                            <i class="fas fa-file"></i> <?php echo basename($member['docStu']); ?>
                            <a href="<?php echo htmlspecialchars($member['docStu']); ?>" target="_blank" style="color:#00d2ff;margin-left:10px;">
                                <i class="fas fa-external-link-alt"></i> View
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="doc-details">
                        <div class="detail-item">
                            <div class="label">Language</div>
                            <div class="value">
                                <span class="badge badge-language">
                                    <i class="fas fa-language"></i> <?php echo isset($member['language']) ? htmlspecialchars($member['language']) : 'N/A'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Document Type</div>
                            <div class="value"><?php echo isset($member['document_type']) ? htmlspecialchars($member['document_type']) : 'N/A'; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Word Count</div>
                            <div class="value"><?php echo isset($member['word_count']) ? number_format($member['word_count']) : 'N/A'; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Pages</div>
                            <div class="value"><?php echo isset($member['page_count']) ? $member['page_count'] : 'N/A'; ?></div>
                        </div>
                    </div>
                    
                    <?php if (isset($member['analysis_date'])): ?>
                        <div style="margin-top:10px;font-size:0.75rem;color:#555;">
                            <i class="far fa-clock"></i> Analyzed: <?php echo date('d/m/Y H:i', strtotime($member['analysis_date'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 10px;">
                        <button class="btn-analyze" onclick="analyzeDocument('<?php echo $member['matric_no']; ?>', '<?php echo htmlspecialchars($member['docStu']); ?>')">
                            <i class="fas fa-robot"></i> Analyze Document
                        </button>
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
    
    <script>
    // ... your other sidebar scripts remain the same ...

    // Updated analyzeDocument function
    function analyzeDocument(event, matricNo, docPath) {
        // Use event.currentTarget to reliably get the button that was clicked
        const button = event.currentTarget; 
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="loading-spinner"></span> Analyzing...';
        
        fetch('analyze_document.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'matric_no=' + encodeURIComponent(matricNo) + '&doc_path=' + encodeURIComponent(docPath)
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
