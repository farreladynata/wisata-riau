
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';

// Escape output agar aman ditampilkan dalam HTML
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Periksa status admin
$role = $_SESSION['role'] ?? $_SESSION['nama_role'] ?? '';
$isAdmin = isset($_SESSION['user_id'])
    && strtolower((string) $role) === 'admin';

// Validasi ID destinasi
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID destinasi tidak valid.');
}

// Ambil data destinasi dan kategori
$sql = "SELECT d.*, k.nama_kategori
        FROM destinasi_wisata AS d
        LEFT JOIN kategori AS k
            ON d.id_kategori = k.id_kategori
        WHERE d.id_wisata = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    error_log('Query detail destinasi gagal: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Terjadi kesalahan saat mengambil data destinasi.');
}

mysqli_stmt_bind_param($stmt, 'i', $id);

if (!mysqli_stmt_execute($stmt)) {
    error_log('Eksekusi query detail destinasi gagal.');
    http_response_code(500);
    exit('Data destinasi gagal dimuat.');
}

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
}

// Informasi destinasi
$namaWisata = $data['nama_wisata'] ?? 'Destinasi Wisata';
$lokasi = $data['kabupaten_kota'] ?? '';
$alamat = $data['alamat'] ?? '';
$deskripsi = $data['deskripsi'] ?? '';
$kategori = $data['nama_kategori'] ?? 'Wisata Riau';
$harga = (float) ($data['harga_tiket'] ?? 0);
$jamOperasional = $data['jam_operasional'] ?? '';

// Siapkan path foto
$foto = trim((string) ($data['foto'] ?? ''));

if (
    $foto !== '' &&
    strpos($foto, 'uploads/') !== 0 &&
    strpos($foto, 'assets/') !== 0 &&
    !preg_match('~^https?://~i', $foto)
) {
    $foto = 'uploads/' . basename($foto);
}

// Buat tautan Google Maps
$latitude = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;

$hasCoordinates =
    $latitude !== null &&
    $longitude !== null &&
    $latitude !== '' &&
    $longitude !== '' &&
    is_numeric($latitude) &&
    is_numeric($longitude) &&
    abs((float) $latitude) <= 90 &&
    abs((float) $longitude) <= 180;

if ($hasCoordinates) {
    $mapsQuery = $latitude . ',' . $longitude;
} else {
    $parts = [$namaWisata];

    if (trim((string) $lokasi) !== '') {
        $parts[] = $lokasi;
    }

    if (trim((string) $alamat) !== '') {
        $parts[] = $alamat;
    }

    $mapsQuery = implode(', ', $parts);
}

