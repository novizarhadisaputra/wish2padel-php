<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Media - Wish2Padel</title>
    <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="admin-page">
 
</head>
<body style="background-color: #303030">

<?php view('partials.navbar'); ?>

<div class="container py-5 mt-5">

  <!-- ================= MEDIA ================= -->
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="text-gold mb-0">Media</h2>
      <button class="btn btn-admin-gold px-4" data-bs-toggle="modal" data-bs-target="#addMediaModal">
          <i class="bi bi-plus-circle me-1"></i> Add Media
      </button>
  </div>

  <div class="card admin-card shadow-lg mb-5">
      <div class="card-body p-0">
          <div class="table-responsive">
              <table class="table table-dark admin-table table-hover mb-0 align-middle">
                  <thead>
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
          <button class="btn btn-sm btn-outline-warning rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#editMedia<?= $m['id'] ?>" title="Edit">
              <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger rounded-circle" data-bs-toggle="modal" data-bs-target="#deleteMedia<?= $m['id'] ?>" title="Delete">
              <i class="bi bi-trash"></i>
          </button>
      </td>
  </tr>

<?php 
$mediaModals[] = $m;
endwhile; 
?>
</tbody>
          </table>
      </div>
  </div>
</div>
      <?php foreach($mediaModals as $m): ?>
