<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'fakultas_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die('<div style="font-family:Poppins,sans-serif;padding:40px;color:#e74c3c;background:#fff0f0;border-left:4px solid #e74c3c;margin:20px;border-radius:8px;">
        <strong>Koneksi Database Gagal!</strong><br>
        ' . $conn->connect_error . '<br><br>
        Pastikan MySQL aktif dan database <code>db_fakultas</code> sudah dibuat.<br>
        Import file <code>database.sql</code> terlebih dahulu.
    </div>');
}

$conn->set_charset("utf8mb4");

define('BASE_URL', '');
define('UPLOAD_DIR', __DIR__ . '/berita/uploads/');
define('UPLOAD_URL', 'berita/uploads/');
?>
