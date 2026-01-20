<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$self = basename($_SERVER['PHP_SELF']);
$uploadDir = "../uploads/news/";

// --- SET TIMEZONE RIYADH ---
date_default_timezone_set('Asia/Riyadh');

// ====== ADD ======
if (isset($_POST['add'])) {
    $title       = $_POST['title'];
    $highlight   = $_POST['highlight'];
    $description = $_POST['description'];

    $fileName = null;
    if (!empty($_FILES["image"]["name"])) {
        $fileName = time().'_'.basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir.$fileName);
    }

    $created_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO blog_news (title, highlight, description, image, created_at) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss", $title, $highlight, $description, $fileName, $created_at);
    $stmt->execute();
    $stmt->close();

    header("Location: $self"); 
    exit;
}

// ====== UPDATE ======
if (isset($_POST['update'])) {
    $id          = $_POST['id'];
    $title       = $_POST['title'];
    $highlight   = $_POST['highlight'];
    $description = $_POST['description'];


    if (!empty($_FILES["image"]["name"])) {
        $fileName = time().'_'.basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir.$fileName);

        $stmt = $conn->prepare("UPDATE blog_news SET title=?, highlight=?, description=?, image=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $highlight, $description, $fileName, $id);
    } else {
        $stmt = $conn->prepare("UPDATE blog_news SET title=?, highlight=?, description=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $highlight, $description, $id);
    }

    $stmt->execute();
    $stmt->close();

    header("Location: $self"); 
    exit;
}

// ====== DELETE ======
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM blog_news WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: $self"); 
    exit;
}

// ====== SELECT ======
$result = $conn->query("SELECT * FROM blog_news ORDER BY created_at DESC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>News - Wish2Padel</title>
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
      <h2 class="fw-bold text-white mb-0">Manage Blog / News</h2>
      <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-2"></i> Add News
      </button>
    </div>

    <!-- Card untuk Table -->
    <div class="card shadow-lg border-0 rounded-3">
     
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead style="background:#343a40;">
              <tr class="text-white">
                <th style="width:60px;">No</th>
                <th>Title</th>
                <th style="width:100px;">Image</th>
                <th>Highlight</th>
                <th>Created</th>
                <th style="width:150px;">Action</th>
              </tr>
            </thead>
            <tbody>
<?php 
$no=1; 
$modalData = []; // <-- simpan data untuk modal
while($row = $result->fetch_assoc()): 
?>
  <tr>
    <td><?= $no++ ?></td>
    <td class="fw-semibold"><?= htmlspecialchars($row['title']) ?></td>
    <td>
      <?php if($row['image']): ?>
        <img src="../uploads/news/<?= $row['image'] ?>" class="img-fluid rounded shadow-sm" style="width:70px; height:50px; object-fit:cover;">
      <?php else: ?>
        <span class="text-muted fst-italic">No Image</span>
      <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($row['highlight']) ?></td>
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
<?php 
$modalData[] = $row; // simpan data
endwhile; 
?>
</tbody>

          </table>
          <?php foreach($modalData as $row): ?>
<!-- Edit Modal -->
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:150px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-dark" style="background:#f3e6b6">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit News</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($row['title']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Highlight</label>
            <input type="text" name="highlight" class="form-control" value="<?= htmlspecialchars($row['highlight']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="4"><?= $row['description'] ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Change Image</label>
            <input type="file" name="image" class="form-control">
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:150px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete News</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete <b><?= htmlspecialchars($row['title']) ?></b>?</p>
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

        </div>
      </div>
    </div>
  </div>
</section>


<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:150px; margin-bottom:50px">
        <div class="modal-content">
            <div class="modal-header" style="background:#bfa14a; color:#fff;">
                <h5 class="modal-title">Add News</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Highlight</label>
                        <input type="text" name="highlight" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Image</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn-gold">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
ClassicEditor
    .create( document.querySelector( '#description' ), {
        toolbar: [ 'bold', 'italic', 'underline', '|', 'bulletedList', 'numberedList', '|', 'undo', 'redo' ]
    } )
    .catch( error => {
        console.error( error );
    } );
</script>


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
