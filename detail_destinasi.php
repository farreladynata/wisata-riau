```php
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

// Ambil detail dari tabel destinasi_wisata
$stmt = $conn->prepare(
    "SELECT
        d.id_wisata,
        d.nama_wisata,
        d.deskripsi,
        d.alamat,
        d.kabupaten_kota,
        d.latitude,
        d.longitude,
        d.jam_operasional,
        d.harga_tiket,
        d.foto,
        d.created_at,
        k.nama_kategori
     FROM destinasi_wisata AS d
     LEFT JOIN kategori AS k
        ON d.id_kategori = k.id_kategori
     WHERE d.id_wisata = ?"
);

if (!$stmt) {
    error_log('Prepare detail destinasi gagal: ' . $conn->error);
    http_response_code(500);
    exit('Terjadi kesalahan saat mengambil data destinasi.');
}

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

    <title>Detail Destinasi - Wisata Riau</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            color: #263238;
            background: #f3f7f5;
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #176b45;
        }

        .category {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 20px;
            background: #e1f5e8;
            color: #176b45;
            border-radius: 20px;
            font-size: 14px;
        }

        .photo {
            display: block;
            max-width: 100%;
            max-height: 400px;
            margin: 10px 0 24px;
            border-radius: 8px;
            object-fit: cover;
        }

        .detail-row {
            padding: 12px 0;
            border-bottom: 1px solid #e5e9e7;
        }

        .detail-row strong {
            display: block;
            margin-bottom: 6px;
            color: #374740;
        }

        .description {
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 24px;
        }

        .button {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            background: #176b45;
            color: #fff;
            text-decoration: none;
        }

        .button:hover {
            background: #105536;
        }

        .button-secondary {
            background: #e9efec;
            color: #263b30;
        }

        .button-secondary:hover {
            background: #dce5e0;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .card {
                padding: 18px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <main class="container">
        <div class="card">
            <h1><?= e($data['nama_wisata']) ?></h1>

            <span class="category">
                <?= e($data['nama_kategori'] ?? 'Tanpa kategori') ?>
            </span>

            <?php if (!empty($data['foto'])): ?>
                <div>
                    <strong>Foto destinasi:</strong>
                    <p><?= e($data['foto']) ?></p>
                </div>
            <?php endif; ?>

            <div class="detail-row">
                <strong>ID Destinasi</strong>
                <?= (int) $data['id_wisata'] ?>
            </div>

            <div class="detail-row">
                <strong>Alamat</strong>
                <?= nl2br(e($data['alamat'] ?: '-')) ?>
            </div>

            <div class="detail-row">
                <strong>Kabupaten/Kota</strong>
                <?= e($data['kabupaten_kota'] ?: '-') ?>
            </div>

            <div class="detail-row">
                <strong>Harga Tiket</strong>
                Rp <?= number_format(
                    (float) $data['harga_tiket'],
                    0,
                    ',',
                    '.'
                ) ?>
            </div>

            <div class="detail-row">
                <strong>Jam Operasional</strong>
                <?= e($data['jam_operasional'] ?: '-') ?>
            </div>

            <div class="detail-row">
                <strong>Latitude</strong>
                <?= e($data['latitude'] ?? '-') ?>
            </div>

            <div class="detail-row">
                <strong>Longitude</strong>
                <?= e($data['longitude'] ?? '-') ?>
            </div>

            <div class="detail-row">
                <strong>Deskripsi</strong>
                <div class="description">
                    <?= nl2br(e($data['deskripsi'] ?: '-')) ?>
                </div>
            </div>

            <div class="detail-row">
                <strong>Tanggal Ditambahkan</strong>
                <?= e($data['created_at']) ?>
            </div>

            <div class="actions">
                <a
                    class="button"
                    href="edit_destinasi.php?id=<?= (int) $data['id_wisata'] ?>"
                >
                    Edit Destinasi
                </a>

                <a class="button button-secondary" href="destinasi.php">
                    Kembali ke Daftar
                </a>
            </div>
        </div>
    </main>
</body>
</html>
```