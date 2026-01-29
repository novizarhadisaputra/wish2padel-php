<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Playoff Generator - Wish2Padel</title>
    <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="admin-page">
<?php
view('partials.navbar');
$conn = getDBConnection();

// Helper for View
function getDivisionName($conn, $id) {
    $id = (int)$id;
    $res = $conn->query("SELECT division_name FROM divisions WHERE id = $id LIMIT 1");
    if ($res && $r = $res->fetch_assoc()) return $r['division_name'];
    return 'Unknown';
}
?>

<div class="container py-5 mt-5">

    <!-- Alerts -->
    <?php if(isset($_GET['ok'])): ?>
      <div class="alert alert-success shadow-sm border-0">
        Playoffs have been successfully generated for the selected divisions.
      </div>
    <?php endif; ?>
    <?php if(isset($_GET['err'])): ?>
      <div class="alert alert-danger shadow-sm border-0">
        ❌ <?= htmlspecialchars($_GET['err']) ?>
      </div>
    <?php endif; ?>

    <!-- Heading -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h2 class="text-gold mb-0"><i class="bi bi-trophy me-2"></i>Playoff Generator</h2>
    </div>

    <!-- Filter Tahun -->
    <div class="card admin-card shadow-lg mb-4">
      <div class="card-body">
        <form class="row align-items-center" method="get" action="<?= asset('admin/playoff') ?>">
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

    <!-- Legend -->
    <div class="mb-3">
      <span class="badge bg-success me-2">Regular Done</span>
      <span class="badge bg-secondary me-2">Playoff Generated</span>
      <span class="badge bg-warning text-dark me-2">Need Top-4</span>
      <span class="badge bg-danger">Regular Pending</span>
    </div>

    <!-- Table Tournament x Division -->
    <div class="card admin-card shadow-lg">
      <div class="card-body p-0">
        <?php if(empty($rows)): ?>
          <div class="alert alert-info border-0 shadow-sm">
            There are no Tournaments/Divisions that meet the year filter yet <strong><?= htmlspecialchars((string)$selected_year) ?></strong>.
          </div>
        <?php else: ?>
        <div class="table-responsive">
  <table class="table table-dark admin-table table-hover mb-0 align-middle">
    <thead>
      <tr>
        <th style="width:56px">#</th>
        <th>League</th>
        <th>Tournament</th>
        <th>Division</th>
        <th>Top-4 Preview</th>
        <th>Status</th>
        <th class="text-center" style="width:220px">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $no = 1;
        foreach ($rows as $r):
          $league_name   = $r['league_name'] ?? '-';
          $tournament_id = (int)$r['tournament_id'];
          $tournament    = $r['tournament'] ?? '-';
          $division      = (int)$r['division'];
          $div_name      = getDivisionName($conn, $division);
          $regular_done  = !empty($r['regular_done']);
          $already       = !empty($r['already']);
          $top4_ids      = $r['top4'] ?? [];

          // Ambil nama Top-4
          $top4_names = [];
          if (!empty($top4_ids)) {
              $ids_join = implode(',', array_map('intval', $top4_ids));
              $qnames = $conn->query("SELECT id, team_name FROM team_info WHERE id IN ($ids_join)");
              $map = [];
              while($tt = $qnames->fetch_assoc()) $map[(int)$tt['id']] = $tt['team_name'];
              foreach ($top4_ids as $tid) {
                  $top4_names[] = htmlspecialchars($map[(int)$tid] ?? ('#'.$tid));
              }
          }

          // Status badge
          if ($already) {
              $status_badge = '<span class="badge bg-secondary">Playoff Generated</span>';
          } elseif (!$regular_done) {
              $status_badge = '<span class="badge bg-danger">Regular Pending</span>';
          } elseif (count($top4_ids) < 4) {
              $status_badge = '<span class="badge bg-warning text-dark">Need Top-4</span>';
          } else {
              $status_badge = '<span class="badge bg-success">Regular Completed</span>';
          }

          // Button enable/disable logic
          $can_generate = ($regular_done && !$already && count($top4_ids) >= 4);

          // Tooltip reason for disabled state
          $tooltip = '';
          if ($already) {
              $tooltip = 'Playoffs already generated';
          } elseif (!$regular_done) {
              $tooltip = 'Regular season not yet completed (all matches with notes=NULL must be completed)';
          } elseif (count($top4_ids) < 4) {
              $tooltip = 'Not enough teams for Top-4';
          } else {
              $tooltip = 'Unavailable';
          }
      ?>
      <tr>
        <td><?= $no++ ?></td>
        <td class="fw-semibold"><?= htmlspecialchars($league_name) ?></td>
        <td><?= htmlspecialchars($tournament) ?></td>
        <td><?= $division ?> – <?= htmlspecialchars($div_name) ?></td>
        <td>
          <?php if (empty($top4_names)): ?>
            <span class="text-muted">–</span>
          <?php else: ?>
            <ol class="m-0 ps-3">
              <?php foreach ($top4_names as $idx => $nm): ?>
                <li>
                  <?= $nm ?>
                  <?php if ($idx === 0): ?>
                    <span class="badge bg-outline-light text-dark border ms-1">Rank 1 (auto promote)</span>
                  <?php elseif ($idx === 1): ?>
                    <span class="badge bg-outline-light text-dark border ms-1">Rank 2 (Final)</span>
                  <?php elseif ($idx === 2 || $idx === 3): ?>
                    <span class="badge bg-outline-light text-dark border ms-1">Semi</span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>
        </td>
        <td><?= $status_badge ?></td>
        <td class="text-center">
          <form method="post" class="d-inline" action="<?= asset('admin/playoff') ?>?year=<?= $selected_year ?>">
            <input type="hidden" name="tournament_id" value="<?= $tournament_id ?>">
            <input type="hidden" name="division" value="<?= $division ?>">
            <?php if ($can_generate): ?>
              <button class="btn btn-admin-gold btn-sm px-3" name="generate_playoff"
                onclick="return confirm('Generate playoffs for <?= htmlspecialchars($tournament) ?> • Division <?= $division ?> — OK?')">
                <i class="bi bi-trophy me-1"></i> Generate Playoff
              </button>
            <?php else: ?>
              <button class="btn btn-secondary btn-sm px-3" type="button" disabled
                      title="<?= htmlspecialchars($tooltip) ?>">
                <i class="bi bi-trophy me-1"></i> Generate Playoff
              </button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

        <?php endif; ?>
      </div>
    </div>

</div>

<!-- Scroll to Top Button -->
<button id="scrollTopBtn" title="Go to top">↑</button>

<script>
  const scrollBtn = document.getElementById("scrollTopBtn");
  window.onscroll = function() {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
      scrollBtn.style.display = "block";
    } else {
      scrollBtn.style.display = "none";
    }
  };
  scrollBtn.addEventListener("click", function() {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
