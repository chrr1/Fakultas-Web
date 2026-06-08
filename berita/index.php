<?php
$page_title = 'Berita Fakultas';
$current_page = 'berita';
require_once '../config.php';

if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $res = $conn->query("SELECT thumbnail FROM berita WHERE id=$id");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (!empty($row['thumbnail']) && file_exists(UPLOAD_DIR . $row['thumbnail'])) {
            unlink(UPLOAD_DIR . $row['thumbnail']);
        }
    }
    $conn->query("DELETE FROM berita WHERE id=$id");
    header('Location: index.php?msg=hapus_berhasil');
    exit;
}

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $res = $conn->query("SELECT status FROM berita WHERE id=$id");
    if ($res) {
        $row = $res->fetch_assoc();
        $new_status = $row['status'] === 'Terbit' ? 'Draft' : 'Terbit';
        $conn->query("UPDATE berita SET status='$new_status' WHERE id=$id");
    }
    header('Location: index.php?msg=status_berhasil');
    exit;
}

require_once '../dashboard.php';

$filter_status = isset($_GET['filter']) ? $_GET['filter'] : '';
$filter_kat    = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search        = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = [];
if ($filter_status) $where[] = "status = '" . $conn->real_escape_string($filter_status) . "'";
if ($filter_kat)    $where[] = "kategori = '" . $conn->real_escape_string($filter_kat) . "'";
if ($search)        $where[] = "(judul LIKE '%" . $conn->real_escape_string($search) . "%' OR penulis LIKE '%" . $conn->real_escape_string($search) . "%')";
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

$total_res  = $conn->query("SELECT COUNT(*) as total FROM berita $where_sql");
$total_rows = $total_res ? $total_res->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $per_page);

