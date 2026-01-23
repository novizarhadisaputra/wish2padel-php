<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$self = "gallery.php";
$uploadDir = "../uploads/gallery/";

// --- SET TIMEZONE RIYADH ---
date_default_timezone_set('Asia/Riyadh');

/*************** MEDIA ACTIONS ***************/
if (isset($_POST['add_media'])) {
    $name = trim($_POST['media_name']);
    $cover = '';
    if (!empty($_FILES['cover_image']['name'])) {
        $cover = time().'_'.preg_replace('/\s+/', '_', basename($_FILES['cover_image']['name']));
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir.$cover);
    }
    if ($name !== '') {
        $created_at = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO media (name, cover_image, created_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $cover, $created_at);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: $self"); exit;
}

if (isset($_POST['update_media'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['media_name']);
    if ($id && $name !== '') {
        if (!empty($_FILES['cover_image']['name'])) {
            // Ambil data lama
            $q = $conn->prepare("SELECT cover_image FROM media WHERE id=?");
            $q->bind_param("i", $id);
            $q->execute();
            $old = $q->get_result()->fetch_assoc();

            // Simpan file baru
            $cover = time().'_'.preg_replace('/\s+/', '_', basename($_FILES['cover_image']['name']));
            move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir.$cover);

            // Hapus file lama
            if (!empty($old['cover_image']) && file_exists($uploadDir.$old['cover_image'])) {
                @unlink($uploadDir.$old['cover_image']);
            }

            // Update dengan gambar baru
            $stmt = $conn->prepare("UPDATE media SET name=?, cover_image=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $cover, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Update tanpa gambar
            $stmt = $conn->prepare("UPDATE media SET name=? WHERE id=?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: $self");
    exit;
}


if (isset($_POST['delete_media'])) {
    $id = intval($_POST['id']);
    if ($id) {
        $q = $conn->prepare("SELECT cover_image FROM media WHERE id=?");
        $q->bind_param("i",$id); $q->execute();
        $old = $q->get_result()->fetch_assoc();
        $stmt = $conn->prepare("DELETE FROM media WHERE id=?");
        $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
        if(!empty($old['cover_image']) && file_exists($uploadDir.$old['cover_image'])) @unlink($uploadDir.$old['cover_image']);
    }
    header("Location: $self"); exit;
}

/*************** CATEGORY ACTIONS ***************/
if (isset($_POST['add_category'])) {
    $media_id = intval($_POST['media_id']);
    $name = trim($_POST['category_name']);
    $cover = '';
    if(!empty($_FILES['cover_image']['name'])){
        $cover = time().'_'.preg_replace('/\s+/','_',basename($_FILES['cover_image']['name']));
        move_uploaded_file($_FILES['cover_image']['tmp_name'],$uploadDir.$cover);
    }
    if($media_id && $name!==''){
        $created_at = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO category (media_id,name,cover_image,created_at) VALUES (?,?,?,?)");
        $stmt->bind_param("isss",$media_id,$name,$cover,$created_at); $stmt->execute(); $stmt->close();
    }
    header("Location: $self"); exit;
}

if (isset($_POST['update_category'])) {
    $id = intval($_POST['id']);
    $media_id = intval($_POST['media_id']);
    $name = trim($_POST['category_name']);
    if ($id && $media_id && $name !== '') {
        if (!empty($_FILES['cover_image']['name'])) {
            // Ambil cover lama
            $q = $conn->prepare("SELECT cover_image FROM category WHERE id=?");
            $q->bind_param("i", $id);
            $q->execute();
            $old = $q->get_result()->fetch_assoc();

            // Upload cover baru
            $cover = time().'_'.preg_replace('/\s+/', '_', basename($_FILES['cover_image']['name']));
            move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir.$cover);

            // Hapus cover lama
            if (!empty($old['cover_image']) && file_exists($uploadDir.$old['cover_image'])) {
                @unlink($uploadDir.$old['cover_image']);
            }

            // Update dengan cover baru
            $stmt = $conn->prepare("UPDATE category SET media_id=?, name=?, cover_image=? WHERE id=?");
            $stmt->bind_param("issi", $media_id, $name, $cover, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Update tanpa cover
            $stmt = $conn->prepare("UPDATE category SET media_id=?, name=? WHERE id=?");
            $stmt->bind_param("isi", $media_id, $name, $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: $self");
    exit;
}


if (isset($_POST['delete_category'])) {
    $id = intval($_POST['id']);
    if($id){
        $q = $conn->prepare("SELECT cover_image FROM category WHERE id=?");
        $q->bind_param("i",$id); $q->execute();
        $old = $q->get_result()->fetch_assoc();
        $stmt = $conn->prepare("DELETE FROM category WHERE id=?");
        $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
        if(!empty($old['cover_image']) && file_exists($uploadDir.$old['cover_image'])) @unlink($uploadDir.$old['cover_image']);
    }
    header("Location: $self"); exit;
}

/*************** PHOTO ACTIONS ***************/
if(isset($_POST['add_photo'])){
    $category_id = intval($_POST['category_id']);
    if($category_id && !empty($_FILES['image']['name'])){
        $fileName = time().'_'.preg_replace('/\s+/','_',basename($_FILES['image']['name']));
        move_uploaded_file($_FILES['image']['tmp_name'],$uploadDir.$fileName);
        $created_at = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO photo (category_id,file_name,created_at) VALUES (?,?,?)");
        $stmt->bind_param("iss",$category_id,$fileName,$created_at); $stmt->execute(); $stmt->close();
    }
    header("Location: $self"); exit;
}

if (isset($_POST['update_photo'])) {
    $id = intval($_POST['id']);
    $category_id = intval($_POST['category_id']);
    if ($id && $category_id) {
        if (!empty($_FILES['image']['name'])) {
            // Ambil file lama
            $q = $conn->prepare("SELECT file_name FROM photo WHERE id=?");
            $q->bind_param("i", $id);
            $q->execute();
            $old = $q->get_result()->fetch_assoc();

            // Upload file baru
            $fileName = time().'_'.preg_replace('/\s+/', '_', basename($_FILES['image']['name']));
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir.$fileName);

            // Hapus file lama
            if (!empty($old['file_name']) && file_exists($uploadDir.$old['file_name'])) {
                @unlink($uploadDir.$old['file_name']);
            }

            // Update dengan file baru
            $stmt = $conn->prepare("UPDATE photo SET category_id=?, file_name=? WHERE id=?");
            $stmt->bind_param("isi", $category_id, $fileName, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Update tanpa ganti file
            $stmt = $conn->prepare("UPDATE photo SET category_id=? WHERE id=?");
            $stmt->bind_param("ii", $category_id, $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: $self");
    exit;
}


if(isset($_POST['delete_photo'])){
    $id = intval($_POST['id']);
    if($id){
        $q = $conn->prepare("SELECT file_name FROM photo WHERE id=?");
        $q->bind_param("i",$id); $q->execute();
        $old = $q->get_result()->fetch_assoc();
        $stmt = $conn->prepare("DELETE FROM photo WHERE id=?");
        $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
        if(!empty($old['file_name']) && file_exists($uploadDir.$old['file_name'])) @unlink($uploadDir.$old['file_name']);
    }
    header("Location: $self"); exit;
}

/*************** FETCH DATA ***************/
$medias = $conn->query("SELECT * FROM media ORDER BY created_at DESC");
$categories = $conn->query("SELECT c.*, m.name AS media_name FROM category c JOIN media m ON c.media_id=m.id ORDER BY c.created_at DESC");
$photos = $conn->query("SELECT p.*, c.name AS category_name, m.name AS media_name FROM photo p JOIN category c ON p.category_id=c.id JOIN media m ON c.media_id=m.id ORDER BY p.created_at DESC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Media - Wish2Padel</title>
  <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
 
</head>
<body style="background-color: #303030">

<?php require 'src/navbar.php' ?>

<section class="container py-5">

  <!-- ================= MEDIA ================= -->
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="fw-bold text-white m-0">Media</h3>
      <button class="btn-gold px-4" data-bs-toggle="modal" data-bs-target="#addMediaModal">+ Add Media</button>
  </div>

  <div class="table-responsive shadow-sm rounded mb-5">
      <table class="table table-dark table-hover align-middle mb-0">
          <thead class="bg-black text-gold">
              <tr>
                  <th>#</th>
                  <th>Media Name</th>
                  <th>Created</th>
                  <th>Action</th>
              </tr>
          </thead>
          <tbody>
<?php 
$no=1; 
$mediaModals = []; // kumpulkan data modal
while($m = $medias->fetch_assoc()): 
?>
  <tr class="row-fade">
      <td><?= $no++ ?></td>
      <td><?= htmlspecialchars($m['name']) ?></td>
      <td><?= date("d M Y H:i", strtotime($m['created_at'])) ?></td>
      <td>
          <button class="btn btn-sm btn-outline-gold" data-bs-toggle="modal" data-bs-target="#editMedia<?= $m['id'] ?>">Edit</button>
          <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteMedia<?= $m['id'] ?>">Delete</button>
      </td>
  </tr>
<?php 
$mediaModals[] = $m; 
endwhile; 
?>
          </tbody>
      </table>
  </div>

  <!-- Add Media Modal (tetap di sini agar tombol Add bekerja cepat) -->
  <div class="modal fade" id="addMediaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Media</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label>Media Name</label>
              <input type="text" name="media_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Cover Image</label>
              <input type="file" name="cover_image" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="add_media" class="btn-gold">Add</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php
  // Prepare media dropdown for category modals
  $mediaOptions = "";
  $mediaQuery = $conn->query("SELECT * FROM media ORDER BY name");
  while($mm = $mediaQuery->fetch_assoc()){
      $mediaOptions .= '<option value="'.$mm['id'].'">'.htmlspecialchars($mm['name']).'</option>';
  }
  ?>

  <!-- ================= CATEGORIES ================= -->
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="fw-bold text-white m-0">Categories</h3>
      <button class="btn-gold px-4" data-bs-toggle="modal" data-bs-target="#addCategoryModal">+ Add Category</button>
  </div>

  <div class="table-responsive shadow-sm rounded mb-5">
      <table class="table table-dark table-hover align-middle mb-0">
          <thead class="bg-black text-gold">
              <tr>
                  <th>#</th>
                  <th>Category Name</th>
                  <th>Media</th>
                  <th>Created</th>
                  <th>Action</th>
              </tr>
          </thead>
          <tbody>
<?php 
$no=1; 
$categoryModals = [];
while($c = $categories->fetch_assoc()): 
?>
  <tr class="row-fade">
      <td><?= $no++ ?></td>
      <td><?= htmlspecialchars($c['name']) ?></td>
      <td><?= htmlspecialchars($c['media_name']) ?></td>
      <td><?= date("d M Y H:i", strtotime($c['created_at'])) ?></td>
      <td>
          <button class="btn btn-sm btn-outline-gold" data-bs-toggle="modal" data-bs-target="#editCategory<?= $c['id'] ?>">Edit</button>
          <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCategory<?= $c['id'] ?>">Delete</button>
      </td>
  </tr>
<?php 
$categoryModals[] = $c; 
endwhile; 
?>
          </tbody>
      </table>
  </div>

  <!-- Add Category Modal -->
  <div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label>Media</label>
              <select name="media_id" class="form-select" required>
                  <?= $mediaOptions ?>
              </select>
            </div>
            <div class="mb-3">
              <label>Category Name</label>
              <input type="text" name="category_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Cover Image</label>
              <input type="file" name="cover_image" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="add_category" class="btn-gold">Add</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- ================= PHOTO ================= -->
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="fw-bold text-white m-0">Photo</h3>
      <button class="btn-gold px-4" data-bs-toggle="modal" data-bs-target="#addPhotoModal">+ Add Photo</button>
  </div>

  <div class="row g-4">
      <?php 
      $photoModals = [];
      while($p=$photos->fetch_assoc()): 
      $photoModals[]=$p;
      ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card gallery-card h-100">
                <div class="img-wrap">
                    <img src="<?= htmlspecialchars($uploadDir.$p['file_name']) ?>" class="img-fluid" alt="photo">
                </div>
                <div class="card-body text-white">
                    <span class="badge bg-gold text-white"><?= htmlspecialchars($p['category_name']) ?></span>
                    <small class="d-block mt-2"><?= htmlspecialchars($p['media_name']) ?></small>
                    <small class="d-block mt-1"><?= date("d M Y H:i", strtotime($p['created_at'])) ?></small>

                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-sm btn-outline-gold flex-fill" data-bs-toggle="modal" data-bs-target="#editPhoto<?= $p['id'] ?>">Edit</button>
                        <button class="btn btn-sm btn-danger flex-fill" data-bs-toggle="modal" data-bs-target="#deletePhoto<?= $p['id'] ?>">Delete</button>
                    </div>
                </div>
            </div>
        </div>
      <?php endwhile; ?>
  </div>

  <!-- Add Photo Modal -->
  <div class="modal fade" id="addPhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Photo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label>Category</label>
              <select name="category_id" class="form-select" required>
                  <?php
                  $catOptions = $conn->query("SELECT c.id, c.name, m.name AS media_name FROM category c JOIN media m ON c.media_id=m.id ORDER BY c.name");
                  while($cat=$catOptions->fetch_assoc()){
                      echo '<option value="'.$cat['id'].'">'.htmlspecialchars($cat['name']).' ('.htmlspecialchars($cat['media_name']).')</option>';
                  }
                  ?>
              </select>
            </div>
            <div class="mb-3">
              <label>Photo Image</label>
              <input type="file" name="image" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="add_photo" class="btn-gold">Add</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </form>
    </div>
  </div>

</section>

<!-- ======================= ALL MODALS MOVED OUTSIDE SECTION ======================= -->

<?php foreach($mediaModals as $m): ?>
<!-- Edit Media Modal -->
<div class="modal fade" id="editMedia<?= $m['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Media</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $m['id'] ?>">
          <div class="mb-3">
            <label>Media Name</label>
            <input type="text" name="media_name" class="form-control" value="<?= htmlspecialchars($m['name']) ?>" required>
          </div>
          <div class="mb-3">
            <label>Cover Image</label>
            <input type="file" name="cover_image" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_media" class="btn-gold">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Delete Media Modal -->
<div class="modal fade" id="deleteMedia<?= $m['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete Media</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $m['id'] ?>">
          Are you sure want to delete "<b><?= htmlspecialchars($m['name']) ?></b>"?
        </div>
        <div class="modal-footer">
          <button type="submit" name="delete_media" class="btn btn-danger">Delete</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>


<?php foreach($categoryModals as $c): ?>
<!-- Edit Category Modal -->
<div class="modal fade" id="editCategory<?= $c['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <div class="mb-3">
            <label>Media</label>
            <select name="media_id" class="form-select" required>
                <?= str_replace('value="'.$c['media_id'].'"', 'value="'.$c['media_id'].'" selected', $mediaOptions) ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Category Name</label>
            <input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($c['name']) ?>" required>
          </div>
          <div class="mb-3">
            <label>Cover Image</label>
            <input type="file" name="cover_image" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_category" class="btn-gold">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategory<?= $c['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          Are you sure want to delete "<b><?= htmlspecialchars($c['name']) ?></b>"?
        </div>
        <div class="modal-footer">
          <button type="submit" name="delete_category" class="btn btn-danger">Delete</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>


<?php foreach($photoModals as $p): ?>
<!-- Edit Photo Modal -->
<div class="modal fade" id="editPhoto<?= $p['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Photo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <div class="mb-3">
            <label>Category</label>
            <select name="category_id" class="form-select" required>
                <?php
                $catOptions = $conn->query("SELECT c.id, c.name, m.name AS media_name FROM category c JOIN media m ON c.media_id=m.id ORDER BY c.name");
                while($cat=$catOptions->fetch_assoc()){
                    $sel = ($cat['id']==$p['category_id'])?'selected':'';
                    echo '<option value="'.$cat['id'].'" '.$sel.'>'.htmlspecialchars($cat['name']).' ('.htmlspecialchars($cat['media_name']).')</option>';
                }
                ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Photo Image</label>
            <input type="file" name="image" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_photo" class="btn-gold">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Delete Photo Modal -->
<div class="modal fade" id="deletePhoto<?= $p['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete Photo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          Are you sure want to delete this photo?
        </div>
        <div class="modal-footer">
          <button type="submit" name="delete_photo" class="btn btn-danger">Delete</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<style>
/* Fade animation */
.row-fade { animation: fadeIn .4s ease both; }
@keyframes fadeIn { from{opacity:0; transform:translateY(6px);} to{opacity:1; transform:translateY(0);} }

/* Table Dark */
.table-dark { background:#1c1c1c; color:#eaeaea; }
.text-gold { color:#bfa14a !important; }

/* Gallery Card */
.gallery-card {
  background:#1c1c1c;
  border:none;
  border-radius:1rem;
  transition: all .25s ease;
  box-shadow:0 6px 18px rgba(0,0,0,.4);
}
.gallery-card:hover { transform: translateY(-6px); box-shadow:0 14px 32px rgba(0,0,0,.7); }
.img-wrap { overflow:hidden; border-top-left-radius:1rem; border-top-right-radius:1rem; }
.img-wrap img { width:100%; height:190px; object-fit:cover; transition: transform .35s ease; }
.gallery-card:hover .img-wrap img { transform: scale(1.08); }

/* Modal Dark */
.modal-content { background:#1c1c1c; color:#eaeaea; border:1px solid #333; border-radius:.75rem; }
.modal-header, .modal-footer { border-color:#333; }
.btn-close { filter:invert(1); }

/* Outline Gold */
.btn-outline-gold {
  border:1px solid #bfa14a;
  color:#bfa14a;
}
.btn-outline-gold:hover { background:#bfa14a; color:#fff; }

/* Badge Gold */
.bg-gold { background:#bfa14a !important; }
</style>


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
