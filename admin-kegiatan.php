<?php
session_start();
require_once __DIR__ . '/../database/db.php';

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM kegiatan WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin-kegiatan.php');
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = $_POST['judul'];
    $tanggal = $_POST['tanggal'];
    $deskripsi = $_POST['deskripsi'];
    $foto = $_POST['gambar_lama']; // default: foto lama

    // Upload gambar jika ada file baru
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
        $uploadDir = __DIR__ . '/../uploads/';
        $fileName = time() . '_' . basename($_FILES['gambar']['name']);
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadFile)) {
            $foto = $fileName; // ganti foto lama
        }
    }

    // Update
    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE kegiatan SET judul=?, tanggal=?, deskripsi=?, foto=? WHERE id=?");
        $stmt->execute([$judul, $tanggal, $deskripsi, $foto, $_POST['id']]);
    } 
    // Insert
    else {
        $stmt = $pdo->prepare("INSERT INTO kegiatan (judul, tanggal, deskripsi, foto) VALUES (?, ?, ?, ?)");
        $stmt->execute([$judul, $tanggal, $deskripsi, $foto]);
    }

    header('Location: admin-kegiatan.php');
    exit;
}

// Get data for edit
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM kegiatan WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Get all kegiatan
$stmt = $pdo->query("SELECT * FROM kegiatan ORDER BY tanggal DESC");
$kegiatanList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kegiatan - Admin</title>
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
                <a href="admin-kegiatan.php" class="active">Kelola Kegiatan</a>
                <a href="admin-galeri.php">Kelola Galeri</a>
                <a href="admin-struktur.php">Kelola Struktur</a>
                <a href="../index-news.php" target="_blank">Lihat Website</a>
                <a href="admin.php?logout=1" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1>Kelola Kegiatan</h1>
            </div>

            <!-- Form Add/Edit -->
            <div class="form-container">
                <h2><?= $editData ? 'Edit' : 'Tambah' ?> Kegiatan</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
                    <input type="hidden" name="gambar_lama" value="<?= $editData['foto'] ?? '' ?>">

                    <div class="form-group">
                        <label>Judul Kegiatan</label>
                        <input type="text" name="judul" value="<?= $editData['judul'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" value="<?= $editData['tanggal'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="3"><?= $editData['deskripsi'] ?? '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Gambar</label>
                        <input type="file" name="gambar" accept="image/*">
                        <?php if ($editData && $editData['foto']): ?>
                            <p class="small-text">Gambar saat ini: <?= $editData['foto'] ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Simpan</button>
                        <?php if ($editData): ?>
                            <a href="admin-kegiatan.php" class="btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- List -->
            <div class="table-container">
                <h2>Daftar Kegiatan</h2>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Tanggal</th>
                            <th>Foto</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kegiatanList as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= $item['judul'] ?></td>
                            <td><?= date('d M Y', strtotime($item['tanggal'])) ?></td>
                            <td>
                                <?php if ($item['foto']): ?>
                                    <img src="../uploads/<?= $item['foto']; ?>" width="80">
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?edit=<?= $item['id'] ?>" class="btn-edit">Edit</a>
                                <a href="?delete=<?= $item['id'] ?>" class="btn-delete" onclick="return confirm('Yakin hapus?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
