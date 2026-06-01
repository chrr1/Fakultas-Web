<?php
/**
 * berita.php — Halaman Publik Daftar Berita Fakultas
 * Letakkan file ini satu folder dengan admin-fakultas/
 * atau sesuaikan path require_once-nya.
 */
require_once __DIR__ . '/config.php';

// ── Filter & Pagination ──────────────────────────────
$filter_kat = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$search     = isset($_GET['q'])        ? trim($_GET['q'])        : '';
$per_page   = 9;
$page       = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset     = ($page - 1) * $per_page;

$kat_list = ['Akademik','Penelitian','Kemahasiswaan','Pengumuman','Event','Prestasi','Umum'];

$where = ["status = 'Terbit'"];
if ($filter_kat && in_array($filter_kat, $kat_list))
    $where[] = "kategori = '" . $conn->real_escape_string($filter_kat) . "'";
if ($search)
    $where[] = "(judul LIKE '%" . $conn->real_escape_string($search) . "%' OR konten LIKE '%" . $conn->real_escape_string($search) . "%' OR penulis LIKE '%" . $conn->real_escape_string($search) . "%')";
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Total & query
$total_res  = $conn->query("SELECT COUNT(*) as t FROM berita $where_sql");
$total_rows = $total_res ? $total_res->fetch_assoc()['t'] : 0;
$total_pages = max(1, ceil($total_rows / $per_page));

$berita_list = [];
$res = $conn->query("SELECT * FROM berita $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
if ($res) while ($row = $res->fetch_assoc()) $berita_list[] = $row;

// Berita Utama (featured) — hanya halaman 1, tanpa filter
$featured = null;
if ($page === 1 && !$filter_kat && !$search) {
    $rf = $conn->query("SELECT * FROM berita WHERE status='Terbit' ORDER BY dilihat DESC LIMIT 1");
    if ($rf) $featured = $rf->fetch_assoc();
    // Hapus featured dari list agar tidak dobel
    if ($featured) $berita_list = array_filter($berita_list, fn($b) => $b['id'] !== $featured['id']);
}

// Kategori dengan jumlah berita
$kat_counts = [];
$rc = $conn->query("SELECT kategori, COUNT(*) as c FROM berita WHERE status='Terbit' GROUP BY kategori");
if ($rc) while ($r = $rc->fetch_assoc()) $kat_counts[$r['kategori']] = $r['c'];

// Berita terpopuler untuk sidebar
$populer = [];
$rp = $conn->query("SELECT id, judul, dilihat, kategori, created_at FROM berita WHERE status='Terbit' ORDER BY dilihat DESC LIMIT 5");
if ($rp) while ($r = $rp->fetch_assoc()) $populer[] = $r;

// Helper URL
function buildUrl($params) {
    $c = $_GET;
    foreach ($params as $k => $v) { if ($v === null || $v === '') unset($c[$k]); else $c[$k] = $v; }
    unset($c['page']);
    $q = http_build_query($c);
    return 'berita.php' . ($q ? '?' . $q : '');
}

// Warna & ikon kategori
$kat_meta = [
    'Akademik'      => ['color'=>'#2563EB','bg'=>'#EFF6FF','icon'=>'graduation-cap'],
    'Penelitian'    => ['color'=>'#7C3AED','bg'=>'#F5F3FF','icon'=>'flask'],
    'Kemahasiswaan' => ['color'=>'#0EA5E9','bg'=>'#F0F9FF','icon'=>'users'],
    'Pengumuman'    => ['color'=>'#F59E0B','bg'=>'#FFFBEB','icon'=>'bullhorn'],
    'Event'         => ['color'=>'#10B981','bg'=>'#ECFDF5','icon'=>'calendar-star'],
    'Prestasi'      => ['color'=>'#EF4444','bg'=>'#FFF1F2','icon'=>'trophy'],
    'Umum'          => ['color'=>'#64748B','bg'=>'#F8FAFC','icon'=>'newspaper'],
];

function katMeta($kat, $kat_meta) {
    return $kat_meta[$kat] ?? ['color'=>'#64748B','bg'=>'#F8FAFC','icon'=>'tag'];
}

function excerptKonten($text, $len = 120) {
    $text = strip_tags($text);
    return mb_strlen($text) > $len ? mb_strimwidth($text, 0, $len, '…') : $text;
}

function thumbUrl($thumb) {
    if ($thumb && file_exists(__DIR__ . '/berita/uploads/' . $thumb))
        return 'berita/uploads/' . htmlspecialchars($thumb);

    return null;
}

function detailUrl($id) {
    return 'berita-detail.php?id=' . $id;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $filter_kat ? $filter_kat . ' — ' : '' ?>Berita Fakultas<?= $search ? ' · "'.htmlspecialchars($search).'"' : '' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ═══════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --blue:       #1D4ED8;
    --blue-mid:   #2563EB;
    --blue-light: #3B82F6;
    --blue-soft:  #EFF6FF;
    --blue-muted: #DBEAFE;
    --ink:        #0F172A;
    --ink-mid:    #1E293B;
    --body:       #F1F5F9;
    --white:      #FFFFFF;
    --border:     #E2E8F0;
    --muted:      #64748B;
    --light:      #94A3B8;
    --radius:     14px;
    --radius-sm:  9px;
    --shadow:     0 2px 12px rgba(15,23,42,0.07);
    --shadow-md:  0 8px 32px rgba(15,23,42,0.10);
    --shadow-lg:  0 20px 60px rgba(15,23,42,0.13);
    --nav-h:      68px;
    --transition: 0.25s cubic-bezier(0.4,0,0.2,1);
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--body);
    color: var(--ink);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}

