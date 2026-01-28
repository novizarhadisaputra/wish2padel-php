<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
  <meta charset="UTF-8" />
  <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sponsor - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  
</head>
<body class="admin-page">

<?php view('partials.navbar'); ?>

<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script>
  function setupDropzone(containerId, inputId, previewId, removeId) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if(!container || !input || !preview) return;

    const previewImg = preview.querySelector('img');
    const removeBtn = document.getElementById(removeId);

    container.addEventListener('click', () => input.click());

    container.addEventListener('dragover', (e) => {
      e.preventDefault();
      container.classList.add('dragover');
    });

    container.addEventListener('dragleave', () => {
      container.classList.remove('dragover');
    });

    container.addEventListener('drop', (e) => {
      e.preventDefault();
      container.classList.remove('dragover');
      if (e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        updatePreview(e.dataTransfer.files[0]);
      }
    });

    input.addEventListener('change', () => {
      if (input.files.length) {
        updatePreview(input.files[0]);
      }
    });

    if(removeBtn) {
      removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        input.value = '';
        preview.style.display = 'none';
        container.querySelector('i').style.display = 'block';
        container.querySelector('p').style.display = 'block';
      });
    }

    function updatePreview(file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        previewImg.src = e.target.result;
        preview.style.display = 'block';
        container.querySelector('i').style.display = 'none';
        container.querySelector('p').style.display = 'none';
      };
      reader.readAsDataURL(file);
    }
  }
</script>

