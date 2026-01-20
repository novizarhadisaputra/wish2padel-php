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

// Ambil tahun unik dari league
$league_years_res = $conn->query("
    SELECT DISTINCT YEAR(date) AS year 
    FROM league 
    ORDER BY year DESC
");

$years = [];
while ($y = $league_years_res->fetch_assoc()) {
    $years[] = (int)$y['year'];
}

// Tahun sekarang
$current_year = (int)date('Y');

// Filter hanya tahun <= sekarang
$years = array_filter($years, fn($yr) => $yr <= $current_year);

// Tangkap filter tahun, default ke tahun sekarang
$selected_year = isset($_GET['year']) && $_GET['year'] !== ""
    ? (int)$_GET['year']
    : $current_year;

// Ambil league sesuai tahun terpilih
$leagues = $conn->query("
    SELECT id, name 
    FROM league 
    WHERE YEAR(date) = $selected_year
");

// Ambil semua tournaments, hanya dari league tahun terpilih
$tournaments = $conn->query("
    SELECT tor.id, tor.name, tor.start_date, tor.id_league
    FROM tournaments tor
    INNER JOIN league l ON tor.id_league = l.id
    WHERE l.date = $selected_year
");


// ===== GENERATE MATCHES (round robin home & away, no dummy stored, odd teams get rest) =====
if (isset($_POST['add_match']) && $selected_year) {
    try {
        $conn->autocommit(FALSE);

        // Ambil tournaments sesuai tahun ini
        $tournaments = $conn->query("
            SELECT tor.id, tor.name, tor.start_date, tor.id_league
            FROM tournaments tor
            INNER JOIN league l ON tor.id_league = l.id
            WHERE YEAR(l.date) = $selected_year
        ");
        if (!$tournaments->num_rows) throw new Exception("No tournaments found for selected year.");

        // prepared statements reused
        $stmt_check_exist = $conn->prepare("
            SELECT id FROM matches 
            WHERE tournament_id = ? AND team1_id = ? AND team2_id = ?
            LIMIT 1
        ");
        $stmt_insert_match = $conn->prepare("
            INSERT INTO matches (tournament_id, team1_id, team2_id, scheduled_date, status, journey)
            VALUES (?, ?, ?, ?, 'scheduled', ?)
        ");
        $stmt_insert_pair = $conn->prepare("INSERT INTO team_pairs (match_id, pair_number, team_id) VALUES (?, ?, ?)");

        while ($tournament = $tournaments->fetch_assoc()) {
            $tournament_id = (int)$tournament['id'];
            $start_date_ts = strtotime($tournament['start_date']);
            $match_days    = [3,4,5]; // Rabu-Kamis-Jumat (N: 1..7)
            $match_hour    = 20;

            // =========================
            // ⬇️ GANTI: langsung ambil DIVISION (tanpa LEVEL), skip NULL
            // =========================
            $divisions_res = $conn->query("
                SELECT DISTINCT tcd.division
                FROM team_contact_details tcd
                INNER JOIN payment_transactions tp ON tcd.team_id = tp.team_id
                WHERE tp.tournament_id = $tournament_id
                  AND tp.status = 'paid'
                  AND tcd.division IS NOT NULL
                ORDER BY tcd.division ASC
            ");

            while ($div = $divisions_res->fetch_assoc()) {
                $division = (int)$div['division'];

                // Ambil tim per division (tanpa level)
                $teams_res = $conn->query("
                    SELECT ti.id, ti.team_name
                    FROM team_info ti
                    INNER JOIN team_contact_details tcd ON ti.id = tcd.team_id
                    INNER JOIN payment_transactions tp ON ti.id = tp.team_id
                    WHERE tcd.division = $division
                      AND tp.tournament_id = $tournament_id
                      AND tp.status = 'paid'
                    ORDER BY ti.id ASC
                ");

                $teams = [];
                while ($t = $teams_res->fetch_assoc()) $teams[] = $t;
                $N = count($teams);
                if ($N < 2) continue; // perlu minimal 2 tim

                // Build array of team IDs
                $team_ids_orig = array_map(fn($t) => $t['id'], $teams);

                // If odd number of teams: append a null slot for rotation only (rest),
                // but DO NOT insert matches where one side is null.
                $has_odd = ($N % 2 === 1);
                $rotating_ids = $team_ids_orig;
                if ($has_odd) {
                    $rotating_ids[] = null; // internal placeholder
                    $num_slots = $N + 1;
                } else {
                    $num_slots = $N;
                }

                // Determine rounds per leg:
                // - even N: rounds = N - 1
                // - odd N: rounds = N (because with placeholder you need N rounds to complete)
                $rounds = ($has_odd) ? $N : ($N - 1);

                // Circle method setup: first element is fixed, others rotate
                // we make a working array of length num_slots
                $work = $rotating_ids;
                // ensure length is num_slots
                if (count($work) !== $num_slots) {
                    // safety pad (shouldn't happen)
                    while (count($work) < $num_slots) $work[] = null;
                }

                // We'll generate leg 1 (rounds) then leg 2 (reverse home-away)
                $current_date = $start_date_ts;
                $journey = 1;

                // Helper: advance $current_date to next valid match day (Wed/Thu/Fri) if needed
                $next_valid_day = function(&$ts) use ($match_days) {
                    $dow = (int)date('N', $ts);
                    // if current day is not allowed, advance day-by-day until allowed
                    while (!in_array($dow, $match_days)) {
                        $ts = strtotime('+1 day', $ts);
                        $dow = (int)date('N', $ts);
                    }
                };

                // generate rounds
                for ($leg = 1; $leg <= 2; $leg++) { // leg 1 and leg 2 (return)
                    // for each round
                    for ($r = 0; $r < $rounds; $r++) {
                        // build current pairing array from $work
                        $pairs = [];
                        $len = count($work);
                        for ($i = 0; $i < $len / 2; $i++) {
                            $a = $work[$i];
                            $b = $work[$len - 1 - $i];

                            // if either side is null => this is a rest (skip inserting)
                            if ($a === null || $b === null) {
                                continue;
                            }

                            // Determine home/away
                            if ($leg === 1) {
                                $home = $a;
                                $away = $b;
                            } else {
                                // reverse home/away for return leg
                                $home = $b;
                                $away = $a;
                            }

                            // safety: avoid same-team matches (shouldn't happen)
                            if ($home === $away) continue;

                            $pairs[] = [$home, $away];
                        }

                        // For each pair, schedule a match (ensure date is valid and no duplicate)
                        $match_per_day = 0; // counter jumlah match dalam satu hari

                        foreach ($pairs as $pair) {

                            // Pastikan tanggal saat ini adalah hari valid (Rabu/Kamis/Jumat)
                            $next_valid_day($current_date);

                            // Jika sudah 1 match dalam 1 hari, pindah ke hari berikutnya (sesuai kode asli)
                            if ($match_per_day >= 1) {
                                $current_date = strtotime('+1 day', $current_date);
                                $match_per_day = 0;
                                $next_valid_day($current_date);
                            }

                            // Jadwalkan match di hari ini
                            $match_date_ts = mktime($match_hour, 0, 0, date('n', $current_date), date('j', $current_date), date('Y', $current_date));
                            $sched_str = date('Y-m-d H:i:s', $match_date_ts);

                            // safety: check existing exact (team1,team2) for this tournament
                            $stmt_check_exist->bind_param("iii", $tournament_id, $pair[0], $pair[1]);
                            $stmt_check_exist->execute();
                            $stmt_check_exist->store_result();
                            $exists = $stmt_check_exist->num_rows > 0;
                            $stmt_check_exist->free_result();

                            // Cek apakah salah satu tim sudah memiliki match di jam yang sama
                            $stmt_check_conflict = $conn->prepare("
                                SELECT id FROM matches 
                                WHERE tournament_id = ? 
                                  AND scheduled_date = ? 
                                  AND (team1_id = ? OR team2_id = ? OR team1_id = ? OR team2_id = ?)
                                LIMIT 1
                            ");
                            $stmt_check_conflict->bind_param("isiiii", $tournament_id, $sched_str, $pair[0], $pair[0], $pair[1], $pair[1]);
                            $stmt_check_conflict->execute();
                            $stmt_check_conflict->store_result();
                            $conflict = $stmt_check_conflict->num_rows > 0;
                            $stmt_check_conflict->free_result();

                            if ($conflict) {
                                // Kalau bentrok, geser ke hari berikutnya
                                $current_date = strtotime('+1 day', $current_date);
                                $next_valid_day($current_date);
                                // Hitung ulang jam baru
                                $match_date_ts = mktime($match_hour, 0, 0, date('n', $current_date), date('j', $current_date), date('Y', $current_date));
                                $sched_str = date('Y-m-d H:i:s', $match_date_ts);
                            }

                            if (!$exists) {
                                // insert match
                                $stmt_insert_match->bind_param("iiisi", $tournament_id, $pair[0], $pair[1], $sched_str, $journey);
                                $stmt_insert_match->execute();
                                $match_id = $stmt_insert_match->insert_id;

                                // insert 3 pairs per team
                                foreach ([$pair[0], $pair[1]] as $tid) {
                                    for ($k = 1; $k <= 3; $k++) {
                                        $stmt_insert_pair->bind_param("iii", $match_id, $k, $tid);
                                        $stmt_insert_pair->execute();
                                    }
                                }
                            }

                            $match_per_day++; // Tambah counter per match
                        }
                        // end foreach pairs

                        $journey++;

                        // rotate (circle method) for next round:
                        // keep index 0 fixed, rotate the rest to the right by 1
                        $first = array_shift($work);
                        $last = array_pop($work);
                        array_unshift($work, $last);
                        array_unshift($work, $first);
                        // After this manipulation, adjust to maintain same ordering length:
                        // restore $work to [first] + rotated_rest where rotated_rest is right-rotated by 1
                        $rest = array_slice($work, 1); // because we pushed first back
                        // rotate rest right by 1
                        $rest_last = array_pop($rest);
                        array_unshift($rest, $rest_last);
                        $work = array_merge([$first], $rest);
                    } // end rounds
                } // end legs

            } // end division loop
        } // end tournament loop

        $conn->commit();
        header("Location: $self?year=$selected_year");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger m-3'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}


// Ambil semua match_id yang sudah punya pairs
$matches_with_pairs = [];
$res = $conn->query("SELECT DISTINCT match_id FROM team_pairs");
while ($r = $res->fetch_assoc()) $matches_with_pairs[] = $r['match_id'];

// Ambil semua match untuk ditampilkan sesuai tahun filter
$matches_exist = false;
$matches = [];
if($selected_year){
    $matches_res = $conn->query("
        SELECT m.*, t1.team_name AS team1_name, t2.team_name AS team2_name, 
               tor.name AS tournament_name, l.name AS league_name, tcd.level, tcd.division
        FROM matches m
        LEFT JOIN team_info t1 ON m.team1_id = t1.id
        LEFT JOIN team_info t2 ON m.team2_id = t2.id
        LEFT JOIN tournaments tor ON m.tournament_id = tor.id
        LEFT JOIN league l ON tor.id_league = l.id
        LEFT JOIN team_contact_details tcd ON t1.id = tcd.team_id
        WHERE YEAR(l.date) = $selected_year
        ORDER BY m.scheduled_date ASC
    ");
    if($matches_res->num_rows > 0){
        $matches_exist = true;
        while($row = $matches_res->fetch_assoc()) $matches[] = $row;
    }
}

// HANDLE UPDATE MATCH
if(isset($_POST['update_match'])){
    $id = (int) $_POST['match_id'];
    $team1_id = (int) $_POST['team1_id'];
    $team2_id = (int) $_POST['team2_id'];
    // HAPUS journey dari POST:
    // $journey = (int) $_POST['journey'];  // ❌ NGGAK DIPAKAI LAGI

    $scheduled_date = str_replace("T"," ",$_POST['scheduled_date']).":00";
    $status = $_POST['status'];

    $scheduled_timestamp = strtotime($scheduled_date);
    $scheduled_date_riyadh = date('Y-m-d H:i:s',$scheduled_timestamp);

    // ✅ Update TANPA journey
    $stmt = $conn->prepare("
        UPDATE matches 
        SET team1_id=?, team2_id=?, scheduled_date=?, status=?
        WHERE id=?
    ");
    $stmt->bind_param("iissi", $team1_id, $team2_id, $scheduled_date_riyadh, $status, $id);
    $stmt->execute();

    header("Location: ".$_SERVER['PHP_SELF']."?year=$selected_year");
    exit;
}

if(isset($_POST['delete_matches']) && !empty($_POST['delete_year'])) {
    $delete_year = $_POST['delete_year'];

    // Ambil semua match_id dari tahun terpilih
    $sql_matches = "SELECT id FROM matches WHERE YEAR(scheduled_date) = ?";
    $stmt = $conn->prepare($sql_matches);
    $stmt->bind_param("i", $delete_year);
    $stmt->execute();
    $res = $stmt->get_result();

    $match_ids = [];
    while($m = $res->fetch_assoc()) {
        $match_ids[] = $m['id'];
    }

    if(!empty($match_ids)) {
        $placeholders = implode(',', array_fill(0, count($match_ids), '?'));
        $types = str_repeat('i', count($match_ids));

        // Hapus team_pairs terkait
        $stmt_tp = $conn->prepare("DELETE FROM team_pairs WHERE match_id IN ($placeholders)");
        $stmt_tp->bind_param($types, ...$match_ids);
        $stmt_tp->execute();

        // Hapus matches
        $stmt_m = $conn->prepare("DELETE FROM matches WHERE id IN ($placeholders)");
        $stmt_m->bind_param($types, ...$match_ids);
        $stmt_m->execute();
    }

    // Reload halaman agar update tampilannya
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
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
  <title>Match - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body style="background-color: #303030">

<?php require 'src/navbar.php' ?>

<!-- ================= MANAGE MATCHES ================= -->
<section class="py-5">
  <div class="container">

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
        <!-- Delete All Matches By Year -->
        <!--<?php if($matches_exist && $selected_year): ?>-->
        <!--    <div class="text-start mb-3" style="margin-left:20px">-->
        <!--      <form method="post" onsubmit="return confirm('Are you sure you want to delete all matches in the year <?= $selected_year ?>?');">-->
        <!--        <input type="hidden" name="delete_year" value="<?= $selected_year ?>">-->
        <!--        <button type="submit" name="delete_matches" class="btn btn-outline-danger shadow-sm">-->
        <!--          <i class="bi bi-trash me-1"></i> Delete All Matches in <?= $selected_year ?>-->
        <!--        </button>-->
        <!--      </form>-->
        <!--    </div>-->
        <!--<?php endif; ?>-->

    </div>

    <!-- Generate Match -->
    <?php if($selected_year && !$matches_exist): ?>
    <div class="text-end mb-4">
      <form method="post">
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
          $modalData = []; // <-- SIMPAN DATA UNTUK MODAL 
          foreach($matches as $row): 
          ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="fw-semibold"><?= htmlspecialchars($row['league_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['tournament_name'] ?? '-') ?></td>
            <td>
              <?php
                if (!empty($row['division'])) {
                    $divId = intval($row['division']);
                    $divNameRes = $conn->query("SELECT division_name FROM divisions WHERE id = $divId LIMIT 1");
                    $divName = $divNameRes->fetch_assoc()['division_name'] ?? 'Unknown';
                    echo $divId . " – " . htmlspecialchars($divName);
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
              <?php endif; ?>
            </td>
          </tr>

          <?php 
          // Simpan data modal untuk dirender nanti
          $modalData[] = $row;
          endforeach; 
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php foreach($modalData as $row): ?>
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="margin-top:100px">
    <div class="modal-content shadow border-0 rounded-3">
      <form method="post">
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
              <?php $teams = $conn->query("SELECT * FROM team_info");
              while($t = $teams->fetch_assoc()): ?>
                <option value="<?= $t['id'] ?>" <?= $t['id']==$row['team1_id']?'selected':'' ?>><?= htmlspecialchars($t['team_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Team 2</label>
            <select name="team2_id" class="form-select" required>
              <?php $teams2 = $conn->query("SELECT * FROM team_info");
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
