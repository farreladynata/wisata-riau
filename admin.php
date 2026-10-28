
<?php
require_once 'auth.php';
require_once 'koneksi.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$totalDestinasi = 0;
$totalKategori = 0;
$destinasiTerbaru = [];
$databaseError = false;

try {
    $result = $conn->query("SELECT COUNT(*) AS total FROM destinasi_wisata");
    if ($result) {
        $totalDestinasi = (int) $result->fetch_assoc()['total'];
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM kategori");
    if ($result) {
        $totalKategori = (int) $result->fetch_assoc()['total'];
    }

    $result = $conn->query(
        "SELECT id_wisata, nama_wisata, kabupaten_kota, foto
         FROM destinasi_wisata
         ORDER BY id_wisata DESC
         LIMIT 5"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $destinasiTerbaru[] = $row;
        }
    } else {
        $databaseError = true;
    }
} catch (Throwable $exception) {
    error_log('Dashboard query error: ' . $exception->getMessage());
    $databaseError = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7faf8">
    <title>Dashboard Admin | Wisata Riau</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="admin-layout">

    <aside class="sidebar" id="sidebar">
        <a href="index.php" class="sidebar-brand">
            <span class="brand-mark">W</span>
            <span class="brand-name">Wisata<span>Riau</span>
                <small>ADMIN PANEL</small>
            </span>
        </a>

        <div class="sidebar-label">MENU UTAMA</div>

        <nav class="sidebar-nav" aria-label="Navigasi admin">
            <a href="admin.php" class="nav-item active">
                <span class="nav-icon">▦</span>
                <span>Dashboard</span>
            </a>
            <a href="destinasi.php" class="nav-item">
                <span class="nav-icon">⌖</span>
                <span>Destinasi Wisata</span>
            </a>
            <a href="tambah_destinasi.php" class="nav-item">
                <span class="nav-icon">＋</span>
                <span>Tambah Destinasi</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-help">
                <span class="help-symbol">✦</span>
                <strong>Jelajahi potensi Riau</strong>
                <p>Kelola informasi wisata agar lebih mudah ditemukan.</p>
                <a href="index.php">Lihat website ↗</a>
            </div>

            <form action="logout.php" method="POST">
                <input type="hidden" name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>">
                <button type="submit" class="logout-button">
                    <span>↪</span> Keluar dari akun
                </button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle"
                type="button" aria-label="Buka menu"
                aria-controls="sidebar" aria-expanded="false">☰</button>

            <div class="breadcrumb">
                <span>Workspace</span>
                <span class="breadcrumb-separator">/</span>
                <strong>Dashboard</strong>
            </div>

            <div class="topbar-right">
                <span class="status-indicator"></span>
                <span class="topbar-status">Administrator</span>
                <div class="avatar">
                    <?= e(strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1))) ?>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <section class="welcome-section">
                <div>
                    <p class="section-eyebrow">OVERVIEW</p>
                    <h1>Dashboard</h1>
                    <p class="welcome-description">
                        Selamat datang kembali,
                        <strong><?= e($_SESSION['nama'] ?? 'Admin') ?></strong>.
                        Berikut ringkasan pengelolaan wisata Riau.
                    </p>
                </div>

                <a class="primary-button" href="tambah_destinasi.php">
                    <span>＋</span> Tambah destinasi
                </a>
            </section>

            <?php if ($databaseError): ?>
                <div class="dashboard-alert" role="alert">
                    Sebagian data tidak dapat dimuat. Periksa struktur
                    tabel database dan konfigurasi koneksi.
                </div>
            <?php endif; ?>

            <section class="stats-grid" aria-label="Statistik website">
                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon green">⌖</span>
                        <span class="stat-tag">DATA</span>
                    </div>
                    <p class="stat-label">Total Destinasi</p>
                    <div class="stat-value"><?= number_format($totalDestinasi) ?></div>
                    <p class="stat-description">Destinasi tersimpan di database</p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon mint">▤</span>
                        <span class="stat-tag">KATEGORI</span>
                    </div>
                    <p class="stat-label">Kategori Wisata</p>
                    <div class="stat-value"><?= number_format($totalKategori) ?></div>
                    <p class="stat-description">Kategori tersedia di sistem</p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon amber">✦</span>
                        <span class="stat-tag">SISTEM</span>
                    </div>
                    <p class="stat-label">Status Website</p>
                    <div class="stat-value status-value">
                        <span class="status-indicator"></span> Aktif
                    </div>
                    <p class="stat-description">Dashboard admin siap digunakan</p>
                </article>
            </section>

            <section class="quick-section">
                <div class="section-heading">
                    <div>
                        <h2>Akses cepat</h2>
                        <p>Pintasan untuk aktivitas pengelolaan.</p>
                    </div>
                </div>

                <div class="quick-grid">
                    <a href="destinasi.php" class="quick-card">
                        <span class="quick-icon">⌖</span>
                        <span class="quick-text">
                            <strong>Kelola destinasi</strong>
                            <small>Lihat dan edit data wisata</small>
                        </span>
                        <span class="quick-arrow">↗</span>
                    </a>

                    <a href="tambah_destinasi.php" class="quick-card">
                        <span class="quick-icon">＋</span>
                        <span class="quick-text">
                            <strong>Tambah destinasi</strong>
                            <small>Masukkan wisata baru</small>
                        </span>
                        <span class="quick-arrow">↗</span>
                    </a>
                </div>
            </section>

            <section class="recent-section">
                <div class="section-heading">
                    <div>
                        <h2>Destinasi terbaru</h2>
                        <p>Lima destinasi terakhir yang tersimpan.</p>
                    </div>
                    <a href="destinasi.php" class="text-link">Lihat semua →</a>
                </div>

                <div class="table-card">
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>DESTINASI</th>
                                    <th>LOKASI</th>
                                    <th>ID</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$destinasiTerbaru): ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        Belum ada data destinasi untuk ditampilkan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($destinasiTerbaru as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="destination-cell">
                                                <span class="destination-thumb">
                                                    <?php if (!empty($item['foto'])): ?>
                                                        <img
                                                            src="uploads/<?= rawurlencode(basename($item['foto'])) ?>"
                                                            alt=""
                                                            loading="lazy"
                                                        >
                                                    <?php else: ?>
                                                        <span>⌖</span>
                                                    <?php endif; ?>
                                                </span>
                                                <strong><?= e($item['nama_wisata']) ?></strong>
                                            </div>
                                        </td>
                                        <td><?= e($item['kabupaten_kota'] ?? '—') ?></td>
                                        <td><span class="id-label">#<?= e($item['id_wisata']) ?></span></td>
                                        <td><span class="status-pill">● Terdaftar</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">
                        <span>Data diambil langsung dari database.</span>
                        <a href="destinasi.php">Buka pengelolaan destinasi ↗</a>
                    </div>
                </div>
            </section>

            <footer class="admin-footer">
                <span>© <?= date('Y') ?> Wisata Riau</span>
                <span>Dibuat untuk pengelolaan pariwisata Riau.</span>
            </footer>
        </div>
    </main>
</div>

<script>
const sidebar = document.getElementById('sidebar');
const menuToggle = document.getElementById('menuToggle');

menuToggle.addEventListener('click', () => {
    const isOpen = sidebar.classList.toggle('sidebar-open');
    menuToggle.setAttribute('aria-expanded', String(isOpen));
});

document.addEventListener('click', (event) => {
    if (
        window.innerWidth <= 900 &&
        sidebar.classList.contains('sidebar-open') &&
        !sidebar.contains(event.target) &&
        !menuToggle.contains(event.target)
    ) {
        sidebar.classList.remove('sidebar-open');
        menuToggle.setAttribute('aria-expanded', 'false');
    }
});
</script>
</body>
</html>