a { text-decoration: none; color: inherit; }
img { display: block; max-width: 100%; }
button { font-family: 'Poppins', sans-serif; }

/* ═══════════════════════════════════════════
   NAVBAR
═══════════════════════════════════════════ */
.navbar {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    height: var(--nav-h);
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 0 var(--border), var(--shadow);
}

.nav-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    height: 100%;
    display: flex;
    align-items: center;
    gap: 24px;
}

.nav-brand {
    display: flex;
    align-items: center;
    gap: 11px;
    flex-shrink: 0;
}

.nav-logo {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, var(--blue), #0EA5E9);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px;
}

.nav-brand-text {
    line-height: 1.2;
}

.nav-brand-name {
    font-size: 15px;
    font-weight: 800;
    color: var(--ink);
    letter-spacing: -0.3px;
}

.nav-brand-sub {
    font-size: 11px;
    color: var(--muted);
    font-weight: 400;
}

.nav-sep { width: 1px; height: 30px; background: var(--border); }

.nav-links {
    display: flex;
    align-items: center;
    gap: 4px;
    flex: 1;
}

.nav-link {
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--muted);
    transition: all var(--transition);
}

.nav-link:hover { background: var(--blue-soft); color: var(--blue-mid); }
.nav-link.active { background: var(--blue-soft); color: var(--blue-mid); font-weight: 600; }

.nav-search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--body);
    border: 1.5px solid var(--border);
    border-radius: 40px;
    padding: 7px 14px;
    transition: border-color var(--transition), box-shadow var(--transition);
}

.nav-search:focus-within {
    border-color: var(--blue-light);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    background: var(--white);
}

.nav-search i { color: var(--light); font-size: 13px; }
.nav-search input {
    border: none; background: none; outline: none;
    font-family: 'Poppins', sans-serif;
    font-size: 13px; color: var(--ink);
    width: 180px;
}

.nav-search input::placeholder { color: var(--light); }

.nav-admin-btn {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 16px;
    background: var(--blue-mid);
    color: #fff;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: background var(--transition), transform var(--transition);
    flex-shrink: 0;
}

.nav-admin-btn:hover { background: var(--blue); transform: translateY(-1px); }

/* ═══════════════════════════════════════════
   HERO BANNER
═══════════════════════════════════════════ */
.hero {
    background: linear-gradient(135deg, var(--ink) 0%, var(--ink-mid) 40%, #1E3A5F 100%);
    padding: 52px 24px;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 80% at 80% 50%, rgba(37,99,235,0.18) 0%, transparent 60%),
        radial-gradient(ellipse 40% 60% at 20% 80%, rgba(14,165,233,0.12) 0%, transparent 55%);
}

