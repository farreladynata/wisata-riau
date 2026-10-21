```php
<?php
require_once __DIR__ . '/koneksi.php';

// Pencarian destinasi
$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// Ambil data dari database
if ($cari !== '') {
    $sql = "SELECT * FROM destinasi
            WHERE nama_wisata LIKE ?
            OR kabupaten_kota LIKE ?
            ORDER BY id DESC";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Terjadi kesalahan saat menyiapkan pencarian.");
    }

    $kata = "%" . $cari . "%";

    mysqli_stmt_bind_param($stmt, "ss", $kata, $kata);
    mysqli_stmt_execute($stmt);
    $wisata = mysqli_stmt_get_result($stmt);
} else {
    $wisata = mysqli_query(
        $conn,
        "SELECT * FROM destinasi ORDER BY id DESC"
    );
}

if (!$wisata) {
    die("Gagal mengambil data wisata. Periksa tabel destinasi.");
}

function e($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Wisata Riau | Jelajahi Keindahan Riau</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7f5;
            color: #26372d;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }

        .container {
            width: 90%;
            max-width: 1150px;
            margin: auto;
        }

        /* NAVIGASI */

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e4eae5;
        }

        .nav-inner {
            min-height: 76px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .logo {
            color: #176b45;
            font-size: 24px;
            font-weight: 800;
        }

        .logo span {
            color: #e8ad3b;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-menu a {
            color: #45554a;
            font-size: 14px;
            font-weight: bold;
        }

        .nav-menu a:hover {
            color: #176b45;
        }

        .nav-menu .nav-cta {
            background: #176b45;
            color: white;
            padding: 11px 17px;
            border-radius: 7px;
        }

        /* HERO */

        .hero {
            background: linear-gradient(120deg, #103d29, #218052);
            color: white;
            padding: 90px 0;
        }

        .hero-content {
            max-width: 750px;
        }

        .hero-label {
            display: inline-block;
            padding: 7px 13px;
            border: 1px solid #ffffff60;
            border-radius: 30px;
            font-size: 12px;
            letter-spacing: 1px;
            margin-bottom: 22px;
        }

        .hero h1 {
            font-size: clamp(36px, 5vw, 58px);
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .hero h1 span {
            color: #f5c35f;
        }

        .hero p {
            color: #e0eee5;
            max-width: 650px;
            margin-bottom: 30px;
        }

        .hero-button {
            display: inline-block;
            background: #f5c35f;
            color: #233528;
            padding: 14px 22px;
            border-radius: 7px;
            font-weight: bold;
        }

        .hero-button:hover {
            background: #ffda86;
        }

        /* INFORMASI */

        .info-section {
            padding: 35px 0 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .info-card {
            padding: 23px;
            background: white;
            border: 1px solid #e4eae5;
            border-radius: 10px;
        }

        .info-card h3 {
            color: #176b45;
            margin-bottom: 5px;
        }

        .info-card p {
            color: #788279;
            font-size: 13px;
        }

        /* DAFTAR WISATA */

        .wisata-section {
            padding: 65px 0 80px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 25px;
            margin-bottom: 30px;
        }

        .section-heading h2 {
            font-size: 30px;
            margin-bottom: 5px;
        }

        .section-heading p {
            color: #788279;
            font-size: 14px;
        }

        .search-form {
            display: flex;
            gap: 8px;
            max-width: 390px;
            width: 100%;
        }

        .search-form input {
            min-width: 0;
            flex: 1;
            padding: 12px;
            border: 1px solid #d9e1da;
            border-radius: 7px;
            outline: none;
            font-size: 14px;
        }

        .search-form input:focus {
            border-color: #176b45;
        }

        .search-form button {
            border: none;
            padding: 0 17px;
            background: #176b45;
            color: white;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
        }

        .wisata-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .wisata-card {
            background: white;
            border: 1px solid #e5ebe6;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.25s;
        }

        .wisata-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px #103d2912;
        }

        .wisata-image {
            height: 190px;
            background: #e4eee6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .wisata-image img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder {
            font-size: 48px;
        }

        .wisata-body {
            padding: 20px;
        }

        .lokasi {
            display: inline-block;
            background: #eaf5ed;
            color: #176b45;
            font-size: 12px;
            padding: 5px 9px;
            border-radius: 5px;
            margin-bottom: 12px;
        }

        .wisata-body h3 {
            font-size: 19px;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .deskripsi {
            color: #788279;
            font-size: 13px;
            margin-bottom: 20px;
            overflow-wrap: anywhere;
        }

        .harga-area {
            border-top: 1px solid #edf0ed;
            padding-top: 14px;
        }

        .harga-label {
            font-size: 12px;
            color: #899289;
        }

        .harga {
            font-size: 17px;
            font-weight: bold;
            color: #176b45;
        }

        .empty-state {
            grid-column: 1 / -1;
            padding: 45px 20px;
            text-align: center;
            background: white;
            border: 1px solid #e5ebe6;
            border-radius: 10px;
        }

        .empty-state p {
            color: #788279;
            margin-top: 8px;
        }

        /* FOOTER */

        footer {
            background: #103d29;
            color: #d8e7dc;
            padding: 32px 0;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .footer-title {
            color: white;
            font-size: 21px;
            font-weight: bold;
        }

        footer p {
            font-size: 12px;
            color: #b5c9bb;
        }

        /* RESPONSIF */

        @media (max-width: 900px) {
            .wisata-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .section-heading {
                align-items: stretch;
                flex-direction: column;
            }

            .search-form {
                max-width: 100%;
            }
        }

        @media (max-width: 600px) {
            .nav-inner {
                padding: 14px 0;
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-menu {
                gap: 14px;
                flex-wrap: wrap;
            }

            .nav-menu a {
                font-size: 12px;
            }

            .hero {
                padding: 65px 0;
            }

            .hero p {
                font-size: 14px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .wisata-section {
                padding: 45px 0 55px;
            }

            .section-heading h2 {
                font-size: 25px;
            }

            .wisata-grid {
                grid-template-columns: 1fr;
            }

            .wisata-image {
                height: 210px;
            }

            .footer-inner {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="container nav-inner">

            <a href="index.php" class="logo">
                WISATA<span>RIAU.</span>
            </a>

            <div class="nav-menu">
                <a href="#beranda">Beranda</a>
                <a href="#wisata">Destinasi</a>
                <a href="#tentang">Tentang</a>
                <a href="#wisata" class="nav-cta">Jelajahi Wisata</a>
            </div>

        </div>
    </nav>

    <header class="hero" id="beranda">
        <div class="container">
            <div class="hero-content">

                <span class="hero-label">
                    SISTEM INFORMASI WISATA DAERAH
                </span>

                <h1>
                    Jelajahi Pesona<br>
                    <span>Wisata Riau</span>
                </h1>

                <p>
                    Temukan keindahan alam, kekayaan sejarah, dan
                    keunikan budaya Melayu di Provinsi Riau.
                    Kenali destinasi menarik dan rencanakan perjalananmu
                    di Bumi Lancang Kuning.
                </p>

                <a href="#wisata" class="hero-button">
                    Temukan Destinasi &rarr;
                </a>

            </div>
        </div>
    </header>

    <section class="info-section" id="tentang">
        <div class="container">
            <div class="info-grid">

                <div class="info-card">
                    <h3>Wisata Alam</h3>
                    <p>
                        Jelajahi pantai, pulau, dan pemandangan alam Riau.
                    </p>
                </div>

                <div class="info-card">
                    <h3>Wisata Sejarah</h3>
                    <p>
                        Kenali peninggalan sejarah dan warisan Melayu.
                    </p>
                </div>

                <div class="info-card">
                    <h3>Wisata Budaya</h3>
                    <p>
                        Temukan tradisi dan kebudayaan masyarakat Riau.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <main class="wisata-section" id="wisata">
        <div class="container">

            <div class="section-heading">
                <div>
                    <h2>Destinasi Wisata</h2>
                    <p>
                        Temukan tempat wisata menarik di Provinsi Riau.
                    </p>
                </div>

                <form action="index.php" method="GET" class="search-form">
                    <input
                        type="search"
                        name="cari"
                        placeholder="Cari destinasi atau daerah..."
                        value="<?= e($cari) ?>"
                    >

                    <button type="submit">Cari</button>
                </form>
            </div>

            <div class="wisata-grid">

                <?php if (mysqli_num_rows($wisata) > 0): ?>

                    <?php while ($item = mysqli_fetch_assoc($wisata)): ?>

                        <article class="wisata-card">

                            <div class="wisata-image">

                                <?php if (!empty($item['gambar'])): ?>

                                    <img
                                        src="uploads/<?= e(basename($item['gambar'])) ?>"
                                        alt="<?= e($item['nama_wisata']) ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <span class="placeholder">
                                        &#127748;
                                    </span>

                                <?php endif; ?>

                            </div>

                            <div class="wisata-body">

                                <span class="lokasi">
                                    &#128205;
                                    <?= e($item['kabupaten_kota'] ?? 'Riau') ?>
                                </span>

                                <h3>
                                    <?= e($item['nama_wisata']) ?>
                                </h3>

                                <p class="deskripsi">
                                    <?= e($item['deskripsi'] ?? '') ?>
                                </p>

                                <div class="harga-area">
                                    <p class="harga-label">
                                        Harga tiket masuk
                                    </p>

                                    <p class="harga">
                                        <?php
                                        $harga = (float) ($item['harga_tiket'] ?? 0);

                                        if ($harga > 0) {
                                            echo 'Rp' . number_format(
                                                $harga,
                                                0,
                                                ',',
                                                '.'
                                            );
                                        } else {
                                            echo 'Gratis / belum tersedia';
                                        }
                                        ?>
                                    </p>
                                </div>

                            </div>

                        </article>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-state">
                        <h3>Destinasi belum ditemukan</h3>
                        <p>
                            Coba kata kunci lain atau tambahkan data
                            wisata ke database.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        </div>
    </main>

    <footer>
        <div class="container footer-inner">

            <div>
                <div class="footer-title">WISATA RIAU</div>
                <p>Sistem Informasi Wisata Daerah Riau</p>
            </div>

            <p>
                &copy; <?= date('Y') ?> Wisata Riau.
                Semua hak dilindungi.
            </p>

        </div>
    </footer>

</body>
</html>
```