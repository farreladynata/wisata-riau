
<?php
require_once 'auth.php';
require_once 'koneksi.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$nama = '';
$deskripsi = '';
$lokasi = '';
$harga = '0';
$gambar = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $nama = trim($_POST['nama_wisata'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $lokasi = trim($_POST['kabupaten_kota'] ?? '');
        $harga = trim($_POST['harga_tiket'] ?? '');
        $gambar = trim($_POST['gambar'] ?? '');

        if ($nama === '' || strlen($nama) > 150) {
            $error = 'Nama wisata wajib diisi (maksimal 150 karakter).';
        } elseif (
            !is_numeric($harga) ||
            (float) $harga < 0 ||
            (float) $harga > 9999999999.99
        ) {
            $error = 'Harga tiket tidak valid.';
        } elseif (
            strlen($lokasi) > 100 ||
            strlen($gambar) > 255
        ) {
            $error = 'Lokasi atau path gambar terlalu panjang.';
        } else {
            $hargaDB = number_format((float) $harga, 2, '.', '');

            $stmt = $conn->prepare(
                "INSERT INTO destinasi
                 (nama_wisata, deskripsi, kabupaten_kota,
                  harga_tiket, gambar)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                'sssss',
                $nama,
                $deskripsi,
                $lokasi,
                $hargaDB,
                $gambar
            );

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: destinasi.php?status=ditambah');
                exit;
            }

            $error = 'Data gagal disimpan. Silakan coba lagi.';
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Destinasi</title>
</head>
<body>
    <h1>Tambah Destinasi Wisata</h1>

    <?php if ($error !== ''): ?>
        <p role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <label>Nama wisata</label><br>
        <input name="nama_wisata" maxlength="150"
               value="<?= e($nama) ?>" required><br><br>

        <label>Deskripsi</label><br>
        <textarea name="deskripsi" rows="5"><?= e($deskripsi) ?></textarea><br><br>

        <label>Kabupaten/Kota</label><br>
        <input name="kabupaten_kota" maxlength="100"
               value="<?= e($lokasi) ?>"><br><br>

        <label>Harga tiket (Rp)</label><br>
        <input type="number" name="harga_tiket" min="0"
               max="9999999999.99" step="0.01"
               value="<?= e($harga) ?>" required><br><br>

        <label>Path gambar (opsional)</label><br>
        <input name="gambar" maxlength="255"
               value="<?= e($gambar) ?>"
               placeholder="uploads/pantai.jpg"><br><br>

        <button type="submit">Simpan Destinasi</button>
        <a href="destinasi.php">Batal</a>
    </form>
</body>
</html>
