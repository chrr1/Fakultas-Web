<?php
$page_title = 'Tambah Berita';
$current_page = 'berita';
require_once '../config.php';


$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul    = trim($_POST['judul'] ?? '');
    $konten   = trim($_POST['konten'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $penulis  = trim($_POST['penulis'] ?? '');
    $status   = trim($_POST['status'] ?? 'Draft');

    if (!$judul)    $errors[] = 'Judul tidak boleh kosong.';
    if (!$konten)   $errors[] = 'Konten tidak boleh kosong.';
    if (!$kategori) $errors[] = 'Kategori harus dipilih.';
    if (!$penulis)  $errors[] = 'Penulis tidak boleh kosong.';

    
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
        $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
        $ftype = mime_content_type($_FILES['thumbnail']['tmp_name']);
        if (!in_array($ftype, $allowed)) {
            $errors[] = 'Format gambar tidak valid. Gunakan JPG, PNG, atau WebP.';
        } elseif ($_FILES['thumbnail']['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 3MB.';
        } else {
            $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $thumbnail = 'thumb_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($_FILES['thumbnail']['tmp_name'], UPLOAD_DIR . $thumbnail)) {
                $errors[] = 'Gagal mengupload gambar.';
                $thumbnail = '';
            }
        }
    }

    if (empty($errors)) {

    $stmt = $conn->prepare("INSERT INTO berita (judul, konten, thumbnail, kategori, penulis, status) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('ssssss', $judul, $konten, $thumbnail, $kategori, $penulis, $status);

    if ($stmt->execute()) {
        header('Location: index.php?msg=tambah_berhasil');
        exit;
    } else {
        $errors[] = 'Gagal menyimpan data: ' . $conn->error;
    }
}
}

require_once '../dashboard.php';
?>

<style>
.form-card {
    max-width: 900px;
    overflow: hidden;

    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
}

.form-header {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
}

.form-header-icon {
    display: flex;
    align-items: center;
    justify-content: center;

    width: 40px;
    height: 40px;

    background: var(--primary-light);
    border-radius: 10px;

    color: var(--primary);
    font-size: 16px;
}

.form-header h2 {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-main);
}

.form-header p {
    font-size: 12.5px;
    color: var(--text-muted);
}

.form-body {
    padding: 24px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    margin-bottom: 7px;

    font-size: 13px;
    font-weight: 600;
    color: var(--text-main);
}

.form-label .req {
    margin-left: 3px;
    color: var(--danger);
}

.form-control {
    width: 100%;
    padding: 10px 14px;

    background: #fafafa;
    border: 1.5px solid var(--border);
    border-radius: 9px;
    outline: none;

    font-family: 'Poppins', sans-serif;
    font-size: 13.5px;
    color: var(--text-main);

    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    background: #fff;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control.is-error {
    border-color: var(--danger);
}

textarea.form-control {
    min-height: 200px;
    resize: vertical;
    line-height: 1.7;
}

select.form-control {
    cursor: pointer;
}

.form-hint {
    margin-top: 5px;

    font-size: 11.5px;
    color: var(--text-light);
}

.upload-area {
    position: relative;

    padding: 28px;

    background: #fafafa;
    border: 2px dashed var(--border);
    border-radius: 10px;

    text-align: center;
    cursor: pointer;

    transition: all 0.2s;
}

.upload-area:hover {
    background: var(--primary-light);
    border-color: var(--primary);
}

.upload-area.has-file {
    background: #f0fdf4;
    border-color: var(--success);
    border-style: solid;
}

.upload-icon {
    margin-bottom: 10px;

    font-size: 32px;
    color: var(--text-light);
}

.upload-text {
    margin-bottom: 4px;

    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
}

.upload-sub {
    font-size: 11.5px;
    color: var(--text-light);
}

.upload-input {
    position: absolute;
    inset: 0;

    width: 100%;
    height: 100%;

    opacity: 0;
    cursor: pointer;
}

.preview-img {
    display: none;

    width: 100%;
    max-height: 180px;

    margin-top: 12px;

    border-radius: 8px;
    object-fit: cover;
}

.alert {
    margin-bottom: 18px;
    padding: 12px 18px;
    border-radius: 9px;

    font-size: 13.5px;
    font-weight: 500;
}

