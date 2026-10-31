<?php
// 1. KONEKSI DATABASE
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'perpustakaan';

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    exit("Koneksi ke database gagal: " . $e->getMessage());
}

// Inisialisasi variabel utama
$edit_buku = null;
$pesan = '';
$jenisPesan = '';
$error = [];

// DETEKSI NAMA KOLOM TAHUN DI TABEL DATABASE AUTOMATIS
$kolom_tahun = 'tahun';
try {
    $check_col = $conn->query("SHOW COLUMNS FROM buku");
    $columns = $check_col->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('terbit', $columns)) {
        $kolom_tahun = 'terbit';
    } elseif (in_array('tahun_terbit', $columns)) {
        $kolom_tahun = 'tahun_terbit';
    }
} catch (Exception $e) {
    $kolom_tahun = 'tahun';
}

// 2. FITUR DELETE (HAPUS DATA BUKU)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_hapus = (int)$_GET['id'];
    try {
        $stmt_del = $conn->prepare("DELETE FROM buku WHERE id = :id");
        $stmt_del->bindParam(':id', $id_hapus);
        $stmt_del->execute();
        header("Location: index.php?status=deleted");
        exit();
    } catch (PDOException $e) {
        header("Location: index.php?status=error");
        exit();
    }
}

// 3. FITUR FETCH DATA UNTUK EDIT
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_edit = (int)$_GET['id'];
    $stmt_edit = $conn->prepare("SELECT * FROM buku WHERE id = :id");
    $stmt_edit->bindParam(':id', $id_edit);
    $stmt_edit->execute();
    $edit_buku = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}

// 4. FITUR CREATE & UPDATE (SIMPAN / EDIT DATA BUKU)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $judul = trim($_POST['judul'] ?? '');
    $penulis = trim($_POST['penulis'] ?? '');
    $terbit = $_POST['terbit'] ?? '';
    $stok = $_POST['stok'] ?? '';

    if (empty($judul) || strlen($judul) < 3) {
        $error[] = "Judul buku harus diisi minimal 3 karakter.";
    }
    if (empty($penulis)) {
        $error[] = "Penulis harus diisi.";
    }
    if (!is_numeric($terbit)) {
        $error[] = "Tahun terbit harus berupa angka.";
    }
    if (!is_numeric($stok)) {
        $error[] = "Stok harus berupa angka.";
    }

    if (empty($error)) {
        try {
            if (!empty($id)) {
                // UPDATE DATA
                $sql = "UPDATE buku SET judul = :judul, penulis = :penulis, $kolom_tahun = :tahun, stok = :stok WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':judul', $judul);
                $stmt->bindParam(':penulis', $penulis);
                $stmt->bindParam(':tahun', $terbit);
                $stmt->bindParam(':stok', $stok);
                $stmt->execute();
                header("Location: index.php?status=updated");
                exit();
            } else {
                // INSERT DATA BARU
                $sql = "INSERT INTO buku (judul, penulis, $kolom_tahun, stok, created_at) VALUES (:judul, :penulis, :tahun, :stok, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':judul', $judul);
                $stmt->bindParam(':penulis', $penulis);
                $stmt->bindParam(':tahun', $terbit);
                $stmt->bindParam(':stok', $stok);
                $stmt->execute();
                header("Location: index.php?status=success");
                exit();
            }
        } catch (PDOException $e) {
            header("Location: index.php?status=error");
            exit();
        }
    } else {
        $pesan = implode("<br>", $error);
        $jenisPesan = 'danger';
    }
}

// Status Notifikasi
$status = $_GET['status'] ?? '';
if ($status === 'success') {
    $pesan = "Data buku berhasil ditambahkan.";
    $jenisPesan = 'success';
} elseif ($status === 'updated') {
    $pesan = "Data buku berhasil diperbarui.";
    $jenisPesan = 'success';
} elseif ($status === 'deleted') {
    $pesan = "Data buku berhasil dihapus.";
    $jenisPesan = 'warning';
} elseif ($status === 'error') {
    $pesan = "Terjadi kesalahan pada server/database.";
    $jenisPesan = 'danger';
}