/* Dot grid texture */
.hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
    background-size: 28px 28px;
    pointer-events: none;
}

.hero-inner {
    max-width: 1280px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.hero-label {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 40px;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255,255,255,0.85);
    margin-bottom: 16px;
    backdrop-filter: blur(8px);
}

.hero-label span { width: 6px; height: 6px; background: #34D399; border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(1.3)} }

.hero-title {
    font-size: clamp(26px, 4vw, 40px);
    font-weight: 900;
    color: #fff;
    line-height: 1.18;
    letter-spacing: -0.8px;
    margin-bottom: 12px;
}

.hero-title span {
    background: linear-gradient(90deg, #60A5FA, #34D399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-desc {
    font-size: 14.5px;
    color: rgba(255,255,255,0.6);
    max-width: 520px;
    line-height: 1.7;
}

/* ═══════════════════════════════════════════
   KATEGORI FILTER PILLS
═══════════════════════════════════════════ */
.kat-bar {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    box-shadow: var(--shadow);
    position: sticky;
    top: var(--nav-h);
    z-index: 80;
}

.kat-bar-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    scrollbar-width: none;
    height: 54px;
}

.kat-bar-inner::-webkit-scrollbar { display: none; }

.kat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 40px;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    border: 1.5px solid transparent;
    transition: all var(--transition);
    text-decoration: none;
}

.kat-pill.all {
    background: var(--body);
    color: var(--muted);
    border-color: var(--border);
}

.kat-pill.all:hover, .kat-pill.all.active {
    background: var(--ink);
    color: #fff;
    border-color: var(--ink);
}

.kat-pill-count {
    font-size: 10.5px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 20px;
    background: rgba(0,0,0,0.1);
    line-height: 1.6;
}

/* ═══════════════════════════════════════════
   MAIN LAYOUT
═══════════════════════════════════════════ */
.main-layout {
    max-width: 1280px;
    margin: 0 auto;
    padding: 36px 24px;
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 28px;
    align-items: start;
}

/* ═══════════════════════════════════════════
   FEATURED CARD
═══════════════════════════════════════════ */
.featured-card {
    position: relative;
    border-radius: 18px;
    overflow: hidden;
    background: var(--ink);
    margin-bottom: 28px;
    cursor: pointer;
    box-shadow: var(--shadow-lg);
    transition: transform var(--transition), box-shadow var(--transition);
}

.featured-card:hover { transform: translateY(-3px); box-shadow: 0 28px 70px rgba(15,23,42,0.18); }

.featured-img {
    width: 100%;
    aspect-ratio: 21/9;
    object-fit: cover;
    opacity: 0.55;
    transition: opacity var(--transition), transform 0.5s ease;
}

.featured-card:hover .featured-img { opacity: 0.65; transform: scale(1.02); }

.featured-placeholder {
    width: 100%; aspect-ratio: 21/9;
    background: linear-gradient(135deg, #1E3A5F 0%, #0F172A 100%);
    display: flex; align-items: center; justify-content: center;
}

.featured-placeholder i { font-size: 64px; color: rgba(255,255,255,0.1); }

.featured-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(15,23,42,0.96) 0%, rgba(15,23,42,0.3) 60%, transparent 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 32px;
}

.featured-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #EF4444;
    color: #fff;
    padding: 4px 12px;
    border-radius: 40px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    width: fit-content;
    text-transform: uppercase;
}

.featured-kat {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    backdrop-filter: blur(6px);
    padding: 4px 12px;
    border-radius: 40px;
    font-size: 11.5px;
    font-weight: 600;
    margin-bottom: 12px;
    width: fit-content;
}

.featured-title {
    font-size: clamp(18px, 2.5vw, 26px);
    font-weight: 800;
    color: #fff;
    line-height: 1.3;
    letter-spacing: -0.3px;
    margin-bottom: 10px;
}

