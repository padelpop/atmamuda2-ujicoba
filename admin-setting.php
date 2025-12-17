<?php
session_start();
require_once __DIR__ . '/../database/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

// Handle Update Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
    }
    $success = "Pengaturan berhasil disimpan!";
}

// Get all settings
$stmt = $pdo->query("SELECT * FROM settings");
$setting = [];
while ($row = $stmt->fetch()) {
    $setting[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Halo, <?= $_SESSION['admin_username'] ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="admin.php">Dashboard</a>
                <a href="admin-kegiatan.php">Kelola Kegiatan</a>
                <a href="admin-galeri.php">Kelola Galeri</a>
                <a href="admin-struktur.php">Kelola Struktur</a>
                <a href="admin-settings.php" class="active">Pengaturan</a>
                <a href="../index-news.php" target="_blank">Lihat Website</a>
                <a href="admin.php?logout=1" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1>Pengaturan Website</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert success"><?= $success ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST">
                    <h3>Informasi Umum</h3>

                    <div class="form-group">
                        <label>Judul Website</label>
                        <input type="text" name="site_title" value="<?= $setting['site_title'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Subtitle/Tagline</label>
                        <input type="text" name="site_subtitle" value="<?= $setting['site_subtitle'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Tentang Kami</label>
                        <textarea name="about_text" rows="4" required><?= $setting['about_text'] ?? '' ?></textarea>
                    </div>

                    <h3>Kontak</h3>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="contact_email" value="<?= $setting['contact_email'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Instagram</label>
                        <input type="text" name="contact_instagram" value="<?= $setting['contact_instagram'] ?? '' ?>" placeholder="@username">
                    </div>

                    <div class="form-group">
                        <label>Alamat</label>
                        <input type="text" name="contact_address" value="<?= $setting['contact_address'] ?? '' ?>" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn-primary">Simpan Pengaturan</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>