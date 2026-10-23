```php
<?php
require_once 'auth.php';
require_once 'koneksi.php';

// Mengamankan output agar terhindar dari XSS
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Mengambil data destinasi dari tabel baru
$sql = "SELECT
            d.id_wisata,
            d.nama_wisata,
            d.kabupaten_kota,
            d.harga_tiket,
            d.foto,
            k.nama_kategori
        FROM destinasi_wisata AS d
        LEFT JOIN kategori AS k
            ON d.id_kategori = k.id_kategori
        ORDER BY d.id_wisata DESC";

$result = $conn->query($sql);

if (!$result) {
    error_log('Query destinasi gagal: ' . $conn->error);
    die('Gagal mengambil data destinasi. Silakan periksa konfigurasi database.');
}

// Pesan notifikasi
$messages = [
    'ditambah' => 'Destinasi berhasil ditambahkan.',
    'diubah'   => 'Destinasi berhasil diperbarui.',
    'dihapus'  => 'Destinasi berhasil dihapus.'
];

$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Kelola Destinasi - Wisata Riau</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 24px;
            background: #f4f7f6;
            color: #263238;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
            color: #176b45;
        }

        .navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        a {
            color: #087443;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .button {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 5px;
            background: #16834a;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .button:hover {
            background: #106638;
            text-decoration: none;
        }

        .alert {
            padding: 12px 15px;
            margin-bottom: 18px;
            background: #e1f5e8;
            color: #176b45;
            border-radius: 5px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #176b45;
            color: white;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background: #f8faf9;
        }

        .actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .delete-form {
            margin: 0;
        }

        .delete-button {
            padding: 6px 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .delete-button:hover {
            background: #b02a37;
        }

        .empty {
            text-align: center;
            padding: 24px;
            color: #666;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 15px;
            }

            h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>
    <main class="container">
        <h1>Kelola Destinasi Wisata</h1>

        <nav class="navigation">
            <a href="admin.php">Dashboard</a>

            <a href="tambah_destinasi.php" class="button">
                + Tambah Destinasi
            </a>
        </nav>

        <?php if (isset($messages[$status])): ?>
            <div class="alert">
                <?= e($messages[$status]) ?>
            </div>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Wisata</th>
                        <th>Kategori</th>
                        <th>Kabupaten/Kota</th>
                        <th>Harga Tiket</th>
                        <th>Foto</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?= (int) $row['id_wisata'] ?>
                                </td>

                                <td>
                                    <?= e($row['nama_wisata']) ?>
                                </td>

                                <td>
                                    <?= e($row['nama_kategori'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= e($row['kabupaten_kota'] ?: '-') ?>
                                </td>

                                <td>
                                    Rp <?= number_format(
                                        (float) $row['harga_tiket'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>
                                    <?= e($row['foto'] ?: '-') ?>
                                </td>

                                <td>
                                    <div class="actions">
                                        <a href="detail_destinasi.php?id=<?= (int) $row['id_wisata'] ?>">
                                            Detail
                                        </a>

                                        <a href="edit_destinasi.php?id=<?= (int) $row['id_wisata'] ?>">
                                            Edit
                                        </a>

                                        <form
                                            class="delete-form"
                                            action="hapus_destinasi.php"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus destinasi ini?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $row['id_wisata'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token'] ?? '') ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="delete-button"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty">
                                Belum ada data destinasi wisata.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
```