.featured-meta {
    display: flex;
    gap: 16px;
    font-size: 12.5px;
    color: rgba(255,255,255,0.6);
    align-items: center;
    flex-wrap: wrap;
}

.featured-meta i { font-size: 11px; }

/* ═══════════════════════════════════════════
   SECTION HEADER
═══════════════════════════════════════════ */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    gap: 12px;
}

.section-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--ink);
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title-bar {
    width: 4px; height: 22px;
    background: linear-gradient(180deg, var(--blue-mid), #0EA5E9);
    border-radius: 4px;
}

.section-count {
    font-size: 12.5px;
    color: var(--muted);
    font-weight: 500;
}

/* ═══════════════════════════════════════════
   BERITA GRID & CARDS
═══════════════════════════════════════════ */
.berita-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.berita-card {
    background: var(--white);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
    display: flex;
    flex-direction: column;
    cursor: pointer;
}

.berita-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: #BFDBFE;
}

.card-thumb-wrap {
    position: relative;
    overflow: hidden;
    aspect-ratio: 16/9;
    background: var(--blue-soft);
    flex-shrink: 0;
}

.card-thumb {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.4,0,0.2,1);
}

.berita-card:hover .card-thumb { transform: scale(1.06); }

.card-thumb-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 36px;
    color: rgba(37,99,235,0.2);
}

.card-kat-badge {
    position: absolute;
    top: 10px; left: 10px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10.5px;
    font-weight: 700;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.25);
    letter-spacing: 0.2px;
}

.card-body {
    padding: 18px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.card-title {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--ink);
    line-height: 1.45;
    margin-bottom: 9px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color var(--transition);
}

.berita-card:hover .card-title { color: var(--blue-mid); }

.card-excerpt {
    font-size: 12.5px;
    color: var(--muted);
    line-height: 1.7;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 14px;
}

.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px solid #F1F5F9;
    gap: 8px;
}

.card-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.card-author {
    font-size: 12px;
    font-weight: 600;
    color: var(--ink);
    display: flex;
    align-items: center;
    gap: 5px;
}

.card-author-avatar {
    width: 20px; height: 20px;
    background: linear-gradient(135deg, var(--blue-mid), #0EA5E9);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 9px; font-weight: 700;
}

.card-date {
    font-size: 11px;
    color: var(--light);
    display: flex;
    align-items: center;
    gap: 4px;
}

.card-views {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    color: var(--light);
    font-weight: 500;
    flex-shrink: 0;
}

.card-read-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    background: var(--blue-soft);
    color: var(--blue-mid);
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 700;
    transition: all var(--transition);
    flex-shrink: 0;
}

.berita-card:hover .card-read-btn {
    background: var(--blue-mid);
    color: #fff;
}

/* ═══════════════════════════════════════════
   EMPTY STATE
═══════════════════════════════════════════ */
.empty-state {
    grid-column: 1/-1;
    text-align: center;
    padding: 72px 24px;
}

.empty-icon {
    font-size: 56px;
    color: #CBD5E1;
    margin-bottom: 18px;
}

.empty-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--muted);
    margin-bottom: 8px;
}

.empty-desc {
    font-size: 14px;
    color: var(--light);
    max-width: 380px;
    margin: 0 auto 20px;
    line-height: 1.7;
}

.empty-link {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 20px; background: var(--blue-mid); color: #fff;
    border-radius: 9px; font-size: 13.5px; font-weight: 600;
    transition: background var(--transition);
}

.empty-link:hover { background: var(--blue); }

/* ═══════════════════════════════════════════
   PAGINATION
═══════════════════════════════════════════ */
.pagination {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 36px;
}

.pag-btn {
    min-width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--muted);
    border: 1.5px solid var(--border);
    background: var(--white);
    transition: all var(--transition);
    text-decoration: none;
    padding: 0 10px;
}

.pag-btn:hover { border-color: var(--blue-light); color: var(--blue-mid); background: var(--blue-soft); }
.pag-btn.active { background: var(--blue-mid); color: #fff; border-color: var(--blue-mid); }
.pag-btn.disabled { opacity: 0.35; pointer-events: none; }

/* ═══════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════ */
.sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: sticky;
    top: calc(var(--nav-h) + 54px + 20px);
}

