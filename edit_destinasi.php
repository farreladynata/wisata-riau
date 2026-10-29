<?php
require_once 'auth.php';
require_once 'koneksi.php';

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Validasi ID destinasi
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID destinasi tidak valid.');
}

// Ambil data destinasi
$stmt = $conn->prepare(
    "SELECT *
     FROM destinasi_wisata
     WHERE id_wisata = ?"
);

if (!$stmt) {
    http_response_code(500);
    exit('Gagal menyiapkan query destinasi.');
}

$stmt->bind_param('i', $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
}

// Ambil semua kategori
$kategoriResult = $conn->query(
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);

if (!$kategoriResult) {
    http_response_code(500);
    exit('Gagal mengambil data kategori.');
}

$kategoriList = [];

while ($kategori = $kategoriResult->fetch_assoc()) {
    $kategoriList[] = $kategori;
}

$error = '';

// Proses penyimpanan perubahan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $nama = trim($_POST['nama_wisata'] ?? '');
        $kategoriId = filter_var(
            $_POST['id_kategori'] ?? '',
            FILTER_VALIDATE_INT
        );
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $lokasi = trim($_POST['kabupaten_kota'] ?? '');
        $latitudeInput = trim($_POST['latitude'] ?? '');
        $longitudeInput = trim($_POST['longitude'] ?? '');
        $jam = trim($_POST['jam_operasional'] ?? '');
        $harga = trim($_POST['harga_tiket'] ?? '');
        $foto = trim($_POST['foto'] ?? '');
        $link_maps = trim($_POST['link_maps'] ?? '');

        // Pertahankan input jika validasi gagal
        $data['nama_wisata'] = $nama;
        $data['id_kategori'] = $kategoriId ?: 0;
        $data['deskripsi'] = $deskripsi;
        $data['alamat'] = $alamat;
        $data['kabupaten_kota'] = $lokasi;
        $data['latitude'] = $latitudeInput;
        $data['longitude'] = $longitudeInput;
        $data['jam_operasional'] = $jam;
        $data['harga_tiket'] = $harga;
        $data['foto'] = $foto;
        $data['link_maps'] = $link_maps;

        $kategoriValid = false;

        foreach ($kategoriList as $kategori) {
            if ((int) $kategori['id_kategori'] === (int) $kategoriId) {
                $kategoriValid = true;
                break;
            }
        }

        if ($nama === '' || strlen($nama) > 150) {
            $error = 'Nama wisata wajib diisi, maksimal 150 karakter.';
        } elseif (!$kategoriId || !$kategoriValid) {
            $error = 'Silakan pilih kategori yang valid.';
        } elseif (strlen($alamat) > 65535) {
            $error = 'Alamat terlalu panjang.';
        } elseif (strlen($lokasi) > 100) {
            $error = 'Kabupaten/kota maksimal 100 karakter.';
        } elseif (strlen($jam) > 150) {
            $error = 'Jam operasional maksimal 150 karakter.';
        } elseif (strlen($foto) > 255) {
            $error = 'Path foto maksimal 255 karakter.';
        } elseif (strlen($link_maps) > 2000) {
            $error = 'Link Google Maps maksimal 2000 karakter.';
        } elseif (
            $link_maps !== '' &&
            (
                !filter_var($link_maps, FILTER_VALIDATE_URL) ||
                !in_array(
                    strtolower((string) parse_url($link_maps, PHP_URL_SCHEME)),
                    ['http', 'https'],
                    true
                ) ||
                !in_array(
                    strtolower((string) parse_url($link_maps, PHP_URL_HOST)),
                    ['maps.app.goo.gl', 'goo.gl', 'google.com', 'www.google.com',
                     'maps.google.com', 'maps.google.co.id', 'www.google.co.id']
                    ,
                    true
                )
            )
        ) {
            $error = 'Link harus berupa URL Google Maps yang valid.';
        } elseif (
            !is_numeric($harga) ||
            (float) $harga < 0 ||
            (float) $harga > 9999999999.99
        ) {
            $error = 'Harga tiket tidak valid.';
        } elseif (
            ($latitudeInput !== '' && !is_numeric($latitudeInput)) ||
            ($longitudeInput !== '' && !is_numeric($longitudeInput))
        ) {
            $error = 'Latitude dan longitude harus berupa angka.';
        } elseif (
            ($latitudeInput !== '' &&
                ((float) $latitudeInput < -90 ||
                 (float) $latitudeInput > 90)) ||
            ($longitudeInput !== '' &&
                ((float) $longitudeInput < -180 ||
                 (float) $longitudeInput > 180))
        ) {
            $error = 'Rentang koordinat tidak valid.';
        } else {
            $latitude = $latitudeInput === ''
                ? null
                : (float) $latitudeInput;

            $longitude = $longitudeInput === ''
                ? null
                : (float) $longitudeInput;

            $hargaDB = number_format(
                (float) $harga,
                2,
                '.',
                ''
            );

            $stmt = $conn->prepare(
                "UPDATE destinasi_wisata
                 SET id_kategori = ?,
                     nama_wisata = ?,
                     deskripsi = ?,
                     alamat = ?,
                     kabupaten_kota = ?,
                     latitude = ?,
                     longitude = ?,
                     jam_operasional = ?,
                     harga_tiket = ?,
                     foto = ?,
                     link_maps = ?
                 WHERE id_wisata = ?"
            );

            if (!$stmt) {
                error_log('Prepare update destinasi gagal: ' . $conn->error);
                $error = 'Gagal menyiapkan perubahan data.';
            } else {
                $stmt->bind_param(
                    'issssddssssi',
                    $kategoriId,
                    $nama,
                    $deskripsi,
                    $alamat,
                    $lokasi,
                    $latitude,
                    $longitude,
                    $jam,
                    $hargaDB,
                    $foto,
                    $link_maps,
                    $id
                );

                if ($stmt->execute()) {
                    $stmt->close();

                    header('Location: destinasi.php?status=diubah');
                    exit;
                }

                error_log('Update destinasi gagal: ' . $stmt->error);
                $error = 'Data gagal diperbarui. Silakan coba lagi.';
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Edit Destinasi - Wisata Riau</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f3f7f5;
            color: #263238;
        }

        .container {
            max-width: 760px;
            margin: auto;
            padding: 28px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
            color: #176b45;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd5d0;
            border-radius: 6px;
            font: inherit;
        }

        textarea {
            resize: vertical;
        }

        .error {
            padding: 12px;
            background: #fde8e7;
            color: #a3211b;
            border-radius: 6px;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 24px;
        }

        button,
        .back-link {
            display: inline-block;
            padding: 11px 16px;
            border: none;
            border-radius: 6px;
            background: #176b45;
            color: white;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }

        .back-link {
            background: #e9efec;
            color: #263b30;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 18px;
            }
        }
    </style>