$berita_list = [];
$res = $conn->query("SELECT * FROM berita $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
if ($res) while ($row = $res->fetch_assoc()) $berita_list[] = $row;

$kat_list = ['Akademik','Penelitian','Kemahasiswaan','Pengumuman','Event','Prestasi','Umum'];
$kat_colors = [
    'Akademik' => '#2563EB', 'Penelitian' => '#7C3AED',
    'Kemahasiswaan' => '#0EA5E9', 'Pengumuman' => '#F59E0B',
    'Event' => '#10B981', 'Prestasi' => '#EF4444', 'Umum' => '#64748B',
];

function buildQuery($params) {
    $current = $_GET;
    foreach ($params as $k => $v) {
        if ($v === null) unset($current[$k]);
        else $current[$k] = $v;
    }
    $q = http_build_query($current);
    return 'index.php' . ($q ? '?' . $q : '');
}
?>

<style>
/* ============================================================
   Berita Index — Responsive Styles
   ============================================================ */

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    gap: 16px;
    flex-wrap: wrap;
}
.page-header-left h1 { font-size: 22px; font-weight: 800; color: var(--text-main); }
.page-header-left p  { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

/* --- Buttons --- */
.btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 18px; border-radius: 9px; font-size: 13px;
    font-weight: 600; font-family: 'Poppins', sans-serif;
    cursor: pointer; text-decoration: none; border: none; transition: all 0.2s;
}
.btn-primary  { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(37,99,235,0.3); }
.btn-danger   { background: #FEF2F2; color: var(--danger); border: 1px solid #FECACA; }
.btn-danger:hover  { background: #FEE2E2; }
.btn-warning  { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
.btn-warning:hover { background: #FEF3C7; }
.btn-success  { background: #F0FDF4; color: #065F46; border: 1px solid #BBF7D0; }
.btn-success:hover { background: #DCFCE7; }
.btn-sm       { padding: 6px 12px; font-size: 12px; }
.btn-icon     { width: 32px; height: 32px; padding: 0; border-radius: 7px; justify-content: center; }

/* --- Filters bar --- */
.filters-bar {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px 18px;
    margin-bottom: 18px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: var(--shadow-sm);
}

.search-box {
    display: flex; align-items: center; gap: 8px;
    background: #F8FAFC; border: 1px solid var(--border);
    border-radius: 8px; padding: 8px 14px;
    flex: 1; min-width: 160px; max-width: 320px;
}
.search-box i     { color: var(--text-light); font-size: 13px; flex-shrink: 0; }
.search-box input { border: none; background: none; font-family: 'Poppins',sans-serif; font-size: 13px; color: var(--text-main); width: 100%; outline: none; }
.search-box input::placeholder { color: var(--text-light); }

.filter-select {
    border: 1px solid var(--border); background: #F8FAFC;
    border-radius: 8px; padding: 8px 14px;
    font-family: 'Poppins',sans-serif; font-size: 13px;
    color: var(--text-main); cursor: pointer; outline: none;
    transition: border-color 0.2s;
}
.filter-select:focus { border-color: var(--primary); }

.filter-chip {
    padding: 7px 14px; border-radius: 20px; font-size: 12.5px;
    font-weight: 600; cursor: pointer; border: 1px solid var(--border);
    background: var(--card-bg); color: var(--text-muted);
    text-decoration: none; transition: all 0.2s; white-space: nowrap;
}
.filter-chip:hover, .filter-chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }

/* --- Alert --- */
.alert {
    padding: 12px 18px; border-radius: 9px; margin-bottom: 16px;
    font-size: 13.5px; font-weight: 500;
    display: flex; align-items: center; gap: 10px;
    animation: slideDown 0.4s ease;
}
.alert-success { background: #F0FDF4; color: #065F46; border: 1px solid #10B981; }
.alert-danger  { background: #FEF2F2; color: #991B1B; }
@keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

/* --- Table card --- */
.table-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

/* ── Desktop table (visible ≥ 769px) ── */
.data-table { width: 100%; border-collapse: collapse; }
.data-table th {
    padding: 11px 16px; text-align: left; font-size: 11px;
    font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px;
    color: var(--text-light); background: #F8FAFC;
    border-bottom: 1px solid var(--border); white-space: nowrap;
}
.data-table td {
    padding: 14px 16px; border-bottom: 1px solid #F1F5F9;
    vertical-align: middle; font-size: 13px;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #FAFBFF; }

.thumb-img         { width: 60px; height: 44px; border-radius: 7px; object-fit: cover; }
.thumb-placeholder { width: 60px; height: 44px; border-radius: 7px; background: var(--primary-light); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 18px; }

.news-title-wrap { max-width: 260px; }
.news-title-main {
    font-weight: 600; color: var(--text-main); line-height: 1.4;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.news-meta { font-size: 11px; color: var(--text-light); margin-top: 4px; }

.kat-badge   { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }

.status-badge  { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
.status-terbit { background: #D1FAE5; color: #065F46; }
.status-draft  { background: #FEF3C7; color: #92400E; }
.status-arsip  { background: #F1F5F9; color: #475569; }

.views-num { font-weight: 700; color: var(--text-muted); }

.action-group { display: flex; gap: 5px; align-items: center; }

/* ── Mobile card list (hidden on desktop) ── */
.mobile-list { display: none; }

.mobile-card {
    padding: 14px 16px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.mobile-card:last-child { border-bottom: none; }

.mobile-thumb      { width: 62px; height: 48px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
.mobile-thumb-ph   { width: 62px; height: 48px; border-radius: 8px; background: var(--primary-light); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 20px; flex-shrink: 0; }

.mobile-body       { flex: 1; min-width: 0; }
.mobile-title      { font-weight: 600; font-size: 13px; color: var(--text-main); line-height: 1.4; margin-bottom: 5px; word-break: break-word; }
.mobile-meta       { font-size: 11px; color: var(--text-light); margin-bottom: 6px; display: flex; flex-wrap: wrap; gap: 4px 8px; align-items: center; }
.mobile-badges     { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; align-items: center; }
.mobile-actions    { display: flex; gap: 6px; }

/* --- Pagination --- */
.pagination {
    display: flex; gap: 6px; align-items: center;
    justify-content: center; padding: 18px; flex-wrap: wrap;
}
.pag-btn {
    min-width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px; text-decoration: none; font-size: 13px;
    font-weight: 600; color: var(--text-muted);
    border: 1px solid var(--border); background: var(--card-bg);
    transition: all 0.2s; padding: 0 8px;
}
.pag-btn:hover    { border-color: var(--primary); color: var(--primary); }
.pag-btn.active   { background: var(--primary); color: #fff; border-color: var(--primary); }
.pag-btn.disabled { opacity: 0.4; pointer-events: none; }

/* --- Empty state --- */
.empty-state  { padding: 60px 20px; text-align: center; }
.empty-icon   { font-size: 52px; color: var(--border); margin-bottom: 14px; }
.empty-title  { font-size: 16px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; }
.empty-desc   { font-size: 13px; color: var(--text-light); }

/* --- Confirm modal --- */
.confirm-modal { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.55); z-index: 1000; align-items: center; justify-content: center; padding: 16px; }
.confirm-modal.show { display: flex; }
.confirm-box {
    background: #fff; border-radius: 16px; padding: 32px;
    max-width: 380px; width: 100%; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    animation: popIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes popIn { from { transform: scale(0.85); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.confirm-icon    { font-size: 44px; color: #EF4444; margin-bottom: 14px; }
.confirm-title   { font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
.confirm-desc    { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; line-height: 1.6; }
.confirm-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

/* ============================================================
   RESPONSIVE BREAKPOINTS
   ============================================================ */

/* Tablet (≤ 900px): sembunyikan kolom kurang penting */
@media (max-width: 900px) {
    .col-penulis,
    .col-dilihat { display: none; }

    .news-title-wrap { max-width: 200px; }
}

/* Mobile (≤ 768px): ganti tabel ke kartu */
@media (max-width: 768px) {
    .desktop-table { display: none; }
    .mobile-list   { display: block; }

    .page-header-left h1 { font-size: 18px; }
    .page-header-left p  { font-size: 12px; }

    .filters-bar {
        padding: 12px;
        gap: 8px;
    }

    .search-box { max-width: 100%; min-width: 0; flex: 1 1 100%; }

    .filter-select { flex: 1; min-width: 0; font-size: 12px; padding: 7px 10px; }
}

/* Small mobile (≤ 480px) */
@media (max-width: 480px) {
    .page-header { gap: 10px; }
    .btn-primary  { padding: 9px 14px; font-size: 12px; }

    .mobile-card  { padding: 12px; gap: 10px; }

    .mobile-thumb,
    .mobile-thumb-ph { width: 54px; height: 42px; }

    .confirm-box { padding: 24px 18px; }
    .confirm-title { font-size: 16px; }
}
</style>

<!-- Alerts -->
<?php if (isset($_GET['msg'])): ?>
<?php $msgs = [
    'hapus_berhasil'  => ['Berita berhasil dihapus.','success'],
    'status_berhasil' => ['Status berita berhasil diubah.','success'],
    'tambah_berhasil' => ['Berita berhasil ditambahkan.','success'],
    'edit_berhasil'   => ['Berita berhasil diperbarui.','success'],
]; ?>
<?php if (isset($msgs[$_GET['msg']])): ?>
<div class="alert alert-<?= $msgs[$_GET['msg']][1] ?>">
    <i class="fas fa-<?= $msgs[$_GET['msg']][1]==='success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= $msgs[$_GET['msg']][0] ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1>Berita Fakultas</h1>
        <p>Kelola semua berita dan artikel yang dipublikasikan oleh Fakultas</p>
    </div>
    <a href="tambah.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Berita
    </a>
</div>

<!-- Table Card -->
<div class="table-card">

    <?php
    $max_views = 1;
    foreach ($berita_list as $b) if ($b['dilihat'] > $max_views) $max_views = $b['dilihat'];
    ?>

    <!-- ═══ DESKTOP TABLE (≥ 769px) ═══ -->
    <div style="overflow-x:auto;" class="desktop-table">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:70px;">THUMBNAIL</th>
                    <th>JUDUL BERITA</th>
                    <th>KATEGORI</th>
                    <th class="col-penulis">PENULIS</th>
                    <th>TANGGAL</th>
                    <th>STATUS</th>
                    <th class="col-dilihat">DILIHAT</th>
                    <th style="width:130px;text-align:center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($berita_list as $b): ?>
                <?php $c = $kat_colors[$b['kategori']] ?? '#64748B'; ?>
                <tr>
                    <td>
                        <?php if ($b['thumbnail'] && file_exists(UPLOAD_DIR . $b['thumbnail'])): ?>
                            <img src="../<?= UPLOAD_URL . htmlspecialchars($b['thumbnail']) ?>" class="thumb-img" alt="">
                        <?php else: ?>
                            <div class="thumb-placeholder"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="news-title-wrap">
                            <div class="news-title-main"><?= htmlspecialchars($b['judul']) ?></div>
                            <div class="news-meta">ID: #<?= $b['id'] ?> &nbsp;·&nbsp; <?= mb_strimwidth(strip_tags($b['konten']), 0, 60, '...') ?></div>
                        </div>
                    </td>
                    <td>
                        <span class="kat-badge" style="background:<?= $c ?>18;color:<?= $c ?>;"><?= htmlspecialchars($b['kategori']) ?></span>
                    </td>
                    <td class="col-penulis">
                        <div style="font-size:13px;font-weight:500;color:var(--text-main);"><?= htmlspecialchars($b['penulis']) ?></div>
                    </td>
                    <td>
                        <div style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                            <div style="font-weight:600;"><?= date('d M Y', strtotime($b['created_at'])) ?></div>
                            <div style="font-size:11px;color:var(--text-light);"><?= date('H:i', strtotime($b['created_at'])) ?> WIB</div>
                        </div>
                    </td>
                    <td>
                        <a href="index.php?toggle=<?= $b['id'] ?>" title="Klik untuk toggle status" style="text-decoration:none;">
                            <span class="status-badge status-<?= strtolower($b['status']) ?>">
                                <i class="fas fa-<?= $b['status']==='Terbit'?'check-circle':($b['status']==='Draft'?'clock':'archive') ?>" style="font-size:10px;"></i>
                                <?= $b['status'] ?>
                            </span>
                        </a>
                    </td>
                    <td class="col-dilihat">
                        <div class="views-num"><?= number_format($b['dilihat']) ?></div>
                    </td>
                    <td>
                        <div class="action-group" style="justify-content:center;">
                            <a href="edit.php?id=<?= $b['id'] ?>" class="btn btn-warning btn-sm btn-icon" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="detail.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-icon" style="background:#F0F9FF;color:#0EA5E9;border:1px solid #BAE6FD;" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button onclick="confirmHapus(<?= $b['id'] ?>, '<?= addslashes(htmlspecialchars($b['judul'])) ?>')" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ═══ MOBILE CARD LIST (≤ 768px) ═══ -->
    <div class="mobile-list">
        <?php foreach ($berita_list as $b):
            $c = $kat_colors[$b['kategori']] ?? '#64748B';
        ?>
        <div class="mobile-card">
            <!-- Thumbnail -->
            <?php if ($b['thumbnail'] && file_exists(UPLOAD_DIR . $b['thumbnail'])): ?>
                <img src="../<?= UPLOAD_URL . htmlspecialchars($b['thumbnail']) ?>" class="mobile-thumb" alt="">
            <?php else: ?>
                <div class="mobile-thumb-ph"><i class="fas fa-image"></i></div>
            <?php endif; ?>

            <!-- Body -->
            <div class="mobile-body">
                <div class="mobile-title"><?= htmlspecialchars($b['judul']) ?></div>
                <div class="mobile-meta">
                    <span><i class="fas fa-user" style="font-size:9px;"></i> <?= htmlspecialchars($b['penulis']) ?></span>
                    <span>·</span>
                    <span><?= date('d M Y', strtotime($b['created_at'])) ?></span>
                    <span>·</span>
                    <span><i class="fas fa-eye" style="font-size:9px;"></i> <?= number_format($b['dilihat']) ?></span>
                </div>
                <div class="mobile-badges">
                    <span class="kat-badge" style="background:<?= $c ?>18;color:<?= $c ?>;"><?= htmlspecialchars($b['kategori']) ?></span>
                    <a href="index.php?toggle=<?= $b['id'] ?>" style="text-decoration:none;" title="Toggle status">
                        <span class="status-badge status-<?= strtolower($b['status']) ?>">
                            <i class="fas fa-<?= $b['status']==='Terbit'?'check-circle':($b['status']==='Draft'?'clock':'archive') ?>" style="font-size:10px;"></i>
                            <?= $b['status'] ?>
                        </span>
                    </a>
                </div>
                <div class="mobile-actions">
                    <a href="edit.php?id=<?= $b['id'] ?>" class="btn btn-warning btn-sm btn-icon" title="Edit">
                        <i class="fas fa-pen"></i>
                    </a>
                    <a href="detail.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-icon" style="background:#F0F9FF;color:#0EA5E9;border:1px solid #BAE6FD;" title="Lihat Detail">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button onclick="confirmHapus(<?= $b['id'] ?>, '<?= addslashes(htmlspecialchars($b['judul'])) ?>')" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty state -->
    <?php if (empty($berita_list)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-newspaper"></i></div>
        <div class="empty-title">Tidak ada berita ditemukan</div>
        <div class="empty-desc">
            <?= ($search || $filter_status || $filter_kat) ? 'Coba ubah filter atau kata kunci pencarian Anda.' : 'Mulai tambahkan berita pertama Anda.' ?>
        </div>
        <?php if (!$search && !$filter_status && !$filter_kat): ?>
        <a href="tambah.php" class="btn btn-primary" style="margin-top:16px;">
            <i class="fas fa-plus"></i> Tambah Berita Pertama
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <a href="<?= buildQuery(['page'=>max(1,$page-1)]) ?>" class="pag-btn <?= $page<=1?'disabled':'' ?>">
            <i class="fas fa-chevron-left" style="font-size:11px;"></i>
        </a>
        <?php for ($i=1; $i<=$total_pages; $i++): ?>
            <?php if ($i==1 || $i==$total_pages || abs($i-$page)<=2): ?>
                <a href="<?= buildQuery(['page'=>$i]) ?>" class="pag-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php elseif (abs($i-$page)==3): ?>
                <span class="pag-btn" style="border:none;background:none;">...</span>
            <?php endif; ?>
        <?php endfor; ?>
        <a href="<?= buildQuery(['page'=>min($total_pages,$page+1)]) ?>" class="pag-btn <?= $page>=$total_pages?'disabled':'' ?>">
            <i class="fas fa-chevron-right" style="font-size:11px;"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Confirm Delete Modal -->
<div class="confirm-modal" id="confirmModal">
    <div class="confirm-box">
        <div class="confirm-icon"><i class="fas fa-trash-can"></i></div>
        <div class="confirm-title">Hapus Berita?</div>
        <div class="confirm-desc" id="confirmDesc">Berita ini akan dihapus permanen beserta thumbnail-nya dan tidak dapat dikembalikan.</div>
        <div class="confirm-actions">
            <button onclick="closeConfirm()" class="btn" style="background:#F1F5F9;color:var(--text-muted);border:none;">Batal</button>
            <a href="#" id="confirmLink" class="btn btn-danger" style="background:#EF4444;color:#fff;border:none;">
                <i class="fas fa-trash"></i> Hapus Sekarang
            </a>
        </div>
    </div>
</div>

<script>
function confirmHapus(id, judul) {
    document.getElementById('confirmDesc').innerHTML = 'Berita <strong>"' + judul + '"</strong> akan dihapus permanen beserta thumbnail-nya.';
    document.getElementById('confirmLink').href = 'index.php?hapus=' + id;
    document.getElementById('confirmModal').classList.add('show');
}
function closeConfirm() {
    document.getElementById('confirmModal').classList.remove('show');
}
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeConfirm();
});

setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity 0.5s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 500);
    });
}, 4000);
</script>

<?php require_once '../dashboard_footer.php'; ?>