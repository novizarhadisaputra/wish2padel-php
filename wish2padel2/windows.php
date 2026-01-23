<?php
session_start();
date_default_timezone_set("Asia/Riyadh");

require 'config.php';
$conn = getDBConnection();

if (!isset($_SESSION['username'])) {
    header("Location: login/login.php");
    exit;
}

$team_id = $_SESSION['team_id'] ?? null;
if (!$team_id) {
    echo '<div class="alert alert-warning">Team ID not found in session.</div>';
    exit;
}

$team_stmt = $conn->prepare("
    SELECT ti.id AS team_id, 
           t.id AS tournament_id, t.name AS tournament_name, 
           t.start_date, t.end_date, t.status AS tournament_status,
           l.date AS league_year
    FROM team_info ti 
    JOIN tournaments t ON t.id = ti.tournament_id 
    JOIN league l ON l.id = t.id_league
    WHERE ti.id = ?
");
$team_stmt->bind_param("i", $team_id);
$team_stmt->execute();
$team = $team_stmt->get_result()->fetch_assoc();
$team_stmt->close();

$seasonYear = $team['league_year'] ?? date('Y');

$win_stmt = $conn->prepare("
    SELECT id, start_date, end_date
    FROM transfer_windows
    WHERE (YEAR(start_date) = ? OR YEAR(end_date) = ?)
    ORDER BY start_date ASC
");
$win_stmt->bind_param("ii", $seasonYear, $seasonYear);
$win_stmt->execute();
$windows = $win_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$win_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Transfer Windows</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/stylee.css?=v12">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    body { background-color:#303030; }
    .page-title { color:#F3E6B6; }
    .transfer-card {
      background:#f9f9f9; border-radius:12px; border-left:4px solid #f3e6b6;
      box-shadow:0 2px 6px rgba(0,0,0,.12);
    }
    .thead-gold th {
      background:#1a1a1a; color:#F3E6B6; border:0;
    }
    .btn-gold {
      background-color:#F3E6B6; font-weight:700; border:1px solid #b58f20; color:#000;
    }
    .btn-gold:hover { filter:brightness(.95); }
  </style>
</head>
<body>

<?php require 'src/navbar.php' ?>

<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="page-title fw-bold text-gold">Transfer Windows</h2>
      <div class="text-white-50">
        Season <span class="fw-semibold"><?= htmlspecialchars($seasonYear) ?></span>
      </div>
    </div>

    <?php if (empty($windows)): ?>
      <div class="alert alert-light border text-center mb-4">
        No transfer windows scheduled for Season <?= htmlspecialchars($seasonYear) ?>.
      </div>
    <?php else: ?>

      <div class="transfer-window-glow-wrapper">
        <?php foreach ($windows as $idx => $w): 
          $start = date("M d, Y H:i", strtotime($w['start_date']));
          $end   = date("M d, Y H:i", strtotime($w['end_date']));
        ?>
          <div class="transfer-window-poster">
            <h3 class="transfer-window-title">
              <?= $idx == 0 ? "FIRST TRANSFER WINDOW" : "SECOND TRANSFER WINDOW" ?>
            </h3>
            <div class="transfer-window-divider"></div>
            <p class="transfer-window-date">
              <?= htmlspecialchars($start) ?> <span class="text-gold">→</span> <?= htmlspecialchars($end) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

    <div class="d-flex justify-content-center mt-4">
      <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#regulationsModal">
        View Transfer Regulations
      </button>
    </div>
  </div>
</section>

<div class="modal fade" id="regulationsModal" tabindex="-1" aria-labelledby="regulationsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" style="margin-top:40px;">
    <div class="modal-content">
      <div class="modal-header" style="background:#1a1a1a;">
        <h5 class="modal-title text-white" id="regulationsModalLabel">Transfer Regulations</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body" style="max-height:60vh; overflow-y:auto; background:#fff;">
        <div class="mb-2"><strong>Scope</strong></div>
        <ul>
          <li>Each team may sign up to <strong>five (5) new players</strong> during the designated transfer window(s).</li>
          <li><strong>Releases are allowed</strong>; however, a released player <strong>may not rejoin</strong> the same team within the same window.</li>
          <li>All transfer submissions must be fully completed and approved <strong>before the window closes</strong>.</li>
          <li>Once the window is closed, <strong>team rosters are locked</strong> until the next registered window.</li>
          <li>Clubs are responsible for ensuring all player information is accurate and compliant with league policy.</li>
          <li>The league reserves the right to <strong>reject</strong> any transfer request that violates policy or deadlines.</li>
        </ul>

        <div class="mb-2"><strong>Player Eligibility</strong></div>
        <ul>
          <li>Players must meet league eligibility criteria (age, documents, disciplinary status, etc.).</li>
          <li>Duplicate registrations across multiple teams within the same tournament are prohibited unless explicitly permitted.</li>
        </ul>

        <div class="mb-2"><strong>Documentation & Approval</strong></div>
        <ul>
          <li>Transfers are considered valid only after administrative review and confirmation by league officials.</li>
          <li>Clubs must provide accurate identity details for all players added or released.</li>
        </ul>

        <div class="mb-2"><strong>Compliance</strong></div>
        <ul>
          <li>Any fraudulent or misleading submissions may result in sanctions, including disqualification.</li>
          <li>League decisions on transfer approvals are final.</li>
        </ul>

        <div class="small text-muted mt-3">For clarifications, please contact league administration.</div>
      </div>

      <div class="modal-footer" style="background:#f7f7f7;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<style>
    .transfer-window-glow-wrapper {
  display: flex;
  flex-direction: column;
  gap: 35px;
}

.transfer-window-poster {
  text-align: center;
  padding: 40px 25px;
  background: linear-gradient(145deg, #1b1b1b, #222);
  border-left: 3px solid rgba(243,230,182,0.5);
  border-right: 3px solid rgba(243,230,182,0.5);
  border-radius: 12px;
  box-shadow: 0 0 18px rgba(243,230,182,0.25);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.transfer-window-poster:hover {
  transform: translateY(-4px);
  box-shadow: 0 0 32px rgba(243,230,182,0.45);
}

.transfer-window-title {
  color: #F3E6B6;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  margin-bottom: 10px;
}

.transfer-window-divider {
  width: 80px;
  height: 2px;
  background-color: rgba(243,230,182,0.6);
  margin: 0 auto 12px auto;
}

.transfer-window-date {
  color: #ddd;
  font-size: 1.1rem;
  font-weight: 500;
}

</style>


<?php require 'src/footer.php' ?>

<button id="scrollTopBtn" title="Go to top">↑</button>
<script>
  const scrollBtn = document.getElementById("scrollTopBtn");
  window.addEventListener('scroll', () => {
    scrollBtn.style.display = (document.documentElement.scrollTop > 200) ? "block" : "none";
  });
  scrollBtn.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
