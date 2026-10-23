    
<?php
require_once 'auth.php';
require_once 'koneksi.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID destinasi tidak valid.');
}

$stmt = $conn->prepare(
    "SELECT id, nama_wisata, deskripsi, kabupaten_kota,
            harga_tiket, gambar, created_at
     FROM destinasi WHERE id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Destinasi</title>
</head>
<body>
    <h1><?= e($data['nama_wisata']) ?></h1>

    <p><strong>ID:</strong> <?= (int) $data['id'] ?></p>
    <p><strong>Lokasi:</strong>
        <?= e($data['kabupaten_kota'] ?: '-') ?>
    </p>
    <p><strong>Harga tiket:</strong> Rp
        <?= number_format((float) $data['harga_tiket'], 0, ',', '.') ?>
    </p>
    <p><strong>Deskripsi:</strong></p>
    <p><?= nl2br(e($data['deskripsi'] ?? '-')) ?></p>

    <?php if (!empty($data['gambar'])): ?>
        <p><strong>Path gambar:</strong> <?= e($data['gambar']) ?></p>
    <?php endif; ?>

    <p><strong>Dibuat:</strong> <?= e($data['created_at']) ?></p>

    <a href="edit_destinasi.php?id=<?= (int) $data['id'] ?>">Edit</a> |
    <a href="destinasi.php">Kembali ke daftar</a>
</body>
</html>
