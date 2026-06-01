<?php
$page_title = 'Dashboard';
$current_page = 'home';
require_once 'config.php';
require_once 'dashboard.php';

// Statistik untuk cards
$stats = [];
$r = $conn->query("SELECT COUNT(*) as total, SUM(dilihat) as views FROM berita");
if ($r) { $row = $r->fetch_assoc(); $stats['total'] = $row['total']; $stats['views'] = number_format($row['views'] ?? 0); }
$r = $conn->query("SELECT COUNT(*) as c FROM berita WHERE status='Terbit'");
if ($r) $stats['terbit'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM berita WHERE status='Draft'");
if ($r) $stats['draft'] = $r->fetch_assoc()['c'];

// Berita terbaru
$berita_terbaru = [];
$res = $conn->query("SELECT id, judul, kategori, penulis, status, dilihat, created_at, thumbnail FROM berita ORDER BY created_at DESC LIMIT 5");
if ($res) while ($row = $res->fetch_assoc()) $berita_terbaru[] = $row;

// Berita terpopuler
$berita_populer = [];
$res = $conn->query("SELECT id, judul, dilihat, kategori FROM berita WHERE status='Terbit' ORDER BY dilihat DESC LIMIT 5");
if ($res) while ($row = $res->fetch_assoc()) $berita_populer[] = $row;

// Distribusi kategori
$kategori_stats = [];
$res = $conn->query("SELECT kategori, COUNT(*) as jumlah FROM berita GROUP BY kategori ORDER BY jumlah DESC");
if ($res) while ($row = $res->fetch_assoc()) $kategori_stats[] = $row;

$max_kat = count($kategori_stats) > 0 ? max(array_column($kategori_stats, 'jumlah')) : 1;

$kat_colors = [
    'Akademik' => '#2563EB',
    'Penelitian' => '#7C3AED',
    'Kemahasiswaan' => '#0EA5E9',
    'Pengumuman' => '#F59E0B',
    'Event' => '#10B981',
    'Prestasi' => '#EF4444',
    'Umum' => '#64748B',
];

function statusBadge($status) {
    $map = [
        'Terbit' => ['bg'=>'#D1FAE5','color'=>'#065F46','icon'=>'check-circle'],
        'Draft'  => ['bg'=>'#FEF3C7','color'=>'#92400E','icon'=>'clock'],
        'Arsip'  => ['bg'=>'#F1F5F9','color'=>'#475569','icon'=>'archive'],
    ];
    $s = $map[$status] ?? $map['Draft'];
    return "<span style='display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;background:{$s['bg']};color:{$s['color']};'>
        <i class='fas fa-{$s['icon']}' style='font-size:10px;'></i>{$status}</span>";
}
?>

<style>
/* Dashboard Home Styles */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--card-bg);
    border-radius: var(--radius);
    padding: 22px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: flex-start;
    gap: 16px;
    transition: box-shadow var(--transition), transform var(--transition);
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.stat-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
}

