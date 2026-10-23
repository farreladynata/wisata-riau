
<?php
require_once 'auth.php';
require_once 'koneksi.php';

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Ambil kategori dari database
$kategoriResult = $conn->query(
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);

if (!$kategoriResult) {
    die('Gagal mengambil data kategori.');
}

$kategoriList = $kategoriResult->fetch_all(MYSQLI_ASSOC);

$error = '';

$nama_wisata = '';
$id_kategori = '';
$deskripsi = '';
$alamat = '';
$kabupaten_kota = '';
$latitude = '';
$longitude = '';
$jam_operasional = '';
$harga_tiket = '';
$foto = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF
    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrf) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrf)
    ) {
        http_response_code(403);
        exit('Token keamanan tidak valid. Muat ulang halaman.');
    }

    // Ambil data form
    $nama_wisata = trim($_POST['nama_wisata'] ?? '');
    $id_kategori = trim($_POST['id_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $kabupaten_kota = trim($_POST['kabupaten_kota'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $jam_operasional = trim($_POST['jam_operasional'] ?? '');
    $harga_tiket = trim($_POST['harga_tiket'] ?? '');
    $foto = trim($_POST['foto'] ?? '');

    // Validasi nama
    if (
        $nama_wisata === '' ||
        mb_strlen($nama_wisata) > 150
    ) {
        $error = 'Nama wisata wajib diisi dan maksimal 150 karakter.';
    }

    // Validasi kategori langsung ke database
    elseif (
        !is_string($id_kategori) ||
        !ctype_digit($id_kategori) ||
        (int) $id_kategori < 1
    ) {
        $error = 'Silakan pilih kategori yang valid.';
    } else {
        $kategoriIdCek = (int) $id_kategori;

        $stmtKategori = $conn->prepare(
            "SELECT id_kategori
             FROM kategori
             WHERE id_kategori = ?"
        );

        $stmtKategori->bind_param('i', $kategoriIdCek);
        $stmtKategori->execute();

        $kategoriValid =
            $stmtKategori->get_result()->num_rows > 0;

        $stmtKategori->close();

        if (!$kategoriValid) {
            $error = 'Kategori tidak ditemukan di database.';
        }
    }

    // Validasi harga tiket
    if ($error === '') {
        if (
            $harga_tiket !== '' &&
            (
                !is_numeric($harga_tiket) ||
                !is_finite((float) $harga_tiket) ||
                (float) $harga_tiket < 0 ||
                (float) $harga_tiket > 9999999999.99
            )
        ) {
            $error = 'Harga tiket tidak valid.';
        }
    }

    // Validasi koordinat
    if ($error === '') {
        if (
            (
                $latitude !== '' &&
                (
                    !is_numeric($latitude) ||
                    !is_finite((float) $latitude) ||
                    (float) $latitude < -90 ||
                    (float) $latitude > 90
                )
            ) ||
            (
                $longitude !== '' &&
                (
                    !is_numeric($longitude) ||
                    !is_finite((float) $longitude) ||
                    (float) $longitude < -180 ||
                    (float) $longitude > 180
                )
            )
        ) {
            $error = 'Latitude atau longitude tidak valid.';
        }
    }

    // Validasi panjang data
    if ($error === '') {
        if (
            mb_strlen($kabupaten_kota) > 100 ||
            mb_strlen($jam_operasional) > 150 ||
            mb_strlen($foto) > 255
        ) {
            $error = 'Kabupaten/kota, jam operasional, atau foto terlalu panjang.';
        }
    }

    // Simpan data jika semua validasi lolos
    if ($error === '') {
        $kategoriId = (int) $id_kategori;
        $harga = $harga_tiket === ''
            ? 0
            : (float) $harga_tiket;

        $lat = $latitude === ''
            ? null
            : (float) $latitude;

        $lon = $longitude === ''
            ? null
            : (float) $longitude;

        $deskripsiValue = $deskripsi === ''
            ? null
            : $deskripsi;

        $alamatValue = $alamat === ''
            ? null
            : $alamat;

        $kotaValue = $kabupaten_kota === ''
            ? null
            : $kabupaten_kota;

        $jamValue = $jam_operasional === ''
            ? null
            : $jam_operasional;

        $fotoValue = $foto === ''
            ? null
            : $foto;

        $stmt = $conn->prepare(
            "INSERT INTO destinasi_wisata (
                id_kategori,
                nama_wisata,
                deskripsi,
                alamat,
                kabupaten_kota,
                latitude,
                longitude,
                jam_operasional,
                harga_tiket,
                foto
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'issssddssd',
            $kategoriId,
            $nama_wisata,
            $deskripsiValue,
            $alamatValue,
            $kotaValue,
            $lat,
            $lon,
            $jamValue,
            $harga,
            $fotoValue
        );

        if ($stmt->execute()) {
            $stmt->close();

            header('Location: destinasi.php?status=ditambah');
            exit;
        }

        // Catat error di log server tanpa membocorkan detail SQL
        error_log('Gagal menyimpan destinasi: ' . $stmt->error);

        $error = 'Gagal menyimpan destinasi. Periksa konfigurasi database atau log PHP.';
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Destinasi - Wisata Riau</title>
</head>
<body>

    <h1>Tambah Destinasi Wisata</h1>

    <p>
        <a href="destinasi.php">Kembali ke daftar destinasi</a>
    </p>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= e($error) ?>
        </p>
    <?php endif; ?>

    <?php if (count($kategoriList) === 0): ?>

        <p>Belum ada kategori. Tambahkan kategori terlebih dahulu.</p>

    <?php else: ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token'] ?? '') ?>"
            >

            <p>
                <label for="nama_wisata">Nama Wisata *</label><br>
                <input
                    type="text"
                    id="nama_wisata"
                    name="nama_wisata"
                    maxlength="150"
                    required
                    value="<?= e($nama_wisata) ?>"
                >
            </p>

            <p>
                <label for="id_kategori">Kategori *</label><br>
                <select id="id_kategori" name="id_kategori" required>
                    <option value="">-- Pilih Kategori --</option>

                    <?php foreach ($kategoriList as $kategori): ?>
                        <option
                            value="<?= (int) $kategori['id_kategori'] ?>"
                            <?= (string) $kategori['id_kategori'] === $id_kategori ? 'selected' : '' ?>
                        >
                            <?= e($kategori['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </p>

            <p>
                <label for="deskripsi">Deskripsi</label><br>
                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    rows="4"
                ><?= e($deskripsi) ?></textarea>
            </p>

            <p>
                <label for="alamat">Alamat</label><br>
                <textarea
                    id="alamat"
                    name="alamat"
                    rows="3"
                ><?= e($alamat) ?></textarea>
            </p>

            <p>
                <label for="kabupaten_kota">Kabupaten/Kota</label><br>
                <input
                    type="text"
                    id="kabupaten_kota"
                    name="kabupaten_kota"
                    maxlength="100"
                    value="<?= e($kabupaten_kota) ?>"
                >
            </p>

            <p>
                <label for="latitude">Latitude</label><br>
                <input
                    type="number"
                    step="any"
                    min="-90"
                    max="90"
                    id="latitude"
                    name="latitude"
                    value="<?= e($latitude) ?>"
                >
            </p>

            <p>
                <label for="longitude">Longitude</label><br>
                <input
                    type="number"
                    step="any"
                    min="-180"
                    max="180"
                    id="longitude"
                    name="longitude"
                    value="<?= e($longitude) ?>"
                >
            </p>

            <p>
                <label for="jam_operasional">Jam Operasional</label><br>
                <input
                    type="text"
                    id="jam_operasional"
                    name="jam_operasional"
                    maxlength="150"
                    placeholder="Contoh: Setiap hari, 08.00-17.00"
                    value="<?= e($jam_operasional) ?>"
                >
            </p>

            <p>
                <label for="harga_tiket">Harga Tiket (Rp)</label><br>
                <input
                    type="number"
                    id="harga_tiket"
                    name="harga_tiket"
                    min="0"
                    max="9999999999.99"
                    step="0.01"
                    value="<?= e($harga_tiket) ?>"
                >
            </p>

            <p>
                <label for="foto">Nama/path foto</label><br>
                <input
                    type="text"
                    id="foto"
                    name="foto"
                    maxlength="255"
                    placeholder="Contoh: uploads/pantai.jpg"
                    value="<?= e($foto) ?>"
                >
                <br>
                <small>
                    Untuk sekarang masukkan nama atau path foto.
                    Fitur upload gambar akan dibuat terpisah.
                </small>
            </p>

            <button type="submit">Simpan Destinasi</button>

        </form>

    <?php endif; ?>

</body>
</html>
