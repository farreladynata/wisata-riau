
<?php
require_once __DIR__ . '/koneksi.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Kata kunci pencarian
$cari = trim($_GET['cari'] ?? '');

// Ambil destinasi beserta kategorinya
$sql = "SELECT
            d.id_wisata,
            d.id_kategori,
            d.nama_wisata,
            d.deskripsi,
            d.kabupaten_kota,
            d.harga_tiket,
            d.foto,
            k.nama_kategori
        FROM destinasi_wisata AS d
        LEFT JOIN kategori AS k
            ON d.id_kategori = k.id_kategori";

if ($cari !== '') {
    $sql .= " WHERE d.nama_wisata LIKE ?
              OR d.kabupaten_kota LIKE ?
              OR k.nama_kategori LIKE ?";
}

$sql .= " ORDER BY d.id_wisata DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    error_log('Gagal menyiapkan query halaman utama: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Data wisata belum dapat dimuat. Silakan periksa konfigurasi database.');
}

if ($cari !== '') {
    $kata = '%' . $cari . '%';
    mysqli_stmt_bind_param($stmt, 'sss', $kata, $kata, $kata);
}

if (!mysqli_stmt_execute($stmt)) {
    error_log('Gagal mengambil destinasi wisata.');
    http_response_code(500);
    exit('Terjadi kesalahan saat mengambil data wisata.');
}

$result = mysqli_stmt_get_result($stmt);

// Jumlah destinasi yang tampil
$totalWisata = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Jelajahi berbagai destinasi wisata menarik di Provinsi Riau. Temukan wisata alam, budaya, sejarah, dan destinasi favoritmu.">

    <title>Wisata Riau — Temukan Destinasi Favoritmu</title>

    
<link rel="stylesheet" href="assets/style.css">

</head>

