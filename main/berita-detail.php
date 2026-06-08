<?php
require_once __DIR__ . '/../config.php';

// Ambil ID dari URL
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: berita.php');
    exit;
}

// Ambil data berita
$res = $conn->query("
    SELECT * FROM berita
    WHERE id = $id AND status = 'Terbit'
    LIMIT 1
");

if (!$res || $res->num_rows === 0) {
    header('Location: berita.php');
    exit;
}

$b = $res->fetch_assoc();

// Tambah jumlah view
$conn->query("UPDATE berita SET dilihat = dilihat + 1 WHERE id = $id");

// ---- Helper metadata kategori ----
$kat_meta = [
    'Akademik'      => ['color'=>'#2563EB','bg'=>'#EFF6FF','icon'=>'graduation-cap'],
    'Penelitian'    => ['color'=>'#7C3AED','bg'=>'#F5F3FF','icon'=>'flask'],
    'Kemahasiswaan' => ['color'=>'#0EA5E9','bg'=>'#F0F9FF','icon'=>'users'],
    'Pengumuman'    => ['color'=>'#F59E0B','bg'=>'#FFFBEB','icon'=>'bullhorn'],
    'Event'         => ['color'=>'#10B981','bg'=>'#ECFDF5','icon'=>'calendar-star'],
    'Prestasi'      => ['color'=>'#EF4444','bg'=>'#FFF1F2','icon'=>'trophy'],
    'Umum'          => ['color'=>'#64748B','bg'=>'#F8FAFC','icon'=>'newspaper'],
];

$m = $kat_meta[$b['kategori']] ?? ['color'=>'#64748B','bg'=>'#F8FAFC','icon'=>'tag'];

// Thumbnail
$thumb = null;
if ($b['thumbnail'] && file_exists(__DIR__ . '/../berita/uploads/' . $b['thumbnail'])) {
    $thumb = '../berita/uploads/' . htmlspecialchars($b['thumbnail']);
}

// ---- Berita terkait (kategori sama, bukan diri sendiri) ----
$kat_esc = $conn->real_escape_string($b['kategori']);
$terkait = [];
$rt = $conn->query("
    SELECT id, judul, thumbnail, created_at, penulis, dilihat
    FROM berita
    WHERE status = 'Terbit'
      AND kategori = '{$kat_esc}'
      AND id <> {$id}
    ORDER BY created_at DESC
    LIMIT 3
");
if ($rt) {
    while ($r = $rt->fetch_assoc()) $terkait[] = $r;
}

// ---- Berita populer sidebar ----
$populer = [];
$rp = $conn->query("
    SELECT id, judul, dilihat, kategori, created_at
    FROM berita
    WHERE status='Terbit'
    ORDER BY dilihat DESC
    LIMIT 5
");
if ($rp) {
    while ($r = $rp->fetch_assoc()) $populer[] = $r;
}

function thumbUrl($thumb) {
    if ($thumb && file_exists(__DIR__ . '/../berita/uploads/' . $thumb)) {
        return '../berita/uploads/' . htmlspecialchars($thumb);
    }
    return null;
}

function katMeta($kat, $kat_meta) {
    return $kat_meta[$kat] ?? ['color'=>'#64748B','bg'=>'#F8FAFC','icon'=>'tag'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($b['judul']) ?> — Berita Fakultas</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="berita.css">
<style>
/* ===== DETAIL PAGE STYLES ===== */
.detail-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 90px 20px 60px;
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 36px;
    align-items: start;
}

@media (max-width: 900px) {
    .detail-wrapper {
        grid-template-columns: 1fr;
    }
}

/* Breadcrumb */
.breadcrumb-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--light, #94a3b8);
    margin-bottom: 22px;
    flex-wrap: wrap;
}
.breadcrumb-bar a {
    color: var(--primary, #2563EB);
    text-decoration: none;
    font-weight: 500;
}
.breadcrumb-bar a:hover { text-decoration: underline; }
.breadcrumb-bar .sep { color: #cbd5e1; }
.breadcrumb-bar .current { color: #475569; font-weight: 500; }

/* Article card */
.article-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.07);
    overflow: hidden;
}

/* Hero image */
.article-hero {
    width: 100%;
    max-height: 460px;
    object-fit: cover;
    display: block;
}
.article-hero-placeholder {
    width: 100%;
    height: 280px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    color: #cbd5e1;
    background: #f8fafc;
}

/* Article body */
.article-inner {
    padding: 36px 40px 40px;
}
@media (max-width: 600px) {
    .article-inner { padding: 24px 20px 28px; }
}

/* Kategori badge */
.article-kat-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.4px;
    margin-bottom: 16px;
}

/* Title */
.article-title {
    font-family: 'Poppins', sans-serif;
    font-size: clamp(22px, 3.5vw, 32px);
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
    margin: 0 0 20px;
}

/* Meta bar */
.article-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 18px;
    padding: 16px 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 32px;
}
.article-meta-item {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}
.article-meta-item i { font-size: 13px; color: #94a3b8; }
.article-author-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563EB, #7C3AED);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
}

/* Content */
.article-content {
    font-family: 'Poppins', sans-serif;
    font-size: 15.5px;
    line-height: 1.9;
    color: #334155;
}
.article-content p { margin-bottom: 1.2em; }
.article-content h2, .article-content h3 {
    font-weight: 700;
    color: #0f172a;
    margin: 1.6em 0 0.6em;
}
.article-content h2 { font-size: 20px; }
.article-content h3 { font-size: 17px; }
.article-content img {
    max-width: 100%;
    border-radius: 12px;
    margin: 12px 0;
}
.article-content a {
    color: #2563EB;
    text-decoration: underline;
}
.article-content ul, .article-content ol {
    padding-left: 24px;
    margin-bottom: 1.2em;
}
.article-content blockquote {
    border-left: 4px solid #2563EB;
    padding: 10px 20px;
    margin: 20px 0;
    background: #EFF6FF;
    border-radius: 0 8px 8px 0;
    color: #1e40af;
    font-style: italic;
}

/* Share bar */
.share-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 36px;
    padding-top: 24px;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.share-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    margin-right: 4px;
}
.share-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: opacity .2s, transform .15s;
    color: #fff;
}
.share-btn:hover { opacity: .85; transform: translateY(-1px); color: #fff; }
.share-btn.wa  { background: #25D366; }
.share-btn.fb  { background: #1877F2; }
.share-btn.tw  { background: #1DA1F2; }
.share-btn.copy { background: #64748b; cursor: pointer; }

/* Back button */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding: 9px 20px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: background .2s, color .2s;
}
.back-btn:hover {
    background: #2563EB;
    color: #fff;
}

/* ---- Related News ---- */
.related-section {
    margin-top: 48px;
    padding-top: 32px;
    border-top: 2px solid #f1f5f9;
}
.related-title {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.related-title::before {
    content: '';
    display: block;
    width: 4px;
    height: 22px;
    background: #2563EB;
    border-radius: 2px;
}
.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 18px;
}
.related-card {
    text-decoration: none;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    transition: transform .2s, box-shadow .2s;
    display: flex;
    flex-direction: column;
}
.related-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 28px rgba(37,99,235,0.12);
}
.related-thumb {
    width: 100%;
    height: 140px;
    object-fit: cover;
}
.related-thumb-ph {
    width: 100%;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    font-size: 32px;
    color: #cbd5e1;
}
.related-body {
    padding: 14px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.related-card-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.related-card-meta {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: auto;
}

/* ===== SIDEBAR (reuse from berita.css pattern) ===== */
.detail-sidebar .sidebar-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    overflow: hidden;
    margin-bottom: 24px;
}
.detail-sidebar .sidebar-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 20px;
    font-weight: 700;
    font-size: 14px;
    color: #0f172a;
    border-bottom: 1px solid #f1f5f9;
    background: #fafbff;
}
.detail-sidebar .sidebar-header-icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: linear-gradient(135deg,#2563EB,#7C3AED);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
}
.populer-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 20px;
    text-decoration: none;
    border-bottom: 1px solid #f8fafc;
    transition: background .15s;
}
.populer-item:hover { background: #f8faff; }
.populer-rank {
    min-width: 28px; height: 28px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800;
    background: #e2e8f0; color: #64748b;
}
.populer-rank.rank-1 { background: #FEF3C7; color: #D97706; }
.populer-rank.rank-2 { background: #F1F5F9; color: #64748B; }
.populer-rank.rank-3 { background: #FEE2E2; color: #DC2626; }
.populer-title {
    font-size: 12.5px; font-weight: 600; color: #1e293b; line-height: 1.4;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.populer-views { font-size: 11px; color: #94a3b8; }

/* Toast notification */
.toast-copy {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: #0f172a;
    color: #fff;
    padding: 10px 22px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    opacity: 0;
    transition: opacity .3s, transform .3s;
    z-index: 9999;
    pointer-events: none;
}
.toast-copy.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
</style>
</head>
<body>

<!-- ===== NAVBAR (sama dengan berita.php) ===== -->
<nav id="navbar" class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="assets/upitra_logo.png" alt="Logo" style="height: 40px">
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
            <ul class="navbar-nav gap-lg-4 text-center">
                <li class="nav-item"><a class="nav-link fw-semibold" href="index.html">Home</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold" href="profile.html">Profile</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-semibold" href="#" role="button" data-bs-toggle="dropdown">Prodi</a>
                    <ul class="dropdown-menu text-start">
                        <li><a class="dropdown-item" href="sistem_informasi.html">S1 Sistem Informasi</a></li>
                        <li><a class="dropdown-item" href="informatika.html">S1 Informatika</a></li>
                        <li><a class="dropdown-item" href="software_engineering.html">S1 Software Engineering</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link fw-semibold active" href="berita.php">Berita</a></li>
                <li class="nav-item d-lg-none mt-3">
                    <a href="https://wa.me/628112907500" target="_blank" class="btn btn-success w-100 rounded-pill">
                        <i class="bi bi-whatsapp me-2"></i> WhatsApp
                    </a>
                </li>
            </ul>
        </div>
        <div class="d-none d-lg-block">
            <a href="https://wa.me/628112907500" target="_blank"
               class="btn btn-success rounded-circle d-flex align-items-center justify-content-center"
               style="width:45px;height:45px">
                <i class="bi bi-whatsapp"></i>
            </a>
        </div>
    </div>
</nav>

<!-- ===== MAIN WRAPPER ===== -->
<div class="detail-wrapper">

    <!-- ===== KONTEN UTAMA ===== -->
    <main>

        <!-- Tombol Kembali -->
        <a href="berita.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Berita
        </a>

        <!-- Breadcrumb -->
        <div class="breadcrumb-bar">
            <a href="index.html"><i class="fas fa-home"></i> Home</a>
            <span class="sep">/</span>
            <a href="berita.php">Berita</a>
            <span class="sep">/</span>
            <a href="berita.php?kategori=<?= urlencode($b['kategori']) ?>"><?= htmlspecialchars($b['kategori']) ?></a>
            <span class="sep">/</span>
            <span class="current"><?= htmlspecialchars(mb_strimwidth($b['judul'], 0, 50, '…')) ?></span>
        </div>

        <!-- Artikel Card -->
        <article class="article-card">

            <!-- Hero Image -->
            <?php if ($thumb): ?>
                <img src="<?= $thumb ?>" class="article-hero" alt="<?= htmlspecialchars($b['judul']) ?>">
            <?php else: ?>
                <div class="article-hero-placeholder">
                    <i class="fas fa-<?= $m['icon'] ?>"></i>
                </div>
            <?php endif; ?>

            <div class="article-inner">

                <!-- Kategori Badge -->
                <span class="article-kat-badge"
                      style="background:<?= $m['bg'] ?>;color:<?= $m['color'] ?>;">
                    <i class="fas fa-<?= $m['icon'] ?>" style="font-size:11px;"></i>
                    <?= htmlspecialchars($b['kategori']) ?>
                </span>

                <!-- Judul -->
                <h1 class="article-title"><?= htmlspecialchars($b['judul']) ?></h1>

                <!-- Meta -->
                <div class="article-meta">
                    <div class="article-meta-item">
                        <div class="article-author-avatar">
                            <?= strtoupper(mb_substr($b['penulis'], 0, 1)) ?>
                        </div>
                        <?= htmlspecialchars($b['penulis']) ?>
                    </div>
                    <div class="article-meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <?= date('d F Y', strtotime($b['created_at'])) ?>
                    </div>
                    <?php if (!empty($b['updated_at']) && $b['updated_at'] !== $b['created_at']): ?>
                    <div class="article-meta-item">
                        <i class="fas fa-clock"></i>
                        Diperbarui <?= date('d F Y', strtotime($b['updated_at'])) ?>
                    </div>
                    <?php endif; ?>
                    <div class="article-meta-item">
                        <i class="fas fa-eye"></i>
                        <?= number_format($b['dilihat']) ?> tayangan
                    </div>
                </div>

                <!-- Konten -->
                <div class="article-content">
                    <?= $b['konten'] /* konten sudah HTML dari editor */ ?>
                </div>

                <!-- Share Bar -->
                <?php
                $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
                    . '://' . $_SERVER['HTTP_HOST']
                    . $_SERVER['REQUEST_URI'];
                $share_title = urlencode($b['judul']);
                $share_url   = urlencode($current_url);
                ?>
                <div class="share-bar">
                    <span class="share-label"><i class="fas fa-share-alt"></i> Bagikan:</span>
                    <a href="https://wa.me/?text=<?= $share_title ?>%20<?= $share_url ?>"
                       target="_blank" class="share-btn wa">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $share_url ?>"
                       target="_blank" class="share-btn fb">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?= $share_title ?>&url=<?= $share_url ?>"
                       target="_blank" class="share-btn tw">
                        <i class="fab fa-twitter"></i> Twitter
                    </a>
                    <button onclick="copyLink()" class="share-btn copy" style="border:none;">
                        <i class="fas fa-link"></i> Salin Link
                    </button>
                </div>

            </div><!-- /.article-inner -->
        </article>

        <!-- ===== BERITA TERKAIT ===== -->
        <?php if (!empty($terkait)): ?>
        <section class="related-section">
            <div class="related-title">Berita Terkait</div>
            <div class="related-grid">
                <?php foreach ($terkait as $t):
                    $tm = katMeta($t['kategori'], $kat_meta);
                    $tt = thumbUrl($t['thumbnail']);
                ?>
                <a href="berita-detail.php?id=<?= $t['id'] ?>" class="related-card">
                    <?php if ($tt): ?>
                        <img src="<?= $tt ?>" class="related-thumb" alt="<?= htmlspecialchars($t['judul']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="related-thumb-ph">
                            <i class="fas fa-<?= $tm['icon'] ?>"></i>
                        </div>
                    <?php endif; ?>
                    <div class="related-body">
                        <div class="related-card-title"><?= htmlspecialchars($t['judul']) ?></div>
                        <div class="related-card-meta">
                            <i class="fas fa-calendar" style="font-size:10px;"></i>
                            <?= date('d M Y', strtotime($t['created_at'])) ?>
                            &nbsp;·&nbsp;
                            <i class="fas fa-eye" style="font-size:10px;"></i>
                            <?= number_format($t['dilihat']) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </main>

    <!-- ===== SIDEBAR ===== -->
    <aside class="detail-sidebar">

        <!-- Paling Banyak Dibaca -->
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
            <div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px;">Belum ada data</div>
            <?php endif; ?>
        </div>

        <!-- Kategori Singkat -->
        <div class="sidebar-card">
            <div class="sidebar-header">
                <div class="sidebar-header-icon"><i class="fas fa-tags"></i></div>
                Kategori Lainnya
            </div>
            <div style="padding:12px 16px;display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($kat_meta as $k => $km): ?>
                <a href="berita.php?kategori=<?= urlencode($k) ?>"
                   style="display:inline-flex;align-items:center;gap:5px;
                          padding:6px 13px;border-radius:50px;
                          background:<?= $km['bg'] ?>;color:<?= $km['color'] ?>;
                          font-size:12px;font-weight:600;text-decoration:none;
                          transition:opacity .2s;"
                   onmouseover="this.style.opacity='.75'"
                   onmouseout="this.style.opacity='1'">
                    <i class="fas fa-<?= $km['icon'] ?>" style="font-size:11px;"></i>
                    <?= $k ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

    </aside>

</div><!-- /.detail-wrapper -->

<!-- Toast notif copy link -->
<div class="toast-copy" id="toastCopy">
    <i class="fas fa-check-circle"></i> Link berhasil disalin!
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Salin link ke clipboard
function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        const t = document.getElementById('toastCopy');
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2500);
    });
}

// Navbar scroll effect (sama dengan berita.php)
window.addEventListener('scroll', () => {
    const nb = document.getElementById('navbar');
    if (nb) {
        nb.style.background = window.scrollY > 50
            ? 'rgba(15,23,42,0.97)'
            : '';
    }
});
</script>
</body>
</html>