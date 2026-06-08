<?php
// dashboard.php - Sidebar & Layout Utama
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = isset($current_page) ? $current_page : '';
$page_title = isset($page_title) ? $page_title : 'Dashboard Admin';

require_once __DIR__ . '/config.php';

$total_berita = 0;
$berita_terbit = 0;
$berita_draft = 0;
$total_views = 0;

$res = $conn->query("SELECT COUNT(*) as total, SUM(dilihat) as views FROM berita");
if ($res) {
    $row = $res->fetch_assoc();
    $total_berita = $row['total'];
    $total_views = $row['views'] ?? 0;
}
$res2 = $conn->query("SELECT COUNT(*) as cnt FROM berita WHERE status='Terbit'");
if ($res2) $berita_terbit = $res2->fetch_assoc()['cnt'];
$res3 = $conn->query("SELECT COUNT(*) as cnt FROM berita WHERE status='Draft'");
if ($res3) $berita_draft = $res3->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Admin Fakultas</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #2563EB;
            --primary-hover: #1D4ED8;
            --primary-subtle: #EFF6FF;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;

            --sidebar-w: 256px;
            --topbar-h: 64px;

            --white: #FFFFFF;
            --gray-50: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-300: #CBD5E1;
            --gray-400: #94A3B8;
            --gray-500: #64748B;
            --gray-700: #334155;
            --gray-900: #0F172A;

            --sidebar-bg: #FFFFFF;
            --sidebar-border: #F1F5F9;
            --body-bg: #F8FAFC;
            --card-bg: #FFFFFF;

            --text-main: #0F172A;
            --text-muted: #64748B;
            --text-light: #94A3B8;

            --radius-sm: 8px;
            --radius-md: 10px;
            --radius-lg: 14px;

            --transition: 0.18s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--body-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            font-size: 14px;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            height: auto;
            position: fixed;
            top: 0; left: 0;
            display: flex;
            bottom: 0;
            flex-direction: column;
            z-index: 100;
            border-right: 1px solid var(--gray-200);
            transition: transform var(--transition);
        }

        .sidebar-brand {
            padding: 0 20px;
            height: var(--topbar-h);
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--gray-100);
            flex-shrink: 0;
        }

        .brand-icon {
            width: 34px; height: 34px;
            background: var(--primary);
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .brand-icon i { color: #fff; font-size: 15px; }

        .brand-name {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: -0.2px;
        }

        .brand-sub {
            font-size: 10px;
            color: var(--text-light);
            font-weight: 400;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 16px 12px;
            scrollbar-width: none;
        }

        .sidebar-nav::-webkit-scrollbar { display: none; }

        .nav-section {
            margin-bottom: 20px;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            padding: 0 8px;
            margin-bottom: 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all var(--transition);
            margin-bottom: 1px;
        }

        .nav-item:hover {
            background: var(--gray-100);
            color: var(--text-main);
        }

        .nav-item.active {
            background: var(--primary-subtle);
            color: var(--primary);
            font-weight: 600;
        }

        .nav-item .nav-icon {
            width: 30px; height: 30px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            color: inherit;
            opacity: 0.7;
        }

        .nav-item.active .nav-icon,
        .nav-item:hover .nav-icon {
            opacity: 1;
        }

        /* Stats Block */
        .sidebar-stats {
            margin: 4px 0;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 14px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
            margin-bottom: 12px;
        }

        .stat-cell {
            text-align: center;
            padding: 4px 0;
        }

        .stat-value {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.2;
        }

        .stat-value.green { color: var(--success); }
        .stat-value.amber { color: var(--warning); }

        .stat-label {
            font-size: 10px;
            color: var(--text-light);
            margin-top: 1px;
        }

        .stats-divider {
            height: 1px;
            background: var(--gray-200);
            margin-bottom: 10px;
        }

        .stats-views {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: var(--text-muted);
        }

        .stats-views i { color: var(--primary); font-size: 11px; }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--gray-100);
            flex-shrink: 0;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background var(--transition);
        }

        .sidebar-user:hover { background: var(--gray-100); }

        .user-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .user-name { font-size: 12.5px; font-weight: 600; color: var(--text-main); }
        .user-role { font-size: 11px; color: var(--text-light); }
        .user-info { flex: 1; min-width: 0; }

        /* ===== MAIN WRAPPER ===== */
        .main-wrapper {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            height: var(--topbar-h);
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 90;
            gap: 14px;
        }

        .topbar-toggle {
            display: none;
            width: 34px; height: 34px;
            border: none;
            background: none;
            cursor: pointer;
            color: var(--text-muted);
            border-radius: var(--radius-sm);
            font-size: 17px;
            transition: background var(--transition);
        }

        .topbar-toggle:hover { background: var(--gray-100); }

        .topbar-breadcrumb { flex: 1; }

        .breadcrumb-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
            line-height: 1;
        }

        .breadcrumb-path {
            font-size: 11.5px;
            color: var(--text-light);
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .breadcrumb-path .crumb-active { color: var(--primary); }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .topbar-btn {
            width: 36px; height: 36px;
            border: 1px solid var(--gray-200);
            background: var(--white);
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 14px;
            transition: all var(--transition);
            text-decoration: none;
            position: relative;
        }

        .topbar-btn:hover {
            background: var(--gray-100);
            color: var(--text-main);
            border-color: var(--gray-300);
        }

        .topbar-divider {
            width: 1px; height: 24px;
            background: var(--gray-200);
            margin: 0 2px;
        }

        .topbar-profile {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px 5px 5px;
            border: 1px solid var(--gray-200);
            border-radius: 40px;
            cursor: pointer;
            background: var(--white);
            transition: all var(--transition);
            text-decoration: none;
        }

        .topbar-profile:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
        }

        .profile-avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
        }

        .profile-name {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-main);
        }

        /* Notif dot — hanya titik kecil, tanpa badge angka */
        .notif-dot {
            position: absolute;
            top: 8px; right: 8px;
            width: 6px; height: 6px;
            background: var(--danger);
            border-radius: 50%;
            border: 1.5px solid var(--white);
        }

        /* ===== PAGE CONTENT ===== */
        .page-content {
            flex: 1;
            padding: 24px;
        }

        /* ===== OVERLAY MOBILE ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.3);
            z-index: 99;
            backdrop-filter: blur(2px);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(calc(-1 * var(--sidebar-w))); }
            .sidebar.open { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
            .topbar-toggle { display: flex; }
            .sidebar-overlay.show { display: block; }
        }

        @media (max-width: 480px){

    .topbar{
        gap:8px;
    }

    .profile-name{
        display:none;
    }

    .topbar-divider{
        display:none;
    }

    .topbar-btn{
        width:32px;
        height:32px;
    }

    .breadcrumb-title{
        font-size:14px;
    }

    .breadcrumb-path{
        font-size:10px;
    }
}

        @media (max-width: 640px) {
            .page-content { padding: 16px; }
            .topbar { padding: 0 16px; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-user"></i>
        </div>
        <div>
            <div class="brand-name">Admin Fakultas</div>
            <div class="brand-sub">Panel Manajemen</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-label">Menu Utama</div>
            <a href="../index.php" class="nav-item <?= $current_page === 'home' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-home"></i></span>
                Dashboard
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Konten</div>
            <a href="berita/index.php" class="nav-item <?= $current_page === 'berita' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-newspaper"></i></span>
                Berita Fakultas
            </a>
        </div>

        <!-- <div class="nav-section">
            <div class="nav-label">Statistik</div>
            <div class="sidebar-stats">
                <div class="stats-row">
                    <div class="stat-cell">
                        <div class="stat-value"><?= number_format($total_berita) ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-value green"><?= number_format($berita_terbit) ?></div>
                        <div class="stat-label">Terbit</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-value amber"><?= number_format($berita_draft) ?></div>
                        <div class="stat-label">Draft</div>
                    </div>
                </div>
                <div class="stats-divider"></div>
                <div class="stats-views">
                    <i class="fas fa-eye"></i>
                    <span><?= number_format($total_views) ?> total tampilan</span>
                </div>
            </div>
        </div> -->
    </nav>

    
</aside>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-wrapper">
    <header class="topbar">
        <button class="topbar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-breadcrumb">
            <div class="breadcrumb-title"><?= htmlspecialchars($page_title) ?></div>
            <div class="breadcrumb-path">
                <i class="fas fa-house" style="font-size:9px;"></i>
                Admin
                <i class="fas fa-chevron-right" style="font-size:8px;color:var(--gray-300);"></i>
                <span class="crumb-active"><?= htmlspecialchars($page_title) ?></span>
            </div>
        </div>

        <div class="topbar-actions">
            <a href="../berita/tambah.php" class="topbar-btn" title="Tambah Berita">
                <i class="fas fa-plus"></i>
            </a>
            <button class="topbar-btn" title="Notifikasi">
                <i class="fas fa-bell"></i>
                <?php if ($berita_draft > 0): ?>
                <span class="notif-dot"></span>
                <?php endif; ?>
            </button>
            <div class="topbar-divider"></div>
            <div class="topbar-profile">
                <div class="profile-avatar">A</div>
                <span class="profile-name">Admin</span>
            </div>
        </div>
    </header>

    <main class="page-content">

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
    </script>