<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="container nav-inner">
            <a href="index.php" class="logo" aria-label="Wisata Riau Beranda">
                <span class="logo-mark">✦</span>
                <span>Wisata <span>Riau</span></span>
            </a>

            <nav class="nav-menu" aria-label="Navigasi utama">
                <a href="#beranda">Beranda</a>
                <a href="#destinasi">Destinasi</a>
                <a href="#tentang">Tentang</a>
                <a href="#destinasi" class="nav-cta">Jelajahi Wisata ↗</a>
            </nav>
        </div>
    </header>

    <main>
        <!-- HERO -->
        <section class="hero" id="beranda">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-label">
                        <span>✦</span>
                        Pesona Wisata Bumi Lancang Kuning
                    </div>

                    <h1>
                        Temukan Pesona Tersembunyi
                        <span>Riau.</span>
                    </h1>

                    <p>
                        Dari keindahan alam hingga kekayaan budaya Melayu,
                        jelajahi berbagai destinasi menarik di Riau dan
                        temukan pengalaman perjalanan yang berkesan.
                    </p>

                    <div class="hero-actions">
                        <a href="#destinasi" class="button button-gold">
                            Jelajahi Destinasi <span>→</span>
                        </a>

                        <a href="#tentang" class="button button-outline">
                            Tentang Wisata Riau
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- HIGHLIGHTS -->
        <section class="highlights" aria-label="Keunggulan Wisata Riau">
            <div class="container">
                <div class="highlight-grid">
                    <div class="highlight">
                        <div class="highlight-icon">🏞️</div>
                        <div>
                            <h3>Destinasi Beragam</h3>
                            <p>Temukan tempat menarik di Riau.</p>
                        </div>
                    </div>

                    <div class="highlight">
                        <div class="highlight-icon">🧭</div>
                        <div>
                            <h3>Informasi Praktis</h3>
                            <p>Kenali lokasi dan harga tiket.</p>
                        </div>
                    </div>

                    <div class="highlight">
                        <div class="highlight-icon">🌿</div>
                        <div>
                            <h3>Pesona Lokal</h3>
                            <p>Jelajahi alam dan budaya Melayu.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- DAFTAR DESTINASI -->
        <section class="wisata-section" id="destinasi">
            <div class="container">
                <div class="section-top">
                    <div>
                        <div class="eyebrow">Jelajahi Riau</div>
                        <h2 class="section-heading">
                            Destinasi Pilihan untukmu
                        </h2>
                        <p class="section-description">
                            Temukan tempat baru, nikmati keindahan alam,
                            dan kenali kekayaan budaya yang ada di Riau.
                        </p>
                    </div>

                    <div class="result-count">
                        <?php if ($cari !== ''): ?>
                            Ditemukan
                            <strong><?= $totalWisata ?></strong>
                            hasil untuk "<?= e($cari) ?>"
                        <?php else: ?>
                            <strong><?= $totalWisata ?></strong>
                            destinasi tersedia
                        <?php endif; ?>
                    </div>
                </div>

                <!-- PENCARIAN -->
                <form action="index.php" method="GET" class="search-panel">
                    <div class="search-input-wrap">
                        <span class="search-icon" aria-hidden="true">⌕</span>

                        <input
                            type="search"
                            name="cari"
                            placeholder="Cari nama wisata, lokasi, atau kategori..."
                            value="<?= e($cari) ?>"
                            aria-label="Cari destinasi wisata"
                        >
                    </div>

                    <button type="submit" class="search-button">
                        Cari Wisata
                    </button>

                    <?php if ($cari !== ''): ?>
                        <a href="index.php" class="clear-search">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <!-- KARTU WISATA -->
                <div class="wisata-grid">
                    <?php if ($totalWisata > 0): ?>

                        <?php while ($item = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $namaFoto = trim((string) ($item['foto'] ?? ''));
                            $fotoSrc = '';

                            if ($namaFoto !== '') {
                                if (
                                    preg_match('~^https?://~i', $namaFoto) ||
                                    strpos($namaFoto, 'assets/') === 0 ||
                                    strpos($namaFoto, 'uploads/') === 0
                                ) {
                                    $fotoSrc = $namaFoto;
                                } else {
                                    $fotoSrc = 'uploads/' . basename($namaFoto);
                                }
                            }

                            $deskripsi = trim((string) ($item['deskripsi'] ?? ''));
                            $lokasi = trim((string) ($item['kabupaten_kota'] ?? ''));
                            $kategori = trim((string) ($item['nama_kategori'] ?? ''));
                            $harga = (float) ($item['harga_tiket'] ?? 0);
                            ?>

                            <article class="wisata-card">
                                <div class="wisata-image">
                                    <?php if ($fotoSrc !== ''): ?>
                                        <img
                                            src="<?= e($fotoSrc) ?>"
                                            alt="<?= e($item['nama_wisata']) ?>"
                                            loading="lazy"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                                        >
                                        <div
                                            class="image-placeholder"
                                            style="display:none"
                                            aria-label="Foto tidak tersedia"
                                        >🏞️</div>
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            🏞️
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($kategori !== ''): ?>
                                        <span class="category-badge">
                                            <?= e($kategori) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="wisata-body">
                                    <div class="location">
                                        <span aria-hidden="true">📍</span>
                                        <?= e($lokasi !== '' ? $lokasi : 'Riau') ?>
                                    </div>

                                    <h3><?= e($item['nama_wisata']) ?></h3>

                                    <p class="description">
                                        <?= e(
                                            $deskripsi !== ''
                                                ? $deskripsi
                                                : 'Jelajahi destinasi ini dan temukan keunikan serta daya tariknya.'
                                        ) ?>
                                    </p>

                                    <div class="card-bottom">
                                        <div>
                                            <div class="price-label">
                                                Harga tiket
                                            </div>

                                            <div class="price">
                                                <?php if ($harga > 0): ?>
                                                    Rp <?= number_format($harga, 0, ',', '.') ?>
                                                <?php else: ?>
                                                    Gratis / belum tersedia
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <a
                                            href="detail_destinasi.php?id=<?= (int) $item['id_wisata'] ?>"
                                            class="detail-link"
                                        >
                                            Lihat Detail <span>→</span>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">🔎</div>

                            <?php if ($cari !== ''): ?>
                                <h3>Destinasi tidak ditemukan</h3>
                                <p>
                                    Kami belum menemukan wisata yang cocok
                                    dengan pencarian "<?= e($cari) ?>".
                                    Coba gunakan kata kunci lain.
                                </p>
                                <a href="index.php">Lihat semua destinasi →</a>
                            <?php else: ?>
                                <h3>Belum ada destinasi wisata</h3>
                                <p>
                                    Destinasi wisata akan ditampilkan di sini
                                    setelah tersedia di database.
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- TENTANG -->
        <section class="closing" id="tentang">
            <div class="container">
                <div class="closing-inner">
                    <div>
                        <div class="eyebrow">Kenali Riau Lebih Dekat</div>
                        <h2>Perjalananmu di Riau Dimulai di Sini.</h2>
                        <p>
                            Temukan inspirasi destinasi dan rencanakan
                            perjalananmu menjelajahi pesona Riau.
                        </p>
                    </div>

                    <a href="#destinasi" class="button">
                        Temukan Wisata <span>→</span>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="container footer-inner">
            <div>
                <div class="footer-brand">Wisata Riau.</div>
                <p class="footer-description">
                    Jelajahi keindahan dan kekayaan wisata Riau.
                </p>
            </div>

            <p class="footer-copy">
                &copy; <?= date('Y') ?> Wisata Riau.
                Dibuat untuk mengenalkan pesona Riau.
            </p>
        </div>
    </footer>

</body>
</html>
<?php
mysqli_stmt_close($stmt);
?>