.stat-card.blue::after { background: linear-gradient(90deg, #2563EB, #0EA5E9); }
.stat-card.green::after { background: linear-gradient(90deg, #10B981, #34D399); }
.stat-card.yellow::after { background: linear-gradient(90deg, #F59E0B, #FCD34D); }
.stat-card.purple::after { background: linear-gradient(90deg, #7C3AED, #A78BFA); }

.stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.stat-icon.blue { background: #EFF6FF; color: #2563EB; }
.stat-icon.green { background: #ECFDF5; color: #10B981; }
.stat-icon.yellow { background: #FFFBEB; color: #F59E0B; }
.stat-icon.purple { background: #F5F3FF; color: #7C3AED; }

.stat-info { flex: 1; }
.stat-value { font-size: 28px; font-weight: 800; color: var(--text-main); line-height: 1; margin-bottom: 5px; }
.stat-label { font-size: 12.5px; color: var(--text-muted); font-weight: 500; }
.stat-sub { font-size: 11px; color: var(--text-light); margin-top: 6px; display: flex; align-items: center; gap: 4px; }

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
    margin-bottom: 20px;
}

.card {
    background: var(--card-bg);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.card-header {
    padding: 18px 22px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-title {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-title i { color: var(--primary); font-size: 13px; }

.card-action {
    font-size: 12px;
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    border-radius: 6px;
    transition: background var(--transition);
}

.card-action:hover { background: var(--primary-light); }

.news-table { width: 100%; border-collapse: collapse; }
.news-table th {
    padding: 10px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text-light);
    background: #F8FAFC;
    border-bottom: 1px solid var(--border);
}

.news-table td {
    padding: 13px 16px;
    border-bottom: 1px solid #F1F5F9;
    font-size: 13px;
    color: var(--text-main);
    vertical-align: middle;
}

.news-table tr:last-child td { border-bottom: none; }
.news-table tr:hover td { background: #F8FAFC; }

.news-thumb {
    width: 44px; height: 34px;
    border-radius: 6px;
    object-fit: cover;
    background: var(--primary-light);
    display: flex; align-items: center; justify-content: center;
    color: var(--primary);
    font-size: 14px;
    flex-shrink: 0;
}

.news-title-cell { display: flex; align-items: center; gap: 11px; }
.news-title-text { font-weight: 600; font-size: 13px; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px; }
.news-date { font-size: 11px; color: var(--text-light); margin-top: 2px; }

.kat-badge {
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.popular-list { padding: 8px 0; }
.popular-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 22px;
    border-bottom: 1px solid #F1F5F9;
    transition: background var(--transition);
}

.popular-item:last-child { border-bottom: none; }
.popular-item:hover { background: #F8FAFC; }
.popular-rank { width: 24px; height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
.popular-rank.r1 { background: #FEF9C3; color: #854D0E; }
.popular-rank.r2 { background: #F1F5F9; color: #475569; }
.popular-rank.r3 { background: #FEF3C7; color: #92400E; }
.popular-rank.rn { background: #F8FAFC; color: #94A3B8; }
.popular-title { flex: 1; font-size: 12.5px; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.popular-views { font-size: 11px; color: var(--text-light); display: flex; align-items: center; gap: 4px; flex-shrink: 0; }

.kategori-section { margin-top: 20px; }
.kategori-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; padding: 18px 22px; }
.kat-bar-item { display: flex; flex-direction: column; gap: 6px; }
.kat-bar-label { display: flex; justify-content: space-between; font-size: 12px; }
.kat-bar-name { font-weight: 600; color: var(--text-main); }
.kat-bar-count { color: var(--text-muted); font-weight: 500; }
.kat-bar-track { height: 6px; background: #F1F5F9; border-radius: 20px; overflow: hidden; }
.kat-bar-fill { height: 100%; border-radius: 20px; transition: width 0.6s cubic-bezier(0.4,0,0.2,1); }

.welcome-banner {
    background: linear-gradient(135deg, #1D4ED8 0%, #0EA5E9 100%);
    border-radius: var(--radius);
    padding: 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.welcome-banner::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.07);
    border-radius: 50%;
}

.welcome-banner::after {
    content: '';
    position: absolute;
    bottom: -60px; right: 80px;
    width: 160px; height: 160px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.welcome-text h2 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 6px; }
.welcome-text p { font-size: 13px; color: rgba(255,255,255,0.75); }
.welcome-btn {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: background var(--transition);
    display: flex; align-items: center; gap: 7px;
    white-space: nowrap;
    position: relative;
    z-index: 1;
    backdrop-filter: blur(4px);
}

.welcome-btn:hover { background: rgba(255,255,255,0.25); }

@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .dashboard-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
    .welcome-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
}
</style>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="welcome-text">
        <h2>Selamat Datang, Administrator! 👋</h2>
        <p>Kelola konten dan berita Fakultas dari panel administrasi ini.</p>
    </div>
    <a href="berita/tambah.php" class="welcome-btn">
        <i class="fas fa-plus"></i> Tambah Berita Baru
    </a>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fas fa-newspaper"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
            <div class="stat-label">Total Berita</div>
            <div class="stat-sub"><i class="fas fa-layer-group" style="font-size:10px;color:#2563EB"></i> Semua kategori</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['terbit'] ?? 0 ?></div>
            <div class="stat-label">Berita Terbit</div>
            <div class="stat-sub"><i class="fas fa-globe" style="font-size:10px;color:#10B981"></i> Tampil di publik</div>
        </div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['draft'] ?? 0 ?></div>
            <div class="stat-label">Berita Draft</div>
            <div class="stat-sub"><i class="fas fa-edit" style="font-size:10px;color:#F59E0B"></i> Menunggu publish</div>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon purple"><i class="fas fa-eye"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['views'] ?? 0 ?></div>
            <div class="stat-label">Total Tampilan</div>
            <div class="stat-sub"><i class="fas fa-chart-line" style="font-size:10px;color:#7C3AED"></i> Semua berita</div>
        </div>
    </div>
</div>

<!-- Main Grid -->
<div class="dashboard-grid">
    <!-- Berita Terbaru -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-clock-rotate-left"></i> Berita Terbaru</div>
            <a href="berita/index.php" class="card-action">Lihat Semua <i class="fas fa-arrow-right" style="font-size:10px;"></i></a>
        </div>
        <table class="news-table">
            <thead>
                <tr>
                    <th>BERITA</th>
                    <th>KATEGORI</th>
                    <th>STATUS</th>
                    <th>VIEWS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($berita_terbaru as $b): ?>
                <tr>
                    <td>
                        <div class="news-title-cell">
                            <?php if ($b['thumbnail'] && file_exists(__DIR__ . '/' . UPLOAD_URL . $b['thumbnail'])): ?>
                                <img src="<?= UPLOAD_URL . htmlspecialchars($b['thumbnail']) ?>" class="news-thumb" alt="">
                            <?php else: ?>
                                <div class="news-thumb"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                            <div>
                                <div class="news-title-text"><?= htmlspecialchars($b['judul']) ?></div>
                                <div class="news-date"><i class="fas fa-user" style="font-size:9px;"></i> <?= htmlspecialchars($b['penulis']) ?> &nbsp;·&nbsp; <?= date('d M Y', strtotime($b['created_at'])) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php $c = $kat_colors[$b['kategori']] ?? '#64748B'; ?>
                        <span class="kat-badge" style="background:<?= $c ?>18;color:<?= $c ?>;"><?= htmlspecialchars($b['kategori']) ?></span>
                    </td>
                    <td><?= statusBadge($b['status']) ?></td>
                    <td style="font-weight:600;color:var(--text-muted);font-size:13px;"><?= number_format($b['dilihat']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($berita_terbaru)): ?>
                <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--text-light);">
                    <i class="fas fa-inbox" style="font-size:28px;display:block;margin-bottom:8px;"></i>Belum ada berita
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Sidebar Kanan -->
    <div style="display:flex;flex-direction:column;gap:20px;">
        <!-- Terpopuler -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-fire"></i> Berita Terpopuler</div>
            </div>
            <div class="popular-list">
                <?php foreach ($berita_populer as $i => $b): ?>
                <div class="popular-item">
                    <div class="popular-rank <?= ['r1','r2','r3'][$i] ?? 'rn' ?>"><?= $i+1 ?></div>
                    <div class="popular-title"><?= htmlspecialchars($b['judul']) ?></div>
                    <div class="popular-views"><i class="fas fa-eye"></i> <?= number_format($b['dilihat']) ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($berita_populer)): ?>
                <div style="padding:24px;text-align:center;color:var(--text-light);font-size:13px;">Belum ada data</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Aksi Cepat -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-bolt"></i> Aksi Cepat</div>
            </div>
            <div style="padding:14px;display:flex;flex-direction:column;gap:8px;">
                <a href="berita/tambah.php" style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:var(--primary-light);border-radius:8px;text-decoration:none;color:var(--primary);font-size:13px;font-weight:600;transition:background 0.2s;">
                    <i class="fas fa-plus-circle"></i> Tambah Berita Baru
                </a>
                <a href="berita/index.php?filter=Draft" style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:#FFFBEB;border-radius:8px;text-decoration:none;color:#92400E;font-size:13px;font-weight:600;transition:background 0.2s;">
                    <i class="fas fa-edit"></i> Lihat Draft (<?= $stats['draft'] ?? 0 ?>)
                </a>
                <a href="berita/index.php" style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:#F0FDF4;border-radius:8px;text-decoration:none;color:#065F46;font-size:13px;font-weight:600;transition:background 0.2s;">
                    <i class="fas fa-list"></i> Kelola Semua Berita
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Distribusi Kategori -->
<?php if (!empty($kategori_stats)): ?>
<div class="card kategori-section">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-tags"></i> Distribusi Kategori Berita</div>
    </div>
    <div class="kategori-grid">
        <?php foreach ($kategori_stats as $k): ?>
        <?php $c = $kat_colors[$k['kategori']] ?? '#64748B'; $pct = round($k['jumlah'] / $max_kat * 100); ?>
        <div class="kat-bar-item">
            <div class="kat-bar-label">
                <span class="kat-bar-name"><?= htmlspecialchars($k['kategori']) ?></span>
                <span class="kat-bar-count"><?= $k['jumlah'] ?> berita</span>
            </div>
            <div class="kat-bar-track">
                <div class="kat-bar-fill" style="width:<?= $pct ?>%;background:<?= $c ?>;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once 'dashboard_footer.php'; ?>
