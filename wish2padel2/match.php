<?php
session_start();
require 'config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Match Detail - Wish2Padel</title>
  <link rel="icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/stylee.css">
</head>
<body style="background-color:#303030; color:#f8f9fa;">

<?php require 'src/navbar.php' ?>

<?php
$match_id = $_GET['id'] ?? null;
if (!$match_id) {
    echo "<div class='alert alert-danger m-4'>Match ID not found.</div>";
    exit;
}

// Ambil data match + logo tim
$sql = "
SELECT m.id AS match_id, m.scheduled_date,
       t1.id AS team1_id, t1.team_name AS team1, t1.logo AS logo1,
       t2.id AS team2_id, t2.team_name AS team2, t2.logo AS logo2,
       tcd1.club AS club1, tcd1.division AS division1,
       tour.name AS tournament_name
FROM matches m
JOIN team_info t1 ON m.team1_id = t1.id
JOIN team_info t2 ON m.team2_id = t2.id
JOIN team_contact_details tcd1 ON tcd1.team_id = t1.id
JOIN tournaments tour ON tour.id = m.tournament_id
WHERE m.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

// Ambil semua pair
$sql_pairs = "
SELECT tp.id AS pair_id, tp.pair_number, tp.team_id
FROM team_pairs tp
WHERE tp.match_id = ?
ORDER BY tp.pair_number ASC
";
$stmt_pairs = $conn->prepare($sql_pairs);
$stmt_pairs->bind_param("i", $match_id);
$stmt_pairs->execute();
$pairs_res = $stmt_pairs->get_result();

$pairs = [];
while($pair = $pairs_res->fetch_assoc()) {
    $pair_id = $pair['pair_id'];

    // Pemain
    $stmt_players = $conn->prepare("SELECT player_name FROM pair_players WHERE pair_id=? ORDER BY id ASC");
    $stmt_players->bind_param("i", $pair_id);
    $stmt_players->execute();
    $players_res = $stmt_players->get_result();
    $players = [];
    while($p = $players_res->fetch_assoc()) $players[] = $p['player_name'];
    $pair['players'] = $players;

    // Skor per set
    $stmt_scores = $conn->prepare("SELECT set_number, team_score FROM pair_scores WHERE match_id=? AND pair_id=? ORDER BY set_number ASC");
    $stmt_scores->bind_param("ii", $match_id, $pair_id);
    $stmt_scores->execute();
    $scores_res = $stmt_scores->get_result();
    $scores = [];
    while($s = $scores_res->fetch_assoc()) $scores[$s['set_number']] = $s['team_score'];
    $pair['scores'] = $scores;

    $pairs[] = $pair;
}

// Kelompokkan berdasarkan pair_number
$pairs_by_number = [];
foreach($pairs as $p) {
    $pairs_by_number[$p['pair_number']][$p['team_id']] = $p;
}

// Final score default
$final_score = [
    $match['team1_id'] => 0,
    $match['team2_id'] => 0
];
?>

