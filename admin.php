<?php
session_start();
require_once __DIR__ . '/../database/db.php';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Process Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: admin.php');
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}

// Hitung total pengunjung
$stmt = $pdo->query("SELECT COUNT(*) AS total_visitors FROM visitors");
$totalVisitors = $stmt->fetch()['total_visitors'];


// Check if logged in
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Karang Taruna</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>

<?php if (!$isLoggedIn): ?>
    <!-- Login Form -->
    <div class="login-container">
        <div class="login-box">
            <h1>Admin Panel</h1>
            <p>Karang Taruna Atma Muda Nawasena</p>

            <?php if (isset($error)): ?>
                <div class="alert error"><?= $error ?></div>
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
                <button type="submit" name="login" class="btn-primary">Login</button>
                <div class="login-footer">
                    <a href="register.php" class="btn-register">Daftar Admin Baru</a>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- Admin Dashboard -->
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Halo, <?= $_SESSION['admin_username'] ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="admin.php" class="active">Dashboard</a>
                <a href="admin-kegiatan.php">Kelola Kegiatan</a>
                <a href="admin-galeri.php">Kelola Galeri</a>
                <a href="admin-struktur.php">Kelola Struktur</a>
                <a href="../index-news.php" target="_blank">Lihat Website</a>
                <a href="?logout=1" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1>Dashboard</h1>
            </div>

            <div class="stats-grid">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM kegiatan");
                $totalKegiatan = $stmt->fetch()['total'];

                $stmt = $pdo->query("SELECT COUNT(*) as total FROM galeri");
                $totalGaleri = $stmt->fetch()['total'];

                $stmt = $pdo->query("SELECT COUNT(*) as total FROM struktur");
                $totalStruktur = $stmt->fetch()['total'];
                ?>

                <div class="stat-card">
                    <h3>Total Kegiatan</h3>
                    <p class="stat-number"><?= $totalKegiatan ?></p>
                </div>

                <div class="stat-card">
                    <h3>Total Galeri</h3>
                    <p class="stat-number"><?= $totalGaleri ?></p>
                </div>

                <div class="stat-card">
                    <h3>Pengurus</h3>
                    <p class="stat-number"><?= $totalStruktur ?></p>
                </div>

                <div class="stat-card">
                    <h3 class="text-muted">TOTAL PENGUNJUNG</h3>
                    <h2 class="mt-2"><?= $totalVisitors ?></h2>
                </div>
    
            </div>

            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="admin-kegiatan.php?action=add" class="btn-action">Tambah Kegiatan</a>
                    <a href="admin-galeri.php?action=add" class="btn-action">Tambah Galeri</a>
                    <a href="admin-struktur.php?action=add" class="btn-action">Tambah Struktur</a>
                </div>
            </div>
        </main>
    </div>
<?php endif; ?>

</body>
</html>