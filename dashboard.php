<?php
// dashboard.php - Sidebar & Layout Utama
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tentukan halaman aktif
$current_page = isset($current_page) ? $current_page : '';
$page_title = isset($page_title) ? $page_title : 'Dashboard Admin';

// Hitung statistik untuk sidebar
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #2563EB;
            --primary-dark: #1D4ED8;
            --primary-light: #EFF6FF;
            --primary-soft: #DBEAFE;
            --accent: #0EA5E9;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --sidebar-bg: #0F172A;
            --sidebar-hover: #1E293B;
            --sidebar-active: rgba(37,99,235,0.15);
            --sidebar-border: rgba(255,255,255,0.06);
            --sidebar-text: #94A3B8;
            --sidebar-text-active: #F8FAFC;
            --topbar-bg: #FFFFFF;
            --body-bg: #F1F5F9;
            --card-bg: #FFFFFF;
            --border: #E2E8F0;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --text-light: #94A3B8;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.10);
            --radius: 12px;
            --radius-sm: 8px;
            --sidebar-w: 260px;
            --topbar-h: 68px;
            --transition: 0.22s cubic-bezier(0.4,0,0.2,1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--body-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform var(--transition);
            box-shadow: 4px 0 24px rgba(0,0,0,0.15);
        }

        .sidebar-brand {
            padding: 0 24px;
            height: var(--topbar-h);
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--sidebar-border);
            flex-shrink: 0;
        }

        .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .brand-icon i { color: #fff; font-size: 17px; }

        .brand-text {
            display: flex; flex-direction: column;
        }

        .brand-name {
            font-size: 14px;
            font-weight: 700;
            color: #F8FAFC;
            line-height: 1.2;
            letter-spacing: -0.2px;
        }

        .brand-sub {
            font-size: 10.5px;
            color: var(--sidebar-text);
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 20px 14px;
            scrollbar-width: thin;
            scrollbar-color: #334155 transparent;
        }

        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        .nav-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #475569;
            padding: 0 10px;
            margin: 18px 0 8px;
        }

        .nav-label:first-child { margin-top: 0; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all var(--transition);
            position: relative;
            margin-bottom: 2px;
        }

        .nav-item:hover {
            background: var(--sidebar-hover);
            color: var(--sidebar-text-active);
        }

        .nav-item.active {
            background: var(--sidebar-active);
            color: #60A5FA;
            font-weight: 600;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 6px; bottom: 6px;
            width: 3px;
            background: var(--primary);
            border-radius: 0 3px 3px 0;
        }

        .nav-item .nav-icon {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px;
            font-size: 14px;
            flex-shrink: 0;
            transition: all var(--transition);
        }

        .nav-item:hover .nav-icon,
        .nav-item.active .nav-icon {
            background: rgba(37,99,235,0.2);
            color: #60A5FA;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--primary);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
            line-height: 1.6;
        }

       

        .sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid var(--sidebar-border);
            flex-shrink: 0;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background var(--transition);
        }

        .sidebar-user:hover { background: var(--sidebar-hover); }

        .user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 12.5px; font-weight: 600; color: #F8FAFC; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 11px; color: var(--sidebar-text); }

        /* ===== MAIN LAYOUT ===== */
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
            background: var(--topbar-bg);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 90;
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }

        .topbar-toggle {
            display: none;
            width: 36px; height: 36px;
            border: none;
            background: none;
            cursor: pointer;
            color: var(--text-muted);
            border-radius: 8px;
            font-size: 18px;
            transition: background var(--transition);
        }

        .topbar-toggle:hover { background: var(--body-bg); }

        .topbar-breadcrumb {
            flex: 1;
        }

        .breadcrumb-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1;
        }

        .breadcrumb-path {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .breadcrumb-path span { color: var(--primary); }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-btn {
            width: 38px; height: 38px;
            border: 1px solid var(--border);
            background: var(--card-bg);
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 15px;
            transition: all var(--transition);
            position: relative;
            text-decoration: none;
        }

        .topbar-btn:hover {
            background: var(--primary-light);
            border-color: var(--primary-soft);
            color: var(--primary);
        }

        .topbar-btn .notif-dot {
            position: absolute;
            top: 7px; right: 7px;
            width: 7px; height: 7px;
            background: var(--danger);
            border-radius: 50%;
            border: 1.5px solid #fff;
        }

        .topbar-divider {
            width: 1px; height: 28px;
            background: var(--border);
            margin: 0 4px;
        }

        .topbar-profile {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 5px 12px 5px 5px;
            border: 1px solid var(--border);
            border-radius: 40px;
            cursor: pointer;
            background: var(--card-bg);
            transition: all var(--transition);
            text-decoration: none;
        }

        .topbar-profile:hover { border-color: var(--primary-soft); background: var(--primary-light); }

        .profile-avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .profile-name { font-size: 12.5px; font-weight: 600; color: var(--text-main); }

        /* ===== PAGE CONTENT ===== */
        .page-content {
            flex: 1;
            padding: 28px;
        }

        /* ===== OVERLAY MOBILE ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(calc(-1 * var(--sidebar-w)));
            }
            .sidebar.open { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
            .topbar-toggle { display: flex; }
            .sidebar-overlay.show { display: block; }
        }

        @media (max-width: 640px) {
            .page-content { padding: 16px; }
            .topbar { padding: 0 16px; }
        }
    </style>
</head>
<body>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-university"></i>
        </div>
        <div class="brand-text">
            <div class="brand-name">Admin Fakultas</div>
            <div class="brand-sub">Panel Manajemen</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-label">Menu Utama</div>

        <a href="../dashboard.php" class="nav-item <?= $current_page === 'home' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-home"></i></span>
            Dashboard
        </a>

        <div class="nav-label">Konten</div>

        <a href="berita/index.php" class="nav-item <?= $current_page === 'berita' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-newspaper"></i></span>
            Berita Fakultas
            <?php if ($berita_draft > 0): ?>
            <?php endif; ?>
        </a>

        <div class="nav-label">Statistik</div>

        <!-- Stats Mini -->
        <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:14px;margin-bottom:4px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                <div style="text-align:center;">
                    <div style="font-size:18px;font-weight:700;color:#F8FAFC;"><?= number_format($total_berita) ?></div>
                    <div style="font-size:10px;color:#64748B;">Total</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:18px;font-weight:700;color:#10B981;"><?= number_format($berita_terbit) ?></div>
                    <div style="font-size:10px;color:#64748B;">Terbit</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:18px;font-weight:700;color:#F59E0B;"><?= number_format($berita_draft) ?></div>
                    <div style="font-size:10px;color:#64748B;">Draft</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;padding-top:10px;border-top:1px solid rgba(255,255,255,0.06);">
                <i class="fas fa-eye" style="color:#60A5FA;font-size:12px;"></i>
                <span style="font-size:11.5px;color:#94A3B8;"><?= number_format($total_views) ?> total tampilan</span>
            </div>
        </div>

    </nav>

    <!-- Footer User -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">A</div>
            <div class="user-info">
                <div class="user-name">Administrator</div>
                <div class="user-role">Super Admin</div>
            </div>
            <i class="fas fa-ellipsis-v" style="color:#475569;font-size:13px;"></i>
        </div>
    </div>
</aside>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
        <button class="topbar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-breadcrumb">
            <div class="breadcrumb-title"><?= htmlspecialchars($page_title) ?></div>
            <div class="breadcrumb-path">
                <i class="fas fa-house" style="font-size:10px;"></i>
                Admin
                <i class="fas fa-chevron-right" style="font-size:9px;color:#CBD5E1;"></i>
                <span><?= htmlspecialchars($page_title) ?></span>
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

    <!-- Page Content Start -->
    <main class="page-content">
