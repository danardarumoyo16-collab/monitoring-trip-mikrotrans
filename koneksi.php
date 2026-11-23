<?php
// Pengaturan koneksi database untuk db_mikrotrans
// Nilai default diarahkan ke lokal; sesuaikan saat deployment ke server.

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'db_mikrotrans';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();
if (!$conn) {
    die('Gagal menginisialisasi koneksi database.');
}

$connected = @$conn->real_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

if (!$connected) {
    error_log('Koneksi MySQL gagal: ' . $conn->connect_error);
    die('Koneksi ke database gagal. Silakan periksa konfigurasi server atau database.');
}

if (!$conn->set_charset('utf8mb4')) {
    error_log('Gagal mengatur charset utf8mb4: ' . $conn->error);
}