<!-- Edit Media Modal -->
<div class="modal fade" id="editMedia<?= $m['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
      <div class="modal-content shadow-lg border-0">
        <div class="modal-header border-0">
          <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Media</h5>
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
  <div class="modal-dialog modal-dialog-centered modal-dark">
    <form method="POST" action="<?= asset('admin/gallery') ?>">
      <div class="modal-content shadow-lg border-0">
        <div class="modal-header border-0">
          <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Media</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $m['id'] ?>">
          <p class="mb-0">Are you sure you want to delete <strong class="text-danger"><?= htmlspecialchars($m['name']) ?></strong>?</p>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="delete_media" class="btn btn-danger px-4">Delete Media</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

  </div>

  <!-- Add Media Modal -->
  <div class="modal fade" id="addMediaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dark">
      <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
        <div class="modal-content border-0">
          <div class="modal-header border-0">
            <h5 class="modal-title text-gold">Add Media</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label>Media Name</label>
              <input type="text" name="media_name" class="form-control" placeholder="Enter media name" required>
            </div>
            <div class="mb-3">
              <label>Cover Image</label>
              <input type="file" name="cover_image" class="form-control">
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_media" class="btn btn-admin-gold px-4">Add Media</button>
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
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="text-gold mb-0">Categories</h2>
      <button class="btn btn-admin-gold px-4" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
          <i class="bi bi-plus-circle me-1"></i> Add Category
      </button>
  </div>

  <div class="card admin-card shadow-lg mb-5">
      <div class="card-body p-0">
          <div class="table-responsive">
              <table class="table table-dark admin-table table-hover mb-0 align-middle">
                  <thead>
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
          <button class="btn btn-sm btn-outline-warning rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#editCategory<?= $c['id'] ?>" title="Edit">
              <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger rounded-circle" data-bs-toggle="modal" data-bs-target="#deleteCategory<?= $c['id'] ?>" title="Delete">
              <i class="bi bi-trash"></i>
          </button>
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
  <div class="modal-dialog modal-dialog-centered modal-dark">
    <form method="POST" action="<?= asset('admin/gallery') ?>">
      <div class="modal-content shadow-lg border-0">
        <div class="modal-header border-0">
          <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <p class="mb-0">Are you sure you want to delete <strong class="text-danger"><?= htmlspecialchars($c['name']) ?></strong>?</p>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="delete_category" class="btn btn-danger px-4">Delete Category</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

  </div>

  <!-- Add Category Modal -->
  <div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dark">
      <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
        <div class="modal-content border-0">
          <div class="modal-header border-0">
            <h5 class="modal-title text-gold">Add Category</h5>
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
              <input type="text" name="category_name" class="form-control" placeholder="Enter category name" required>
            </div>
            <div class="mb-3">
              <label>Cover Image</label>
              <input type="file" name="cover_image" class="form-control">
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_category" class="btn btn-admin-gold px-4">Add Category</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- ================= PHOTO ================= -->
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="text-gold mb-0">Photo</h2>
      <button class="btn btn-admin-gold px-4" data-bs-toggle="modal" data-bs-target="#addPhotoModal">
          <i class="bi bi-plus-circle me-1"></i> Add Photo
      </button>
  </div>

  <div class="row g-4">
      <?php while($p=$photos->fetch_assoc()): ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card admin-card gallery-card h-100">
                <div class="img-wrap">
                    <img src="<?= asset('uploads/gallery/'.$p['file_name']) ?>" class="img-fluid" alt="photo">
                </div>
                <div class="card-body">
                    <span class="badge bg-admin-gold text-dark"><?= htmlspecialchars($p['category_name']) ?></span>
                    <?php if(!empty($p['video_url'])): ?>
                    <span class="badge bg-danger ms-1">Video</span>
                    <?php endif; ?>
                    <small class="d-block mt-2 text-gold"><?= htmlspecialchars($p['media_name']) ?></small>
                    <small class="d-block mt-1 text-muted"><?= date("d M Y H:i", strtotime($p['created_at'])) ?></small>

                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-sm btn-outline-warning flex-fill" data-bs-toggle="modal" data-bs-target="#editPhoto<?= $p['id'] ?>">Edit</button>
                        <button class="btn btn-sm btn-outline-danger flex-fill" data-bs-toggle="modal" data-bs-target="#deletePhoto<?= $p['id'] ?>">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Photo Modal -->
        <div class="modal fade" id="editPhoto<?= $p['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-dark">
            <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
              <div class="modal-content shadow-lg border-0">
                <div class="modal-header border-0">
                  <h5 class="modal-title text-gold">Edit Photo</h5>
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
                    <label>Photo Image (or Thumbnail)</label>
                    <input type="file" name="image" class="form-control">
                  </div>
                  <div class="mb-3">
                    <label>Video URL</label>
                    <input type="url" name="video_url" class="form-control" value="<?= htmlspecialchars($p['video_url'] ?? '') ?>" placeholder="https://youtube.com/...">
                  </div>
                  <div class="mb-3">
                    <label>OR Upload Video</label>
                    <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg">
                    <?php if(!empty($p['video_url']) && !filter_var($p['video_url'], FILTER_VALIDATE_URL)): ?>
                        <small class="text-muted d-block mt-1">Current File: <?= basename($p['video_url']) ?></small>
                    <?php endif; ?>
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
          <div class="modal-dialog modal-dialog-centered modal-dark">
            <form method="POST" action="<?= asset('admin/gallery') ?>">
              <div class="modal-content shadow-lg border-0">
                <div class="modal-header border-0">
                  <h5 class="modal-title text-danger">Delete Photo</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <p class="mb-0">Are you sure you want to delete this photo?</p>
                </div>
                <div class="modal-footer border-0">
                  <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" name="delete_photo" class="btn btn-danger px-4">Delete Photo</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      <?php endwhile; ?>
  </div>

  <!-- Add Photo Modal -->
  <div class="modal fade" id="addPhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dark">
      <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/gallery') ?>">
        <div class="modal-content border-0">
          <div class="modal-header border-0">
            <h5 class="modal-title text-gold">Add Photo</h5>
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
              <label>Photo Image (or Thumbnail)</label>
              <input type="file" name="image" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Video URL (Optional)</label>
              <input type="url" name="video_url" class="form-control" placeholder="https://youtube.com/...">
            </div>
            <div class="mb-3">
              <label>OR Upload Video (Optional, max 50MB)</label>
              <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg">
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_photo" class="btn btn-admin-gold px-4">Add Photo</button>
          </div>
        </div>
      </form>
    </div>
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
