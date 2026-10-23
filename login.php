
<?php
session_start();

require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header(
        'Location: ' .
        ($_SESSION['role'] === 'admin' ? 'admin.php' : 'index.php')
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

        if (
            $user &&
            password_verify($password, $user['password'])
        ) {
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
    <title>Login - Wisata Riau</title>
</head>
<body>
    <h1>Login Wisata Riau</h1>
    <p>Silakan masuk menggunakan akun kamu.</p>

    <?php if ($error !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <label for="email">Email</label><br>
        <input
            type="email"
            id="email"
            name="email"
            autocomplete="username"
            required
        ><br><br>

        <label for="password">Password</label><br>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        ><br><br>

        <button type="submit">Login</button>
    </form>

    <p><a href="index.php">Kembali ke beranda</a></p>
</body>
</html>
