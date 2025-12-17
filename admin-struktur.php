<?php
session_start();
require_once __DIR__ . '/../database/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

// DELETE
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("SELECT foto FROM struktur WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $old = $stmt->fetch();

    if ($old && $old['foto'] && file_exists(__DIR__ . '/../uploads/' . $old['foto'])) {
        unlink(__DIR__ . '/../uploads/' . $old['foto']);
    }

    $stmt = $pdo->prepare("DELETE FROM struktur WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: admin-struktur.php');
    exit;
}

// ADD / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $jabatan = $_POST['jabatan'];
    $sub_jabatan = $_POST['sub_jabatan'];
    $urutan = $_POST['urutan'];
    $foto = $_POST['foto_lama'];

    // Upload foto
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === 0) {
        $uploadDir = __DIR__ . '/../uploads/';
        $fileName = time() . '_' . basename($_FILES['foto']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $filePath)) {
            $foto = $fileName;
        }
    }

    // Update
    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE struktur SET nama=?, jabatan=?, sub_jabatan=?, foto=?, urutan=? WHERE id=?");
        $stmt->execute([$nama, $jabatan, $sub_jabatan, $foto, $urutan, $_POST['id']]);
    }
    // Insert
    else {
        $stmt = $pdo->prepare("INSERT INTO struktur (nama, jabatan, sub_jabatan, foto, urutan) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $jabatan, $sub_jabatan, $foto, $urutan]);
    }

    header('Location: admin-struktur.php');
    exit;
}

// Edit data
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM struktur WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Fetch all
$stmt = $pdo->query("SELECT * FROM struktur ORDER BY urutan ASC");
$strukturList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Struktur - Admin</title>
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
                <a href="admin-struktur.php" class="active">Kelola Struktur</a>
                <a href="../index-news.php" target="_blank">Lihat Website</a>
                <a href="admin.php?logout=1" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1>Kelola Struktur Organisasi</h1>
            </div>

            <div class="form-container">
                <h2><?= $editData ? 'Edit' : 'Tambah' ?> Anggota</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
                    <input type="hidden" name="foto_lama" value="<?= $editData['foto'] ?? '' ?>">

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" value="<?= $editData['nama'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Jabatan</label>
                        <select name="jabatan" required>
                            <option value="">Pilih Jabatan</option>
                            <?php
                            $jabatanList = ['Ketua','Wakil Ketua','Sekretaris','Bendahara','Humas','Lainnya'];
                            foreach ($jabatanList as $j):
                            ?>
                                <option value="<?= $j ?>" <?= ($editData['jabatan'] ?? '') === $j ? 'selected' : '' ?>>
                                    <?= $j ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Sub Jabatan (opsional)</label>
                        <input type="text" name="sub_jabatan" value="<?= $editData['sub_jabatan'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Urutan</label>
                        <input type="number" name="urutan" value="<?= $editData['urutan'] ?? 0 ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Foto</label>
                        <input type="file" name="foto" accept="image/*">
                        <?php if ($editData && $editData['foto']): ?>
                            <p class="small-text">Foto sekarang: <?= htmlspecialchars($editData['foto']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Simpan</button>
                        <?php if ($editData): ?>
                            <a href="admin-struktur.php" class="btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <h2>Daftar Struktur Organisasi</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Urutan</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Sub Jabatan</th>
                            <th>Foto</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($strukturList as $item): ?>
                        <tr>
                            <td><?= $item['urutan'] ?></td>
                            <td><?= $item['nama'] ?></td>
                            <td><?= $item['jabatan'] ?></td>
                            <td><?= $item['sub_jabatan'] ?: '-' ?></td>
                            <td>
                                <?php if ($item['foto']): ?>
                                    <img src="../uploads/<?= htmlspecialchars($item['foto']) ?>" class="table-img">
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
