<?php

require_once __DIR__ . '/../config.php';



$per_page = 9;

$kat_list = [
    'Akademik',
    'Penelitian',
    'Kemahasiswaan',
    'Pengumuman',
    'Event',
    'Prestasi',
    'Umum'
];

$kat_meta = [
    'Akademik'      => ['color'=>'#2563EB','bg'=>'#EFF6FF','icon'=>'graduation-cap'],
    'Penelitian'    => ['color'=>'#7C3AED','bg'=>'#F5F3FF','icon'=>'flask'],
    'Kemahasiswaan' => ['color'=>'#0EA5E9','bg'=>'#F0F9FF','icon'=>'users'],
    'Pengumuman'    => ['color'=>'#F59E0B','bg'=>'#FFFBEB','icon'=>'bullhorn'],
    'Event'         => ['color'=>'#10B981','bg'=>'#ECFDF5','icon'=>'calendar-star'],
    'Prestasi'      => ['color'=>'#EF4444','bg'=>'#FFF1F2','icon'=>'trophy'],
    'Umum'          => ['color'=>'#64748B','bg'=>'#F8FAFC','icon'=>'newspaper'],
];



function buildUrl($params)
{
    $c = $_GET;

    foreach ($params as $k => $v) {
        if ($v === null || $v === '') {
            unset($c[$k]);
        } else {
            $c[$k] = $v;
        }
    }

    unset($c['page']);

    $q = http_build_query($c);

    return 'berita.php' . ($q ? '?' . $q : '');
}

function katMeta($kat, $kat_meta)
{
    return $kat_meta[$kat] ?? [
        'color' => '#64748B',
        'bg'    => '#F8FAFC',
        'icon'  => 'tag'
    ];
}

function excerptKonten($text, $len = 120)
{
    $text = strip_tags($text);

    return mb_strlen($text) > $len
        ? mb_strimwidth($text, 0, $len, '…')
        : $text;
}

function thumbUrl($thumb)
{
    if (
        $thumb &&
        file_exists(__DIR__ . '/../berita/uploads/' . $thumb)
    ) {
        return '../berita/uploads/' . htmlspecialchars($thumb);
    }

    return null;
}

function detailUrl($id)
{
    return 'berita-detail.php?id=' . $id;
}



$filter_kat = isset($_GET['kategori'])
    ? trim($_GET['kategori'])
    : '';

$search = isset($_GET['q'])
    ? trim($_GET['q'])
    : '';

$page = isset($_GET['page']) && is_numeric($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

$offset = ($page - 1) * $per_page;



$where = ["status = 'Terbit'"];

if (
    $filter_kat &&
    in_array($filter_kat, $kat_list)
) {
    $where[] = "kategori = '" .
        $conn->real_escape_string($filter_kat) .
        "'";
}

if ($search) {

    $keyword = $conn->real_escape_string($search);

    $where[] = "
        (
            judul LIKE '%{$keyword}%'
            OR konten LIKE '%{$keyword}%'
            OR penulis LIKE '%{$keyword}%'
        )
    ";
}

$where_sql = 'WHERE ' . implode(' AND ', $where);



$total_res = $conn->query("
    SELECT COUNT(*) AS t
    FROM berita
    {$where_sql}
");

$total_rows = $total_res
    ? $total_res->fetch_assoc()['t']
    : 0;

$total_pages = max(
    1,
    ceil($total_rows / $per_page)
);

$berita_list = [];

$res = $conn->query("
    SELECT *
    FROM berita
    {$where_sql}
    ORDER BY created_at DESC
    LIMIT {$per_page}
    OFFSET {$offset}
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $berita_list[] = $row;
    }
}



$featured = null;

if (
    $page === 1 &&
    !$filter_kat &&
    !$search
) {
    $rf = $conn->query("
        SELECT *
        FROM berita
        WHERE status = 'Terbit'
        ORDER BY dilihat DESC
        LIMIT 1
    ");

    if ($rf) {
        $featured = $rf->fetch_assoc();
    }

    if ($featured) {
        $berita_list = array_filter(
            $berita_list,
            fn($b) => $b['id'] !== $featured['id']
        );
    }
}



$kat_counts = [];

$rc = $conn->query("
    SELECT kategori, COUNT(*) AS c
    FROM berita
    WHERE status='Terbit'
    GROUP BY kategori
");

if ($rc) {
    while ($r = $rc->fetch_assoc()) {
        $kat_counts[$r['kategori']] = $r['c'];
    }
}



$populer = [];

$rp = $conn->query("
    SELECT id, judul, dilihat, kategori, created_at
    FROM berita
    WHERE status='Terbit'
    ORDER BY dilihat DESC
    LIMIT 5
");

if ($rp) {
    while ($r = $rp->fetch_assoc()) {
        $populer[] = $r;
    }
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
 <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet" />
<link rel="stylesheet" href="berita.css">
</head>
<body>
<nav id="navbar" class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">

      <a class="navbar-brand" href="#">
        <img src="assets/upitra_logo.png" alt="Logo" style="height: 40px" />
      </a>


      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>


      <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
        <ul class="navbar-nav gap-lg-4 text-center">
          <li class="nav-item">
            <a class="nav-link active fw-semibold" href="index.html">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="profile.html">Profile</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
              Prodi
            </a>
            <ul class="dropdown-menu text-start">
              <li><a class="dropdown-item" href="sistem_informasi.html">S1 Sistem Informasi</a></li>
              <li><a class="dropdown-item" href="informatika.html">S1 Informatika</a></li>
              <li><a class="dropdown-item" href="software_engineering.html">S1 Software Engineering</a></li>
            </ul>
          </li>

          <li class="nav-item">
            <a class="nav-link fw-semibold" href="berita.php">Berita</a>
          </li>


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
          style="width: 45px; height: 45px">
          <i class="bi bi-whatsapp"></i>
        </a>
      </div>
    </div>
  </nav>


<div class="main-layout">
    
    <div>

        
        <?php if ($search): ?>
        <div class="search-info">
            <span>Hasil pencarian untuk: <strong>"<?= htmlspecialchars($search) ?>"</strong> — <?= $total_rows ?> berita ditemukan</span>
            <a href="<?= buildUrl(['q' => null]) ?>" class="search-clear">
                <i class="fas fa-times" style="font-size:10px;"></i> Hapus
            </a>
        </div>
        <?php endif; ?>

      
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

    </div>

   
    <aside class="sidebar">

       
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

    </aside>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>