<section class="container py-5">
  <!-- Match Info -->
  <div id="match" class="card border-0 shadow-sm mb-4 p-4 bg-light text-dark">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-bold mb-0"><?= htmlspecialchars($match['tournament_name']) ?></h4>
      <span class="badge bg-secondary"><?= date("d M Y", strtotime($match['scheduled_date'])) ?></span>
    </div>

    <div class="row">
      <div class="col-md-6">
        <p class="mb-1"><strong>Club:</strong> <?= htmlspecialchars($match['club1']) ?></p>
        <p class="mb-1"><strong>Division:</strong> <?= htmlspecialchars($match['division1']) ?></p>
      </div>
      <div class="col-md-6 text-md-end">
        <p class="mb-1"><strong>Home:</strong>
          <a href="team_profile.php?id=<?= $match['team1_id'] ?>" class="text-decoration-none" style="color:#88604A;">
            <?= htmlspecialchars($match['team1']) ?>
          </a>
        </p>
        <p class="mb-0"><strong>Away:</strong>
          <a href="team_profile.php?id=<?= $match['team2_id'] ?>" class="text-decoration-none" style="color:#88604A;">
            <?= htmlspecialchars($match['team2']) ?>
          </a>
        </p>
      </div>
    </div>
  </div>

  <!-- Pair-by-Pair Breakdown -->
  <?php foreach($pairs_by_number as $pair_number => $pair_group): ?>
    <div class="card border-0 shadow-sm mb-4 p-4 bg-white text-dark">
      <h6 class="fw-bold text-uppercase text-center mb-4 text-muted">Pair <?= $pair_number ?></h6>

      <div class="row text-center align-items-center">
        <!-- Team 1 -->
        <div class="col-4">
          <img src="uploads/logo/<?= htmlspecialchars($match['logo1'] ?? 'default.png') ?>" 
               alt="<?= htmlspecialchars($match['team1']) ?>"
               style="width:45px;height:45px;object-fit:contain;background-color:#fff;border-radius:50%;padding:3px;margin-bottom:5px;">
          <div class="fw-semibold mb-1"><?= htmlspecialchars($match['team1']) ?></div>
          <div class="small text-muted">
            <?= isset($pair_group[$match['team1_id']]) ? implode("<br>", $pair_group[$match['team1_id']]['players']) : "" ?>
          </div>
        </div>

        <!-- Scores -->
        <div class="col-4">
          <?php
          $team1_scores = $pair_group[$match['team1_id']]['scores'] ?? [];
          $team2_scores = $pair_group[$match['team2_id']]['scores'] ?? [];
          $max_set = max(count($team1_scores), count($team2_scores));

          $team1_sets_won = 0;
          $team2_sets_won = 0;

          for($set=1; $set<=$max_set; $set++):
              $s1 = $team1_scores[$set] ?? 0;
              $s2 = $team2_scores[$set] ?? 0;

              if($s1 > $s2) $team1_sets_won++;
              elseif($s2 > $s1) $team2_sets_won++;

              $winner_class = $s1>$s2 ? "text-success fw-bold" : ($s2>$s1 ? "text-danger fw-bold" : "text-muted");
              echo "<div class='$winner_class'>Set $set: $s1 – $s2</div>";
          endfor;

          if($team1_sets_won > $team2_sets_won) $final_score[$match['team1_id']]++;
          elseif($team2_sets_won > $team1_sets_won) $final_score[$match['team2_id']]++;
          ?>
        </div>

        <!-- Team 2 -->
        <div class="col-4">
          <img src="uploads/logo/<?= htmlspecialchars($match['logo2'] ?? 'default.png') ?>" 
               alt="<?= htmlspecialchars($match['team2']) ?>"
               style="width:45px;height:45px;object-fit:contain;background-color:#fff;border-radius:50%;padding:3px;margin-bottom:5px;">
          <div class="fw-semibold mb-1"><?= htmlspecialchars($match['team2']) ?></div>
          <div class="small text-muted">
            <?= isset($pair_group[$match['team2_id']]) ? implode("<br>", $pair_group[$match['team2_id']]['players']) : "" ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Final Score -->
  <?php
  $stmt_results = $conn->prepare("SELECT team_id, pairs_won FROM match_results WHERE match_id=?");
  $stmt_results->bind_param("i", $match_id);
  $stmt_results->execute();
  $res = $stmt_results->get_result();
  $match_results = [];
  while ($r = $res->fetch_assoc()) {
      $match_results[$r['team_id']] = $r['pairs_won'];
  }
  ?>

  <div class="card border-0 shadow-sm p-4 text-center bg-light text-dark">
    <h6 class="fw-bold text-uppercase text-muted mb-2">Final Score</h6>
    <div class="fs-5 fw-semibold">
      <?= htmlspecialchars($match['team1']) ?> 
      <span class="text-dark"><?= $match_results[$match['team1_id']] ?? 0 ?></span>
      <span class="mx-2">–</span>
      <span class="text-dark"><?= $match_results[$match['team2_id']] ?? 0 ?></span>
      <?= htmlspecialchars($match['team2']) ?>
    </div>
  </div>
</section>

<?php require 'src/footer.php'; ?>

<button id="scrollTopBtn" title="Go to top">↑</button>

<script>
const btn = document.getElementById("scrollTopBtn");
window.onscroll = () => btn.style.display = (window.scrollY > 200) ? "block" : "none";
btn.onclick = () => window.scrollTo({ top: 0, behavior: "smooth" });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
