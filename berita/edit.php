<?php

$page_title = 'Edit Berita';
$current_page = 'berita';

require_once '../config.php';

$id = isset($_GET['id']) && is_numeric($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

$berita = null;

$res = $conn->query("
    SELECT *
    FROM berita
    WHERE id = $id
    LIMIT 1
");

if ($res) {
    $berita = $res->fetch_assoc();
}

if (!$berita) {
    header('Location: index.php?msg=not_found');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul    = trim($_POST['judul'] ?? '');
    $konten   = trim($_POST['konten'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $penulis  = trim($_POST['penulis'] ?? '');
    $status   = trim($_POST['status'] ?? 'Draft');

    if (!$judul) {
        $errors[] = 'Judul tidak boleh kosong.';
    }

    if (!$konten) {
        $errors[] = 'Konten tidak boleh kosong.';
    }

    if (!$kategori) {
        $errors[] = 'Kategori harus dipilih.';
    }

    if (!$penulis) {
        $errors[] = 'Penulis tidak boleh kosong.';
    }

    $thumbnail = $berita['thumbnail'];

    if (
        isset($_FILES['thumbnail']) &&
        $_FILES['thumbnail']['error'] === 0
    ) {

        $allowed = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp'
        ];

        $ftype = mime_content_type(
            $_FILES['thumbnail']['tmp_name']
        );

        if (!in_array($ftype, $allowed)) {

            $errors[] = 'Format gambar tidak valid.';

        } elseif ($_FILES['thumbnail']['size'] > 3 * 1024 * 1024) {

            $errors[] = 'Ukuran gambar maksimal 3MB.';

        } else {

            $ext = pathinfo(
                $_FILES['thumbnail']['name'],
                PATHINFO_EXTENSION
            );

            $new_thumb = 'thumb_' . uniqid() . '.' . $ext;

            if (
                move_uploaded_file(
                    $_FILES['thumbnail']['tmp_name'],
                    UPLOAD_DIR . $new_thumb
                )
            ) {

                if (
                    $thumbnail &&
                    file_exists(UPLOAD_DIR . $thumbnail)
                ) {
                    unlink(UPLOAD_DIR . $thumbnail);
                }

                $thumbnail = $new_thumb;

            } else {

                $errors[] = 'Gagal mengupload gambar.';
            }
        }
    }

    if (
        isset($_POST['hapus_thumb']) &&
        $_POST['hapus_thumb'] == '1'
    ) {

        if (
            $thumbnail &&
            file_exists(UPLOAD_DIR . $thumbnail)
        ) {
            unlink(UPLOAD_DIR . $thumbnail);
        }

        $thumbnail = '';
    }

    if (empty($errors)) {

    $stmt = $conn->prepare("
        UPDATE berita
        SET
            judul = ?,
            konten = ?,
            thumbnail = ?,
            kategori = ?,
            penulis = ?,
            status = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        'ssssssi',
        $judul,
        $konten,
        $thumbnail,
        $kategori,
        $penulis,
        $status,
        $id
    );

    if ($stmt->execute()) {

        header('Location: index.php?msg=edit_berhasil');
        exit;

    } else {

        $errors[] = 'Gagal memperbarui data: ' . $conn->error;
    }
}

    $berita = array_merge($berita, [
        'judul'     => $judul,
        'konten'    => $konten,
        'kategori'  => $kategori,
        'penulis'   => $penulis,
        'status'    => $status,
        'thumbnail' => $thumbnail
    ]);
}

require_once '../dashboard.php';

?>

<style>

</style>

<div style="margin-bottom:18px;">
    <a href="index.php" style="color:var(--text-muted);text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:6px;font-weight:500;">
        <i class="fas fa-arrow-left" style="font-size:11px;"></i> Kembali ke Daftar Berita
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong><i class="fas fa-exclamation-circle"></i> Terdapat kesalahan:</strong>
    <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
</div>
<?php endif; ?>

<!-- Edit Meta -->
<div class="edit-meta">
    <span>ID: <strong>#<?= $berita['id'] ?></strong></span>
    <span>Dibuat: <strong><?= date('d M Y H:i', strtotime($berita['created_at'])) ?></strong></span>
    <span>Diperbarui: <strong><?= date('d M Y H:i', strtotime($berita['updated_at'])) ?></strong></span>
    <span>Dilihat: <strong><?= number_format($berita['dilihat']) ?> kali</strong></span>
</div>

<div class="form-card">
    <div class="form-header">
        <div class="form-header-icon"><i class="fas fa-pen"></i></div>
        <div>
            <h2>Edit Berita</h2>
            <p>Perbarui informasi berita yang sudah ada</p>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="hapus_thumb" id="hapus_thumb_input" value="0">
        <div class="form-body">

            <div class="form-group">
                <label class="form-label">Judul Berita <span class="req">*</span></label>
                <input type="text" name="judul" class="form-control"
                    placeholder="Masukkan judul berita..."
                    value="<?= htmlspecialchars($berita['judul']) ?>" maxlength="255">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kategori <span class="req">*</span></label>
                    <select name="kategori" class="form-control">
                        <?php foreach (['Akademik','Penelitian','Kemahasiswaan','Pengumuman','Event','Prestasi','Umum'] as $k): ?>
                        <option value="<?= $k ?>" <?= $berita['kategori']===$k?'selected':'' ?>><?= $k ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Penulis <span class="req">*</span></label>
                    <input type="text" name="penulis" class="form-control"
                        value="<?= htmlspecialchars($berita['penulis']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Konten Berita <span class="req">*</span></label>
                <textarea name="konten" class="form-control"><?= htmlspecialchars($berita['konten']) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Thumbnail Berita</label>
                    <?php if ($berita['thumbnail'] && file_exists(UPLOAD_DIR . $berita['thumbnail'])): ?>
                    <div class="current-thumb" id="currentThumbWrap">
                        <img src="../<?= UPLOAD_URL . htmlspecialchars($berita['thumbnail']) ?>" alt="Thumbnail saat ini" id="currentThumbImg">
                        <div class="current-thumb-label">
                            <span><i class="fas fa-image"></i> Thumbnail saat ini</span>
                            <button type="button" class="del-thumb-btn" onclick="hapusThumbnail()">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                    <div style="margin-top:10px;font-size:12px;color:var(--text-light);">Upload gambar baru untuk mengganti thumbnail saat ini.</div>
                    <?php else: ?>
                    <div style="font-size:12.5px;color:var(--text-light);margin-bottom:8px;"><i class="fas fa-info-circle"></i> Belum ada thumbnail</div>
                    <?php endif; ?>
                    <div class="upload-area" id="uploadArea" style="margin-top:10px;">
                        <input type="file" name="thumbnail" class="upload-input" accept="image/*" onchange="previewThumb(this)">
                        <i class="fas fa-cloud-arrow-up" style="font-size:24px;color:var(--text-light);"></i>
                        <div style="font-size:13px;font-weight:600;color:var(--text-muted);margin-top:8px;">Upload Thumbnail Baru</div>
                        <div style="font-size:11.5px;color:var(--text-light);">JPG, PNG, WebP — Maks. 3MB</div>
                        <img id="thumbPreview" style="max-height:140px;border-radius:8px;margin-top:10px;object-fit:cover;width:100%;display:none;" src="" alt="">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Publikasi <span class="req">*</span></label>
                    <select name="status" class="form-control">
                        <option value="Draft" <?= $berita['status']==='Draft'?'selected':'' ?>>Draft</option>
                        <option value="Terbit" <?= $berita['status']==='Terbit'?'selected':'' ?>>Terbit</option>
                        <option value="Arsip" <?= $berita['status']==='Arsip'?'selected':'' ?>>Arsip</option>
                    </select>
                    <div style="font-size:12px;color:var(--text-light);margin-top:8px;">
                        Status saat ini: <strong style="color:var(--text-main);"><?= $berita['status'] ?></strong>
                    </div>
                    <div style="margin-top:14px;padding:14px;background:#F0FDF4;border-radius:9px;">
                        <div style="font-size:12px;font-weight:600;color:#065F46;margin-bottom:4px;"><i class="fas fa-shield-check"></i> Perhatian</div>
                        <div style="font-size:12px;color:#065F46;line-height:1.6;">Mengubah status dari <b>Terbit</b> ke <b>Draft</b> akan menyembunyikan berita dari publik.</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="form-footer">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save"></i> Perbarui Berita
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
            <a href="detail.php?id=<?= $id ?>" class="btn btn-secondary" style="margin-left:auto;">
                <i class="fas fa-eye"></i> Lihat Detail
            </a>
        </div>
    </form>
</div>

<script>
function previewThumb(input) {
    const area = document.getElementById('uploadArea');
    const img = document.getElementById('thumbPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            img.style.display = 'block';
            area.classList.add('has-file');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function hapusThumbnail() {
    if (confirm('Yakin ingin menghapus thumbnail ini?')) {
        document.getElementById('hapus_thumb_input').value = '1';
        document.getElementById('currentThumbWrap').style.display = 'none';
    }
}
</script>

<?php require_once '../dashboard_footer.php'; ?>
