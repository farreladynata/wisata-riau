```php
<?php

require_once 'auth.php';
require_once 'koneksi.php';

// Pastikan permintaan menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: destinasi.php');
    exit;
}

// Ambil ID destinasi
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

// Validasi ID
if ($id < 1) {
    header('Location: destinasi.php?status=gagal');
    exit;
}

// Validasi token keamanan CSRF
$token = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $token)
) {
    http_response_code(403);
    exit('Token keamanan tidak valid. Silakan muat ulang halaman.');
}

// Siapkan query untuk tabel destinasi_wisata
$stmt = $conn->prepare(
    'DELETE FROM destinasi_wisata WHERE id_wisata = ?'
);

if (!$stmt) {
    error_log('Gagal menyiapkan query hapus: ' . $conn->error);
    http_response_code(500);
    exit('Gagal menyiapkan penghapusan destinasi.');
}

// Jalankan penghapusan
$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    error_log('Gagal menghapus destinasi: ' . $stmt->error);
    $stmt->close();
    http_response_code(500);
    exit('Destinasi gagal dihapus dari database.');
}

// Periksa hasil penghapusan
$jumlahTerhapus = $stmt->affected_rows;
$stmt->close();

if ($jumlahTerhapus < 1) {
    header('Location: destinasi.php?status=gagal');
    exit;
}

// Berhasil dihapus
header('Location: destinasi.php?status=dihapus');
exit;
