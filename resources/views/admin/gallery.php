<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p.png') ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Media - Wish2Padel</title>
  <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
 
</head>
<body style="background-color: #303030">

<?php view('partials.navbar'); ?>

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
$mediaModals = [];
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
      <?php foreach($mediaModals as $m): ?>
<!-- Edit Media Modal -->
<div class="modal fade" id="editMedia<?= $m['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
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
    <form method="POST" action="<?= asset('admin/gallery') ?>">
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

  </div>

  <!-- Add Media Modal -->
  <div class="modal fade" id="addMediaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
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
  $conn = getDBConnection();
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
      <?php foreach($categoryModals as $c): ?>
<!-- Edit Category Modal -->
<div class="modal fade" id="editCategory<?= $c['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
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
    <form method="POST" action="<?= asset('admin/gallery') ?>">
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

  </div>

  <!-- Add Category Modal -->
  <div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
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
      <?php while($p=$photos->fetch_assoc()): ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card gallery-card h-100">
                <div class="img-wrap">
                    <img src="<?= asset('uploads/gallery/'.$p['file_name']) ?>" class="img-fluid" alt="photo">
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

        <!-- Edit Photo Modal -->
        <div class="modal fade" id="editPhoto<?= $p['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
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
            <form method="POST" action="<?= asset('admin/gallery') ?>">
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
      <?php endwhile; ?>
  </div>

  <!-- Add Photo Modal -->
  <div class="modal fade" id="addPhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
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
