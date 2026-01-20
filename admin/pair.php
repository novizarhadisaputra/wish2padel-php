<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}
require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Overview - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
</head>
<body style="background-color: #303030">

<?php require 'src/navbar.php' ?>

<style>
.container-section {
    max-width: 1100px;
    margin: 20px auto;
    padding: 20px;
}
.match-card {
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    animation: fadeIn 0.4s ease-in-out;
}
.match-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 10px;
}
.table-mini {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}
.table-mini th, .table-mini td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: center;
    font-size: 14px;
}
.badge-win { background: #28a745; color:#fff; padding:3px 6px; border-radius:5px; }
.badge-lose { background: #dc3545; color:#fff; padding:3px 6px; border-radius:5px; }
@keyframes fadeIn {
    from { opacity:0; transform:translateY(10px); }
    to { opacity:1; transform:translateY(0); }
}
</style>

<style>
.filter-form {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}
.filter-form input {
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
}
.filter-form button {
    padding: 6px 12px;
    border: none;
    border-radius: 8px;
    background: #007bff;
    color: #fff;
    cursor: pointer;
}
.filter-form button:hover {
    background: #0056b3;
}
</style>


<?php
$filter_match_id = $_GET['match_id'] ?? null;

// --- Handle POST accept/reject hasil ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['match_id'], $_POST['team_id'])) {
    $match_id = (int) $_POST['match_id'];
    $team_id  = (int) $_POST['team_id'];

    if ($_POST['action'] === 'accept') {
        $stmt = $conn->prepare("UPDATE match_results SET status='accept', updated_at=NOW() WHERE match_id=? AND team_id=?");
        $stmt->bind_param("ii", $match_id, $team_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'reject') {
    $conn->autocommit(false);
    try {
        // Ambil info divisi, journey, notes
        $res_team = $conn->query("
            SELECT d.division AS division_id, m.journey, m.notes
            FROM team_contact_details d
            JOIN matches m ON m.id = $match_id
            WHERE d.team_id = $team_id
        ");
        $team_info = $res_team->fetch_assoc();
        $division_id = (int)$team_info['division_id'];
        $journey     = (int)$team_info['journey'];
        $notes       = $team_info['notes'];

        // Hanya rollback point jika notes = NULL (bukan playoff)
        if ($notes === null) {
            $pointsTable = [
                1 => [650,604,558,511,464,418,372,372,418,464,511,558,604,650],
                2 => [540,501,463,424,386,348,309,309,348,386,424,463,501,540],
                3 => [450,417,386,354,321,289,257,257,289,321,354,386,417,450],
                4 => [375,348,320,294,268,241,214,214,241,268,294,320,348,375],
            ];

            $pairs_res = $conn->query("SELECT id FROM team_pairs WHERE match_id=$match_id AND team_id=$team_id");
            while ($pair = $pairs_res->fetch_assoc()) {
                $pair_id = (int)$pair['id'];

                $res_set = $conn->query("
                    SELECT SUM(is_winner) AS won_sets
                    FROM pair_scores
                    WHERE match_id=$match_id AND pair_id=$pair_id AND team_id=$team_id
                ")->fetch_assoc();
                $won_sets = (int)$res_set['won_sets'];

                // Tentukan point per pair
                if ($won_sets >= 2) {
                    $pair_point = $pointsTable[$division_id][$journey - 1] ?? 0;
                } elseif ($won_sets === 1) {
                    $pair_point = 15;
                } else {
                    $pair_point = 10;
                }

                // Kurangi point tiap pemain
                $res_players = $conn->query("SELECT player_name FROM pair_players WHERE pair_id=$pair_id");
                while ($p = $res_players->fetch_assoc()) {
                    $player_name = $conn->real_escape_string($p['player_name']);
                    $conn->query("
                        UPDATE team_members_info
                        SET point = point - $pair_point
                        WHERE team_id=$team_id AND player_name='$player_name'
                    ");
                }
            }
        }

        // Hapus pair_scores
        $conn->query("DELETE FROM pair_scores WHERE match_id=$match_id AND team_id=$team_id");

        // Hapus match_results
        $conn->query("DELETE FROM match_results WHERE match_id=$match_id AND team_id=$team_id");

        $conn->commit();
        echo "<script>window.location.href='pair.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>Error rollback: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}


    echo "<script>window.location.href='pair.php';</script>";
    exit;
}


// --- Handle POST update score + hitung pemenang ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scores'], $_POST['match_id'])) {
    $match_id = (int) $_POST['match_id'];

    // Update tiap score
    foreach ($_POST['scores'] as $score_id => $score_value) {
        $stmt = $conn->prepare("UPDATE pair_scores SET team_score=? WHERE id=?");
        $stmt->bind_param("ii", $score_value, $score_id);
        $stmt->execute();
    }

    // Hitung total score tiap pair
    $pair_totals = [];
    $pairs = $conn->query("SELECT id FROM team_pairs WHERE match_id={$match_id}");
    while ($p = $pairs->fetch_assoc()) {
        $res = $conn->query("SELECT SUM(team_score) AS total FROM pair_scores WHERE pair_id={$p['id']}")->fetch_assoc();
        $pair_totals[$p['id']] = $res['total'];
    }

    // Tentukan pemenang per pair
    $max_score = max($pair_totals);
    foreach ($pair_totals as $pair_id => $total) {
        $is_winner = ($total == $max_score) ? 1 : 0;
        $conn->query("UPDATE pair_scores SET is_winner={$is_winner} WHERE pair_id={$pair_id}");
    }

    // Update match_results
    $winner_pair_id = array_search($max_score, $pair_totals);
    $conn->query("UPDATE match_results SET winner_pair_id={$winner_pair_id}, last_updated=NOW() WHERE match_id={$match_id}");

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// --- Ambil match ---
if ($filter_match_id) {
    $stmt = $conn->prepare("
        SELECT m.id AS match_id, t1.team_name AS team1, t2.team_name AS team2
        FROM matches m
        JOIN team_info t1 ON m.team1_id = t1.id
        JOIN team_info t2 ON m.team2_id = t2.id
        WHERE m.id=?
    ");
    $stmt->bind_param("i", $filter_match_id);
    $stmt->execute();
    $matches = $stmt->get_result();
} else {
    $matches = $conn->query("
        SELECT m.id AS match_id, t1.team_name AS team1, t2.team_name AS team2
        FROM matches m
        JOIN team_info t1 ON m.team1_id = t1.id
        JOIN team_info t2 ON m.team2_id = t2.id
        ORDER BY m.id ASC
    ");
}
?>

<section class="container text-white my-5">
   <?php
$anomaly = "
SELECT 
  m.id AS match_id,
  t1.team_name AS team1,
  t2.team_name AS team2,
  mr1.pairs_won AS pairs_won_team1,
  mr1.pairs_lost AS pairs_lost_team1,
  mr2.pairs_won AS pairs_won_team2,
  mr2.pairs_lost AS pairs_lost_team2
FROM matches m
LEFT JOIN match_results mr1 
  ON mr1.match_id = m.id AND mr1.team_id = m.team1_id
LEFT JOIN match_results mr2 
  ON mr2.match_id = m.id AND mr2.team_id = m.team2_id
LEFT JOIN team_info t1 ON t1.id = m.team1_id
LEFT JOIN team_info t2 ON t2.id = m.team2_id
WHERE 
  mr1.match_id IS NOT NULL 
  AND mr2.match_id IS NOT NULL 
  AND (
    (mr1.pairs_won > mr1.pairs_lost AND mr2.pairs_won > mr2.pairs_lost)
    OR
    (mr1.pairs_won < mr1.pairs_lost AND mr2.pairs_won < mr2.pairs_lost)
  )
ORDER BY m.id DESC
";

$result = $conn->query($anomaly);
?>

<?php if ($result && $result->num_rows > 0): ?>
<div class="alert alert-danger border-0 shadow-sm rounded-4 mt-3">
  <h5 class="fw-bold text-danger mb-2">⚠️ Match Conflicts Detected</h5>
  <p class="text-muted mb-3">Some matches have conflicting results (both teams marked as win or lose):</p>
  <ul class="list-group list-group-flush">
    <?php while($row = $result->fetch_assoc()): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <span>
          <strong>Match #<?= htmlspecialchars($row['match_id']) ?></strong> 
          <span class="badge bg-danger">Conflict</span><br>
          <?= htmlspecialchars($row['team1']) ?> (<?= $row['pairs_won_team1'] ?? '-' ?>-<?= $row['pairs_lost_team1'] ?? '-' ?>)
          vs
          <?= htmlspecialchars($row['team2']) ?> (<?= $row['pairs_won_team2'] ?? '-' ?>-<?= $row['pairs_lost_team2'] ?? '-' ?>)
        </span>
      </li>
    <?php endwhile; ?>
  </ul>
</div>
<?php else: ?>
<div class="alert alert-success border-0 shadow-sm rounded-4 mt-3">
  <i class="bi bi-check-circle-fill text-success me-2"></i>
  No anomalies detected — all match results are consistent.
</div>
<?php endif; ?>

  <h2 class="fw-bold mb-4">📊 Match Overview</h2>

 <!-- Filter -->
<div class="card shadow-sm border-0 rounded-3 mb-4">
  <div class="card-body">
    <form method="get" class="row g-3 align-items-end">
      <!-- Input Match ID -->
      <div class="col-md-4">
        <label for="match_id" class="form-label fw-semibold text-secondary">Filter by Match ID</label>
        <input 
          type="number" 
          id="match_id" 
          name="match_id" 
          class="form-control" 
          placeholder="Enter Match ID" 
          value="<?= htmlspecialchars($filter_match_id) ?>">
      </div>

      <!-- Tombol -->
      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-dark px-4">
          <i class="bi bi-search me-1"></i> Filter
        </button>
        <?php if ($filter_match_id): ?>
          <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-outline-secondary px-4">
            <i class="bi bi-x-circle me-1"></i> Reset
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>


  <?php if ($matches->num_rows === 0): ?>
    <div class="alert alert-warning">No matches found.</div>
  <?php endif; ?>

  <?php while ($match = $matches->fetch_assoc()): ?>
  <div class="card shadow-sm mb-4 rounded-3 overflow-hidden">
    <!-- Header -->
    <div class="card-header text-white d-flex justify-content-between align-items-center"
         style="background: linear-gradient(135deg, #343a40, #495057);">
      <span><strong>Match <?= $match['match_id'] ?></strong> | 
        <?= htmlspecialchars($match['team1']) ?> <span class="fw-light">vs</span> <?= htmlspecialchars($match['team2']) ?>
      </span>
      <button type="button" class="btn-gold" data-bs-toggle="modal" data-bs-target="#editScoresModal<?= $match['match_id'] ?>">
        <i class="bi bi-pencil-square me-1"></i> Edit Score
      </button>
    </div>

    <!-- Body -->
    <div class="card-body bg-white">
      <!-- Pair Table -->
      <table class="table table-bordered text-center align-middle mb-4">
        <thead class="table-light">
          <tr>
            <th>Pair #</th>
            <th>Team</th>
            <th>Players</th>
            <th>Scores</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $pairs = $conn->query("SELECT tp.id AS pair_id, tp.pair_number, ti.team_name 
                                 FROM team_pairs tp 
                                 JOIN team_info ti ON tp.team_id=ti.id 
                                 WHERE tp.match_id={$match['match_id']} 
                                 ORDER BY tp.pair_number, tp.team_id");
          while ($pair = $pairs->fetch_assoc()):
              $players = $conn->query("SELECT player_name FROM pair_players WHERE pair_id={$pair['pair_id']}");
              $player_names = array_column($players->fetch_all(MYSQLI_ASSOC), 'player_name');

              $scores = $conn->query("SELECT set_number, team_score, is_winner 
                                      FROM pair_scores WHERE pair_id={$pair['pair_id']} 
                                      ORDER BY set_number ASC");
          ?>
          <tr>
            <td><?= $pair['pair_number'] ?></td>
            <td class="fw-semibold"><?= htmlspecialchars($pair['team_name']) ?></td>
            <td><?= implode(", ", $player_names) ?></td>
            <td>
              <?php while ($s = $scores->fetch_assoc()): ?>
                <div>
                  <strong>Set <?= $s['set_number'] ?>:</strong> <?= $s['team_score'] ?>
                  <?php if ($s['is_winner']): ?>
                    <span class="badge bg-success">Win</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Lose</span>
                  <?php endif; ?>
                </div>
              <?php endwhile; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <!-- Per Team Info -->
      <div class="row">
        <?php
        $res_teams = $conn->query("SELECT mr.*, ti.team_name 
                                   FROM match_results mr
                                   JOIN team_info ti ON mr.team_id = ti.id
                                   WHERE mr.match_id = {$match['match_id']}");
        while ($team = $res_teams->fetch_assoc()): ?>
        <div class="col-md-6 mb-3">
          <div class="border rounded p-3 h-100 bg-light">
            <h5 class="fw-bold"><?= htmlspecialchars($team['team_name']) ?></h5>
            <p>Status: 
              <?php if ($team['status'] === 'accept'): ?>
                <span class="badge bg-success">Accepted</span>
              <?php elseif ($team['status'] === 'reject'): ?>
                <span class="badge bg-danger">Rejected</span>
              <?php else: ?>
                <span class="badge bg-secondary"><?= ucfirst($team['status']) ?></span>
              <?php endif; ?>
            </p>
            <p>Won: <?= $team['pairs_won'] ?> | Lost: <?= $team['pairs_lost'] ?></p>

            <!-- Dokumen -->
            <?php
            $lineup = $conn->query("SELECT letter FROM lineup_letters WHERE match_id={$match['match_id']} AND team_id={$team['team_id']} ORDER BY uploaded_at DESC LIMIT 1");
            ?>
            <?php if ($lineup && $lineup->num_rows > 0): ?>
              <a href="<?= htmlspecialchars($lineup->fetch_assoc()['letter']) ?>" target="_blank" class="btn btn-outline-dark btn-sm w-100 mb-2">📄 View Lineup</a>
            <?php endif; ?>

            <?php if (!empty($team['letter'])): ?>
              <a href="<?= htmlspecialchars($team['letter']) ?>" target="_blank" class="btn btn-outline-success btn-sm w-100">🏆 View Score</a>
            <?php endif; ?>

            <!-- Accept / Reject -->
            <form method="post" class="d-flex gap-2 mt-3">
              <input type="hidden" name="match_id" value="<?= $match['match_id'] ?>">
              <input type="hidden" name="team_id" value="<?= $team['team_id'] ?>">
              <button type="submit" name="action" value="accept" class="btn btn-success btn-sm flex-fill">Accept</button>
              <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm flex-fill">Reject</button>
            </form>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal fade" id="editScoresModal<?= $match['match_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow rounded-3">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Edit Scores - Match <?= $match['match_id'] ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="post">
            <input type="hidden" name="match_id" value="<?= $match['match_id'] ?>">
            <?php
            $pairs_modal = $conn->query("SELECT tp.id AS pair_id, tp.pair_number, ti.team_name 
                                         FROM team_pairs tp 
                                         JOIN team_info ti ON tp.team_id=ti.id 
                                         WHERE tp.match_id={$match['match_id']}
                                         ORDER BY tp.pair_number, tp.team_id");
            while ($pm = $pairs_modal->fetch_assoc()):
              $players_modal = $conn->query("SELECT player_name FROM pair_players WHERE pair_id={$pm['pair_id']}");
              $names_modal = array_column($players_modal->fetch_all(MYSQLI_ASSOC), 'player_name');

              $scores_modal = $conn->query("SELECT id,set_number,team_score FROM pair_scores WHERE pair_id={$pm['pair_id']} ORDER BY set_number ASC");
            ?>
            <div class="mb-3 border rounded p-3 bg-light">
              <h6>Pair <?= $pm['pair_number'] ?> - <?= htmlspecialchars($pm['team_name']) ?></h6>
              <p class="small text-muted"><?= implode(", ", $names_modal) ?></p>
              <?php while($sm=$scores_modal->fetch_assoc()): ?>
                <label class="form-label">Set <?= $sm['set_number'] ?>:</label>
                <input type="number" name="scores[<?= $sm['id'] ?>]" value="<?= $sm['team_score'] ?>" class="form-control mb-2">
              <?php endwhile; ?>
            </div>
            <?php endwhile; ?>
            <button type="submit" class="btn btn-dark">Save Changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php endwhile; ?>
</section>


<script>
function openModal(id){document.getElementById('modal-'+id).style.display='flex';}
function closeModal(id){document.getElementById('modal-'+id).style.display='none';}
</script>

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