$mapsUrl = 'https://www.google.com/maps/search/?api=1&query='
    . urlencode($mapsQuery);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta
        name="description"
        content="<?= e('Informasi ' . $namaWisata . ' di ' . $lokasi . '. Temukan lokasi dan informasi wisata di Riau.') ?>"
    >

    <title><?= e($namaWisata) ?> — Wisata Riau</title>

    <style>
        :root {
            --green: #176b45;
            --green-dark: #103d29;
            --green-light: #eaf5ed;
            --text: #26372d;
            --muted: #788279;
            --border: #e4eae5;
            --background: #f7f9f7;
            --white: #fff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Inter, -apple-system, BlinkMacSystemFont,
                "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background: var(--background);
            line-height: 1.7;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }

        .nav-inner {
            width: min(1100px, 90%);
            min-height: 74px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .brand {
            color: var(--green);
            font-size: 22px;
            font-weight: 850;
            letter-spacing: -.7px;
        }

        .brand span {
            color: #d99c25;
        }

        .nav-link {
            color: var(--green);
            font-size: 13px;
            font-weight: 750;
        }

        .container {
            width: min(900px, 90%);
            margin: 34px auto 65px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--green);
            font-size: 14px;
            font-weight: 750;
        }

        .card {
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 17px;
            box-shadow: 0 12px 40px #153c2509;
        }

        .photo-wrap {
            position: relative;
            width: 100%;
            height: 390px;
            overflow: hidden;
            background: #e7f0e8;
        }

        .photo {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .photo-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #eaf5ed, #d4e8d8);
            color: var(--green);
            font-size: 58px;
        }

        .content {
            padding: 32px;
        }

        .category {
            display: inline-block;
            padding: 5px 12px;
            margin-bottom: 13px;
            color: var(--green);
            background: var(--green-light);
            border-radius: 30px;
            font-size: 12px;
            font-weight: 800;
        }

        h1 {
            margin-bottom: 8px;
            font-size: clamp(28px, 5vw, 40px);
            line-height: 1.2;
            letter-spacing: -1px;
            overflow-wrap: anywhere;
        }

        .location {
            margin-bottom: 26px;
            color: var(--muted);
            font-size: 14px;
        }

        .info-row {
            padding: 19px 0;
            border-top: 1px solid var(--border);
        }

        .info-label {
            margin-bottom: 5px;
            color: #536258;
            font-size: 13px;
            font-weight: 750;
        }

        .description {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .price {
            color: var(--green);
            font-size: 21px;
            font-weight: 850;
        }

        .coordinates {
            color: var(--muted);
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 28px;
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            min-height: 43px;
            padding: 11px 16px;
            border: 1px solid transparent;
            border-radius: 9px;
            background: var(--green);
            color: white;
            font-size: 13px;
            font-weight: 750;
            transition: background .2s, transform .2s;
        }

        .button:hover {
            transform: translateY(-1px);
            background: var(--green-dark);
        }

        .button-secondary {
            background: #edf2ee;
            color: var(--text);
            border-color: var(--border);
        }

        .button-secondary:hover {
            background: #dfe8e1;
        }

        .button-maps {
            background: #e7f2e9;
            color: var(--green);
            border-color: #cfe4d4;
        }

        .button-maps:hover {
            background: #d5e9d9;
        }

        .admin-note {
            margin-top: 20px;
            padding: 12px 15px;
            border: 1px solid #dcebe0;
            border-radius: 9px;
            background: #f2f7f3;
            color: var(--muted);
            font-size: 12px;
        }

        footer {
            padding: 28px 5%;
            background: var(--green-dark);
            color: #dce9df;
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 600px) {
            .nav-inner {
                min-height: 66px;
            }

            .brand {
                font-size: 19px;
            }

            .container {
                margin-top: 23px;
            }

            .photo-wrap {
                height: 250px;
            }

            .content {
                padding: 21px;
            }

            .actions {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="brand">
            ✦ Wisata <span>Riau</span>
        </a>

        <a href="index.php" class="nav-link">
            ← Kembali ke beranda
        </a>
    </div>
</header>

<main class="container">
    <a href="index.php" class="back-link">
        ← Kembali ke daftar destinasi
    </a>

    <article class="card">

        <div class="photo-wrap">
            <?php if ($foto !== ''): ?>
                <img
                    src="<?= e($foto) ?>"
                    alt="<?= e($namaWisata) ?>"
                    class="photo"
                    onerror="this.style.display='none'; document.getElementById('photo-placeholder').style.display='grid';"
                >

                <div
                    id="photo-placeholder"
                    class="photo-placeholder"
                    style="display:none"
                >🏞️</div>
            <?php else: ?>
                <div class="photo-placeholder">🏞️</div>
            <?php endif; ?>
        </div>

        <div class="content">

            <span class="category">
                <?= e($kategori ?: 'Wisata Riau') ?>
            </span>

            <h1><?= e($namaWisata) ?></h1>

            <p class="location">
                📍 <?= e($lokasi ?: 'Riau') ?>
            </p>

            <section class="info-row">
                <h2 class="info-label">Tentang Destinasi</h2>
                <div class="description">
                    <?php if (trim((string) $deskripsi) !== ''): ?>
                        <?= nl2br(e($deskripsi)) ?>
                    <?php else: ?>
                        Informasi deskripsi destinasi ini belum tersedia.
                    <?php endif; ?>
                </div>
            </section>

            <section class="info-row">
                <h2 class="info-label">Alamat</h2>
                <div>
                    <?= trim((string) $alamat) !== ''
                        ? nl2br(e($alamat))
                        : 'Informasi alamat belum tersedia.' ?>
                </div>
            </section>

            <section class="info-row">
                <h2 class="info-label">Harga Tiket</h2>
                <div class="price">
                    <?php if ($harga > 0): ?>
                        Rp <?= number_format($harga, 0, ',', '.') ?>
                    <?php else: ?>
                        Gratis / belum tersedia
                    <?php endif; ?>
                </div>
            </section>

            <section class="info-row">
                <h2 class="info-label">Jam Operasional</h2>
                <div>
                    <?= trim((string) $jamOperasional) !== ''
                        ? nl2br(e($jamOperasional))
                        : 'Informasi jam operasional belum tersedia.' ?>
                </div>
            </section>

            <?php if ($hasCoordinates): ?>
                <section class="info-row">
                    <h2 class="info-label">Koordinat Lokasi</h2>
                    <div class="coordinates">
                        <?= e($latitude) ?>, <?= e($longitude) ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="actions">

                <a href="index.php" class="button button-secondary">
                    ← Daftar Wisata
                </a>

                <a
                    href="<?= e($mapsUrl) ?>"
                    class="button button-maps"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    📍 Lihat Lokasi di Google Maps ↗
                </a>

                <?php if ($isAdmin): ?>
                    <a
                        href="edit_destinasi.php?id=<?= (int) $data['id_wisata'] ?>"
                        class="button"
                    >
                        ✎ Edit Destinasi
                    </a>
                <?php endif; ?>

            </div>

            <?php if ($isAdmin): ?>
                <div class="admin-note">
                    Kamu sedang melihat halaman sebagai admin.
                    Tombol pengelolaan hanya ditampilkan untuk akun admin.
                </div>
            <?php endif; ?>

        </div>
    </article>
</main>

<footer>
    &copy; <?= date('Y') ?> Wisata Riau — Jelajahi Pesona Bumi Lancang Kuning.
</footer>

</body>
</html>
