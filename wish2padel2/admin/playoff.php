<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$self = basename($_SERVER['PHP_SELF']);

// --- SET TIMEZONE RIYADH ---
date_default_timezone_set('Asia/Riyadh');

// =========================
// Helpers
// =========================

// Snap ke hari Rabu/Kamis/Jumat terdekat dan set jam 20:00
function moveToValidMatchSlot(&$ts, $match_days = [3,4,5], $hour = 20) {
    $dow = (int)date('N', $ts);
    while (!in_array($dow, $match_days)) {
        $ts = strtotime('+1 day', $ts);
        $dow = (int)date('N', $ts);
    }
    $ts = mktime($hour, 0, 0, (int)date('n', $ts), (int)date('j', $ts), (int)date('Y', $ts));
}

// Cek apakah regular season selesai (semua match journey ≤ 14 completed) untuk tournament & division
function isRegularSeasonCompleted($conn, $tournament_id, $division) {
    $sql = "
        SELECT COUNT(*) AS pending
        FROM matches m
        JOIN team_info t1 ON t1.id = m.team1_id
        JOIN team_contact_details d1 ON d1.team_id = t1.id
        JOIN team_info t2 ON t2.id = m.team2_id
        JOIN team_contact_details d2 ON d2.team_id = t2.id
        WHERE m.tournament_id = ?
          AND d1.division = ?
          AND d2.division = ?
          AND m.status <> 'completed'
          AND m.notes IS NULL
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $tournament_id, $division, $division);
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc()['pending'] ?? 0;
    return ((int)$pending) === 0;
}

// Ambil tanggal terakhir regular season (notes IS NULL)
function getLastRegularDate($conn, $tournament_id, $division) {
    $sql = "
        SELECT MAX(m.scheduled_date) AS last_regular
        FROM matches m
        JOIN team_info t1 ON t1.id = m.team1_id
        JOIN team_contact_details d1 ON d1.team_id = t1.id
        JOIN team_info t2 ON t2.id = m.team2_id
        JOIN team_contact_details d2 ON d2.team_id = t2.id
        WHERE m.tournament_id = ?
          AND d1.division = ?
          AND d2.division = ?
          AND m.notes IS NULL
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $tournament_id, $division, $division);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['last_regular'] ? strtotime($row['last_regular']) : null;
}

// Ambil Top-4 berdasarkan points/pairs_won di regular (notes IS NULL)
function getTop4ByPairsWon(mysqli $conn, int $tournament_id, int $division): array {
    $sql = "
        WITH pr AS (
            SELECT
                tp.team_id,
                m.id AS match_id,
                tp.id AS pair_id,
                SUM(ps.is_winner) AS sets_won_pair,
                (COUNT(*) - SUM(ps.is_winner)) AS sets_lost_pair,
                CASE WHEN SUM(ps.is_winner) > COUNT(*)/2 THEN 1 ELSE 0 END AS pair_won
            FROM matches m
            JOIN team_pairs tp ON tp.match_id = m.id
            JOIN pair_scores ps ON ps.match_id = m.id AND ps.pair_id = tp.id
            WHERE m.tournament_id = ?
              AND m.status = 'completed'
              AND m.notes IS NULL
            GROUP BY tp.team_id, m.id, tp.id
            HAVING COUNT(*) > 0
        ),
        per_match AS (
            SELECT
                team_id,
                match_id,
                SUM(pair_won) AS pairs_won_match,
                SUM(sets_won_pair) AS sets_won_match,
                SUM(sets_lost_pair) AS sets_lost_match,
                CASE
                    WHEN SUM(pair_won) = 3 THEN 3
                    WHEN SUM(pair_won) = 2 THEN 2
                    WHEN SUM(pair_won) = 1 THEN 1
                    ELSE 0
                END AS points_match
            FROM pr
            GROUP BY team_id, match_id
        ),
        agg AS (
            SELECT
                team_id,
                SUM(pairs_won_match) AS pairs_won,
                SUM(3 - pairs_won_match) AS pairs_lost,
                SUM(sets_won_match) AS sets_won,
                SUM(sets_lost_match) AS sets_lost,
                SUM(points_match) AS points
            FROM per_match
            GROUP BY team_id
        )
        SELECT a.team_id
        FROM agg a
        JOIN team_contact_details d ON d.team_id = a.team_id
        JOIN team_info ti ON ti.id = a.team_id
        WHERE d.division = ?
        ORDER BY a.points DESC,
                 (a.sets_won - a.sets_lost) DESC,
                 (a.pairs_won - a.pairs_lost) DESC,
                 ti.team_name ASC
        LIMIT 4
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $tournament_id, $division);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];
    while ($r = $res->fetch_assoc()) $out[] = (int)$r['team_id'];
    return $out;
}