.sidebar-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.sidebar-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 14px;
    font-weight: 700;
    color: var(--ink);
}

.sidebar-header-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: var(--blue-soft);
    color: var(--blue-mid);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
}

/* Populer */
.populer-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 13px 20px;
    border-bottom: 1px solid #F8FAFC;
    transition: background var(--transition);
    cursor: pointer;
}

.populer-item:last-child { border-bottom: none; }
.populer-item:hover { background: #F8FAFC; }

.populer-rank {
    width: 26px; height: 26px;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800;
    flex-shrink: 0;
    margin-top: 1px;
}

.rank-1 { background: #FEF9C3; color: #854D0E; }
.rank-2 { background: #F1F5F9; color: #475569; }
.rank-3 { background: #FEF3C7; color: #92400E; }
.rank-n { background: #F8FAFC; color: #94A3B8; }

.populer-title {
    flex: 1;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--ink);
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color var(--transition);
}

.populer-item:hover .populer-title { color: var(--blue-mid); }

.populer-views {
    font-size: 11px;
    color: var(--light);
    display: flex;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
    margin-top: 3px;
}

/* Kategori Sidebar */
.kat-sidebar-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 20px;
    border-bottom: 1px solid #F8FAFC;
    cursor: pointer;
    transition: background var(--transition);
    text-decoration: none;
    color: inherit;
}

.kat-sidebar-item:last-child { border-bottom: none; }
.kat-sidebar-item:hover { background: var(--body); }
.kat-sidebar-item.active { background: var(--blue-soft); }

.kat-sidebar-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.kat-icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
}

.kat-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
}

.kat-sidebar-item.active .kat-name { color: var(--blue-mid); }

.kat-num {
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    background: var(--body);
    padding: 2px 8px;
    border-radius: 20px;
}

.kat-sidebar-item.active .kat-num { background: var(--blue-muted); color: var(--blue-mid); }

/* Search result info */
.search-info {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 12px 18px;
    margin-bottom: 18px;
    font-size: 13px;
    color: var(--muted);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.search-info strong { color: var(--ink); }
.search-clear { color: var(--blue-mid); font-weight: 600; font-size: 12.5px; display:flex;align-items:center;gap:4px; }

/* ═══════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════ */
.site-footer {
    background: var(--ink);
    color: rgba(255,255,255,0.5);
    text-align: center;
    padding: 28px 24px;
    font-size: 13px;
    margin-top: 60px;
}

.site-footer strong { color: rgba(255,255,255,0.8); }

/* ═══════════════════════════════════════════
   ANIMATIONS
═══════════════════════════════════════════ */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}

.berita-card {
    animation: fadeUp 0.4s ease both;
}

<?php for ($i = 1; $i <= 9; $i++): ?>
.berita-card:nth-child(<?= $i ?>) { animation-delay: <?= ($i - 1) * 0.06 ?>s; }
<?php endfor; ?>

.featured-card { animation: fadeUp 0.5s ease both; }

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 1100px) {
    .main-layout { grid-template-columns: 1fr; }
    .sidebar { position: static; display: grid; grid-template-columns: 1fr 1fr; }
}

@media (max-width: 768px) {
    .nav-links { display: none; }
    .nav-search input { width: 120px; }
    .hero { padding: 36px 16px; }
    .main-layout { padding: 20px 16px; }
    .kat-bar-inner { padding: 0 16px; }
    .berita-grid { grid-template-columns: 1fr; }
    .sidebar { grid-template-columns: 1fr; }
    .featured-overlay { padding: 20px; }
}

@media (max-width: 480px) {
    .nav-inner { padding: 0 14px; gap: 12px; }
    .nav-search { display: none; }
    .hero-title { font-size: 22px; }
}
</style>
</head>
<body>