<div class="container py-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="text-gold mb-0">Manage Sponsors & Collaborate</h2>
      <button class="btn btn-admin-gold" data-bs-toggle="modal" data-bs-target="#addModal">
          <i class="bi bi-plus-lg me-1"></i> Add Sponsor
      </button>
    </div>

    <div class="card admin-card shadow-lg">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark admin-table table-hover mb-0 align-middle">
                <thead>
                  <tr>
                    <th>Logo</th>
                    <th>Name</th>
                    <th>Website</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Type</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                <?php while($row = $sponsors->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <?php if($row['sponsor_logo']): ?>
                        <img src="<?= asset('uploads/sponsor/' . $row['sponsor_logo']) ?>" alt="logo" width="60" class="rounded bg-light p-1">
                      <?php else: ?>
                        <span class="text-muted small">No Logo</span>
                      <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= htmlspecialchars($row['sponsor_name']) ?></td>
                    <td>
                      <?php if($row['website']): ?>
                        <a href="<?= $row['website'] ?>" target="_blank" class="text-decoration-none text-primary">
                          <i class="bi bi-link-45deg"></i> Link
                        </a>
                      <?php endif; ?>
                    </td>
                    <td class="text-start small text-muted"><?= nl2br(htmlspecialchars(substr($row['description'], 0, 50))) . (strlen($row['description'])>50?'...':'') ?></td>
                    <td>
                      <span class="badge <?= $row['status']=='sponsor'?'bg-success':'bg-info' ?>">
                        <?= ucfirst($row['status']) ?>
                      </span>
                    </td>
                    <td>
                      <?php if($row['status']=='sponsor'): ?>
                        <span class="badge bg-warning text-dark">
                          <?= $row['type'] ? ucfirst($row['type']) : '-' ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['sponsor_id'] ?>"><i class="bi bi-pencil-square"></i></button>
                      <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['sponsor_id'] ?>"><i class="bi bi-trash"></i></button>
                    </td>
                  </tr>

              <!-- EDIT MODAL -->
<div class="modal fade" id="editModal<?= $row['sponsor_id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable modal-lg custom-modal modal-dark">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form method="post" enctype="multipart/form-data" action="<?= asset('admin/sponsors') ?>">
        <div class="modal-header border-0">
          <h5 class="modal-title">Update Sponsor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-start">
          <input type="hidden" name="sponsor_id" value="<?= $row['sponsor_id'] ?>">

          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="sponsor_name" class="form-control" 
                   value="<?= htmlspecialchars($row['sponsor_name']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-control" 
                   value="<?= htmlspecialchars($row['website']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($row['description']) ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select status-select" 
                    data-target="type-wrapper-<?= $row['sponsor_id'] ?>" required>
              <option value="sponsor" <?= $row['status']=='sponsor'?'selected':'' ?>>Sponsor</option>
              <option value="collaborate" <?= $row['status']=='collaborate'?'selected':'' ?>>Collaborate</option>
            </select>
          </div>

          <!-- ✅ TYPE FIELD (Auto show kalau sponsor) -->
          <div class="mb-3" 
               id="type-wrapper-<?= $row['sponsor_id'] ?>" 
               style="<?= ($row['status']=='sponsor') ? 'display:block' : 'display:none' ?>">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
              <option value="" disabled <?= !$row['type']?'selected':'' ?>>-- Select Type --</option>
              <option value="premium" <?= $row['type']=='premium'?'selected':'' ?>>Premium</option>
              <option value="standard" <?= $row['type']=='standard'?'selected':'' ?>>Standard</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Logo</label>
            <div id="dropzone-edit-<?= $row['sponsor_id'] ?>" class="dropzone-container">
              <i class="bi bi-cloud-arrow-up" style="<?= $row['sponsor_logo'] ? 'display:none' : '' ?>"></i>
              <p style="<?= $row['sponsor_logo'] ? 'display:none' : '' ?>">Drag & drop logo here or click to upload</p>
              <input type="file" name="sponsor_logo" id="file-edit-<?= $row['sponsor_id'] ?>" hidden accept="image/*">
              <div id="preview-edit-<?= $row['sponsor_id'] ?>" class="dropzone-preview" style="<?= $row['sponsor_logo'] ? 'display:block' : '' ?>">
                <img src="<?= $row['sponsor_logo'] ? asset('uploads/sponsor/' . $row['sponsor_logo']) : '' ?>" alt="Preview">
                <button type="button" class="dropzone-remove" id="remove-edit-<?= $row['sponsor_id'] ?>">&times;</button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0">
          <button type="submit" name="edit_sponsor" class="btn btn-admin-gold px-4">Save</button>
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  setTimeout(() => {
    setupDropzone(
      'dropzone-edit-<?= $row['sponsor_id'] ?>', 
      'file-edit-<?= $row['sponsor_id'] ?>', 
      'preview-edit-<?= $row['sponsor_id'] ?>', 
      'remove-edit-<?= $row['sponsor_id'] ?>'
    );
  }, 100);
</script>

              <!-- DELETE MODAL -->
              <div class="modal fade" id="deleteModal<?= $row['sponsor_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered mt-5 modal-dark">
                  <div class="modal-content border-0 shadow-lg rounded-3">
                    <form method="post" action="<?= asset('admin/sponsors') ?>">
                      <div class="modal-header border-0">
                        <h5 class="modal-title text-danger">Delete Sponsor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p>Are you sure want to delete <strong><?= htmlspecialchars($row['sponsor_name']) ?></strong>?</p>
                        <input type="hidden" name="sponsor_id" value="<?= $row['sponsor_id'] ?>">
                      </div>
                      <div class="modal-footer border-0">
                        <button type="submit" name="delete_sponsor" class="btn btn-danger px-4">Yes, Delete</button>
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable modal-lg custom-modal modal-dark">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form method="post" enctype="multipart/form-data" action="<?= asset('admin/sponsors') ?>">
        <div class="modal-header border-0">
          <h5 class="modal-title">Add Sponsor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="sponsor_name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
          </div>

          <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status" id="status_select" class="form-select" required>
                <option value="" selected disabled>-- Select Status --</option>
                <option value="sponsor">Sponsor</option>
                <option value="collaborate">Collaborate</option>
              </select>
            </div>

          <!-- ✅ TYPE (Hidden dulu) -->
          <div class="mb-3" id="type_wrapper" style="display: none;">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
              <option value="premium">Premium</option>
              <option value="standard">Standard</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Logo</label>
            <div id="dropzone-add" class="dropzone-container">
              <i class="bi bi-cloud-arrow-up"></i>
              <p>Drag & drop logo here or click to upload</p>
              <input type="file" name="sponsor_logo" id="file-add" hidden accept="image/*">
              <div id="preview-add" class="dropzone-preview">
                <img src="" alt="Preview">
                <button type="button" class="dropzone-remove" id="remove-add">&times;</button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="submit" name="add_sponsor" class="btn btn-admin-gold px-4">Add</button>
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Setup for Add Modal
  setupDropzone('dropzone-add', 'file-add', 'preview-add', 'remove-add');

  document.getElementById('status_select').addEventListener('change', function () {
    const typeWrapper = document.getElementById('type_wrapper');
    if (this.value === 'sponsor') {
      typeWrapper.style.display = 'block';
    } else {
      typeWrapper.style.display = 'none';
    }
  });

  // Re-run setup for edit modals just in case (though they have inline scripts now)
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
