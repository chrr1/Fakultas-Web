<?php
$page_title = 'Detail Berita';
$current_page = 'berita';
require_once '../config.php';

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

$berita = null;
$res = $conn->query("SELECT * FROM berita WHERE id=$id LIMIT 1");
if ($res) $berita = $res->fetch_assoc();
if (!$berita) { header('Location: index.php'); exit; }

$page_title = 'Detail: ' . mb_strimwidth($berita['judul'], 0, 40, '...');

$kat_colors = [
    'Akademik' => '#2563EB', 'Penelitian' => '#7C3AED',
    'Kemahasiswaan' => '#0EA5E9', 'Pengumuman' => '#F59E0B',
    'Event' => '#10B981', 'Prestasi' => '#EF4444', 'Umum' => '#64748B',
];

require_once '../dashboard.php';
?>

<style>
.detail-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
}

.detail-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.detail-hero {
    width: 100%;
    aspect-ratio: 16 / 7;
    object-fit: cover;
}

.detail-hero-placeholder {
    width: 100%;
    aspect-ratio: 16 / 7;
    background: linear-gradient(
        135deg,
        var(--primary-light),
        var(--primary-soft)
    );
    display: flex;
    align-items: center;
    justify-content: center;
}

.detail-hero-placeholder i {
    font-size: 60px;
    color: var(--primary);
    opacity: 0.3;
}

.detail-body {
    padding: 28px;
}

.detail-kat {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 14px;
}

.detail-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.4;
    margin-bottom: 14px;
}

.detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    color: var(--text-muted);
}

.meta-item i {
    font-size: 12px;
    color: var(--text-light);
}

.detail-content {
    font-size: 14px;
    line-height: 1.9;
    color: #334155;
    white-space: pre-line;
}

.sidebar-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 16px;
}

.sidebar-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
    font-weight: 700;
    color: var(--text-main);
}

.sidebar-card-header i {
    color: var(--primary);
}

.sidebar-card-body {
    padding: 16px 18px;
}

.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-muted);
    font-weight: 500;
}

.info-value {
    text-align: right;
    font-weight: 700;
    color: var(--text-main);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    margin-bottom: 8px;
    padding: 10px 18px;
    border: none;
    border-radius: 9px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--primary);
    color: #fff;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-warning {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
}

.btn-warning:hover {
    background: #fef3c7;
}

.btn-danger {
    background: #fef2f2;
    color: var(--danger);
    border: 1px solid #fecaca;
}

.btn-danger:hover {
    background: #fee2e2;
}

.btn-outline {
    background: var(--card-bg);
    color: var(--text-muted);
    border: 1px solid var(--border);
}

.btn-outline:hover {
    background: var(--body-bg);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 13px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.status-terbit {
    background: #d1fae5;
    color: #065f46;
}

.status-draft {
    background: #fef3c7;
    color: #92400e;
}

.status-arsip {
    background: #f1f5f9;
    color: #475569;
}

@media (max-width: 900px) {
    .detail-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
    <a href="index.php" style="color:var(--text-muted);text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:6px;font-weight:500;">
        <i class="fas fa-arrow-left" style="font-size:11px;"></i> Kembali ke Daftar Berita
    </a>
    <div style="display:flex;gap:8px;">
        <a href="edit.php?id=<?= $id ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;text-decoration:none;color:#92400E;font-size:13px;font-weight:600;">
            <i class="fas fa-pen"></i> Edit
        </a>
        <a href="index.php?toggle=<?= $id ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:var(--primary-light);border:1px solid var(--primary-soft);border-radius:8px;text-decoration:none;color:var(--primary);font-size:13px;font-weight:600;">
            <i class="fas fa-toggle-on"></i> Toggle Status
        </a>
    </div>
</div>

<div class="detail-layout">
    <!-- Konten Utama -->
    <div>
        <div class="detail-card">
            <?php if ($berita['thumbnail'] && file_exists(UPLOAD_DIR . $berita['thumbnail'])): ?>
                <img src="../<?= UPLOAD_URL . htmlspecialchars($berita['thumbnail']) ?>" class="detail-hero" alt="">
            <?php else: ?>
                <div class="detail-hero-placeholder"><i class="fas fa-image"></i></div>
            <?php endif; ?>

            <div class="detail-body">
                <?php $c = $kat_colors[$berita['kategori']] ?? '#64748B'; ?>
                <div class="detail-kat" style="background:<?= $c ?>18;color:<?= $c ?>;">
                    <i class="fas fa-tag" style="font-size:11px;"></i>
                    <?= htmlspecialchars($berita['kategori']) ?>
                </div>

                <h1 class="detail-title"><?= htmlspecialchars($berita['judul']) ?></h1>

                <div class="detail-meta">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($berita['penulis']) ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <?= date('d F Y', strtotime($berita['created_at'])) ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <?= date('H:i', strtotime($berita['created_at'])) ?> WIB
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-eye"></i>
                        <?= number_format($berita['dilihat']) ?> tayangan
                    </div>
                </div>

                <div class="detail-content"><?= htmlspecialchars($berita['konten']) ?></div>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div>
        <div class="sidebar-card">
            <div class="sidebar-card-header"><i class="fas fa-info-circle"></i> Informasi Berita</div>
            <div class="sidebar-card-body">
                <div class="info-row">
                    <span class="info-label">ID</span>
                    <span class="info-value">#<?= $berita['id'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <span class="status-badge status-<?= strtolower($berita['status']) ?>">
                            <?= $berita['status'] ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kategori</span>
                    <span class="info-value"><?= htmlspecialchars($berita['kategori']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Penulis</span>
                    <span class="info-value"><?= htmlspecialchars($berita['penulis']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Dibuat</span>
                    <span class="info-value" style="font-size:12px;"><?= date('d M Y H:i', strtotime($berita['created_at'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Diperbarui</span>
                    <span class="info-value" style="font-size:12px;"><?= date('d M Y H:i', strtotime($berita['updated_at'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Dilihat</span>
                    <span class="info-value"><?= number_format($berita['dilihat']) ?>×</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Slug</span>
                    <span class="info-value" style="font-size:11px;word-break:break-all;"><?= htmlspecialchars($berita['slug']) ?></span>
                </div>
            </div>
        </div>

        <div class="sidebar-card">
            <div class="sidebar-card-header"><i class="fas fa-gear"></i> Aksi</div>
            <div class="sidebar-card-body">
                <a href="edit.php?id=<?= $id ?>" class="btn btn-warning">
                    <i class="fas fa-pen"></i> Edit Berita
                </a>
                <a href="index.php?toggle=<?= $id ?>" class="btn btn-primary">
                    <i class="fas fa-rotate"></i>
                    <?= $berita['status']==='Terbit' ? 'Jadikan Draft' : 'Terbitkan' ?>
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-list"></i> Kembali ke Daftar
                </a>
                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                <a href="index.php?hapus=<?= $id ?>" class="btn btn-danger"
                   onclick="return confirm('Hapus berita ini secara permanen?')">
                    <i class="fas fa-trash"></i> Hapus Berita
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../dashboard_footer.php'; ?>
