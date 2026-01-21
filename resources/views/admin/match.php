<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Match - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body style="background-color: #303030">

<?php view('partials.navbar'); ?>

<!-- ================= MANAGE MATCHES ================= -->
<section class="py-5">
  <div class="container">

    <!-- Filter Tahun -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
      <div class="card-body">
        <form class="row align-items-center" method="get" action="<?= asset('admin/matches') ?>">
          <div class="col-md-4">
            <label class="fw-semibold text-secondary mb-2">Filter by Year</label>
            <select class="form-select form-select-lg shadow-sm" name="year" onchange="this.form.submit()">
              <option value="">-- Select Year --</option>
              <?php foreach($years as $y): ?>
                <option value="<?= $y ?>" <?= $selected_year==$y?'selected':'' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      </div>
    </div>

    <!-- Generate Match -->
    <?php if($selected_year && !$matches_exist): ?>
    <div class="text-end mb-4">
      <form method="post" action="<?= asset('admin/matches') ?>?year=<?= $selected_year ?>">
        <button class="btn btn-dark shadow-sm rounded px-4" name="add_match">
          <i class="bi bi-shuffle me-1"></i> Generate All Matches
        </button>
      </form>
    </div>
    <?php endif; ?>

    <!-- Table Matches -->
    <?php if($matches_exist): ?>
    <div class="card shadow-sm border-0 rounded-3">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>League</th>
            <th>Tournament</th>
            <th>Division</th>
            <th>Journey</th>
            <th>Team 1</th>
            <th>Team 2</th>
            <th>Scheduled Date & Time</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no=1;
          $modalData = [];
          foreach($matches_list as $row):
          ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="fw-semibold"><?= htmlspecialchars($row['league_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['tournament_name'] ?? '-') ?></td>
            <td>
              <?php
                if (!empty($row['division'])) {
                    $divId = intval($row['division']);
                    // Using direct connection for division name if not joined.
                    // Controller didn't join division name? Let's check.
                    // Controller query: `LEFT JOIN team_contact_details tcd ...`
                    // It didn't join divisions table.
                    // I will use just ID for now or perform dirty query if needed.
                    echo $divId;
                } else {
                    echo '<span class="text-muted">No Division</span>';
                }
              ?>
            </td>
            <td><?= $row['journey'] ?? '-' ?></td>
            <td class="fw-semibold"><?= htmlspecialchars($row['team1_name']) ?></td>
            <td class="fw-semibold"><?= htmlspecialchars($row['team2_name']) ?></td>
            <td><i class="bi bi-calendar-event me-1 text-muted"></i><?= $row['scheduled_date'] ?></td>
            <td>
              <?php if($row['status']=='scheduled'): ?>
                <span class="badge bg-secondary">Scheduled</span>
              <?php elseif($row['status']=='completed'): ?>
                <span class="badge bg-success">Completed</span>
              <?php elseif($row['status']=='postponed'): ?>
                <span class="badge bg-warning text-dark">Postponed</span>
              <?php elseif($row['status']=='cancelled'): ?>
                <span class="badge bg-danger">Cancelled</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if(in_array($row['status'], ['scheduled','pending'])): ?>
                <button class="btn btn-sm btn-outline-dark rounded-circle" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="Edit">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger rounded-circle ms-1" data-bs-toggle="modal" data-bs-target="#noShowModal<?= $row['id'] ?>" title="Report No Show">
                  <i class="bi bi-exclamation-triangle"></i>
                </button>
              <?php endif; ?>
            </td>
          </tr>

          <?php
          $modalData[] = $row;
          endforeach;
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php foreach($modalData as $row): ?>
<div class="modal fade" id="noShowModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Report No Show</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Select the team that did <b>not</b> show up. The opponent will be declared the winner.</p>
        <form method="POST" action="<?= asset('admin/matches') ?>?year=<?= $selected_year ?>">
            <input type="hidden" name="match_id" value="<?= $row['id'] ?>">
            <input type="hidden" name="tournament_id" value="<?= $row['tournament_id'] ?? 0 ?>">
            <button type="submit" name="report_no_show" value="<?= $row['team1_id'] ?>" class="btn btn-outline-danger w-100 mb-2">
                <?= htmlspecialchars($row['team1_name']) ?> (No Show)
            </button>
            <button type="submit" name="report_no_show" value="<?= $row['team2_id'] ?>" class="btn btn-outline-danger w-100">
                <?= htmlspecialchars($row['team2_name']) ?> (No Show)
            </button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="margin-top:100px">
    <div class="modal-content shadow border-0 rounded-3">
      <form method="post" action="<?= asset('admin/matches') ?>?year=<?= $selected_year ?>">
        <div class="modal-header bg-dark text-white rounded-top-3">
          <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Match</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="match_id" value="<?= $row['id'] ?>">

          <div class="mb-3">
            <label class="form-label">League / Tournament</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($row['league_name'] ?? '-') ?> / <?= htmlspecialchars($row['tournament_name'] ?? '-') ?>" disabled>
          </div>

          <div class="mb-3">
            <label class="form-label">Division</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($row['division'] ?? '-') ?>" disabled>
          </div>

          <div class="mb-3">
            <label class="form-label">Team 1</label>
            <select name="team1_id" class="form-select" required>
              <?php
              // Dirty Query again for teams dropdown
              $conn = getDBConnection();
              $teams = $conn->query("SELECT * FROM team_info");
              while($t = $teams->fetch_assoc()): ?>
                <option value="<?= $t['id'] ?>" <?= $t['id']==$row['team1_id']?'selected':'' ?>><?= htmlspecialchars($t['team_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Team 2</label>
            <select name="team2_id" class="form-select" required>
              <?php
              $teams2 = $conn->query("SELECT * FROM team_info");
              while($t2 = $teams2->fetch_assoc()): ?>
                <option value="<?= $t2['id'] ?>" <?= $t2['id']==$row['team2_id']?'selected':'' ?>><?= htmlspecialchars($t2['team_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Journey</label>
            <input type="number" disabled name="journey" class="form-control" value="<?= $row['journey'] ?? 1 ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Scheduled Date & Time</label>
            <input type="datetime-local" name="scheduled_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($row['scheduled_date'])) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
              <?php $statuses = ['scheduled','completed','cancelled','postponed']; ?>
              <?php foreach($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $row['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-dark" name="update_match">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

    <?php endif; ?>

  </div>
</section>


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