.alert-danger {
    background: #fef2f2;
    color: #991b1b;
   
}

.alert ul {
    margin: 6px 0 0 16px;
}

.form-footer {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 18px 24px;

    background: #f8fafc;
    border-top: 1px solid var(--border);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 10px 20px;
    border: none;
    border-radius: 9px;

    font-family: 'Poppins', sans-serif;
    font-size: 13.5px;
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
    transform: translateY(-1px);
    color: black;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
}

.btn-secondary {
    background: #f1f5f9;
    color: var(--text-muted);
}

.btn-secondary:hover {
    background: #e2e8f0;
}

.char-count {
    float: right;

    font-size: 11px;
    color: var(--text-light);
}
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

<div class="form-card">
    <div class="form-header">
        <div class="form-header-icon"><i class="fas fa-plus"></i></div>
        <div>
            <h2>Tambah Berita Baru</h2>
            <p>Isi semua informasi yang diperlukan untuk menerbitkan berita</p>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data">
        <div class="form-body">

            <div class="form-group">
                <label class="form-label">Judul Berita <span class="req">*</span></label>
                <input type="text" name="judul" class="form-control <?= in_array('Judul tidak boleh kosong.',$errors)?'is-error':'' ?>"
                    placeholder="Masukkan judul berita yang menarik..."
                    value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" maxlength="255"
                    oninput="document.getElementById('jc').textContent=this.value.length+'/255'">
                <div class="form-hint"><span id="jc">0/255</span> karakter</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kategori <span class="req">*</span></label>
                    <select name="kategori" class="form-control">
                        <option value="">— Pilih Kategori —</option>
                        <?php foreach (['Akademik','Penelitian','Kemahasiswaan','Pengumuman','Event','Prestasi','Umum'] as $k): ?>
                        <option value="<?= $k ?>" <?= ($_POST['kategori'] ?? '')===$k?'selected':'' ?>><?= $k ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Penulis <span class="req">*</span></label>
                    <input type="text" name="penulis" class="form-control"
                        placeholder="Nama penulis / redaksi"
                        value="<?= htmlspecialchars($_POST['penulis'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Konten Berita <span class="req">*</span></label>
                <textarea name="konten" class="form-control" placeholder="Tulis isi berita di sini..."><?= htmlspecialchars($_POST['konten'] ?? '') ?></textarea>
                <div class="form-hint">Mendukung teks biasa. Gunakan paragraf terpisah untuk keterbacaan.</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Thumbnail Berita</label>
                    <div class="upload-area" id="uploadArea">
                        <input type="file" name="thumbnail" class="upload-input" accept="image/*" onchange="previewThumb(this)">
                        <div class="upload-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                        <div class="upload-text">Klik atau seret gambar ke sini</div>
                        <div class="upload-sub">JPG, PNG, WebP — Maks. 3MB</div>
                        <img id="thumbPreview" class="preview-img" src="" alt="Preview">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Publikasi <span class="req">*</span></label>
                    <select name="status" class="form-control">
                        <option value="Draft" <?= ($_POST['status']??'Draft')==='Draft'?'selected':'' ?>>Draft - Belum dipublikasi</option>
                        <option value="Terbit" <?= ($_POST['status']??'')==='Terbit'?'selected':'' ?>>Terbit - Langsung tampil</option>
                        <option value="Arsip" <?= ($_POST['status']??'')==='Arsip'?'selected':'' ?>>Arsip</option>
                    </select>
                    <div class="form-hint">Draft: hanya terlihat admin. Terbit: tampil di website.</div>
                    
                </div>
            </div>

        </div>

        <div class="form-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Berita
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
            <div style="margin-left:auto;font-size:12px;color:var(--text-light);">
                <i class="fas fa-info-circle"></i> Kolom bertanda <span style="color:var(--danger);">*</span> wajib diisi
            </div>
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
            area.querySelector('.upload-text').textContent = input.files[0].name;
            area.querySelector('.upload-sub').textContent = (input.files[0].size / 1024).toFixed(0) + ' KB';
        };
        reader.readAsDataURL(input.files[0]);
    }
}


const judulInput = document.querySelector('input[name="judul"]');
if (judulInput) document.getElementById('jc').textContent = judulInput.value.length + '/255';
</script>

<?php require_once '../dashboard_footer.php'; ?>
