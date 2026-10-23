
<?php
require_once 'auth.php';
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: destinasi.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (
    !is_string($token) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Token keamanan tidak valid.');
}

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID destinasi tidak valid.');
}

$stmt = $conn->prepare(
    "DELETE FROM destinasi WHERE id = ?"
);
$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    exit('Destinasi gagal dihapus.');
}

$deleted = $stmt->affected_rows;
$stmt->close();

if ($deleted === 0) {
    http_response_code(404);
    exit('Destinasi tidak ditemukan.');
}

header('Location: destinasi.php?status=dihapus');
exit;
