<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p.png') ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Documents - Wish2Padel</title>
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-color: #303030">

<?php view('partials.navbar'); ?>

<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-white mb-0">Manage Documents</h2>
      <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-2"></i> Add Document
      </button>
    </div>

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
$docModals = [];
while($row = $result->fetch_assoc()):
?>
  <tr>
    <td><?= $no++ ?></td>
    <td class="fw-semibold"><?= htmlspecialchars($row['doc_name']) ?></td>
    <td>
      <?php if($row['file_path']): ?>
        <i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>
        <a href="<?= asset($row['file_path']) ?>" target="_blank">View</a>
        <span class="text-muted">·</span>
        <a href="<?= asset($row['file_path']) ?>" download>Download</a>
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

<?php
$docModals[] = $row;
endwhile;
?>
</tbody>

          </table>
          <?php foreach($docModals as $row): ?>
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:70px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-dark" style="background:#f3e6b6">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Document</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/documents') ?>">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Document Name</label>
            <input type="text" name="doc_name" class="form-control" value="<?= htmlspecialchars($row['doc_name']) ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">Replace PDF (optional)</label>
            <input type="file" name="pdf" class="form-control" accept="application/pdf">
            <small class="text-muted">Current:
              <?php if($row['file_path']): ?>
                <a href="<?= asset($row['file_path']) ?>" target="_blank">View file</a>
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

<div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:150px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete Document</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete <strong><?= htmlspecialchars($row['doc_name']) ?></strong>?</p>
      </div>
      <div class="modal-footer">
        <form method="POST" action="<?= asset('admin/documents') ?>">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <input type="hidden" name="delete" value="1">
            <button type="submit" class="btn btn-danger rounded-pill px-4">Delete</button>
        </form>
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
      </div>
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
  <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top:70px; margin-bottom:50px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-dark" style="background:#f3e6b6">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Document</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="<?= asset('admin/documents') ?>">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
