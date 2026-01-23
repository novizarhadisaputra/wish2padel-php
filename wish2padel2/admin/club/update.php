
<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../login/login.php");
    exit();
}
require '../../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

// Set timezone Riyadh
date_default_timezone_set("Asia/Riyadh");
$now = date("Y-m-d H:i:s");

// Ambil data club beserta relasi
$club = null;
$pistas = [];
$schedules = [];
$photos = [];

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // centers
    $club = $conn->query("SELECT * FROM centers WHERE id=$id")->fetch_assoc();

    // pistas
    $res = $conn->query("SELECT * FROM pistas WHERE center_id=$id");
    while ($row = $res->fetch_assoc()) $pistas[] = $row;

    // schedules
    $res = $conn->query("SELECT * FROM schedules WHERE center_id=$id");
    while ($row = $res->fetch_assoc()) $schedules[] = $row;

    // photos
    $res = $conn->query("SELECT * FROM photos WHERE center_id=$id");
    while ($row = $res->fetch_assoc()) $photos[] = $row;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $center_id    = intval($_POST['id']);
    $name         = $_POST['name'];
    $street       = $_POST['street'];
    $postal_code  = $_POST['postal_code'];
    $city         = $_POST['city'];
    $zone         = $_POST['zone'];
    $phone        = $_POST['phone'];
    $email        = $_POST['email'];
    $website      = $_POST['website'];
    $description  = $_POST['description'];

    // --- Logo (replace jika upload baru) ---
    $logo_url = $club['logo_url'];
    if (!empty($_FILES['logo']['name'])) {
        $logo_name = time() . '_' . basename($_FILES['logo']['name']);
        $logo_path = '../../uploads/club/' . $logo_name;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
            $logo_url = $logo_name;
        }
    }

    // --- Update centers ---
    $stmt = $conn->prepare("
        UPDATE centers 
        SET name=?, street=?, postal_code=?, city=?, zone=?, phone=?, email=?, website=?, description=?, logo_url=?, updated_at=? 
        WHERE id=?
    ");
    $stmt->bind_param(
        "sssssssssssi",
        $name, $street, $postal_code, $city, $zone,
        $phone, $email, $website, $description,
        $logo_url, $now, $center_id
    );
    $stmt->execute();
    $stmt->close();

    // --- Reset & insert ulang pistas ---
    $conn->query("DELETE FROM pistas WHERE center_id=$center_id");
    if (!empty($_POST['pista_name'])) {
        foreach ($_POST['pista_name'] as $i => $pname) {
            $qty = $_POST['pista_quantity'][$i] ?? '';
            if (!empty($pname) && !empty($qty)) {
                $stmt = $conn->prepare("INSERT INTO pistas (center_id, name, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $center_id, $pname, $qty);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // --- Reset & insert ulang schedules ---
    $conn->query("DELETE FROM schedules WHERE center_id=$center_id");
    if (!empty($_POST['schedule_day'])) {
        foreach ($_POST['schedule_day'] as $i => $day) {
            $open  = $_POST['open_time'][$i]  ?? '';
            $close = $_POST['close_time'][$i] ?? '';
            if (!empty($day)) {
                $stmt = $conn->prepare("INSERT INTO schedules (center_id, day, open_time, close_time) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $center_id, $day, $open, $close);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // --- Tambah photos baru (tidak hapus yang lama) ---
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $i => $photo_name) {
            if ($_FILES['photos']['error'][$i] === 0) {
                $new_name = time() . '_' . basename($photo_name);
                $upload_path = '../../uploads/club/' . $new_name;
                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $upload_path)) {
                    $stmt = $conn->prepare("INSERT INTO photos (center_id, url, caption, type, created_at) VALUES (?, ?, '', '', ?)");
                    $stmt->bind_param("iss", $center_id, $new_name, $now);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    // Redirect
    header("Location: club.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Update Club - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/stylee.css?v=12">
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
</head>
<body style="background-color: #303030">

<?php require '../src/navbar2.php' ?>

<section class="container py-5">
  <div class="card shadow border-0 rounded-3">
    <div class="card-header bg-dark text-white">
      <h4 class="mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Club</h4>
    </div>
    <div class="card-body">
      <?php if ($club): ?>
      <form method="post" enctype="multipart/form-data" class="row g-3">
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
            <img src="../../uploads/club/<?= $club['logo_url'] ?>" alt="logo" class="mt-2 rounded shadow-sm" style="height:50px;">
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
                <img src="../../uploads/club/<?= $ph['url'] ?>" class="img-fluid rounded shadow-sm">
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
          <a href="club.php" class="btn btn-lg rounded-pill px-4 btn-secondary">
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
