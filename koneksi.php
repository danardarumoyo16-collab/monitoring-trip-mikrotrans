<?php
$host = "localhost";
$user = "root";      // sesuaikan username database Anda
$pass = "";          // sesuaikan password database Anda
$db   = "db_mikrotrans";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
?>