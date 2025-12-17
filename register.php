<?php
require_once __DIR__ . '/../database/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = 'Username sudah digunakan';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
            $success = 'Registrasi berhasil, silakan login';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Admin</title>
<link rel="stylesheet" href="admin-style.css">
</head>
<body>

<div class="login-container">
    <div class="login-box">
        <h1>Daftar Admin</h1>

        <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn-primary">Daftar</button>
            <div class="login-footer">
                <a href="admin.php">← Kembali ke Login</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
