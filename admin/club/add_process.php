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

// Set timezone ke Riyadh
date_default_timezone_set("Asia/Riyadh");
$now = date("Y-m-d H:i:s");

// === DEBUG MODE SEMENTARA (hapus nanti) ===
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ====== Ambil input utama ======
    $name         = $_POST['name'];
    $street       = $_POST['street'];
    $postal_code  = $_POST['postal_code'];
    $city         = $_POST['city'];
    $zone         = $_POST['zone'];
    $phone        = $_POST['phone'];
    $email        = $_POST['email'];
    $website      = $_POST['website'];
    $description  = $_POST['description']; 
    $logo_url     = '';

    // ====== Upload logo utama ======
    if (!empty($_FILES['logo']['name'])) {
        $logo_name = time() . '_' . basename($_FILES['logo']['name']);
        $logo_path = '../../uploads/club/' . $logo_name;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
            $logo_url = $logo_name;
        }
    }

    // ====== Insert ke centers ======
    $stmt = $conn->prepare("
        INSERT INTO centers 
        (name, street, postal_code, city, zone, phone, email, website, description, logo_url, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssssssssss", 
        $name, $street, $postal_code, $city, $zone, 
        $phone, $email, $website, $description, 
        $logo_url, $now, $now
    );
    $stmt->execute();
    $center_id = $stmt->insert_id;
    $stmt->close();

    // ====== Insert ke tabel PISTAS ======
    if (!empty($_POST['pista_name']) && !empty($_POST['pista_quantity'])) {
        foreach ($_POST['pista_name'] as $index => $pista_name) {
            $quantity = $_POST['pista_quantity'][$index] ?? '';
            if (!empty($pista_name) && !empty($quantity)) {
                $stmt = $conn->prepare("
                    INSERT INTO pistas (center_id, name, quantity) 
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("isi", $center_id, $pista_name, $quantity);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // ====== Insert ke tabel SCHEDULES ======
    if (!empty($_POST['schedule_day'])) {
        foreach ($_POST['schedule_day'] as $index => $day) {
            $open_time  = $_POST['open_time'][$index]  ?? '';
            $close_time = $_POST['close_time'][$index] ?? '';
            if (!empty($day)) {
                $stmt = $conn->prepare("
                    INSERT INTO schedules (center_id, day, open_time, close_time) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("isss", $center_id, $day, $open_time, $close_time);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // ====== Insert ke tabel PHOTOS ======
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $index => $photo_name) {
            if ($_FILES['photos']['error'][$index] === 0) {
                $new_name = time() . '_' . basename($photo_name);
                $upload_path = '../../uploads/club/' . $new_name;

                if (move_uploaded_file($_FILES['photos']['tmp_name'][$index], $upload_path)) {
                    // caption dan type dikosongkan dulu
                    $stmt = $conn->prepare("
                        INSERT INTO photos (center_id, url, caption, type, created_at) 
                        VALUES (?, ?, '', '', ?)
                    ");
                    $stmt->bind_param("iss", $center_id, $new_name, $now);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    // ====== Redirect ke halaman utama ======
    header("Location: club.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Club - Wish2Padel</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
    <link rel="stylesheet" href="../../assets/css/stylee.css?v=12">
  </head>
  <body style="background-color: #303030">
    <?php require '../src/navbar2.php' ?>

    <section class="container py-5">
  <div class="card shadow border-0 rounded-3">
    <div class="card-header py-3" style="background:#212529;">
      <h4 class="mb-0 text-white">
        <i class="bi bi-building me-2 text-warning"></i> Add New Club
      </h4>
    </div>
    <div class="card-body">
      <form action="" method="post" enctype="multipart/form-data" class="row g-3">

        <!-- Club Info -->
        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-building"></i> Club Name</label>
          <input type="text" name="name" class="form-control" required />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-image"></i> Logo</label>
          <input type="file" name="logo" class="form-control" />
          <small class="text-muted">Upload club logo</small>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-geo-alt"></i> Street</label>
          <input type="text" name="street" class="form-control" required />
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold"><i class="bi bi-mailbox"></i> Postal Code</label>
          <input type="text" name="postal_code" class="form-control" required />
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold"><i class="bi bi-geo"></i> City</label>
          <input type="text" name="city" class="form-control" required />
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold"><i class="bi bi-grid"></i> Zone</label>
          <select name="zone" class="form-select" required>
            <option value="">-- Select Zone --</option>
            <option value="North Zone">North Zone</option>
            <option value="South Zone">South Zone</option>
            <option value="East Zone">East Zone</option>
            <option value="West Zone">West Zone</option>
            <option value="Central Zone">Central Zone</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold"><i class="bi bi-telephone"></i> Phone</label>
          <input type="text" name="phone" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold"><i class="bi bi-envelope"></i> Email</label>
          <input type="email" name="email" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold"><i class="bi bi-globe"></i> Website</label>
          <input type="text" name="website" class="form-control" />
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold"><i class="bi bi-card-text"></i> Description</label>
          <textarea name="description" id="description" class="form-control" rows="6"></textarea>
        </div>

        <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
        <script>
          ClassicEditor.create(document.querySelector("#description"), {
            toolbar: ["bold","italic","underline","|","bulletedList","numberedList","|","undo","redo"],
          }).catch((error)=>console.error(error));
        </script>

        <!-- Fields -->
        <div class="col-12 mt-4 border rounded-3 p-3">
          <h5 class="fw-bold mb-3"><i class="bi bi-layout-text-window me-2 text-warning"></i> Fields</h5>
          <div id="pistas-wrapper">
            <div class="row g-2 mb-2 pista-item">
              <div class="col-md-6">
                <input type="text" name="pista_name[]" class="form-control" placeholder="Field Type" />
              </div>
              <div class="col-md-4">
                <input type="number" name="pista_quantity[]" class="form-control" placeholder="Amount" />
              </div>
              <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger remove-pista w-100"><i class="bi bi-x-circle"></i> Remove</button>
              </div>
            </div>
          </div>
          <button type="button" id="add-pista" class="btn btn-sm btn-outline-primary mt-2">
            <i class="bi bi-plus-circle"></i> Add Field
          </button>
        </div>

        <!-- Schedules -->
        <div class="col-12 mt-4 border rounded-3 p-3">
          <h5 class="fw-bold mb-3"><i class="bi bi-calendar-week me-2 text-warning"></i> Schedules</h5>
          <div id="schedules-wrapper">
            <div class="row g-2 mb-2 schedule-item">
              <div class="col-md-3">
                <select name="schedule_day[]" class="form-select">
                  <option value="">-- Select Day --</option>
                  <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                  <option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option>
                </select>
              </div>
              <div class="col-md-3">
                <input type="time" name="open_time[]" class="form-control" />
              </div>
              <div class="col-md-3">
                <input type="time" name="close_time[]" class="form-control" />
              </div>
              <div class="col-md-3">
                <button type="button" class="btn btn-outline-danger remove-schedule w-100"><i class="bi bi-x-circle"></i> Remove</button>
              </div>
            </div>
          </div>
          <button type="button" id="add-schedule" class="btn btn-sm btn-outline-primary mt-2">
            <i class="bi bi-plus-circle"></i> Add Schedule
          </button>
        </div>

        <!-- Photos -->
        <div class="col-12 mt-4 border rounded-3 p-3">
          <h5 class="fw-bold mb-3"><i class="bi bi-images me-2 text-warning"></i> Photos</h5>
          <div id="photos-wrapper">
            <div class="mb-2 photo-item">
              <input type="file" name="photos[]" class="form-control" />
              <button type="button" class="btn btn-outline-danger btn-sm remove-photo mt-1">
                <i class="bi bi-x-circle"></i> Remove
              </button>
            </div>
          </div>
          <button type="button" id="add-photo" class="btn btn-sm btn-outline-primary mt-2">
            <i class="bi bi-plus-circle"></i> Add Photo
          </button>
        </div>

        <!-- Submit -->
        <div class="col-12 mt-4 text-end">
          <button type="submit" class="btn-gold px-4">
            <i class="bi bi-check-circle me-2"></i> Submit Club
          </button>
        </div>
      </form>
    </div>
  </div>
</section>


    <script>
      document
        .getElementById("add-pista")
        .addEventListener("click", function () {
          let wrapper = document.getElementById("pistas-wrapper");
          let item = document.querySelector(".pista-item").cloneNode(true);
          item.querySelectorAll("input").forEach((input) => (input.value = ""));
          wrapper.appendChild(item);
        });

      document.addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-pista"))
          e.target.closest(".pista-item").remove();
      });

      document
        .getElementById("add-schedule")
        .addEventListener("click", function () {
          let wrapper = document.getElementById("schedules-wrapper");
          let item = document.querySelector(".schedule-item").cloneNode(true);
          item.querySelectorAll("input").forEach((input) => (input.value = ""));
          item.querySelector("select").selectedIndex = 0;
          wrapper.appendChild(item);
        });

      document.addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-schedule"))
          e.target.closest(".schedule-item").remove();
      });

      document
        .getElementById("add-photo")
        .addEventListener("click", function () {
          let wrapper = document.getElementById("photos-wrapper");
          let item = document.querySelector(".photo-item").cloneNode(true);
          item.querySelector("input").value = "";
          wrapper.appendChild(item);
        });

      document.addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-photo"))
          e.target.closest(".photo-item").remove();
      });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