<!-- ════════════════════ NAVBAR ════════════════════ -->
<nav class="navbar">
    <div class="nav-inner">
        <a href="berita.php" class="nav-brand">
            <div class="nav-logo"><i class="fas fa-university"></i></div>
            <div class="nav-brand-text">
                <div class="nav-brand-name">Fakultas Teknik</div>
                <div class="nav-brand-sub">Portal Berita Resmi</div>
            </div>
        </a>
        <div class="nav-sep"></div>
        <div class="nav-links">
            <a href="berita.php" class="nav-link active">Beranda</a>
            <a href="berita.php?kategori=Akademik" class="nav-link">Akademik</a>
            <a href="berita.php?kategori=Event" class="nav-link">Event</a>
            <a href="berita.php?kategori=Prestasi" class="nav-link">Prestasi</a>
        </div>
        <form method="get" action="berita.php" style="display:contents;">
            <?php if ($filter_kat): ?>
            <input type="hidden" name="kategori" value="<?= htmlspecialchars($filter_kat) ?>">
            <?php endif; ?>
            <div class="nav-search">
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Cari berita..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </form>
        <a href="admin-fakultas/" class="nav-admin-btn">
            <i class="fas fa-lock"></i> Admin
        </a>
    </div>
</nav>

<!-- ════════════════════ HERO ════════════════════ -->
<?php if (!$filter_kat && !$search): ?>
<section class="hero">
    <div class="hero-inner">
        <div class="hero-label">
            <span></span> Portal Berita Resmi Fakultas
        </div>
        <h1 class="hero-title">
            Berita & Informasi<br><span>Terkini Fakultas</span>
        </h1>
        <p class="hero-desc">
            Temukan berita terbaru seputar akademik, penelitian, kemahasiswaan, dan berbagai prestasi yang diraih civitas akademika.
        </p>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════ KATEGORI BAR ════════════════════ -->
<div class="kat-bar">
    <div class="kat-bar-inner">
        <a href="berita.php<?= $search ? '?q='.urlencode($search) : '' ?>"
           class="kat-pill all <?= !$filter_kat ? 'active' : '' ?>">
            <i class="fas fa-th-large" style="font-size:11px;"></i> Semua
            <span class="kat-pill-count"><?= array_sum($kat_counts) ?></span>
        </a>
        <?php foreach ($kat_list as $k):
            $m = katMeta($k, $kat_meta);
            $isActive = $filter_kat === $k;
            $cnt = $kat_counts[$k] ?? 0;
        ?>
        <a href="<?= buildUrl(['kategori' => $k]) ?>"
           class="kat-pill"
           style="
               background: <?= $isActive ? $m['color'] : $m['bg'] ?>;
               color: <?= $isActive ? '#fff' : $m['color'] ?>;
               border-color: <?= $isActive ? $m['color'] : 'transparent' ?>;
           ">
            <i class="fas fa-<?= $m['icon'] ?>" style="font-size:11px;"></i>
            <?= $k ?>
            <span class="kat-pill-count" style="background:rgba(<?= $isActive ? '255,255,255' : '0,0,0' ?>,0.15);"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ════════════════════ MAIN ════════════════════ -->
