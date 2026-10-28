
<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header(
        'Location: ' .
        (($_SESSION['role'] ?? '') === 'admin' ? 'admin.php' : 'index.php')
    );
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $password === ''
    ) {
        $error = 'Email atau password salah.';
    } else {
        $stmt = $conn->prepare(
            "SELECT users.id, users.nama, users.email,
                    users.password, roles.nama_role
             FROM users
             INNER JOIN roles ON users.role_id = roles.id
             WHERE users.email = ?
             LIMIT 1"
        );

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['nama_role'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($user['nama_role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: index.php');
            }
            exit;
        }

        $error = 'Email atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>Login Admin | Wisata Riau</title>

    <link rel="stylesheet" href="assets/login.css">
</head>
<body>
    <main class="login-page">
        <section class="login-showcase">
            <a class="brand" href="index.php">
                <span class="brand-mark">W</span>
                <span>Wisata<span class="brand-light">Riau</span></span>
            </a>

            <div class="showcase-content">
                <span class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    ADMINISTRATOR PORTAL
                </span>

                <h1>Jelajahi keindahan,<br>
                    <span>kelola potensi Riau.</span>
                </h1>

                <p>
                    Satu tempat untuk mengelola destinasi wisata,
                    informasi, dan kekayaan pariwisata Riau.
                </p>

                <div class="showcase-note">
                    <span class="note-icon">✦</span>
                    <span>Discover more. Experience Riau.</span>
                </div>
            </div>

            <div class="showcase-footer">
                <span>Platform Pengelolaan Pariwisata</span>
                <span>RIAU, INDONESIA</span>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-card">
                <div class="mobile-brand">
                    <span class="brand-mark">W</span>
                    <span>Wisata<span class="brand-light">Riau</span></span>
                </div>

                <div class="login-heading">
                    <span class="heading-icon">↗</span>
                    <p class="form-eyebrow">SELAMAT DATANG KEMBALI</p>
                    <h2>Masuk ke akunmu</h2>
                    <p class="form-description">
                        Masukkan informasi akun untuk melanjutkan.
                    </p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert-error" role="alert">
                        <span aria-hidden="true">!</span>
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="login-form">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION['csrf_token'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <div class="form-group">
                        <label for="email">Alamat email</label>
                        <div class="input-wrap">
                            <span class="input-icon" aria-hidden="true">@</span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="nama@email.com"
                                autocomplete="username"
                                value="<?= htmlspecialchars(
                                    $_POST['email'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <span class="input-icon" aria-hidden="true">⌑</span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                class="password-toggle"
                                id="togglePassword"
                                aria-label="Tampilkan password"
                            >Lihat</button>
                        </div>
                    </div>

                    <button type="submit" class="login-button">
                        Masuk ke Dashboard
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <div class="login-divider">
                    <span></span>
                    <small>WISATA RIAU ADMIN</small>
                    <span></span>
                </div>

                <a href="index.php" class="back-link">
                    <span aria-hidden="true">←</span>
                    Kembali ke halaman utama
                </a>

                <p class="login-legal">
                    Akses khusus untuk pengguna terdaftar.
                </p>
            </div>
        </section>
    </main>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            togglePassword.textContent = isHidden ? 'Sembunyi' : 'Lihat';
            togglePassword.setAttribute(
                'aria-label',
                isHidden ? 'Sembunyikan password' : 'Tampilkan password'
            );
        });
    </script>
</body>
</html>
