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
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p logo.jpeg') ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p logo.jpeg') ?>">
        <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p logo.jpeg') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
  </head>
  <body style="background-color: #303030">
    <?php view('partials.navbar'); ?>

    <section class="container py-5">
  <div class="card shadow border-0 rounded-3">
    <div class="card-header py-3" style="background:#212529;">
      <h4 class="mb-0 text-white">
        <i class="bi bi-building me-2 text-warning"></i> Add New Club
      </h4>
    </div>
    <div class="card-body">
      <form action="<?= asset('admin/club/store') ?>" method="post" enctype="multipart/form-data" class="row g-3">

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
