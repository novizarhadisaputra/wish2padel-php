<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Update Club - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
        <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
</head>
<body style="background-color: #303030">

<?php view('partials.navbar'); ?>

<section class="container py-5">
  <div class="card shadow border-0 rounded-3">
    <div class="card-header bg-dark text-white">
      <h4 class="mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Club</h4>
    </div>
    <div class="card-body">
      <?php if ($club): ?>
      <form method="post" enctype="multipart/form-data" class="row g-3" action="<?= asset('admin/club/update') ?>">
        <input type="hidden" name="id" value="<?= $club['id'] ?>">

        <!-- Basic Info -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Club Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($club['name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Logo</label>
          <input type="file" name="logo" class="form-control">
          <?php if ($club['logo_url']): ?>
            <img src="<?= asset('uploads/club/' . $club['logo_url']) ?>" alt="logo" class="mt-2 rounded shadow-sm" style="height:50px;">
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Street</label>
          <input type="text" name="street" class="form-control" value="<?= htmlspecialchars($club['street']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Postal Code</label>
          <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($club['postal_code']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">City</label>
          <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($club['city']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Zone</label>
          <select name="zone" class="form-select">
            <?php
            $zones = ["North Zone","South Zone","East Zone","West Zone","Central Zone"];
            foreach ($zones as $z) {
              $sel = ($club['zone']==$z)?"selected":"";
              echo "<option value='$z' $sel>$z</option>";
            }
            ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($club['phone']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($club['email']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Website</label>
          <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($club['website']) ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Description</label>
          <textarea name="description" id="description" class="form-control" rows="6"><?= htmlspecialchars($club['description']) ?></textarea>
        </div>
        <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
        <script>
          ClassicEditor.create(document.querySelector('#description'), {
            toolbar: [ 'bold','italic','underline','|','bulletedList','numberedList','|','undo','redo' ]
          }).catch(error => console.error(error));
        </script>

        <!-- Fields -->
        <div class="col-12 mt-4 border rounded-3 p-3">
          <h5 class="fw-bold mb-3"><i class="bi bi-layout-text-window me-2 text-warning"></i> Fields</h5>
          <div id="pistas-wrapper">
            <?php if(!empty($pistas)): foreach ($pistas as $p): ?>
              <div class="row g-2 mb-2 pista-item">
                <div class="col-md-6"><input type="text" name="pista_name[]" class="form-control" value="<?= htmlspecialchars($p['name']) ?>"></div>
                <div class="col-md-4"><input type="number" name="pista_quantity[]" class="form-control" value="<?= $p['quantity'] ?>"></div>
                <div class="col-md-2"><button type="button" class="btn btn-outline-danger remove-pista w-100"><i class="bi bi-x-circle"></i> Remove</button></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
          <button type="button" id="add-pista" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-plus-circle"></i> Add Field</button>
        </div>

        <!-- Schedules -->
        <div class="col-12 mt-4 border rounded-3 p-3">
          <h5 class="fw-bold mb-3"><i class="bi bi-calendar-week me-2 text-warning"></i> Schedules</h5>
          <div id="schedules-wrapper">
            <?php if(!empty($schedules)): foreach ($schedules as $s): ?>
              <div class="row g-2 mb-2 schedule-item">
                <div class="col-md-3">
                  <select name="schedule_day[]" class="form-select">
                    <?php
                    $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
                    foreach ($days as $d) {
                      $sel = ($s['day']==$d)?"selected":"";
                      echo "<option value='$d' $sel>$d</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="col-md-3"><input type="time" name="open_time[]" class="form-control" value="<?= $s['open_time'] ?>"></div>
                <div class="col-md-3"><input type="time" name="close_time[]" class="form-control" value="<?= $s['close_time'] ?>"></div>
                <div class="col-md-3"><button type="button" class="btn btn-outline-danger remove-schedule w-100"><i class="bi bi-x-circle"></i> Remove</button></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
          <button type="button" id="add-schedule" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-plus-circle"></i> Add Schedule</button>
        </div>

        <!-- Photos -->
        <div class="col-12 mt-4 border rounded-3 p-3">
          <h5 class="fw-bold mb-3"><i class="bi bi-images me-2 text-warning"></i> Photos</h5>
          <div class="row g-2 mb-3">
            <?php if(!empty($photos)): foreach ($photos as $ph): ?>
              <div class="col-md-3 mb-2">
                <img src="<?= asset('uploads/club/' . $ph['url']) ?>" class="img-fluid rounded shadow-sm">
              </div>
            <?php endforeach; endif; ?>
          </div>
          <div id="photos-wrapper"></div>
          <button type="button" id="add-photo" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-plus-circle"></i> Add Photo</button>
        </div>

        <!-- Actions -->
        <div class="col-12 mt-4 text-end">
          <button type="submit" class="btn-gold px-4">
            <i class="bi bi-check-circle me-1"></i> Update Club
          </button>
          <a href="<?= asset('admin/club') ?>" class="btn btn-lg rounded-pill px-4 btn-secondary">
            <i class="bi bi-x-circle me-1"></i> Cancel
          </a>
        </div>
      </form>
      <?php else: ?>
        <div class="alert alert-warning">Data club tidak ditemukan.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- TEMPLATES -->
<template id="pista-template">
  <div class="row g-2 mb-2 pista-item">
    <div class="col-md-6"><input type="text" name="pista_name[]" class="form-control" placeholder="Field Type"></div>
    <div class="col-md-4"><input type="number" name="pista_quantity[]" class="form-control" placeholder="Amount"></div>
    <div class="col-md-2"><button type="button" class="btn btn-outline-danger remove-pista w-100"><i class="bi bi-x-circle"></i> Remove</button></div>
  </div>
</template>

<template id="schedule-template">
  <div class="row g-2 mb-2 schedule-item">
    <div class="col-md-3">
      <select name="schedule_day[]" class="form-select">
        <option value="">-- Select Day --</option>
        <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
        <option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option>
      </select>
    </div>
    <div class="col-md-3"><input type="time" name="open_time[]" class="form-control"></div>
    <div class="col-md-3"><input type="time" name="close_time[]" class="form-control"></div>
    <div class="col-md-3"><button type="button" class="btn btn-outline-danger remove-schedule w-100"><i class="bi bi-x-circle"></i> Remove</button></div>
  </div>
</template>

<template id="photo-template">
  <div class="mb-2 photo-item">
    <input type="file" name="photos[]" class="form-control">
    <button type="button" class="btn btn-outline-danger btn-sm remove-photo mt-1"><i class="bi bi-x-circle"></i> Remove</button>
  </div>
</template>

<script>
document.addEventListener("DOMContentLoaded", () => {
  // === FIELD ===
  document.getElementById("add-pista").addEventListener("click", () => {
    let tmpl = document.getElementById("pista-template").content.cloneNode(true);
    document.getElementById("pistas-wrapper").appendChild(tmpl);
  });
  document.addEventListener("click", (e) => {
    if (e.target.closest(".remove-pista")) e.target.closest(".pista-item").remove();
  });

  // === SCHEDULE ===
  document.getElementById("add-schedule").addEventListener("click", () => {
    let tmpl = document.getElementById("schedule-template").content.cloneNode(true);
    document.getElementById("schedules-wrapper").appendChild(tmpl);
  });
  document.addEventListener("click", (e) => {
    if (e.target.closest(".remove-schedule")) e.target.closest(".schedule-item").remove();
  });

  // === PHOTO ===
  document.getElementById("add-photo").addEventListener("click", () => {
    let tmpl = document.getElementById("photo-template").content.cloneNode(true);
    document.getElementById("photos-wrapper").appendChild(tmpl);
  });
  document.addEventListener("click", (e) => {
    if (e.target.closest(".remove-photo")) e.target.closest(".photo-item").remove();
  });
});
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
