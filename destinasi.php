
<?php
require_once 'auth.php';
require_once 'koneksi.php';

$result = $conn->query(
    "SELECT id, nama_wisata, kabupaten_kota,
            harga_tiket, gambar
     FROM destinasi
     ORDER BY id DESC"
);

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Destinasi - Wisata Riau</title>
</head>
<body>
    <h1>Kelola Destinasi Wisata</h1>

    <p>
        <a href="admin.php">Dashboard</a> |
        <a href="tambah_destinasi.php">+ Tambah Destinasi</a>
    </p>

    <?php if (isset($_GET['status'])): ?>
        <p>
            <?php
            $messages = [
                'ditambah' => 'Destinasi berhasil ditambahkan.',
                'diubah' => 'Destinasi berhasil diperbarui.',
                'dihapus' => 'Destinasi berhasil dihapus.'
            ];

            echo e($messages[$_GET['status']] ?? '');
            ?>
        </p>
    <?php endif; ?>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Wisata</th>
                <th>Kabupaten/Kota</th>
                <th>Harga Tiket</th>
                <th>Gambar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td><?= e($row['nama_wisata']) ?></td>
                <td><?= e($row['kabupaten_kota'] ?? '-') ?></td>
                <td>
                    Rp <?= number_format(
                        (float) $row['harga_tiket'],
                        0,
                        ',',
                        '.'
                    ) ?>
                </td>
                <td><?= e($row['gambar'] ?? '-') ?></td>
                <td>
                    <a href="detail_destinasi.php?id=<?= (int) $row['id'] ?>">
                        Detail
                    </a> |
                    <a href="edit_destinasi.php?id=<?= (int) $row['id'] ?>">
                        Edit
                    </a>

                    <form
                        action="hapus_destinasi.php"
                        method="POST"
                        onsubmit="return confirm('Yakin ingin menghapus destinasi ini?')"
                    >
                        <input type="hidden" name="id"
                               value="<?= (int) $row['id'] ?>">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION['csrf_token']) ?>">
                        <button type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
