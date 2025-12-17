<?php
session_start();
require_once __DIR__ . '/../database/db.php';

// -----------------------------
// Konfigurasi upload
// -----------------------------
define('UPLOAD_DIR', __DIR__ . '/../uploads/'); // folder fisik
$MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB
$ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// -----------------------------
// Helper functions
// -----------------------------
function ensure_upload_dir_exists() {
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
}

function generate_unique_filename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $random = bin2hex(random_bytes(6));
    return time() . '_' . $random . '.' . $ext;
}

function is_uploaded_image_valid($file, $maxSize, $allowedExt, $allowedMime, &$errorMsg = '') {
    if (!isset($file) || !isset($file['error'])) {
        $errorMsg = 'No file uploaded.';
        return false;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Upload error code: ' . $file['error'];
        return false;
    }

    if ($file['size'] > $maxSize) {
        $errorMsg = 'File terlalu besar. Maksimum ' . ($maxSize / (1024*1024)) . ' MB.';
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        $errorMsg = 'Ekstensi file tidak diizinkan.';
        return false;
    }

    // Validasi MIME type menggunakan finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMime)) {
        $errorMsg = 'Tipe file tidak diizinkan.';
        return false;
    }

    return true;
}

// -----------------------------
// Cek login
// -----------------------------
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

// Pesan untuk debug/feedback (opsional, tidak memecah alur)
$flashError = null;

// -----------------------------
// Hapus data
// -----------------------------
if (isset($_GET['delete'])) {
    $idToDelete = (int) $_GET['delete'];
    try {
        // ambil nama file untuk dihapus
        $stmt = $pdo->prepare("SELECT foto FROM galeri WHERE id = ?");
        $stmt->execute([$idToDelete]);
        $row = $stmt->fetch();

        if ($row && !empty($row['foto'])) {
            $oldFile = UPLOAD_DIR . $row['foto'];
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM galeri WHERE id = ?");
        $stmt->execute([$idToDelete]);
    } catch (PDOException $e) {
        // log error jika perlu, set flash
        $flashError = 'Gagal menghapus data: ' . $e->getMessage();
    }

    header('Location: admin-galeri.php');
    exit;
}

// -----------------------------
// Tambah / Edit data (POST)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitasi dasar
    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $link      = trim($_POST['link'] ?? '');
    $id        = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $foto      = trim($_POST['gambar_lama'] ?? '');

    try {
        ensure_upload_dir_exists();

        // Jika ada file baru di-upload
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $validationErr = '';
            if (is_uploaded_image_valid($_FILES['gambar'], $MAX_FILE_SIZE, $ALLOWED_EXT, $ALLOWED_MIME, $validationErr)) {
                // generate nama file aman
                $safeName = generate_unique_filename($_FILES['gambar']['name']);
                $targetPath = UPLOAD_DIR . $safeName;

                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetPath)) {
                    // hapus file lama jika ada dan berbeda
                    if (!empty($foto) && $foto !== $safeName) {
                        $old = UPLOAD_DIR . $foto;
                        if (is_file($old)) {
                            @unlink($old);
                        }
                    }
                    $foto = $safeName;
                } else {
                    $flashError = 'Gagal memindahkan file upload.';
                }
            } else {
                // jika validasi gagal, set flashError (tidak melakukan DB)
                $flashError = $validationErr;
            }
        }

        // Jika tidak ada error validasi/upload, lakukan DB
        if (empty($flashError)) {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE galeri SET judul = ?, deskripsi = ?, foto = ?, link = ? WHERE id = ?");
                $stmt->execute([$judul, $deskripsi, $foto, $link, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO galeri (judul, deskripsi, foto, link, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$judul, $deskripsi, $foto, $link]);
            }

            header('Location: admin-galeri.php');
            exit;
        }
    } catch (PDOException $e) {
        $flashError = 'Terjadi kesalahan database: ' . $e->getMessage();
    } catch (Exception $e) {
        $flashError = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// -----------------------------
// Ambil data edit (GET edit)
// -----------------------------
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM galeri WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}

// -----------------------------
// Ambil list galeri
// -----------------------------
$stmt = $pdo->query("SELECT * FROM galeri ORDER BY created_at DESC");
$galeriList = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Galeri - Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        /* Sedikit styling fallback untuk preview pesan error */
        .flash-error { background:#ffe6e6; color:#900; padding:10px; margin:10px 0; border-radius:4px; }
        .table-img { max-width:120px; height:auto; display:block; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Halo, <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="admin.php">Dashboard</a>
                <a href="admin-kegiatan.php">Kelola Kegiatan</a>
                <a href="admin-galeri.php" class="active">Kelola Galeri</a>
                <a href="admin-struktur.php">Kelola Struktur</a>
                <a href="../index-news.php" target="_blank">Lihat Website</a>
                <a href="admin.php?logout=1" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1>Kelola Galeri</h1>
            </div>
    
            <?php if ($flashError): ?>
                <div class="flash-error"><?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>

            <!-- Form Tambah/Edit -->
            <div class="form-container">
                <h2><?= $editData ? 'Edit' : 'Tambah' ?> Galeri</h2>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
                    <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($editData['foto'] ?? '') ?>">

                    <div class="form-group">
                        <label>Judul</label>
                        <input type="text" name="judul" value="<?= htmlspecialchars($editData['judul'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="3"><?= htmlspecialchars($editData['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Link (Google Drive / lainnya)</label>
                        <input type="url" name="link" value="<?= htmlspecialchars($editData['link'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Gambar (maks 5MB, jpg/png/gif/webp)</label>
                        <input type="file" name="gambar" accept="image/*">
                        <?php if ($editData && $editData['foto']): ?>
                            <p class="small-text">Gambar saat ini: <?= htmlspecialchars($editData['foto']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Simpan</button>
                        <?php if ($editData): ?>
                            <a href="admin-galeri.php" class="btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabel List -->
            <div class="table-container">
                <h2>Daftar Galeri</h2>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Deskripsi</th>
                            <th>Gambar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($galeriList as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($item['judul']) ?></td>
                            <td><?= htmlspecialchars($item['deskripsi']) ?></td>
                            <td>
                                <?php if (!empty($item['foto'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($item['foto']) ?>" class="table-img" alt="Foto">
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
