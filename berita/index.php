<?php

$page_title = 'Berita Fakultas';
$current_page = 'berita';

require_once '../config.php';

if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $res = $conn->query("SELECT thumbnail FROM berita WHERE id=$id");

    if ($res && $res->num_rows > 0) {

        $row = $res->fetch_assoc();

        if (
            !empty($row['thumbnail']) &&
            file_exists(UPLOAD_DIR . $row['thumbnail'])
        ) {
            unlink(UPLOAD_DIR . $row['thumbnail']);
        }
    }

    $conn->query("DELETE FROM berita WHERE id=$id");

    header('Location: index.php?msg=hapus_berhasil');
    exit;
}

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {

    $id = (int) $_GET['toggle'];

    $res = $conn->query("SELECT status FROM berita WHERE id=$id");

    if ($res) {

        $row = $res->fetch_assoc();

        $new_status = $row['status'] === 'Terbit'
            ? 'Draft'
            : 'Terbit';

        $conn->query("
            UPDATE berita
            SET status='$new_status'
            WHERE id=$id
        ");
    }

    header('Location: index.php?msg=status_berhasil');
    exit;
}

require_once '../dashboard.php';



$filter_status = isset($_GET['filter'])
    ? $_GET['filter']
    : '';

$filter_kat = isset($_GET['kategori'])
    ? $_GET['kategori']
    : '';

$search = isset($_GET['q'])
    ? trim($_GET['q'])
    : '';


$where = [];

if ($filter_status) {
    $where[] = "status = '" .
        $conn->real_escape_string($filter_status) .
        "'";
}

if ($filter_kat) {
    $where[] = "kategori = '" .
        $conn->real_escape_string($filter_kat) .
        "'";
}

if ($search) {

    $search_safe = $conn->real_escape_string($search);

    $where[] = "
        (
            judul LIKE '%$search_safe%'
            OR penulis LIKE '%$search_safe%'
        )
    ";
}

$where_sql = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';


$per_page = 10;

$page = isset($_GET['page']) && is_numeric($_GET['page'])
    ? (int) $_GET['page']
    : 1;

$offset = ($page - 1) * $per_page;


$total_res = $conn->query("
    SELECT COUNT(*) AS total
    FROM berita
    $where_sql
");

$total_rows = $total_res
    ? $total_res->fetch_assoc()['total']
    : 0;

$total_pages = ceil($total_rows / $per_page);


$berita_list = [];

$res = $conn->query("
    SELECT *
    FROM berita
    $where_sql
    ORDER BY created_at DESC
    LIMIT $per_page
    OFFSET $offset
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $berita_list[] = $row;
    }
}


$kat_list = [
    'Akademik',
    'Penelitian',
    'Kemahasiswaan',
    'Pengumuman',
    'Event',
    'Prestasi',
    'Umum'
];

$kat_colors = [
    'Akademik'      => '#2563EB',
    'Penelitian'    => '#7C3AED',
    'Kemahasiswaan' => '#0EA5E9',
    'Pengumuman'    => '#F59E0B',
    'Event'         => '#10B981',
    'Prestasi'      => '#EF4444',
    'Umum'          => '#64748B',
];

function buildQuery($params)
{
    $current = $_GET;

    foreach ($params as $k => $v) {

        if ($v === null) {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }

    $q = http_build_query($current);

    return 'index.php' . ($q ? '?' . $q : '');
}
?>
<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success">
    <?=
        $_GET['msg'] == 'tambah_berhasil' ? 'Berita berhasil ditambahkan.' :
        ($_GET['msg'] == 'edit_berhasil' ? 'Berita berhasil diperbarui.' :
        ($_GET['msg'] == 'hapus_berhasil' ? 'Berita berhasil dihapus.' :
        ($_GET['msg'] == 'status_berhasil' ? 'Status berita berhasil diubah.' : '')))
    ?>
</div>
<?php endif; ?>


<div class="page-header">
    <div class="page-header-left">
        <h1>Berita Fakultas</h1>
        <p>Kelola semua berita dan artikel yang dipublikasikan oleh Fakultas</p>
    </div>

    <div style="display:flex; gap:10px; align-items:center;">
        
        <a href="../main/index.html" 
           target="_blank"
           style="width:auto; display:inline-flex; align-items:center; gap:8px; padding:10px 16px; background:#10B981; color:#fff; text-decoration:none; border-radius:8px; font-weight:500;">
            <i class="fas fa-globe"></i> Lihat Website
        </a>

        <a href="tambah.php" 
           class="btn btn-primary"
           style="width:auto; display:inline-flex; align-items:center; gap:8px; padding:10px 16px;">
            <i class="fas fa-plus"></i> Tambah Berita
        </a>

    </div>
</div>


<div class="table-card">

    <?php
    $max_views = 1;
    foreach ($berita_list as $b) if ($b['dilihat'] > $max_views) $max_views = $b['dilihat'];
    ?>

    
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