<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Transfer Windows - Wish2Padel</title>
   <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-color: #303030">

<?php view('partials.navbar'); ?>

<section class="py-5">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-white mb-0">Manage Transfer Windows</h2>
      <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-2"></i> Add Transfer Window
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
                <th>Start Date</th>
                <th>End Date</th>
                <th style="width:150px;">Action</th>
              </tr>
            </thead>
            <tbody>
  <?php $no=1; while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= $no++ ?></td>
      <td>
        <span class="badge bg-light text-dark">
          <?= date("d M Y H:i", strtotime($row['start_date'])) ?>
        </span>
      </td>
      <td>
        <span class="badge bg-light text-dark">
          <?= date("d M Y H:i", strtotime($row['end_date'])) ?>
        </span>
      </td>
      <td>
        <button class="btn btn-sm btn-outline-warning me-1 edit-btn"
          data-id="<?= $row['id'] ?>"
          data-start="<?= date('Y-m-d\TH:i', strtotime($row['start_date'])) ?>"
          data-end="<?= date('Y-m-d\TH:i', strtotime($row['end_date'])) ?>">
          <i class="bi bi-pencil-square"></i>
        </button>

        <button class="btn btn-sm btn-outline-danger delete-btn"
          data-id="<?= $row['id'] ?>">
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

<!-- Reusable Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:70px;">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-dark" style="background:#f3e6b6">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Transfer Window</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= asset('admin/windows') ?>">
        <div class="modal-body">
          <input type="hidden" name="id" id="edit-id">
          <div class="mb-3">
            <label class="form-label fw-semibold">Start Date</label>
            <input type="datetime-local" name="start_date" id="edit-start" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">End Date</label>
            <input type="datetime-local" name="end_date" id="edit-end" class="form-control">
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

<!-- Reusable Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:150px;">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete Transfer Window</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this transfer window?</p>
      </div>
      <div class="modal-footer">
        <form method="POST" id="delete-form" action="<?= asset('admin/windows') ?>">
          <input type="hidden" name="id" id="delete-id">
          <input type="hidden" name="delete" value="1">
          <button type="submit" class="btn btn-danger rounded-pill px-4">Delete</button>
        </form>
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>


<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:70px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-dark" style="background:#f3e6b6">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Transfer Window</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= asset('admin/windows') ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Start Date</label>
            <input type="datetime-local" name="start_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">End Date</label>
            <input type="datetime-local" name="end_date" class="form-control" required>
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
document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('edit-id').value = this.dataset.id;

    document.getElementById('edit-start').value = this.dataset.start;
    document.getElementById('edit-end').value = this.dataset.end;
    new bootstrap.Modal(document.getElementById('editModal')).show();
  });
});

document.querySelectorAll('.delete-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('delete-id').value = this.dataset.id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
  });
});
</script>

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
