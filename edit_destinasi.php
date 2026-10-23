
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

function ambilDestinasi($conn, $id) {
    $stmt = $conn->prepare(
        "SELECT * FROM destinasi WHERE id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $data;
}

$data = ambilDestinasi($conn, $id);

if (!$data) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
}

$error = '';

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
                "UPDATE destinasi
                 SET nama_wisata = ?, deskripsi = ?,
                     kabupaten_kota = ?, harga_tiket = ?,
                     gambar = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'sssssi',
                $nama,
                $deskripsi,
                $lokasi,
                $hargaDB,
                $gambar,
                $id
            );

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: destinasi.php?status=diubah');
                exit;
            }

            $error = 'Data gagal diperbarui. Silakan coba lagi.';
            $stmt->close();
        }
    }

    // Tampilkan kembali data yang dikirim jika validasi gagal.
    $data['nama_wisata'] = $nama ?? $data['nama_wisata'];
    $data['deskripsi'] = $deskripsi ?? $data['deskripsi'];
    $data['kabupaten_kota'] = $lokasi ?? $data['kabupaten_kota'];
    $data['harga_tiket'] = $harga ?? $data['harga_tiket'];
    $data['gambar'] = $gambar ?? $data['gambar'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Destinasi</title>
</head>
<body>
    <h1>Edit Destinasi Wisata</h1>

    <?php if ($error !== ''): ?>
        <p role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <label>Nama wisata</label><br>
        <input name="nama_wisata" maxlength="150"
               value="<?= e($data['nama_wisata']) ?>" required><br><br>

        <label>Deskripsi</label><br>
        <textarea name="deskripsi" rows="5"><?= e($data['deskripsi']) ?></textarea><br><br>

        <label>Kabupaten/Kota</label><br>
        <input name="kabupaten_kota" maxlength="100"
               value="<?= e($data['kabupaten_kota']) ?>"><br><br>

        <label>Harga tiket (Rp)</label><br>
        <input type="number" name="harga_tiket" min="0"
               max="9999999999.99" step="0.01"
               value="<?= e($data['harga_tiket']) ?>" required><br><br>

        <label>Path gambar</label><br>
        <input name="gambar" maxlength="255"
               value="<?= e($data['gambar']) ?>"><br><br>

        <button type="submit">Simpan Perubahan</button>
        <a href="destinasi.php">Batal</a>
    </form>
</body>
</html>
