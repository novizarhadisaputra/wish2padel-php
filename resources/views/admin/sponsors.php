<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p.png') ?>">
  <meta charset="UTF-8" />
  <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sponsor - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
</head>
<body  style="background-color: #303030">

<?php view('partials.navbar'); ?>

<section class="p-4 sponsor-admin-section mt-5">
  <div class="container">
    <h2 class="text-center mb-4 fw-bold text-white">Manage Sponsors & Collaborate</h2>
    
    <div class="mb-3 text-end">
      <button class="btn-gold px-4" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Sponsor</button>
    </div>

    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">
      <div class="table-responsive">
        <table class="table align-middle text-center mb-0">
          <thead class="bg-light text-dark">
  <tr>
    <th>Logo</th>
    <th>Name</th>
    <th>Website</th>
    <th>Description</th>
    <th>Status</th>
    <th>Type</th>  <!-- ✅ DITAMBAHKAN -->
    <th>Action</th>
  </tr>
</thead>
<tbody class="bg-white">
<?php while($row = $sponsors->fetch_assoc()): ?>
  <tr class="row-fade">
    <td>
      <?php if($row['sponsor_logo']): ?>
        <img src="<?= asset('uploads/sponsor/' . $row['sponsor_logo']) ?>" alt="logo" width="80" class="rounded shadow-sm">
      <?php else: ?>
        <span class="text-muted">No Logo</span>
      <?php endif; ?>
    </td>
    <td class="fw-semibold"><?= htmlspecialchars($row['sponsor_name']) ?></td>
    <td>
      <?php if($row['website']): ?>
        <a href="<?= $row['website'] ?>" target="_blank" class="text-decoration-none text-primary">
          <?= $row['website'] ?>
        </a>
      <?php endif; ?>
    </td>
    <td class="text-start"><?= nl2br(htmlspecialchars($row['description'])) ?></td>
    <td>
      <span class="badge <?= $row['status']=='sponsor'?'bg-success':'bg-info' ?> px-3 py-2">
        <?= ucfirst($row['status']) ?>
      </span>
    </td>
    <td>
      <?php if($row['status']=='sponsor'): ?>
        <span class="badge bg-warning text-dark px-3 py-2">
          <?= $row['type'] ? ucfirst($row['type']) : '-' ?>
        </span>
      <?php else: ?>
        <span class="text-muted">-</span>
      <?php endif; ?>
    </td>
    <td>
      <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['sponsor_id'] ?>">Update</button>
      <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['sponsor_id'] ?>">Delete</button>
    </td>
  </tr>

              <!-- EDIT MODAL -->
<div class="modal fade" id="editModal<?= $row['sponsor_id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable modal-lg custom-modal">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form method="post" enctype="multipart/form-data" action="<?= asset('admin/sponsors') ?>">
        <div class="modal-header bg-light border-0">
          <h5 class="modal-title">Update Sponsor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
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
            <input type="file" name="sponsor_logo" class="form-control">
          </div>
        </div>

        <div class="modal-footer border-0">
          <button type="submit" name="edit_sponsor" class="btn-gold px-4">Save</button>
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  document.querySelectorAll(".status-select").forEach(select => {
    select.addEventListener("change", function() {
      const targetId = this.getAttribute("data-target");
      const typeWrapper = document.getElementById(targetId);
      if (this.value === "sponsor") {
        typeWrapper.style.display = "block";
      } else {
        typeWrapper.style.display = "none";
      }
    });
  });
});
</script>

              <!-- DELETE MODAL -->
              <div class="modal fade" id="deleteModal<?= $row['sponsor_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered mt-5">
                  <div class="modal-content border-0 shadow-lg rounded-3">
                    <form method="post" action="<?= asset('admin/sponsors') ?>">
                      <div class="modal-header bg-danger text-white border-0">
                        <h5 class="modal-title">Delete Sponsor</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
</section>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable modal-lg custom-modal">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form method="post" enctype="multipart/form-data" action="<?= asset('admin/sponsors') ?>">
        <div class="modal-header bg-light border-0">
          <h5 class="modal-title">Add Sponsor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="">
            <label class="form-label">Name</label>
            <input type="text" name="sponsor_name" class="form-control" required>
          </div>

          <div class="">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-control">
          </div>

          <div class="">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
          </div>

          <div class="">
              <label class="form-label">Status</label>
              <select name="status" id="status_select" class="form-select" required>
                <option value="" selected disabled>-- Select Status --</option>
                <option value="sponsor">Sponsor</option>
                <option value="collaborate">Collaborate</option>
              </select>
            </div>

          <!-- ✅ TYPE (Hidden dulu) -->
          <div class="" id="type_wrapper" style="display: none;">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
              <option value="premium">Premium</option>
              <option value="standard">Standard</option>
            </select>
          </div>

          <div class="">
            <label class="form-label">Logo</label>
            <input type="file" name="sponsor_logo" class="form-control">
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="submit" name="add_sponsor" class="btn-gold px-4">Add</button>
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.getElementById('status_select').addEventListener('change', function () {
    const typeWrapper = document.getElementById('type_wrapper');

    if (this.value === 'sponsor') {
      typeWrapper.style.display = 'block'; // Tampilkan kalau sponsor
    } else {
      typeWrapper.style.display = 'none';  // Sembunyikan kalau collaborate
    }
  });
</script>



<style>
/* kasih jarak atas & bawah */
.modal.show .custom-modal {
  margin-top: 150px;
  margin-bottom: 10px;
}

.row-fade { animation: fadeIn .4s ease both; }
@keyframes fadeIn { from{opacity:0; transform:translateY(6px);} to{opacity:1; transform:translateY(0);} }

.table thead th { font-weight:600; }
.card { background:#fff; }
.modal-content { background:#fff; }
.modal-lg { max-width:700px; }

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