</head>

<body>
    <main class="container">
        <h1>Edit Destinasi Wisata</h1>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token'] ?? '') ?>"
            >

            <label for="nama_wisata">Nama Wisata</label>
            <input
                id="nama_wisata"
                name="nama_wisata"
                maxlength="150"
                value="<?= e($data['nama_wisata']) ?>"
                required
            >

            <label for="id_kategori">Kategori</label>
            <select id="id_kategori" name="id_kategori" required>
                <option value="">-- Pilih Kategori --</option>

                <?php foreach ($kategoriList as $kategori): ?>
                    <option
                        value="<?= (int) $kategori['id_kategori'] ?>"
                        <?= (int) $data['id_kategori'] === (int) $kategori['id_kategori'] ? 'selected' : '' ?>
                    >
                        <?= e($kategori['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="deskripsi">Deskripsi</label>
            <textarea
                id="deskripsi"
                name="deskripsi"
                rows="5"
            ><?= e($data['deskripsi'] ?? '') ?></textarea>

            <label for="alamat">Alamat</label>
            <textarea
                id="alamat"
                name="alamat"
                rows="3"
            ><?= e($data['alamat'] ?? '') ?></textarea>

            <label for="kabupaten_kota">Kabupaten/Kota</label>
            <input
                id="kabupaten_kota"
                name="kabupaten_kota"
                maxlength="100"
                value="<?= e($data['kabupaten_kota'] ?? '') ?>"
            >

            <label for="latitude">Latitude (opsional)</label>
            <input
                id="latitude"
                type="number"
                name="latitude"
                min="-90"
                max="90"
                step="any"
                value="<?= e($data['latitude'] ?? '') ?>"
            >

            <label for="longitude">Longitude (opsional)</label>
            <input
                id="longitude"
                type="number"
                name="longitude"
                min="-180"
                max="180"
                step="any"
                value="<?= e($data['longitude'] ?? '') ?>"
            >

            <label for="jam_operasional">Jam Operasional</label>
            <input
                id="jam_operasional"
                name="jam_operasional"
                maxlength="150"
                placeholder="Contoh: 08.00 - 17.00"
                value="<?= e($data['jam_operasional'] ?? '') ?>"
            >

            <label for="harga_tiket">Harga Tiket (Rp)</label>
            <input
                id="harga_tiket"
                type="number"
                name="harga_tiket"
                min="0"
                max="9999999999.99"
                step="0.01"
                value="<?= e($data['harga_tiket']) ?>"
                required
            >

            <label for="foto">Path Foto (opsional)</label>
            <input
                id="foto"
                name="foto"
                maxlength="255"
                placeholder="Contoh: uploads/pantai.jpg"
                value="<?= e($data['foto'] ?? '') ?>"
            >

            <label for="link_maps">Link Lokasi Google Maps (opsional)</label>
            <input
                id="link_maps"
                name="link_maps"
                type="url"
                maxlength="2000"
                placeholder="https://maps.app.goo.gl/..."
                value="<?= e($data['link_maps'] ?? '') ?>"
            >
            <small>Salin link dari Google Maps lalu tempel di sini. Link akan ditampilkan sebagai tombol lokasi untuk pengunjung.</small>

            <div class="buttons">
                <button type="submit">Simpan Perubahan</button>
                <a class="back-link" href="destinasi.php">Batal</a>
            </div>
        </form>
    </main>
</body>
</html>
```