<div class="main-layout">
    <!-- Kolom Konten -->
    <div>

        <!-- Search Result Info -->
        <?php if ($search): ?>
        <div class="search-info">
            <span>Hasil pencarian untuk: <strong>"<?= htmlspecialchars($search) ?>"</strong> — <?= $total_rows ?> berita ditemukan</span>
            <a href="<?= buildUrl(['q' => null]) ?>" class="search-clear">
                <i class="fas fa-times" style="font-size:10px;"></i> Hapus
            </a>
        </div>
        <?php endif; ?>

        <!-- Featured -->
        <?php if ($featured): ?>
        <?php $fm = katMeta($featured['kategori'], $kat_meta); ?>
        <a href="berita-detail.php?id=<?= $featured['id'] ?>" class="featured-card">
            <?php $ft = thumbUrl($featured['thumbnail']); ?>
            <?php if ($ft): ?>
                <img src="<?= $ft ?>" class="featured-img" alt="<?= htmlspecialchars($featured['judul']) ?>">
            <?php else: ?>
                <div class="featured-placeholder"><i class="fas fa-newspaper"></i></div>
            <?php endif; ?>
            <div class="featured-overlay">
                <span class="featured-badge"><i class="fas fa-fire"></i> Berita Populer</span>
                <span class="featured-kat">
                    <i class="fas fa-<?= $fm['icon'] ?>" style="font-size:10px;"></i>
                    <?= htmlspecialchars($featured['kategori']) ?>
                </span>
                <h2 class="featured-title"><?= htmlspecialchars($featured['judul']) ?></h2>
                <div class="featured-meta">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($featured['penulis']) ?></span>
                    <span><i class="fas fa-calendar"></i> <?= date('d F Y', strtotime($featured['created_at'])) ?></span>
                    <span><i class="fas fa-eye"></i> <?= number_format($featured['dilihat']) ?> tayangan</span>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <!-- Grid Berita -->
        <div class="section-header">
            <div class="section-title">
                <div class="section-title-bar"></div>
                <?= $filter_kat ? htmlspecialchars($filter_kat) : ($search ? 'Hasil Pencarian' : 'Berita Terbaru') ?>
            </div>
            <span class="section-count"><?= $total_rows ?> berita</span>
        </div>

        <div class="berita-grid">
            <?php if (empty($berita_list)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-newspaper"></i></div>
                <div class="empty-title">
                    <?= $search ? 'Tidak ada hasil untuk "'.htmlspecialchars($search).'"' : 'Belum ada berita' ?>
                </div>
                <p class="empty-desc">
                    <?= $search ? 'Coba gunakan kata kunci yang berbeda atau hapus filter yang aktif.' : 'Belum ada berita yang diterbitkan pada kategori ini.' ?>
                </p>
                <a href="berita.php" class="empty-link">
                    <i class="fas fa-home"></i> Lihat Semua Berita
                </a>
            </div>

            <?php else: foreach ($berita_list as $b):
                $m = katMeta($b['kategori'], $kat_meta);
                $thumb = thumbUrl($b['thumbnail']);
                $initial = strtoupper(mb_substr($b['penulis'], 0, 1));
            ?>
            <a href="berita-detail.php?id=<?= $b['id'] ?>" class="berita-card">
                <!-- Thumbnail -->
                <div class="card-thumb-wrap">
                    <?php if ($thumb): ?>
                        <img src="<?= $thumb ?>" class="card-thumb" alt="<?= htmlspecialchars($b['judul']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-thumb-placeholder">
                            <i class="fas fa-<?= $m['icon'] ?>"></i>
                        </div>
                    <?php endif; ?>
                    <span class="card-kat-badge"
                        style="background:<?= $m['color'] ?>;color:#fff;">
                        <?= htmlspecialchars($b['kategori']) ?>
                    </span>
                </div>

                <!-- Body -->
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($b['judul']) ?></h3>
                    <p class="card-excerpt"><?= excerptKonten($b['konten']) ?></p>

                    <div class="card-footer">
                        <div class="card-meta">
                            <div class="card-author">
                                <div class="card-author-avatar"><?= $initial ?></div>
                                <?= htmlspecialchars(mb_strimwidth($b['penulis'], 0, 20, '…')) ?>
                            </div>
                            <div class="card-date">
                                <i class="fas fa-clock" style="font-size:10px;"></i>
                                <?= date('d M Y', strtotime($b['created_at'])) ?>
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;">
                            <div class="card-views">
                                <i class="fas fa-eye" style="font-size:10px;"></i>
                                <?= number_format($b['dilihat']) ?>
                            </div>
                            <span class="card-read-btn">
                                Baca <i class="fas fa-arrow-right" style="font-size:10px;"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <a href="<?= buildUrl(['page'=>max(1,$page-1)]) ?>" class="pag-btn <?= $page<=1?'disabled':'' ?>">
                <i class="fas fa-chevron-left" style="font-size:11px;"></i>
            </a>
            <?php for ($i=1; $i<=$total_pages; $i++):
                if ($i==1 || $i==$total_pages || abs($i-$page)<=2): ?>
                <a href="<?= buildUrl(['page'=>$i]) ?>" class="pag-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                <?php elseif (abs($i-$page)==3): ?>
                <span class="pag-btn" style="border:none;background:none;pointer-events:none;">…</span>
                <?php endif;
            endfor; ?>
            <a href="<?= buildUrl(['page'=>min($total_pages,$page+1)]) ?>" class="pag-btn <?= $page>=$total_pages?'disabled':'' ?>">
                <i class="fas fa-chevron-right" style="font-size:11px;"></i>
            </a>
        </div>
        <?php endif; ?>

    </div><!-- end konten -->

    <!-- ════ SIDEBAR ════ -->
    <aside class="sidebar">

        <!-- Terpopuler -->
        <div class="sidebar-card">
            <div class="sidebar-header">
                <div class="sidebar-header-icon"><i class="fas fa-fire"></i></div>
                Paling Banyak Dibaca
            </div>
            <?php foreach ($populer as $i => $p): ?>
            <a href="berita-detail.php?id=<?= $p['id'] ?>" class="populer-item">
                <div class="populer-rank rank-<?= $i < 3 ? $i+1 : 'n' ?>"><?= $i+1 ?></div>
                <div style="flex:1;min-width:0;">
                    <div class="populer-title"><?= htmlspecialchars($p['judul']) ?></div>
                    <div class="populer-views" style="margin-top:4px;">
                        <i class="fas fa-eye" style="font-size:10px;"></i>
                        <?= number_format($p['dilihat']) ?> tayangan
                        &nbsp;·&nbsp; <?= date('d M', strtotime($p['created_at'])) ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php if (empty($populer)): ?>
            <div style="padding:20px;text-align:center;color:var(--light);font-size:13px;">Belum ada data</div>
            <?php endif; ?>
        </div>

        <!-- Kategori -->
        <div class="sidebar-card">
            <div class="sidebar-header">
                <div class="sidebar-header-icon"><i class="fas fa-tags"></i></div>
                Jelajah Kategori
            </div>
            <?php foreach ($kat_list as $k):
                $m = katMeta($k, $kat_meta);
                $cnt = $kat_counts[$k] ?? 0;
                if ($cnt === 0) continue;
            ?>
            <a href="<?= buildUrl(['kategori'=>$k,'page'=>null]) ?>"
               class="kat-sidebar-item <?= $filter_kat===$k?'active':'' ?>">
                <div class="kat-sidebar-left">
                    <div class="kat-icon" style="background:<?= $m['bg'] ?>;color:<?= $m['color'] ?>;">
                        <i class="fas fa-<?= $m['icon'] ?>"></i>
                    </div>
                    <span class="kat-name"><?= $k ?></span>
                </div>
                <span class="kat-num"><?= $cnt ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Widget Info -->
        <div class="sidebar-card" style="background:linear-gradient(135deg,#1D4ED8,#0EA5E9);border:none;">
            <div style="padding:22px;">
                <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,0.65);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Tentang Portal</div>
                <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:8px;line-height:1.4;">Portal Berita Resmi Fakultas Teknik</div>
                <p style="font-size:12.5px;color:rgba(255,255,255,0.7);line-height:1.7;margin-bottom:16px;">
                    Sumber informasi terpercaya seputar kegiatan akademik, penelitian, dan prestasi civitas akademika.
                </p>
                <a href="admin-fakultas/" style="display:flex;align-items:center;justify-content:center;gap:7px;background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.3);color:#fff;padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;backdrop-filter:blur(6px);transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.28)'" onmouseout="this.style.background='rgba(255,255,255,0.18)'">
                    <i class="fas fa-pen-to-square"></i> Panel Admin
                </a>
            </div>
        </div>

    </aside>
</div>

<!-- ════════════════════ FOOTER ════════════════════ -->
<footer class="site-footer">
    <strong>Fakultas Teknik</strong> &mdash; Portal Berita Resmi &copy; <?= date('Y') ?>
    &nbsp;·&nbsp; Dikelola oleh Tim Humas Fakultas
</footer>

</body>
</html>