<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;

$uploadDir = "../uploads/template/"; // direktori upload di server (filesystem)
$dbDir     = "uploads/template/";    // path yang disimpan di database (untuk <a href>)

// --- BUAT FOLDER JIKA BELUM ADA ---
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// --- SET TIMEZONE RIYADH ---
date_default_timezone_set('Asia/Riyadh');

// Helper: validasi PDF by MIME & ext
function isValidPdf($fileTmp, $fileName) {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') return false;

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);
        // beberapa server mengirim application/pdf, kadang octet-stream saat relay; minimal cek pdf magic
        if ($mime !== 'application/pdf') {
            // fallback: cek 4 byte pertama "%PDF"
            $fh = fopen($fileTmp, 'rb');
            $sig = $fh ? fread($fh, 4) : '';
            if ($fh) fclose($fh);
            if ($sig !== "%PDF") return false;
        }
    }
    return true;
}

// ====== ADD ======
if (isset($_POST['add'])) {
    $doc_name = trim($_POST['doc_name'] ?? '');
    if ($doc_name === '') {
        die("Document name is required.");
    }

    $filePath = null;

    if (!empty($_FILES["pdf"]["name"])) {
        if (!isValidPdf($_FILES["pdf"]["tmp_name"], $_FILES["pdf"]["name"])) {
            die("Only valid PDF files are allowed.");
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($_FILES["pdf"]["name"]));
        $fileName = time().'_'.$safeBase; // unik + aman
        $target   = $uploadDir.$fileName;

        if (!move_uploaded_file($_FILES["pdf"]["tmp_name"], $target)) {
            die("Failed to upload file.");
        }
        $filePath = $dbDir.$fileName;
    } else {
        die("PDF file is required.");
    }

    $created_at = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO documents (doc_name, file_path, created_at) VALUES (?,?,?)");
    $stmt->bind_param("sss", $doc_name, $filePath, $created_at);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ====== UPDATE (nama + opsional ganti PDF) ======
// ====== UPDATE (nama + opsional ganti PDF) ======
if (isset($_POST['update'])) {
    $id       = (int)($_POST['id'] ?? 0);
    $doc_name = trim($_POST['doc_name'] ?? '');

    if ($id <= 0) die("Invalid ID.");
    if ($doc_name === '') die("Document name is required.");

    // Ambil file_path lama untuk delete jika ada file baru
    $old = $conn->prepare("SELECT file_path FROM documents WHERE id=? LIMIT 1");
    $old->bind_param("i", $id);
    $old->execute();
    $oldRes = $old->get_result();
    $oldRow = $oldRes->fetch_assoc();
    $old->close();

    $newFilePath = null;
    $replaceFile = !empty($_FILES["pdf"]["name"]);

    if ($replaceFile) {
        if (!isValidPdf($_FILES["pdf"]["tmp_name"], $_FILES["pdf"]["name"])) {
            die("Only valid PDF files are allowed.");
        }
        $safeBase = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($_FILES["pdf"]["name"]));
        $fileName = time().'_'.$safeBase;
        $target   = $uploadDir.$fileName;

        if (!move_uploaded_file($_FILES["pdf"]["tmp_name"], $target)) {
            die("Failed to upload file.");
        }
        $newFilePath = $dbDir.$fileName;

        // ✅ FIX TANPA updated_at
        $stmt = $conn->prepare("UPDATE documents SET doc_name=?, file_path=? WHERE id=?");
        $stmt->bind_param("ssi", $doc_name, $newFilePath, $id);
        $stmt->execute();
        $stmt->close();

        // Hapus file lama (jika ada)
        if (!empty($oldRow['file_path'])) {
            $oldFs = dirname(__DIR__) . '/' . $oldRow['file_path'];
            if (!file_exists($oldFs)) {
                $oldFs = __DIR__ . '/../' . $oldRow['file_path'];
            }
            if (is_file($oldFs)) @unlink($oldFs);
        }
    } else {
        // ✅ FIX TANPA updated_at
        $stmt = $conn->prepare("UPDATE documents SET doc_name=? WHERE id=?");
        $stmt->bind_param("si", $doc_name, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// ====== DELETE ======
if (isset($_POST['delete'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) die("Invalid ID.");

    // Ambil file untuk dihapus
    $old = $conn->prepare("SELECT file_path FROM documents WHERE id=? LIMIT 1");
    $old->bind_param("i", $id);
    $old->execute();
    $oldRes = $old->get_result();
    $oldRow = $oldRes->fetch_assoc();
    $old->close();

    $stmt = $conn->prepare("DELETE FROM document WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    if (!empty($oldRow['file_path'])) {
        $oldFs = dirname(__DIR__) . '/' . $oldRow['file_path'];
        if (!file_exists($oldFs)) {
            $oldFs = __DIR__ . '/../' . $oldRow['file_path'];
        }
        if (is_file($oldFs)) @unlink($oldFs);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ====== SELECT ======
$result = $conn->query("SELECT * FROM documents ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Documents - Wish2Padel</title>
    <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-color: #303030">

<?php require 'src/navbar.php' ?>

<section class="py-5">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-white mb-0">Manage Documents</h2>
      <!--<button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-2"></i> Add Document
      </button>-->
    </div>

    <!-- Card untuk Table -->
    <div class="card shadow-lg border-0 rounded-3">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead style="background:#343a40;">
              <tr class="text-white">
                <th style="width:60px;">No</th>
                <th>Document Name</th>
                <th>File</th>
                <th>Created</th>
                <th style="width:210px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $no=1; 
              $docModals = []; // kumpulkan data untuk modal
              while($row = $result->fetch_assoc()): 
                $docModals[] = $row; 
              ?>
              <tr>
                <td><?= $no++ ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($row['doc_name']) ?></td>
                <td>
                  <?php if($row['file_path']): ?>
                    <i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>
                    <a href="../<?= htmlspecialchars($row['file_path']) ?>" target="_blank">View</a>
                    <span class="text-muted">·</span>
                    <a href="../<?= htmlspecialchars($row['file_path']) ?>" download>Download</a>
                  <?php else: ?>
                    <span class="text-muted fst-italic">No File</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-light text-dark">
                    <?= date("d M Y H:i", strtotime($row['created_at'])) ?>
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======================= ALL DOCUMENT MODALS MOVED HERE ======================= -->
<?php foreach($docModals as $row): ?>

<!-- ======================= Edit Modal ======================= -->
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:70px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-dark" style="background:#f3e6b6">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Document</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Document Name</label>
            <input type="text" name="doc_name" class="form-control" value="<?= htmlspecialchars($row['doc_name']) ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">Replace PDF (optional)</label>
            <input type="file" name="pdf" class="form-control" accept="application/pdf">
            <small class="text-muted">
              Current: 
              <?php if($row['file_path']): ?>
                <a href="../<?= htmlspecialchars($row['file_path']) ?>" target="_blank">View file</a>
              <?php else: ?>
                <em>No file</em>
              <?php endif; ?>
            </small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update" class="btn-gold rounded-pill px-4">Save</button>
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================= Delete Modal ======================= -->
<div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:150px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete <strong><?= htmlspecialchars($row['doc_name']) ?></strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="submit" form="deleteForm<?= $row['id'] ?>" class="btn btn-danger rounded-pill px-4">Delete</button>
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
      </div>
      <form method="POST" id="deleteForm<?= $row['id'] ?>">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <input type="hidden" name="delete" value="1">
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>


<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:70px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-dark" style="background:#f3e6b6">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Document</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Document Name</label>
            <input type="text" name="doc_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Upload PDF</label>
            <input type="file" name="pdf" class="form-control" accept="application/pdf" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add" class="btn-gold rounded-pill px-4">Save</button>
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Scroll to Top Button -->
<button id="scrollTopBtn" title="Go to top">↑</button>

<script>
  const scrollBtn = document.getElementById("scrollTopBtn");
  window.onscroll = function() {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
      scrollBtn.style.display = "block";
    } else {
      scrollBtn.style.display = "none";
    }
  };
  scrollBtn.addEventListener("click", function() {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