// Cek apakah playoff sudah pernah digenerate (notes IS NOT NULL)
function hasPlayoffGenerated($conn, $tournament_id, $division) {
    $sql = "
        SELECT COUNT(*) AS cnt
        FROM matches m
        JOIN team_info t1 ON t1.id = m.team1_id
        JOIN team_contact_details d1 ON d1.team_id = t1.id
        JOIN team_info t2 ON t2.id = m.team2_id
        JOIN team_contact_details d2 ON d2.team_id = t2.id
        WHERE m.tournament_id = ?
          AND d1.division = ?
          AND d2.division = ?
          AND m.notes IS NOT NULL
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $tournament_id, $division, $division);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    return ((int)$cnt) > 0;
}

// Insert match + team_pairs + notes
function insertMatchWithPairs($conn, $tournament_id, $team1_id, $team2_id, $scheduled_str, $journey, $note = null) {
    $note_sql = $note ? "'" . $conn->real_escape_string($note) . "'" : "NULL";
    $team1_sql = $team1_id === null ? "NULL" : (int)$team1_id;
    $team2_sql = $team2_id === null ? "NULL" : (int)$team2_id;

    $q = "
        INSERT INTO matches (tournament_id, team1_id, team2_id, scheduled_date, status, journey, notes)
        VALUES (".(int)$tournament_id.", $team1_sql, $team2_sql, '".$conn->real_escape_string($scheduled_str)."', 'scheduled', ".(int)$journey.", $note_sql)
    ";
    $ok = $conn->query($q);
    if (!$ok) throw new Exception("Insert match failed: ".$conn->error);
    $match_id = (int)$conn->insert_id;

    // Insert team_pairs hanya jika dua tim sudah fix
    if ($team1_id && $team2_id) {
        $stmt_pair = $conn->prepare("INSERT INTO team_pairs (match_id, pair_number, team_id) VALUES (?, ?, ?)");
        foreach ([$team1_id, $team2_id] as $tid) {
            for ($k = 1; $k <= 3; $k++) {
                $stmt_pair->bind_param("iii", $match_id, $k, $tid);
                $stmt_pair->execute();
            }
        }
    }
    return $match_id;
}

// =========================
// Tahun & Filter
// =========================