// 5. AMBIL DATA
$list_buku = [];
try {
    $query = $conn->query("SELECT * FROM buku ORDER BY id DESC");
    if ($query) {
        $list_buku = $query->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $list_buku = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Praktik Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Header -->
    <header class="bg-primary text-white p-3">
        <div class="container-fluid">
            <h1 class="h3 m-0">Website Praktik Perpustakaan</h1>
        </div>
    </header>

    <!-- Navigasi -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-info mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Navbar</a>
            <form class="d-flex" role="search">
                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-light" type="submit">Search</button>
            </form>
        </div>
    </nav>

    <!-- Content Utama -->
    <div class="container-fluid my-4">
        <div class="row">
            <!-- Sidebar Menu -->
            <aside class="col-md-3 mb-4">
                <div class="bg-white border rounded p-3 shadow-sm">
                    <h2>Menu</h2>
                    <ul class="list-group">
                        <li class="list-group-item"><a href="#" class="text-decoration-none text-dark">Dashboard</a></li>
                        <li class="list-group-item active"><a href="index.php" class="text-decoration-none text-white">Daftar Buku</a></li>
                        <li class="list-group-item"><a href="#" class="text-decoration-none text-dark">Form Peminjaman</a></li>
                    </ul>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9">
                <!-- Section Form Tambah / Edit Buku -->
                <section class="border rounded p-4 bg-white shadow-sm mb-4">
                    <h2><?= $edit_buku ? 'Form Edit Buku' : 'Form Tambah Buku' ?></h2>
                    
                    <form action="index.php" method="post" id="form-buku" class="needs-validation" novalidate>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($edit_buku['id'] ?? '') ?>" />
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="judul" class="form-label">Judul Buku</label>
                                <input type="text" class="form-control" name="judul" id="judul" value="<?= htmlspecialchars($edit_buku['judul'] ?? $edit_buku['Judul'] ?? '') ?>" required minlength="3"> 
                                <div class="invalid-feedback">Minimal 3 karakter</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="penulis" class="form-label">Penulis</label>
                                <input type="text" class="form-control" name="penulis" id="penulis" value="<?= htmlspecialchars($edit_buku['penulis'] ?? $edit_buku['Penulis'] ?? '') ?>" required>
                                <div class="invalid-feedback">Isi Penulis</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="terbit" class="form-label">Tahun Terbit</label>
                                <input type="number" class="form-control" name="terbit" min="1900" max="2026" id="terbit" value="<?= htmlspecialchars($edit_buku['tahun'] ?? $edit_buku['terbit'] ?? $edit_buku['tahun_terbit'] ?? $edit_buku['Tahun'] ?? '') ?>" required>
                                <div class="invalid-feedback">Isi Tahun Terbit</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="stok" class="form-label">Stok</label>
                                <input type="number" class="form-control" name="stok" min="0" step="1" id="stok" value="<?= htmlspecialchars($edit_buku['stok'] ?? $edit_buku['Stok'] ?? 0) ?>" required>
                                <div class="invalid-feedback">Stok tidak boleh kosong</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <?= $edit_buku ? 'Simpan Perubahan' : 'Tambah Buku' ?>
                        </button>
                        
                        <?php if ($edit_buku): ?>
                            <a href="index.php" class="btn btn-secondary ms-2">Batal Edit</a>
                        <?php endif; ?>
                    </form>
                </section>

                <!-- Section Tabel Daftar Buku -->
                <section class="border rounded p-4 bg-white shadow-sm">
                    <h2>Daftar Buku</h2>
                    
                    <?php if ($pesan !== ''): ?>
                        <div class="alert alert-<?= $jenisPesan ?> alert-dismissible fade show" role="alert">
                            <?= $pesan ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Penulis</th>
                                    <th>Tahun Terbit</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($list_buku)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Tidak ada data buku. Silakan tambahkan data di atas.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($list_buku as $index => $buku): 
                                        $val_id = $buku['id'] ?? $buku['ID'] ?? 0;
                                        $val_judul = $buku['judul'] ?? $buku['Judul'] ?? '-';
                                        $val_penulis = $buku['penulis'] ?? $buku['Penulis'] ?? '-';
                                        
                                        // MENCARI SELURUH KEMUNGKINAN NAMA KOLOM TAHUN DI ARRAY DATABASE
                                        $val_tahun = '-';
                                        foreach ($buku as $key => $value) {
                                            if (in_array(strtolower($key), ['tahun', 'terbit', 'tahun_terbit', 'tahunterbit']) && $value !== null && $value !== '') {
                                                $val_tahun = $value;
                                                break;
                                            }
                                        }

                                        $val_stok = $buku['stok'] ?? $buku['Stok'] ?? '0';
                                    ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars((string)$val_judul) ?></td>
                                            <td><?= htmlspecialchars((string)$val_penulis) ?></td>
                                            <td><?= htmlspecialchars((string)$val_tahun) ?></td>
                                            <td><?= htmlspecialchars((string)$val_stok) ?></td>
                                            <td>
                                                <?php if ($val_id > 0): ?>
                                                    <a href="index.php?action=edit&id=<?= $val_id ?>" class="btn btn-sm btn-warning me-1">Edit</a>
                                                    <a href="index.php?action=delete&id=<?= $val_id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini?')">Hapus</a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center p-3 mt-4">
        <p class="mb-0">&copy; 2026 Website Praktik Perpustakaan. All rights reserved.</p>
    </footer>

    <!-- JS Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const formbuku = document.getElementById("form-buku");
        if (formbuku) {
            formbuku.addEventListener("submit", function(event) {
                if (!formbuku.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                formbuku.classList.add("was-validated");
            });
        }
    </script>
</body>
</html>