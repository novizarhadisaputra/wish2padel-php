<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$uploadDir = "../uploads/presentasion/"; // direktori upload di server
$dbDir     = "uploads/presentasion/";    // path yang disimpan di database

// --- BUAT FOLDER JIKA BELUM ADA ---
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// --- SET TIMEZONE RIYADH ---
date_default_timezone_set('Asia/Riyadh');

// ====== ADD ======
if (isset($_POST['add'])) {
    $description = $_POST['description'];
    $filePath = null;

    if (!empty($_FILES["image"]["name"])) {
        $allowedExt = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            die("Only JPG, JPEG, PNG files are allowed.");
        }

        $fileName = time().'_'.basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir.$fileName);
        $filePath = $dbDir.$fileName;
    }

    $created_at = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO presentations (description, file_path, created_at) VALUES (?,?,?)");
    $stmt->bind_param("sss", $description, $filePath, $created_at);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ====== UPDATE ======
if (isset($_POST['update'])) {
    $id          = $_POST['id'];
    $description = $_POST['description'];

    if (!empty($_FILES["image"]["name"])) {
        $allowedExt = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            die("Only JPG, JPEG, PNG files are allowed.");
        }

        $fileName = time().'_'.basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir.$fileName);
        $filePath = $dbDir.$fileName;

        $stmt = $conn->prepare("UPDATE presentations SET description=?, file_path=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssi", $description, $filePath, $id);
    } else {
        $stmt = $conn->prepare("UPDATE presentations SET description=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $description, $id);
    }

    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ====== DELETE ======
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM presentations WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// ====== SELECT ======
$result = $conn->query("SELECT * FROM presentations ORDER BY created_at DESC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Presentations - Wish2Padel</title>
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
      <h2 class="fw-bold text-white mb-0">Manage Presentations</h2>
      <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-2"></i> Add Presentation
      </button>
    </div>

    <!-- Card Table -->
    <div class="card shadow-lg border-0 rounded-3">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead style="background:#343a40;">
              <tr class="text-white">
                <th style="width:60px;">No</th>
                <th style="width:120px;">Image</th>
                <th>Description</th>
                <th>Created</th>
                <th style="width:150px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $no = 1; 
              $modalData = []; // kumpulkan data modal
              while($row = $result->fetch_assoc()): 
                $modalData[] = $row;
              ?>
              <tr>
                <td><?= $no++ ?></td>
                <td>
                  <?php if($row['file_path']): ?>
                    <img src="../<?= htmlspecialchars($row['file_path']) ?>" 
                         class="img-fluid rounded shadow-sm" 
                         style="width:100px; height:70px; object-fit:cover;">
                  <?php else: ?>
                    <span class="text-muted fst-italic">No Image</span>
                  <?php endif; ?>
                </td>
                <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
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

<!-- ======================= ALL PRESENTATION MODALS MOVED HERE ======================= -->
<?php foreach($modalData as $row): ?>

<!-- ===== Edit Modal ===== -->
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:70px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-dark" style="background:#f3e6b6">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Presentation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($row['description']) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Change Image</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <small class="text-muted">
              Current: 
              <?php if($row['file_path']): ?>
                <a href="../<?= htmlspecialchars($row['file_path']) ?>" target="_blank">View image</a>
              <?php else: ?>
                <em>No image</em>
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

<!-- ===== Delete Modal ===== -->
<div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:150px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete Presentation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this presentation?</p>
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
        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Presentation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="4"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Upload Image</label>
            <input type="file" name="image" class="form-control" required>
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

  // Show/hide button on scroll
  window.onscroll = function() {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
      scrollBtn.style.display = "block";
    } else {
      scrollBtn.style.display = "none";
    }
  };

  // Scroll to top smoothly
  scrollBtn.addEventListener("click", function() {
    window.scrollTo({
      top: 0,
      behavior: "smooth"
    });
  });
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