// Ambil tahun unik dari league
$league_years_res = $conn->query("
    SELECT DISTINCT YEAR(date) AS year 
    FROM league 
    ORDER BY year DESC
");
$years = [];
while ($y = $league_years_res->fetch_assoc()) $years[] = (int)$y['year'];

// Tahun sekarang
$current_year = (int)date('Y');
// Filter hanya tahun <= sekarang
$years = array_filter($years, fn($yr) => $yr <= $current_year);

// Tangkap filter tahun, default ke tahun sekarang
$selected_year = isset($_GET['year']) && $_GET['year'] !== "" ? (int)$_GET['year'] : $current_year;

// Ambil semua tournaments di tahun terpilih
$tournaments = $conn->query("
    SELECT tor.id, tor.name, tor.start_date, tor.id_league, l.name AS league_name
    FROM tournaments tor
    INNER JOIN league l ON tor.id_league = l.id
    WHERE YEAR(l.date) = $selected_year
    ORDER BY l.name ASC, tor.name ASC
");

// =========================
// Generate playoff
// =========================
if (isset($_POST['generate_playoff'])) {
    $tournament_id = (int)$_POST['tournament_id'];
    $division      = (int)$_POST['division'];

    try {
        $conn->autocommit(FALSE);

        if (hasPlayoffGenerated($conn, $tournament_id, $division))
            throw new Exception("Playoff already exists.");

        if (!isRegularSeasonCompleted($conn, $tournament_id, $division))
            throw new Exception("Regular season not finished yet.");

        $top4 = getTop4ByPairsWon($conn, $tournament_id, $division);
        if (count($top4) < 4) throw new Exception("Not enough teams to generate playoff.");

        [$rank1, $rank2, $rank3, $rank4] = $top4;

        // 🔹 Ambil journey terakhir dari match reguler di divisi ini
        $stmtLast = $conn->prepare("
            SELECT MAX(m.journey) AS last_journey
            FROM matches m
            JOIN team_contact_details tcd ON tcd.team_id IN (m.team1_id, m.team2_id)
            WHERE m.tournament_id = ?
              AND tcd.division = ?
        ");
        $stmtLast->bind_param("ii", $tournament_id, $division);
        $stmtLast->execute();
        $last = (int)($stmtLast->get_result()->fetch_assoc()['last_journey'] ?? 0);
        $stmtLast->close();

        // Kalau belum ada match sama sekali, mulai dari 14 by default
        $baseJourney = max($last, 14);

        // 🔹 Ambil tanggal terakhir match reguler untuk acuan tanggal playoff
        $lastRegular = getLastRegularDate($conn, $tournament_id, $division) ?? time();
        $week = function($days) use ($lastRegular) {
            $ts = strtotime("+$days days", $lastRegular);
            return date('Y-m-d 20:00:00', $ts);
        };

        // 🔹 Tentukan journey otomatis (lanjut dari terakhir)
        $semi1_journey = $baseJourney + 1;
        $semi2_journey = $baseJourney + 2;
        $final1_journey = $baseJourney + 3;
        $final2_journey = $baseJourney + 4;

        // --- SEMIFINAL ---
        insertMatchWithPairs($conn, $tournament_id, $rank3, $rank4, $week(7), $semi1_journey, 'Semi Final 1');
        insertMatchWithPairs($conn, $tournament_id, $rank4, $rank3, $week(14), $semi2_journey, 'Semi Final 2');

        // --- FINAL ---
        insertMatchWithPairs($conn, $tournament_id, null, $rank2, $week(21), $final1_journey, 'Final 1');
        insertMatchWithPairs($conn, $tournament_id, $rank2, null, $week(28), $final2_journey, 'Final 2');

        $conn->commit();
        header("Location: ?ok=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("❌ Error: ".$e->getMessage());
    }
}


// =========================
// Data untuk UI: daftar rows Tournament × Division
// =========================
// =========================
// Data untuk UI: daftar Tournament × Division
// =========================
$rows = [];

if ($tournaments && $tournaments->num_rows) {
    while ($t = $tournaments->fetch_assoc()) {
        $tid = (int)$t['id'];

        // ambil semua divisi yang punya tim terdaftar & sudah bayar
        $div_query = $conn->prepare("
            SELECT DISTINCT d.division
            FROM team_contact_details d
            INNER JOIN payment_transactions tp ON tp.team_id = d.team_id
            WHERE tp.tournament_id = ?
              AND tp.status = 'paid'
              AND d.division IS NOT NULL
            ORDER BY d.division ASC
        ");
        $div_query->bind_param("i", $tid);
        $div_query->execute();
        $divs = $div_query->get_result();

        while ($d = $divs->fetch_assoc()) {
            $division = (int)$d['division'];

            // regular season sudah selesai (notes IS NULL)
            $regular_done = isRegularSeasonCompleted($conn, $tid, $division);

            // sudah ada pertandingan playoff (notes IS NOT NULL)
            $already = hasPlayoffGenerated($conn, $tid, $division);

            // ambil preview top 4 (berdasarkan hasil regular)
            $top4 = getTop4ByPairsWon($conn, $tid, $division);

            // masukkan ke array hasil
            $rows[] = [
                'league_name'   => $t['league_name'],
                'tournament_id' => $tid,
                'tournament'    => $t['name'],
                'division'      => $division,
                'regular_done'  => $regular_done,
                'already'       => $already,
                'top4'          => $top4
            ];
        }
    }
}

// Ambil nama division (id → name) helper cache
function getDivisionName($conn, $id) {
    $id = (int)$id;
    $res = $conn->query("SELECT division_name FROM divisions WHERE id = $id LIMIT 1");
    if ($res && $r = $res->fetch_assoc()) return $r['division_name'];
    return 'Unknown';
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Playoff Generator - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body style="background-color: #303030">
<?php require 'src/navbar.php' ?>

<section class="py-5">
  <div class="container">

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
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 text-white m-0"><i class="bi bi-trophy me-2"></i>Playoff Generator</h1>
      
    </div>

    <!-- Filter Tahun -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
      <div class="card-body">
        <form class="row align-items-center" method="get">
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
    <div class="card shadow-sm border-0 rounded-3">
      <div class="card-body">
        <?php if(empty($rows)): ?>
          <div class="alert alert-info border-0 shadow-sm">
            There are no Tournaments/Divisions that meet the year filter yet <strong><?= htmlspecialchars((string)$selected_year) ?></strong>.
          </div>
        <?php else: ?>
        <div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-dark">
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
          <form method="post" class="d-inline">
            <input type="hidden" name="tournament_id" value="<?= $tournament_id ?>">
            <input type="hidden" name="division" value="<?= $division ?>">
            <?php if ($can_generate): ?>
              <button class="btn btn-success btn-sm px-3" name="generate_playoff"
                onclick="return confirm('Generate playoffs for <?= htmlspecialchars($tournament) ?> • Division <?= $division ?> — OK?')">
                <i class="bi bi-trophy me-1"></i> Generate Playoff
              </button>
            <?php else: ?>
              <button class="btn btn-outline-light text-dark btn-sm px-3" type="button" disabled
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
</section>

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
