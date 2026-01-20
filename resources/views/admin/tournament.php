<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p.png') ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>League Zone - Wish2Padel</title>
  <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: #303030">

<?php view('partials.navbar'); ?>

<!-- ================= MANAGE LEAGUE ================= -->
<section class="p-4">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-white">
        <i class="bi bi-trophy-fill text-warning me-2"></i> Manage League
      </h2>
      <button class="btn-gold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addLeagueModal">
        <i class="bi bi-plus-circle me-1"></i> Add League
      </button>
    </div>

    <!-- Table Card -->
    <div class="card shadow border-0 rounded-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-success">
              <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Year</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
  <?php while($row = $leagues->fetch_assoc()): ?>
    <tr>
      <td class="fw-semibold text-dark"><?= htmlspecialchars($row['name']) ?></td>
      <td class="text-muted"><?= htmlspecialchars($row['deskripsi'] ?? '-') ?></td>
      <td>
        <span class="badge bg-info text-dark px-3 py-2 rounded-pill">
          <?= $row['date'] ?>
        </span>
      </td>
      <td class="text-center">
        <button class="btn btn-sm btn-outline-warning me-1 rounded-circle"
                data-bs-toggle="modal"
                data-bs-target="#editLeagueModal<?= $row['id'] ?>"
                title="Edit">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger rounded-circle"
                data-bs-toggle="modal"
                data-bs-target="#deleteLeagueModal<?= $row['id'] ?>"
                title="Delete">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>
  <?php endwhile; ?>
</tbody>

          </table>
          <?php
$leagues->data_seek(0); // Reset pointer agar bisa dibaca ulang
while($row = $leagues->fetch_assoc()):
    $leagueId = (int)$row['id'];
?>
    <!-- Edit League Modal -->
    <div class="modal fade" id="editLeagueModal<?= $leagueId ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered" style="margin-top:70px">
        <div class="modal-content shadow-lg rounded-4 border-0">
          <div class="modal-header bg-warning text-dark rounded-top-4">
            <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update League</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" action="<?= asset('admin/tournament') ?>">
            <div class="modal-body">
              <input type="hidden" name="id" value="<?= $leagueId ?>">

              <div class="form-floating mb-3">
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($row['name']) ?>"
                       placeholder="Enter league name" required>
                <label>Name</label>
              </div>

              <div class="form-floating mb-3">
                <input type="text" name="deskripsi" class="form-control"
                       value="<?= htmlspecialchars($row['deskripsi'] ?? '') ?>"
                       placeholder="Optional: short description">
                <label>Description</label>
              </div>

              <div class="form-floating mb-3">
                <input type="number" name="date" class="form-control"
                       value="<?= $row['date'] ?>"
                       placeholder="e.g., 2025" required>
                <label>Year</label>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="edit_league" class="btn-gold">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete League Modal -->
    <div class="modal fade" id="deleteLeagueModal<?= $leagueId ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4 border-0">
          <div class="modal-header bg-danger text-white rounded-top-4">
            <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" action="<?= asset('admin/tournament') ?>">
            <div class="modal-body">
              <p class="mb-0">Are you sure you want to delete league
                <strong class="text-danger"><?= htmlspecialchars($row['name']) ?></strong>?
              </p>
              <input type="hidden" name="id" value="<?= $leagueId ?>">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="delete_league" class="btn btn-danger">Delete</button>
            </div>
          </form>
        </div>
      </div>
    </div>
<?php endwhile; ?>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= MANAGE ZONE ================= -->
<?php
// Simpan ke array supaya bisa dipakai ulang
$tournaments_list = [];
while($row = $tournaments->fetch_assoc()) {
    $tournaments_list[] = $row;
}
?>

