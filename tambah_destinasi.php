
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
$link_maps = '';

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
    $link_maps = trim($_POST['link_maps'] ?? '');

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

        $linkMapsValue = $link_maps === ''
            ? null
            : $link_maps;

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
                foto,
                link_maps
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'issssddsdss',
            $kategoriId,
            $nama_wisata,
            $deskripsiValue,
            $alamatValue,
            $kotaValue,
            $lat,
            $lon,
            $jamValue,
            $harga,
            $fotoValue,
            $linkMapsValue
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
    <meta name="theme-color" content="#16834a">
    <title>Tambah Destinasi - Wisata Riau</title>
    <link rel="stylesheet" href="assets/tambah-destinasi.css">
</head>
<body>

    <header class="td-navbar">
        <div class="td-navbar-inner">
            <a href="index.php" class="td-brand">
                <span class="td-brand-icon">🌿</span>
                <span>Wisata Riau</span>
            </a>
            <span class="td-navbar-label">Panel Pengelolaan Destinasi</span>
        </div>
    </header>

    <main class="td-container">

        <nav class="td-breadcrumb" aria-label="Breadcrumb">
            <a href="admin.php">Dashboard</a>
            <span>/</span>
            <a href="destinasi.php">Destinasi</a>
            <span>/</span>
            <span>Tambah Destinasi</span>
        </nav>

        <section class="td-heading">
            <span class="td-eyebrow">✦ Kelola wisata</span>
            <h1>Tambah Destinasi</h1>
            <p>Lengkapi informasi destinasi wisata baru agar pengunjung dapat mengenalnya.</p>
        </section>

        <section class="td-card">

            <div class="td-card-header">
                <div class="td-section-icon">📍</div>
                <div>
                    <h2>Informasi Destinasi</h2>
                    <p>Kolom bertanda <span class="td-required">*</span> wajib diisi.</p>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="td-alert" role="alert" style="margin: 20px 28px 0;">
                    <strong>Gagal menyimpan data</strong><br>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if (count($kategoriList) === 0): ?>

                <div class="td-empty">
                    <strong>Belum ada kategori wisata.</strong>
                    <p>Tambahkan kategori terlebih dahulu sebelum membuat destinasi baru.</p>
                </div>

            <?php else: ?>

                <form method="POST" action="" class="td-form">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['csrf_token'] ?? '') ?>"
                    >

                    <section class="td-form-section">
                        <h3 class="td-section-title">Informasi utama</h3>

                        <div class="td-form-grid">

                            <div class="td-field td-field-full">
                                <label for="nama_wisata">
                                    Nama Destinasi <span class="td-required">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_wisata"
                                    name="nama_wisata"
                                    maxlength="150"
                                    placeholder="Contoh: Taman Wisata Alam Mayang"
                                    required
                                    value="<?= e($nama_wisata) ?>"
                                >
                            </div>

                            <div class="td-field td-field-full">
                                <label for="id_kategori">
                                    Kategori Wisata <span class="td-required">*</span>
                                </label>
                                <select id="id_kategori" name="id_kategori" required>
                                    <option value="">-- Pilih kategori --</option>
                                    <?php foreach ($kategoriList as $kategori): ?>
                                        <option
                                            value="<?= (int) $kategori['id_kategori'] ?>"
                                            <?= (string) $kategori['id_kategori'] === $id_kategori ? 'selected' : '' ?>
                                        >
                                            <?= e($kategori['nama_kategori']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="td-field td-field-full">
                                <label for="deskripsi">Deskripsi Destinasi</label>
                                <textarea
                                    id="deskripsi"
                                    name="deskripsi"
                                    placeholder="Ceritakan daya tarik dan hal menarik dari destinasi ini..."
                                ><?= e($deskripsi) ?></textarea>
                                <small class="td-help">
                                    Jelaskan daya tarik destinasi agar pengunjung mendapat gambaran yang jelas.
                                </small>
                            </div>

                        </div>
                    </section>

                    <section class="td-form-section">
                        <h3 class="td-section-title">Lokasi dan akses</h3>

                        <div class="td-form-grid">

                            <div class="td-field td-field-full">
                                <label for="alamat">Alamat Lengkap</label>
                                <textarea
                                    id="alamat"
                                    name="alamat"
                                    rows="3"
                                    placeholder="Masukkan alamat atau petunjuk lokasi"
                                ><?= e($alamat) ?></textarea>
                            </div>

                            <div class="td-field td-field-full">
                                <label for="kabupaten_kota">Kabupaten / Kota</label>
                                <input
                                    type="text"
                                    id="kabupaten_kota"
                                    name="kabupaten_kota"
                                    maxlength="100"
                                    placeholder="Contoh: Pekanbaru"
                                    value="<?= e($kabupaten_kota) ?>"
                                >
                            </div>

                            <div class="td-field">
                                <label for="latitude">Latitude</label>
                                <input
                                    type="number"
                                    step="any"
                                    min="-90"
                                    max="90"
                                    id="latitude"
                                    name="latitude"
                                    placeholder="Contoh: 0.5071"
                                    value="<?= e($latitude) ?>"
                                >
                                <small class="td-help">Rentang -90 hingga 90.</small>
                            </div>

                            <div class="td-field">
                                <label for="longitude">Longitude</label>
                                <input
                                    type="number"
                                    step="any"
                                    min="-180"
                                    max="180"
                                    id="longitude"
                                    name="longitude"
                                    placeholder="Contoh: 101.4478"
                                    value="<?= e($longitude) ?>"
                                >
                                <small class="td-help">Rentang -180 hingga 180.</small>
                            </div>

                            <div class="td-field td-field-full">
                                <label for="link_maps">Link Lokasi Google Maps</label>
                                <input
                                    type="url"
                                    id="link_maps"
                                    name="link_maps"
                                    maxlength="2000"
                                    placeholder="https://maps.app.goo.gl/..."
                                    value="<?= e($link_maps) ?>"
                                >
                                <small class="td-help">
                                    Opsional. Buka Google Maps, pilih lokasi wisata, klik Bagikan lalu Salin link dan tempel di sini.
                                </small>
                            </div>

                        </div>
                    </section>

                    <section class="td-form-section">
                        <h3 class="td-section-title">Informasi kunjungan</h3>

                        <div class="td-form-grid">

                            <div class="td-field">
                                <label for="jam_operasional">Jam Operasional</label>
                                <input
                                    type="text"
                                    id="jam_operasional"
                                    name="jam_operasional"
                                    maxlength="150"
                                    placeholder="Contoh: 08.00–17.00"
                                    value="<?= e($jam_operasional) ?>"
                                >
                            </div>

                            <div class="td-field">
                                <label for="harga_tiket">Harga Tiket (Rp)</label>
                                <input
                                    type="number"
                                    id="harga_tiket"
                                    name="harga_tiket"
                                    min="0"
                                    max="9999999999.99"
                                    step="0.01"
                                    placeholder="0"
                                    value="<?= e($harga_tiket) ?>"
                                >
                                <small class="td-help">
                                    Isi 0 atau kosong jika tidak ada biaya tiket.
                                </small>
                            </div>

                            <div class="td-field td-field-full">
                                <label for="foto">Path Foto Destinasi</label>
                                <input
                                    type="text"
                                    id="foto"
                                    name="foto"
                                    maxlength="255"
                                    placeholder="Contoh: uploads/pantai.jpg"
                                    value="<?= e($foto) ?>"
                                >
                                <small class="td-help">
                                    Masukkan path foto yang sudah tersedia. Fitur upload foto belum termasuk dalam form ini.
                                </small>
                            </div>

                        </div>
                    </section>

                    <div class="td-form-actions">
                        <a href="destinasi.php" class="td-button td-button-secondary">
                            ← Kembali
                        </a>

                        <button type="submit" class="td-button td-button-primary">
                            ✓ Simpan Destinasi
                        </button>
                    </div>

                </form>

            <?php endif; ?>

        </section>

    </main>

    <footer class="td-footer">
        Wisata Riau · Sistem Informasi Destinasi Wisata
    </footer>

</body>
</html>
