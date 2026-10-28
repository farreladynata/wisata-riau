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
    <meta name="theme-color" content="#16834a">
    <title>Kelola Destinasi - Wisata Riau</title>
    <link rel="stylesheet" href="assets/destinasi.css">
</head>
<body>

    <header class="dest-navbar">
        <div class="dest-navbar-inner">
            <a href="index.php" class="dest-brand">
                <span class="dest-brand-icon">🌿</span>
                <span>Wisata Riau</span>
            </a>
            <span class="dest-navbar-label">Panel Pengelolaan Destinasi</span>
        </div>
    </header>

    <main class="dest-container">

        <nav class="dest-breadcrumb" aria-label="Breadcrumb">
            <a href="admin.php">Dashboard</a>
            <span>/</span>
            <span>Kelola Destinasi</span>
        </nav>

        <section class="dest-heading">
            <div>
                <span class="dest-eyebrow">✦ Manajemen wisata</span>
                <h1>Kelola Destinasi</h1>
                <p>Kelola informasi destinasi wisata yang ditampilkan kepada pengunjung.</p>
            </div>

            <div class="dest-heading-actions">
                <a href="admin.php" class="dest-button dest-button-secondary">
                    ← Dashboard
                </a>
                <a href="tambah_destinasi.php" class="dest-button dest-button-primary">
                    + Tambah Destinasi
                </a>
            </div>
        </section>

        <?php if (isset($messages[$status])): ?>
            <div class="dest-alert" role="status">
                <span class="dest-alert-icon">✓</span>
                <div>
                    <strong><?= e($messages[$status]) ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <section class="dest-card">

            <div class="dest-card-header">
                <div>
                    <h2 class="dest-card-title">Daftar Destinasi Wisata</h2>
                    <p class="dest-card-subtitle">
                        Data destinasi yang tersimpan di database.
                    </p>
                </div>

                <span class="dest-count">
                    <?= (int) $result->num_rows ?> destinasi
                </span>
            </div>

            <?php if ($result->num_rows > 0): ?>

                <div class="dest-table-wrapper">
                    <table class="dest-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Wisata</th>
                                <th>Kategori</th>
                                <th>Kabupaten / Kota</th>
                                <th>Harga Tiket</th>
                                <th>Foto</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="dest-id">
                                            #<?= (int) $row['id_wisata'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="dest-name">
                                            <?= e($row['nama_wisata']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="dest-category">
                                            <?= e($row['nama_kategori'] ?? '-') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="dest-location">
                                            <?= e($row['kabupaten_kota'] ?: '-') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="dest-price">
                                            Rp <?= number_format(
                                                (float) $row['harga_tiket'],
                                                0,
                                                ',',
                                                '.'
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="dest-photo">
                                            <?= e($row['foto'] ?: 'Belum ada foto') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="dest-actions">
                                            <a
                                                class="dest-action-link"
                                                href="detail_destinasi.php?id=<?= (int) $row['id_wisata'] ?>"
                                            >
                                                Detail
                                            </a>

                                            <a
                                                class="dest-action-link dest-action-edit"
                                                href="edit_destinasi.php?id=<?= (int) $row['id_wisata'] ?>"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                class="dest-delete-form"
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
                                                    class="dest-delete-button"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="dest-empty">
                    <div class="dest-empty-icon">📍</div>
                    <h3>Belum ada destinasi wisata</h3>
                    <p>Mulai tambahkan destinasi agar datanya muncul di halaman ini.</p>
                    <a href="tambah_destinasi.php" class="dest-button dest-button-primary">
                        + Tambah Destinasi
                    </a>
                </div>

            <?php endif; ?>

        </section>

    </main>

    <footer class="dest-footer">
        Wisata Riau · Sistem Informasi Destinasi Wisata
    </footer>

</body>
</html>
