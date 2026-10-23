
<?php
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - Wisata Riau</title>
</head>
<body>
    <h1>Dashboard Admin</h1>

    <p>
        Selamat datang,
        <?= htmlspecialchars($_SESSION['nama'], ENT_QUOTES, 'UTF-8') ?>!
    </p>

    <p>Role: Admin</p>

    <h2>Kelola Wisata Riau</h2>
    <p>Dashboard admin berhasil diakses.</p>

    <form action="logout.php" method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
        <button type="submit">Logout</button>
    </form>
    
<nav>
    <h2>Menu Admin</h2>
    <ul>
        <li><a href="admin.php">Dashboard</a></li>
        <li><a href="destinasi.php">Kelola Destinasi Wisata</a></li>
        <li><a href="tambah_destinasi.php">Tambah Destinasi</a></li>
    </ul>
</nav>

</body>
</html>