<!-- ================= MANAGE ZONE ================= -->
<section class="p-4">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-white">
        <i class="bi bi-geo-alt-fill text-primary me-2"></i> Manage Zone
      </h2>
      <button class="btn-gold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addTournamentModal">
        <i class="bi bi-plus-circle me-1"></i> Add Zone
      </button>
    </div>

    <!-- Table -->
    <div class="card shadow border-0 rounded-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>League</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>

            <tbody>
            <?php if (empty($tournaments_list)): ?>
              <tr><td colspan="6" class="text-center text-muted">No zones found.</td></tr>
            <?php else: ?>
              <?php foreach($tournaments_list as $row): ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                  <td class="text-muted"><?= htmlspecialchars($row['league_name'] ?? '-') ?></td>
                  <td><span class="badge bg-info text-dark px-3 py-2 rounded-pill"><?= $row['start_date'] ?></span></td>
                  <td><span class="badge bg-secondary text-dark px-3 py-2 rounded-pill"><?= $row['end_date'] ?: '-' ?></span></td>
                  <td>
                    <?php if($row['status']=='upcoming'): ?>
                      <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Upcoming</span>
                    <?php elseif($row['status']=='completed'): ?>
                      <span class="badge bg-success px-3 py-2 rounded-pill">Completed</span>
                    <?php else: ?>
                      <span class="badge bg-secondary px-3 py-2 rounded-pill"><?= ucfirst($row['status']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-warning me-1 rounded-circle" data-bs-toggle="modal" data-bs-target="#editTournamentModal<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                    <?php if($row['status']=='upcoming'): ?>
                      <button class="btn btn-sm btn-outline-success rounded-circle" data-bs-toggle="modal" data-bs-target="#completeTournamentModal<?= $row['id'] ?>"><i class="bi bi-check2-circle"></i></button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>

          <!-- ===== Render Semua Modal ===== -->
          <?php foreach($tournaments_list as $row): $id = (int)$row['id']; ?>
            <!-- Edit Modal -->
            <div class="modal fade" id="editTournamentModal<?= $id ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0 rounded-4">
                  <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update Zone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST" action="<?= asset('admin/tournament') ?>">
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= $id ?>">
                      <div class="form-floating mb-3">
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
                        <label>Name</label>
                      </div>
                      <div class="form-floating mb-3">
                        <select name="id_league" class="form-select" required>
                          <option value="">-- Select League --</option>
                          <?php
                          $leagues->data_seek(0);
                          while($league = $leagues->fetch_assoc()): ?>
                            <option value="<?= $league['id'] ?>" <?= $row['id_league']==$league['id'] ? 'selected' : '' ?>><?= htmlspecialchars($league['name']) ?></option>
                          <?php endwhile; ?>
                        </select>
                        <label>League</label>
                      </div>
                      <div class="form-floating mb-3">
                        <input type="date" name="start_date" class="form-control" value="<?= $row['start_date'] ?>" required>
                        <label>Start Date</label>
                      </div>
                      <div class="form-floating mb-3">
                        <input type="date" name="end_date" class="form-control" value="<?= $row['end_date'] ?>" required>
                        <label>End Date</label>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" name="edit_tournament" class="btn-gold">Save Changes</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Complete Modal -->
            <div class="modal fade" id="completeTournamentModal<?= $id ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0 rounded-4">
                  <div class="modal-header bg-success text-white rounded-top-4">
                    <h5 class="modal-title"><i class="bi bi-check2-circle me-2"></i>Complete Zone</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST" action="<?= asset('admin/tournament') ?>">
                    <div class="modal-body">
                      Are you sure you want to mark <strong class="text-success">"<?= htmlspecialchars($row['name']) ?>"</strong> as completed?
                      <input type="hidden" name="id" value="<?= $id ?>">
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" name="complete_tournament" class="btn btn-success">Complete</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

        </div>
      </div>
    </div>
  </div>
</section>


<section class="p-4">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-white">
        <i class="bi bi-grid-3x3-gap-fill text-warning me-2"></i> Manage Division
      </h2>
      <button class="btn-gold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addDivisionModal">
        <i class="bi bi-plus-circle me-1"></i> Add Division
      </button>
    </div>

    <!-- Table Card -->
    <div class="card shadow border-0 rounded-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-success">
              <tr>
                <th>Division Number</th>
                <th>Name</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
           <tbody>
  <?php
  $rows = [];
  while($row = $divisions->fetch_assoc()):
      $rows[] = $row;
  ?>
    <tr>
      <td class="fw-semibold text-dark"><?= $row['id'] ?></td>
      <td class="text-muted"><?= htmlspecialchars($row['division_name']) ?></td>
      <td class="text-center">
        <button class="btn btn-sm btn-outline-warning me-1 rounded-circle" data-bs-toggle="modal" data-bs-target="#editDivisionModal<?= $row['id'] ?>" title="Edit">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger rounded-circle" data-bs-toggle="modal" data-bs-target="#deleteDivisionModal<?= $row['id'] ?>" title="Delete">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>
  <?php endwhile; ?>
</tbody>

          </table>
          <?php foreach($rows as $row): $id = (int)$row['id']; ?>

<!-- Edit Division Modal -->
<div class="modal fade" id="editDivisionModal<?= $id ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="margin-top:70px">
    <div class="modal-content shadow-lg rounded-4 border-0">
      <div class="modal-header bg-warning text-dark rounded-top-4">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update Division</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= asset('admin/tournament') ?>">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $id ?>">

          <div class="form-floating mb-3">
            <input type="number" name="id_new" class="form-control"
                   value="<?= $id ?>"
                   placeholder="Division ID" required>
            <label>Division ID</label>
          </div>

          <div class="form-floating mb-3">
            <input type="text" name="division_name" class="form-control"
                   value="<?= htmlspecialchars($row['division_name']) ?>"
                   placeholder="Division Name" required>
            <label>Division Name</label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_division" class="btn-gold">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Division Modal -->
<div class="modal fade" id="deleteDivisionModal<?= $id ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-4 border-0">
      <div class="modal-header bg-danger text-white rounded-top-4">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= asset('admin/tournament') ?>">
        <div class="modal-body">
          <p class="mb-0">Are you sure you want to delete division
            <strong class="text-danger"><?= htmlspecialchars($row['division_name']) ?></strong>?
          </p>
          <input type="hidden" name="id" value="<?= $id ?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="delete_division" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endforeach; ?>

        </div>
      </div>
    </div>
  </div>
</section>



<!-- Add League Modal -->
<div class="modal fade" id="addLeagueModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="margin-top:70px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add League</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= asset('admin/tournament') ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" placeholder="Enter league name" required>
          </div>
          <div class="mb-3">
            <label>Description</label>
            <input type="text" name="deskripsi" class="form-control" placeholder="Optional: short description">
          </div>
          <div class="mb-3">
            <label>Year</label>
            <input type="number" name="date" class="form-control" placeholder="e.g., 2025" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_league" class="btn-gold">Add League</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Add Tournament Modal -->
<div class="modal fade" id="addTournamentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="margin-top:100px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Zone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= asset('admin/tournament') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Zone</label>
                        <select name="name" class="form-select" required>
                            <option value="" disabled selected>Select Zone</option>
                            <option value="North Zone">North Zone</option>
                            <option value="South Zone">South Zone</option>
                            <option value="East Zone">East Zone</option>
                            <option value="West Zone">West Zone</option>
                            <option value="Central Zone">Central Zone</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>League</label>
                        <select name="id_league" class="form-control" required>
                            <option value="">-- Select League --</option>
                            <?php
                            $leagues->data_seek(0);
                            while($league = $leagues->fetch_assoc()): ?>
                            <option value="<?= $league['id'] ?>">
                            <?= htmlspecialchars($league['name']) ?> (<?= htmlspecialchars($league['date']) ?>)
                        </option>

                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <!-- ✅ Tambahan End Date -->
                    <div class="mb-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_tournament" class="btn-gold">Add Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Division Modal -->
<div class="modal fade" id="addDivisionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="margin-top:70px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Division</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= asset('admin/tournament') ?>">
        <div class="modal-body">

          <div class="mb-3">
            <label>Division Rank</label>
            <input type="number" name="id" class="form-control" placeholder="Enter division rank (e.g. 1)" required>
          </div>

          <div class="mb-3">
            <label>Division Name</label>
            <input type="text" name="division_name" class="form-control" placeholder="Enter division name (e.g. Advanced B)" required>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_division" class="btn-gold">Add Division</button>
        </div>
      </form>
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
