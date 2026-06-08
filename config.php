<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'fakultas_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

define('BASE_URL', '');
define('UPLOAD_DIR', __DIR__ . '/berita/uploads/');
define('UPLOAD_URL', 'berita/uploads/');
?>
