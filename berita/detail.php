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



<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
    <a href="index.php" style="color:var(--text-muted);text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:6px;font-weight:500;">
        <i class="fas fa-arrow-left" style="font-size:11px;"></i> Kembali ke Daftar Berita
    </a>
    <div style="display:flex;gap:8px;">
        <a href="edit.php?id=<?= $id ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;text-decoration:none;color:#92400E;font-size:13px;font-weight:600;">
            <i class="fas fa-pen"></i> Edit
        </a>
        
    </div>
</div>

<div class="detail-layout">
  
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

    
    <div>
        <div class="sidebar-card">
            <div class="sidebar-card-header"><i class="fas fa-info-circle"></i> Informasi Berita</div>
            <div class="sidebar-card-body">
                <div class="info-row">
                    <span class="info-label">ID</span>
                    <span class="info-value">#<?= $berita['id'] ?></span>
                </div>
                <div class="info-row">
    <span class="info-label">Slug</span>
    <span class="info-value" style="font-size:12px;word-break:break-all;color:var(--text-muted);">
        <?= htmlspecialchars($berita['slug']) ?>
    </span>
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
