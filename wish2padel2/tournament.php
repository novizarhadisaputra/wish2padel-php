<?php
    session_start();
    require 'config.php';
    $conn = getDBConnection();
    $username = $_SESSION['username'] ?? null;
    $current_page = basename($_SERVER['PHP_SELF']);
    
    $tournament_id = $_GET['id'] ?? null;
    $selected_division = isset($_GET['division']) ? intval($_GET['division']) : null;
    
    if (!$tournament_id) {
        echo "<div class='alert alert-danger'>Tournament ID tidak ditemukan.</div>";
        exit;
    }
    
    $stmt = $conn->prepare("SELECT name FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();
    
    $divisions_res = $conn->query("SELECT id, division_name FROM divisions ORDER BY id ASC");
    $divisions = [];
    while($row = $divisions_res->fetch_assoc()) {
        $divisions[] = $row;
    }
    
    if ($selected_division === null) {
        foreach ($divisions as $div) {
            $check_sql = "
                SELECT 1
                FROM matches m
                JOIN team_info t1 ON m.team1_id = t1.id
                JOIN team_info t2 ON m.team2_id = t2.id
                JOIN team_contact_details c1 ON c1.team_id = t1.id
                JOIN team_contact_details c2 ON c2.team_id = t2.id
                WHERE m.tournament_id = ?
                AND c1.division = ?
                AND c2.division = ?
                LIMIT 1
            ";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iii", $tournament_id, $div['id'], $div['id']);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
    
           
        }
    
        if ($selected_division === null && !empty($divisions)) {
            $selected_division = $divisions[0]['id'];
        }
    }
    
    $sql = "
        SELECT m.id, m.scheduled_date, m.status, m.journey,
            t1.id AS team1_id, t1.team_name AS team1, t1.logo AS team1_logo, c1.division AS team1_division,
            t2.id AS team2_id, t2.team_name AS team2, t2.logo AS team2_logo, c2.division AS team2_division,
            mr1.pairs_won AS score1,
            mr2.pairs_won AS score2
        FROM matches m
        JOIN team_info t1 ON m.team1_id = t1.id
        JOIN team_info t2 ON m.team2_id = t2.id
        JOIN team_contact_details c1 ON c1.team_id = t1.id
        JOIN team_contact_details c2 ON c2.team_id = t2.id
        LEFT JOIN match_results mr1 ON mr1.match_id = m.id AND mr1.team_id = t1.id AND mr1.status='accept'
        LEFT JOIN match_results mr2 ON mr2.match_id = m.id AND mr2.team_id = t2.id AND mr2.status='accept'
        WHERE m.tournament_id = ?
    ";
    
    if ($selected_division !== null && $selected_division !== 0) {
        $sql .= " AND (c1.division = ? AND c2.division = ?)";
    }
    
    $sql .= " ORDER BY m.journey ASC, m.scheduled_date ASC";
    
    $stmt = $conn->prepare($sql);
    
    if ($selected_division !== null && $selected_division !== 0) {
        $stmt->bind_param("iii", $tournament_id, $selected_division, $selected_division);
    } else {
        $stmt->bind_param("i", $tournament_id);
    }
    
    $stmt->execute();
    $res = $stmt->get_result();
    
    $journeys = [];
    while($row = $res->fetch_assoc()) {
        $journeys[$row['journey']][] = $row;
    }
    $total_journey = count($journeys);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>League - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="assets/css/stylee.css?v=12">
</head>
<body>

<?php require 'src/navbar.php' ?>


<section class="container py-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-black">League: <?= htmlspecialchars($tournament['name']) ?></h2>
        <p class="text-muted">Padel Competition Management</p>
    </div>
    

    <ul id="nav-tab" class="nav nav-tabs mb-4" id="tournamentTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#schedule">Schedule</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#teams">Teams</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#leaderboard">Clasification</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#playoff">Playoff</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#ranking">Player Stats</a>
        </li>
    </ul>
    
    

    <div class="tab-content">
        <div class="tab-pane fade show active" id="schedule">
            <div class="row mb-3 g-3">
                <div class="col-md-6">
                    <label for="filterDivision" class="form-label fw-bold">Filter by Division</label>
                    <form method="get" id="divisionForm">
                        <input type="hidden" name="id" value="<?= $tournament_id ?>">
        
                        <?php if ($selected_division === null && !empty($divisions)) {
                            $selected_division = $divisions[0]['id'];
                        } ?>
        
                        <select name="division" id="filterDivision" class="form-select" onchange="this.form.submit()" required>
                            <?php foreach($divisions as $div): ?>
                                <option value="<?= $div['id'] ?>" <?= ($selected_division == $div['id'] ? 'selected' : '') ?>>
                                    <?= $div['id'] . ' – ' . htmlspecialchars($div['division_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <div class="row g-4" id="scheduleContainer">
                <?php if(empty($journeys)): ?>
                    <div class="alert alert-warning">No schedules found for this filter.</div>
                <?php else: ?>
               
                    <?php foreach($journeys as $journey_number => $matches): ?>
                        <?php if($journey_number >= 1 && $journey_number <= 14): ?>
                            <div class="col-12">
                                <div class="card border-1 shadow-sm mb-4">
                                    <div class="card-header text-white d-flex justify-content-between align-items-center"
                                         style="background-color:black"
                                         data-bs-toggle="collapse"
                                         data-bs-target="#journey<?= $journey_number ?>">
                                        <?php $label_total = ($journey_number <= 14) ? 14 : (($journey_number <= 16) ? 16 : 18); ?>
                                        <strong>Journey <?= $journey_number ?> / <?= $label_total ?></strong>
                                        <i class="bi bi-chevron-down"></i>
                                    </div>
        
                                    <div id="journey<?= $journey_number ?>" class="collapse <?= ($journey_number <= 4 ? 'show' : '') ?>">
                                        <div class="card-body">
                                            <?php foreach($matches as $row): 
                                                $score1 = $row['score1'] ?? null;
                                                $score2 = $row['score2'] ?? null;
                                                $formatted_date = date("l, d M Y, H:i", strtotime($row['scheduled_date']));
                                                $team1_div_level = 'Division '.$row['team1_division'].' – '.$row['team1_level'];
                                                $team2_div_level = 'Division '.$row['team2_division'].' – '.$row['team2_level'];
                                            ?>
                                                <a href="match?id=<?= $row['id'] ?>" class="text-decoration-none text-dark mb-3 d-block match-card">
                                                    <div class="d-flex justify-content-between align-items-center position-relative">
                                                        <div class="text-center flex-fill d-flex flex-column align-items-center">
                                                            <img src="uploads/logo/<?= htmlspecialchars($row['team1_logo']) ?>" style="height:40px;">
                                                            <h6 class="mb-0"><?= htmlspecialchars($row['team1']) ?></h6>
                                                            <small class="text-muted"><?= htmlspecialchars($team1_div_level) ?></small>
                                                            <small class="text-muted">Home</small>
                                                        </div>
                                                        <div class="fw-bold mx-3 text-center" style="font-size:1rem;">
                                                            <?= ($score1 !== null && $score2 !== null) ? "$score1 - $score2" : "VS" ?>
                                                        </div>
                                                        <div class="text-center flex-fill d-flex flex-column align-items-center">
                                                            <img src="uploads/logo/<?= htmlspecialchars($row['team2_logo']) ?>" style="height:40px;">
                                                            <h6 class="mb-0"><?= htmlspecialchars($row['team2']) ?></h6>
                                                            <small class="text-muted"><?= htmlspecialchars($team2_div_level) ?></small>
                                                            <small class="text-muted">Away</small>
                                                        </div>
                                                    </div>
                                                    <div class="text-center mt-1">
                                                        <small class="text-muted"><?= $formatted_date ?></small>
                                                    </div>
                                                </a>
                                                <hr>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
    
                <?php endif; ?>
            </div>
        </div>
        <script>
            document.querySelectorAll('.card-header[data-bs-toggle="collapse"]').forEach(header => {
                header.addEventListener('click', function () {
                    const icon = this.querySelector('i');
                    const target = document.querySelector(this.dataset.bsTarget);
                    target.addEventListener('shown.bs.collapse', () => {
                        icon.classList.remove('bi-chevron-down');
                        icon.classList.add('bi-chevron-up');
                    });
                    target.addEventListener('hidden.bs.collapse', () => {
                        icon.classList.remove('bi-chevron-up');
                        icon.classList.add('bi-chevron-down');
                    });
                });
            });
        </script>

        <div class="tab-pane fade" id="teams">
            <div class="row mb-3 g-3">
                <div class="col-md-6">
                    <label for="filterDivision" class="form-label fw-bold">Filter by Division</label>
                    <form method="get" id="divisionForm">
                        <input type="hidden" name="id" value="<?= $tournament_id ?>">
        
                        <?php if ($selected_division === null && !empty($divisions)) {
                            $selected_division = $divisions[0]['id'];
                        } ?>
        
                        <select name="division" id="filterDivision" class="form-select" onchange="this.form.submit()" required>
                            <?php foreach($divisions as $div): ?>
                                <option value="<?= $div['id'] ?>" <?= ($selected_division == $div['id'] ? 'selected' : '') ?>>
                                    <?= $div['id'] . ' – ' . htmlspecialchars($div['division_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Team Name</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        
                        if ($selected_division) {
                            $sql = "
                                SELECT DISTINCT ti.id AS team_id, ti.team_name, ti.logo
                                FROM team_info ti
                                JOIN team_contact_details tcd ON tcd.team_id = ti.id
                                WHERE tcd.division = ?
                                  AND ti.id IN (
                                      SELECT team1_id FROM matches WHERE tournament_id = ?
                                      UNION
                                      SELECT team2_id FROM matches WHERE tournament_id = ?
                                  )
                                ORDER BY ti.team_name ASC
                            ";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("iii", $selected_division, $tournament_id, $tournament_id);
                        } else {
                            $sql = "
                                SELECT DISTINCT ti.id AS team_id, ti.team_name, ti.logo
                                FROM team_info ti
                                WHERE ti.id IN (
                                    SELECT team1_id FROM matches WHERE tournament_id = ?
                                    UNION
                                    SELECT team2_id FROM matches WHERE tournament_id = ?
                                )
                                ORDER BY ti.team_name ASC
                            ";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("ii", $tournament_id, $tournament_id);
                        }
                        
                        $stmt->execute();
                        $res = $stmt->get_result();
                        $no = 1;
                        
                        if ($res->num_rows > 0):
                            while($row = $res->fetch_assoc()):
                                $logo = !empty($row['logo']) ? "../uploads/logo/" . htmlspecialchars($row['logo']) : "../uploads/logo/default.png";
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <a href="team_profile?id=<?= $row['team_id'] ?>" 
                                   class="text-dark text-decoration-none d-flex align-items-center">
                                    <img src="<?= $logo ?>" 
                                         alt="Logo <?= htmlspecialchars($row['team_name']) ?>" 
                                         style="
                                           width: 36px; 
                                           height: 36px; 
                                           object-fit: contain; 
                                           border-radius: 50%; 
                                           background-color: #fff; 
                                           padding: 2px; 
                                           margin-right: 8px; 
                                           box-shadow: 0 0 2px rgba(0,0,0,0.2);
                                         ">

                                    
                                    <?= htmlspecialchars($row['team_name']) ?>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                    ?>
                    <tr>
                        <td colspan="2" class="text-center text-muted">No teams found.</td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


        <div class="tab-pane fade" id="leaderboard">
    <!-- 1) FILTER DIVISION (tetap) -->
    <div class="row mb-3 g-3">
        <div class="col-md-6">
            <label for="filterDivision" class="form-label fw-bold">Filter by Division</label>
            <form method="get" id="divisionForm">
                <input type="hidden" name="id" value="<?= $tournament_id ?>">

                <?php if ($selected_division === null && !empty($divisions)) {
                    $selected_division = $divisions[0]['id'];
                } ?>

                <select name="division" id="filterDivision" class="form-select" onchange="this.form.submit()" required>
                    <?php foreach($divisions as $div): ?>
                        <option value="<?= $div['id'] ?>" <?= ($selected_division == $div['id'] ? 'selected' : '') ?>>
                            <?= $div['id'] . ' – ' . htmlspecialchars($div['division_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

<?php
// ===================== BUILD LEADERBOARD (Journey 1–14) =====================
function buildLeaderboard($conn, $tournament_id, $selected_division = null) {
    // Ambil tim per divisi (kalau ada filter), hanya yang ikut tournament ini
    if (!empty($selected_division)) {
        $sqlTeams = "
            SELECT DISTINCT ti.id AS team_id, ti.team_name
            FROM team_info ti
            JOIN team_contact_details tcd ON tcd.team_id = ti.id AND tcd.division = ?
            WHERE EXISTS (
                SELECT 1 FROM matches m
                WHERE (m.team1_id = ti.id OR m.team2_id = ti.id)
                  AND m.tournament_id = ?
            )
            ORDER BY ti.team_name ASC
        ";
        $stmtTeams = $conn->prepare($sqlTeams);
        $stmtTeams->bind_param("ii", $selected_division, $tournament_id);
    } else {
        $sqlTeams = "
            SELECT DISTINCT ti.id AS team_id, ti.team_name
            FROM team_info ti
            WHERE EXISTS (
                SELECT 1 FROM matches m
                WHERE (m.team1_id = ti.id OR m.team2_id = ti.id)
                  AND m.tournament_id = ?
            )
            ORDER BY ti.team_name ASC
        ";
        $stmtTeams = $conn->prepare($sqlTeams);
        $stmtTeams->bind_param("i", $tournament_id);
    }
    $stmtTeams->execute();
    $resTeams = $stmtTeams->get_result();

    $leaderboard = [];

    // Kueri bantu
    $sqlMatches = "
        SELECT DISTINCT m.id AS match_id
        FROM matches m
        WHERE (m.team1_id = ? OR m.team2_id = ?)
          AND m.tournament_id = ?
          AND m.status = 'completed'
          AND m.notes IS NULL
    ";
    $stmtM = $conn->prepare($sqlMatches);

    $sqlPairs = "SELECT id FROM team_pairs WHERE match_id = ? AND team_id = ?";
    $stmtP = $conn->prepare($sqlPairs);

    $sqlSets  = "SELECT is_winner FROM pair_scores WHERE pair_id = ? AND match_id = ?";
    $stmtS = $conn->prepare($sqlSets);

    while ($t = $resTeams->fetch_assoc()) {
        $team_id = (int)$t['team_id'];

        $matches_played = 0;
        $matches_won    = 0;
        $matches_lost   = 0;
        $pairs_won_total = 0;
        $pairs_lost_total = 0;
        $sets_won_total  = 0;
        $sets_lost_total  = 0;
        $points          = 0;

        // Ambil semua match completed REGULAR (notes IS NULL)
        $stmtM->bind_param("iii", $team_id, $team_id, $tournament_id);
        $stmtM->execute();
        $resM = $stmtM->get_result();

        while ($m = $resM->fetch_assoc()) {
            $match_id = (int)$m['match_id'];

            $pairs_won  = 0;
            $pairs_lost = 0;
            $sets_won   = 0;
            $sets_lost  = 0;
            $has_result = false;

            // Pairs dari tim ini pada match tsb
            $stmtP->bind_param("ii", $match_id, $team_id);
            $stmtP->execute();
            $resP = $stmtP->get_result();

            while ($p = $resP->fetch_assoc()) {
                $pair_id = (int)$p['id'];
                $pw = 0; $pl = 0;

                $stmtS->bind_param("ii", $pair_id, $match_id);
                $stmtS->execute();
                $resS = $stmtS->get_result();

                while ($s = $resS->fetch_assoc()) {
                    $has_result = true;
                    if ((int)$s['is_winner'] === 1) { $pw++; $sets_won++; }
                    else                           { $pl++; $sets_lost++; }
                }

                if ($pw > $pl)      $pairs_won++;
                elseif ($pw < $pl)  $pairs_lost++;
            }

            if ($has_result) {
                $matches_played++;
                // Poin match (3-0 => 3 pts, 2-1 => 2 pts, 1-2 => 1 pt, 0-3 => 0 pt)
                if     ($pairs_won == 3) { $matches_won++;  $points += 3; }
                elseif ($pairs_won == 2 && $pairs_lost == 1) { $matches_won++;  $points += 2; }
                elseif ($pairs_won == 1 && $pairs_lost == 2) { $matches_lost++; $points += 1; }
                elseif ($pairs_won == 0 && $pairs_lost == 3) { $matches_lost++; $points += 0; }

                $pairs_won_total  += $pairs_won;
                $pairs_lost_total += $pairs_lost;
                $sets_won_total   += $sets_won;
                $sets_lost_total  += $sets_lost;
            }
        }

        $leaderboard[] = [
            'team_id'        => $team_id,
            'team_name'      => $t['team_name'],
            'matches_played' => $matches_played,
            'matches_won'    => $matches_won,
            'matches_lost'   => $matches_lost,
            'pairs_won'      => $pairs_won_total,
            'pairs_lost'     => $pairs_lost_total,
            'sets_won'       => $sets_won_total,
            'sets_lost'      => $sets_lost_total,
            'points'         => $points,
        ];
    }

    // Head-to-head tie-break + set/pair diff
    $__H2H_SQL = "
        SELECT t.team_id, SUM(t.pair_won) AS pairs_won
        FROM (
            SELECT tp.team_id, tp.id AS pair_id,
                   CASE WHEN SUM(ps.is_winner) > COUNT(*)/2 THEN 1 ELSE 0 END AS pair_won
            FROM matches m
            JOIN team_pairs tp ON tp.match_id = m.id
            JOIN pair_scores ps ON ps.match_id = m.id AND ps.pair_id = tp.id
            WHERE m.tournament_id = ?
              AND m.status = 'completed'
              AND m.notes IS NULL
              AND ( (m.team1_id = ? AND m.team2_id = ?) OR (m.team1_id = ? AND m.team2_id = ?) )
              AND tp.team_id IN (?, ?)
            GROUP BY tp.team_id, tp.id
            HAVING COUNT(*) > 0
        ) AS t
        GROUP BY t.team_id
    ";
    $__H2H_STMT  = $conn->prepare($__H2H_SQL);
    $__H2H_CACHE = [];

    usort($leaderboard, function($a, $b) use ($tournament_id, $__H2H_STMT, &$__H2H_CACHE) {
        // 1) Points
        if ($b['points'] !== $a['points']) return $b['points'] - $a['points'];

        // 2) H2H by pairs won
        $A = (int)$a['team_id']; $B = (int)$b['team_id'];
        $k = $A.'-'.$B;
        if (!isset($__H2H_CACHE[$k])) {
            $__H2H_STMT->bind_param("iiiiiii", $tournament_id, $A, $B, $B, $A, $A, $B);
            $__H2H_STMT->execute();
            $res = $__H2H_STMT->get_result();
            $pw = [$A=>0, $B=>0];
            while ($row = $res->fetch_assoc()) $pw[(int)$row['team_id']] = (int)$row['pairs_won'];
            $diff = $pw[$A] - $pw[$B];
            $__H2H_CACHE[$A.'-'.$B] = $diff;
            $__H2H_CACHE[$B.'-'.$A] = -$diff;
        }
        $h2h = $__H2H_CACHE[$k];
        if ($h2h !== 0) return ($h2h > 0) ? -1 : 1;

        // 3) Set diff
        $sdA = $a['sets_won'] - $a['sets_lost'];
        $sdB = $b['sets_won'] - $b['sets_lost'];
        if ($sdB !== $sdA) return $sdB - $sdA;

        // 4) Pair diff
        $pdA = $a['pairs_won'] - $a['pairs_lost'];
        $pdB = $b['pairs_won'] - $b['pairs_lost'];
        if ($pdB !== $pdA) return $pdB - $pdA;

        // 5) Set won
        if ($b['sets_won'] !== $a['sets_won']) return $b['sets_won'] - $a['sets_won'];

        // 6) Pair won
        if ($b['pairs_won'] !== $a['pairs_won']) return $b['pairs_won'] - $a['pairs_won'];

        // 7) Name
        return strcasecmp($a['team_name'], $b['team_name']);
    });

    return $leaderboard;
}


// ===== Panggil builder, lalu ambil champion =====
$leaderboard = buildLeaderboard($conn, $tournament_id, $selected_division);

// Map posisi kalau perlu dipakai di tempat lain (Semi/Final label)
$teamPosition = [];
$__posCounter = 1;
foreach ($leaderboard as $row) {
    $teamPosition[(int)$row['team_id']] = $__posCounter++;
}

// ==== League Champion Detection ====
// Ambil champion dari leaderboard
$championTeamId   = $leaderboard[0]['team_id']   ?? null;
$championTeamName = $leaderboard[0]['team_name'] ?? 'TBD';

// Ambil logo champion
if ($championTeamId) {
    $stmtLogo = $conn->prepare("SELECT logo FROM team_info WHERE id = ? LIMIT 1");
    $stmtLogo->bind_param("i", $championTeamId);
    $stmtLogo->execute();
    $logoRow = $stmtLogo->get_result()->fetch_assoc();
    $championLogo = !empty($logoRow['logo']) ? "../uploads/logo/".$logoRow['logo'] : "../uploads/logo/default.png";
} else {
    $championLogo = "../uploads/logo/default.png";
}

$championTeamNameEsc = htmlspecialchars($championTeamName, ENT_QUOTES, 'UTF-8');
$championLogoEsc     = htmlspecialchars($championLogo, ENT_QUOTES, 'UTF-8');

// ==== GET TOURNAMENT ID DARI URL ====
$tournament_id = $_GET['id'] ?? null;

// ==== CEK MATCH BELUM SELESAI UNTUK REGULAR SEASON (NOTES NULL) ====
$journeyCheck = $conn->prepare("
    SELECT COUNT(*) AS not_done
    FROM matches m
    JOIN team_contact_details t1 ON t1.team_id = m.team1_id
    JOIN team_contact_details t2 ON t2.team_id = m.team2_id
    WHERE m.tournament_id = ?
      AND t1.division = ?
      AND t2.division = ?
      AND (m.notes IS NULL OR TRIM(m.notes) = '')
      AND (m.status IS NULL OR LOWER(m.status) <> 'completed')
");
$journeyCheck->bind_param("iii", $tournament_id, $selected_division, $selected_division);
$journeyCheck->execute();
$notDone = $journeyCheck->get_result()->fetch_assoc()['not_done'] ?? 0;

$allJourneysDone = ($notDone == 0);

?>

<?php if ($allJourneysDone && !empty($selected_division) && count($leaderboard) > 1): ?>

<style>
.champion-pulse { animation: pulseZoom 3s ease-in-out infinite; transition: transform 0.3s; }
@keyframes pulseZoom { 0%{transform:scale(1);} 50%{transform:scale(1.03);} 100%{transform:scale(1);} }
/* shake keyframes dibutuhkan untuk efek klik (sudah dipakai di JS confetti) */
@keyframes shake {
  0%,100%{transform:translateX(0);}
  20%{transform:translateX(-3px);}
  40%{transform:translateX(3px);}
  60%{transform:translateX(-3px);}
  80%{transform:translateX(3px);}
}
</style>

<!-- 2) CHAMPION BOX (muncul hanya jika allJourneysDone) -->
<div id="leagueChampionBox" class="text-center p-5 mb-4 champion-pulse" style="
    background:#111;
    border:1px solid #d4af37;
    border-radius:14px;
    position:relative;
    cursor:pointer;
    box-shadow:0 0 12px rgba(212,175,55,0.3);
">
    <div style="position:absolute; top:15px; right:15px; font-size:1.8rem;">🥇</div>

    <div style="font-size:1.4rem;color:#d4af37;font-weight:700;letter-spacing:1px;">
        League Champion
    </div>

    <img src="<?= $championLogoEsc ?>" alt="<?= $championTeamNameEsc ?>" style="
        width:120px;height:120px;
        border-radius:50%;
        border:3px solid #d4af37;
        margin-top:12px;
        box-shadow:0 0 15px rgba(212,175,55,0.5);
    ">

    <h2 class="mt-3" style="
        text-transform:uppercase;
        font-weight:800;
        background:linear-gradient(to right,#d4af37,#f9e76d);
        -webkit-background-clip:text;
        -webkit-text-fill-color:transparent;
    ">
        <?= $championTeamNameEsc ?>
    </h2>
</div>
<?php endif; ?>

<!-- Confetti Library -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
// Pasang event listener hanya jika elemen ada (hindari error saat box disembunyikan)
const champBox = document.getElementById('leagueChampionBox');
if (champBox) {
    champBox.addEventListener('click', function() {
        const box = this;

        // Tambah efek shake
        box.style.animation = "shake 0.4s";
        setTimeout(() => { box.style.animation = "pulseZoom 3s ease-in-out infinite"; }, 400);

        // Confetti Mewah Kombinasi
        confetti({ particleCount: 200, spread: 80, origin: { y: 0.6 }, scalar: 1.2, colors: ['#d4af37','#ffffff','#f7d766'] });
        confetti({ particleCount: 80, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#f7d766','#d4af37'] });
        confetti({ particleCount: 80, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#f7d766','#d4af37'] });

        // Munculkan Emoji Terompet Sebentar
        let trumpet = document.createElement("div");
        trumpet.innerHTML = "🎺";
        trumpet.style.position = "absolute";
        trumpet.style.fontSize = "2.5rem";
        trumpet.style.top = "50%";
        trumpet.style.left = "50%";
        trumpet.style.transform = "translate(-50%,-50%) scale(1.2)";
        trumpet.style.opacity = "1";
        trumpet.style.transition = "opacity 0.8s ease-out, transform 0.8s ease-out";
        box.appendChild(trumpet);

        setTimeout(() => {
            trumpet.style.opacity = "0";
            trumpet.style.transform = "translate(-50%,-50%) scale(0.5)";
        }, 200);
        setTimeout(() => {
            trumpet.remove();
        }, 1000);
    });
}
</script>

    <!-- 3) LEADERBOARD CARD (Classification + Table) -->
    <div class="card shadow border-0 mb-4">
        <div class="mb-3 p-3 border rounded bg-light">
            <h5 class="mb-1 fw-bold">Clasification</h5>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-primary px-3">🥇 1st — League Champion</span>
                <span class="badge bg-info text-dark px-3">🥈 2–4 — Playoff Qualifiers</span>
                <span class="badge bg-danger px-3">Last 2 are relegated</span>
            </div>
        </div>

        <div class="table-responsive p-3">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Position</th>
                        <th>Team</th>
                        <th>Matches Played</th>
                        <th>Matches Won</th>
                        <th>Matches Lost</th>
                        <th>Pairs Won</th>
                        <th>Pairs Lost</th>
                        <th>Sets Won</th>
                        <th>Sets Lost</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if (!empty($selected_division)) {
                            $sql_teams = "
                                SELECT DISTINCT ti.id AS team_id, ti.team_name
                                FROM team_info ti
                                JOIN team_contact_details tcd ON tcd.team_id = ti.id
                                WHERE tcd.division = ?
                                  AND ti.id IN (
                                      SELECT team1_id FROM matches WHERE tournament_id = ?
                                      UNION
                                      SELECT team2_id FROM matches WHERE tournament_id = ?
                                  )
                                ORDER BY ti.team_name ASC
                            ";
                            $stmt = $conn->prepare($sql_teams);
                            $stmt->bind_param("iii", $selected_division, $tournament_id, $tournament_id);
                        } else {
                            $sql_teams = "
                                SELECT DISTINCT ti.id AS team_id, ti.team_name
                                FROM team_info ti
                                WHERE ti.id IN (
                                    SELECT team1_id FROM matches WHERE tournament_id = ?
                                    UNION
                                    SELECT team2_id FROM matches WHERE tournament_id = ?
                                )
                                ORDER BY ti.team_name ASC
                            ";
                            $stmt = $conn->prepare($sql_teams);
                            $stmt->bind_param("ii", $tournament_id, $tournament_id);
                        }

                        $stmt->execute();
                        $res_teams = $stmt->get_result();

                        $leaderboard = [];

                        while ($team = $res_teams->fetch_assoc()) {
                            $team_id = $team['team_id'];

                            $sql_matches = "
    SELECT m.id AS match_id
    FROM matches m
    WHERE (m.team1_id = ? OR m.team2_id = ?)
      AND m.tournament_id = ?
      AND m.status = 'completed'
      AND (
          m.notes IS NULL
          OR (
              m.notes NOT LIKE '%Semi Final%'
              AND m.notes NOT LIKE '%Final%'
          )
      )
";

                            $stmt_matches = $conn->prepare($sql_matches);
                            $stmt_matches->bind_param("iii", $team_id, $team_id, $tournament_id);
                            $stmt_matches->execute();
                            $res_matches = $stmt_matches->get_result();

                            $matches_played = 0;
                            $matches_won    = 0;
                            $matches_lost   = 0;
                            $pairs_won_total = 0;
                            $pairs_lost_total = 0;
                            $sets_won_total  = 0;
                            $sets_lost_total = 0;
                            $points = 0;

                            while ($match = $res_matches->fetch_assoc()) {
                                $match_id = $match['match_id'];

                                $sql_pairs = "
                                    SELECT id AS pair_id FROM team_pairs 
                                    WHERE match_id = ? AND team_id = ?
                                ";
                                $stmt_pairs = $conn->prepare($sql_pairs);
                                $stmt_pairs->bind_param("ii", $match_id, $team_id);
                                $stmt_pairs->execute();
                                $res_pairs = $stmt_pairs->get_result();

                                $pairs_won = 0;
                                $pairs_lost = 0;
                                $sets_won = 0;
                                $sets_lost = 0;
                                $has_result = false;

                                while ($pair = $res_pairs->fetch_assoc()) {
                                    $pair_id = $pair['pair_id'];

                                    $sql_sets = "
                                        SELECT is_winner FROM pair_scores 
                                        WHERE pair_id = ? AND match_id = ?
                                    ";
                                    $stmt_sets = $conn->prepare($sql_sets);
                                    $stmt_sets->bind_param("ii", $pair_id, $match_id);
                                    $stmt_sets->execute();
                                    $res_sets = $stmt_sets->get_result();

                                    $pair_sets_won = 0;
                                    $pair_sets_lost = 0;

                                    while ($set = $res_sets->fetch_assoc()) {
                                        $has_result = true;
                                        if ($set['is_winner']) {
                                            $pair_sets_won++;
                                            $sets_won++;
                                        } else {
                                            $pair_sets_lost++;
                                            $sets_lost++;
                                        }
                                    }

                                    if ($pair_sets_won > $pair_sets_lost) {
                                        $pairs_won++;
                                    } elseif ($pair_sets_won < $pair_sets_lost) {
                                        $pairs_lost++;
                                    }
                                }

                                if ($has_result) {
                                    $matches_played++;
                                    if ($pairs_won == 3) { $matches_won++; $points += 3; }
                                    elseif ($pairs_won == 2 && $pairs_lost == 1) { $matches_won++; $points += 2; }
                                    elseif ($pairs_won == 1 && $pairs_lost == 2) { $matches_lost++; $points += 1; }
                                    elseif ($pairs_won == 0 && $pairs_lost == 3) { $matches_lost++; $points += 0; }

                                    $pairs_won_total += $pairs_won;
                                    $pairs_lost_total += $pairs_lost;
                                    $sets_won_total  += $sets_won;
                                    $sets_lost_total += $sets_lost;
                                }
                            }

                            $leaderboard[] = [
                                'team_id'        => $team_id,
                                'team_name'      => $team['team_name'],
                                'matches_played' => $matches_played,
                                'matches_won'    => $matches_won,
                                'matches_lost'   => $matches_lost,
                                'pairs_won'      => $pairs_won_total,
                                'pairs_lost'     => $pairs_lost_total,
                                'sets_won'       => $sets_won_total,
                                'sets_lost'      => $sets_lost_total,
                                'points'         => $points
                            ];
                        }

                        $__H2H_SQL = "
    SELECT t.team_id, SUM(t.pair_won) AS pairs_won
    FROM (
        SELECT
            tp.team_id,
            tp.id AS pair_id,
            CASE WHEN SUM(ps.is_winner) > COUNT(*)/2 THEN 1 ELSE 0 END AS pair_won
        FROM matches m
        JOIN team_pairs tp
          ON tp.match_id = m.id
        JOIN pair_scores ps
          ON ps.match_id = m.id AND ps.pair_id = tp.id
        WHERE m.tournament_id = ?
          AND m.status = 'completed'
          AND (
              m.notes IS NULL
              OR (
                  m.notes NOT LIKE '%Semi Final%'
                  AND m.notes NOT LIKE '%Final%'
              )
          )
          AND (
               (m.team1_id = ? AND m.team2_id = ?)
            OR (m.team1_id = ? AND m.team2_id = ?)
          )
          AND tp.team_id IN (?, ?)
        GROUP BY tp.team_id, tp.id
        HAVING COUNT(*) > 0
    ) AS t
    GROUP BY t.team_id
";

                        $__H2H_STMT  = $conn->prepare($__H2H_SQL);
                        $__H2H_CACHE = []; // key: "A-B" dan "B-A"
                       
                        usort($leaderboard, function($a, $b) use ($tournament_id, $__H2H_STMT, &$__H2H_CACHE) {
                            if ($b['points'] !== $a['points']) {
                                return $b['points'] - $a['points'];
                            }
                        
                            $A  = (int)$a['team_id'];
                            $B  = (int)$b['team_id'];
                            $k1 = $A.'-'.$B;   
                            if (!isset($__H2H_CACHE[$k1])) {
                                $__H2H_STMT->bind_param("iiiiiii", $tournament_id, $A, $B, $B, $A, $A, $B);
                                $__H2H_STMT->execute();
                                $res = $__H2H_STMT->get_result();
                        
                                $pw = [$A => 0, $B => 0];
                                while ($row = $res->fetch_assoc()) {
                                    $pw[(int)$row['team_id']] = (int)$row['pairs_won'];
                                }
                                $diff = $pw[$A] - $pw[$B];           // >0 A unggul, <0 B unggul, 0 imbang
                                $__H2H_CACHE[$A.'-'.$B] = $diff;
                                $__H2H_CACHE[$B.'-'.$A] = -$diff;
                            }
                            $h2h = $__H2H_CACHE[$k1];
                            if ($h2h !== 0) {
                                return ($h2h > 0) ? -1 : 1;
                            }
                        
                            $sdA = $a['sets_won'] - $a['sets_lost'];
                            $sdB = $b['sets_won'] - $b['sets_lost'];
                            if ($sdB !== $sdA) return $sdB - $sdA;
                        
                            $pdA = $a['pairs_won'] - $a['pairs_lost'];
                            $pdB = $b['pairs_won'] - $b['pairs_lost'];
                            if ($pdB !== $pdA) return $pdB - $pdA;
                        
                            if ($b['sets_won'] !== $a['sets_won']) return $b['sets_won'] - $a['sets_won'];
                        
                            if ($b['pairs_won'] !== $a['pairs_won']) return $b['pairs_won'] - $a['pairs_won'];
                        
                            return strcasecmp($a['team_name'], $b['team_name']);
                        });

                        // Setelah leaderboard sudah diurutkan
                        $teamPosition = [];
                        $posCounter = 1;
                        foreach ($leaderboard as $row) {
                            $teamPosition[(int)$row['team_id']] = $posCounter;
                            $posCounter++;
                        }

                        $pos = 1;
                        $total_teams = count($leaderboard);
                        foreach ($leaderboard as $row):
                        $row_class = ""; // default
                    
                        if ($pos == 1) {
                            $row_class = "table-primary fw-bold"; // biru tua (Bootstrap)
                        } elseif ($pos >= 2 && $pos <= 4) {
                            $row_class = "table-info"; // biru muda (Bootstrap)
                        }
                        elseif ($pos > $total_teams - 2) { $row_class = "table-danger"; }
                    ?>
                    <tr class="<?= $row_class ?>">
                        <td><?= $pos++ ?></td>
                        <td><?= htmlspecialchars($row['team_name']) ?></td>
                        <td><?= $row['matches_played'] ?></td>
                        <td><?= $row['matches_won'] ?></td>
                        <td><?= $row['matches_lost'] ?></td>
                        <td><?= $row['pairs_won'] ?></td>
                        <td><?= $row['pairs_lost'] ?></td>
                        <td><?= $row['sets_won'] ?></td>
                        <td><?= $row['sets_lost'] ?></td>
                        <td><?= $row['points'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



        <div class="tab-pane fade" id="ranking">
    <h4 class="fw-bold mb-4">Player Ranking</h4>

    <div class="position-relative mb-4" style="max-width:600px;">
        <input 
            type="text" 
            id="searchPlayer" 
            class="form-control border-0 ps-5 py-2 rounded-pill" 
            placeholder="Search player name..."
            style="box-shadow: 0 4px 12px rgba(0,0,0,0.15);"
        >
        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted fs-5"></i>
    </div>

    <div id="playerList" class="list-group">
        <?php
            $sql = "
                SELECT 
                    tmi.player_name,
                    tmi.profile,
                    tmi.point AS points,
                    ti.team_name,
                    ti.logo
                FROM team_members_info tmi
                JOIN team_info ti ON tmi.team_id = ti.id
                JOIN team_contact_details tcd ON tcd.team_id = ti.id
                WHERE ti.tournament_id = ?
                  AND tcd.division IS NOT NULL
                ORDER BY tmi.point DESC, tmi.player_name ASC
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $tournament_id);
            $stmt->execute();
            $res = $stmt->get_result();

            $rank = 1;
            while ($row = $res->fetch_assoc()):
                $profile = !empty($row['profile']) 
                    ? "uploads/profile/" . $row['profile'] 
                    : "uploads/profile/default.png";
                $logo = !empty($row['logo']) 
                    ? "uploads/logo/" . $row['logo'] 
                    : "uploads/logo/default.png";
        ?>
            <div class="player-item list-group-item d-flex align-items-center mb-3 shadow-sm p-3 rounded">
                <div class="me-3 text-center" style="width:50px;">
                    <span class="fw-bold fs-5"><?= $rank++ ?></span>
                </div>
                <div class="d-flex align-items-center flex-grow-1">
                    <img src="<?= htmlspecialchars($profile) ?>" alt="Profile" class="rounded-circle me-3" style="width:80px;height:80px;object-fit:cover;">
                    <div class="player-data">
                        <div class="fw-bold fs-6"><?= htmlspecialchars($row['player_name']) ?></div>
                        <div class="d-flex align-items-center mt-1">
                            <img src="<?= htmlspecialchars($logo) ?>" alt="Team Logo" class="me-2" style="width:24px;height:24px;object-fit:contain;">
                            <span class="text-muted"><?= htmlspecialchars($row['team_name']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="ms-auto text-end">
                    <div class="fw-bold fs-6"><?= (int)$row['points'] ?> pts</div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>


        
<div class="tab-pane fade" id="playoff">
           <?php
// --- Pastikan variabel $conn, $tournament_id, $selected_division sudah tersedia sebelum blok ini ---

// STEP 1: Ambil semua playoff match (journey >= 15)
$sqlPlayoff = "
    SELECT m.*, 
           t1.team_name AS team1_name, t2.team_name AS team2_name,
           t1.logo AS team1_logo, t2.logo AS team2_logo
    FROM matches m
    LEFT JOIN team_info t1 ON m.team1_id = t1.id
    LEFT JOIN team_info t2 ON m.team2_id = t2.id
    JOIN team_contact_details d1 ON d1.team_id = t1.id
    WHERE m.tournament_id = ? 
      AND d1.division = ? 
      AND m.notes IN ('semi final 1', 'semi final 2', 'final 1', 'final 2')
    ORDER BY FIELD(m.notes, 'semi final 1','semi final 2','final 1','final 2')
";
$stmtP = $conn->prepare($sqlPlayoff);
$stmtP->bind_param("ii", $tournament_id, $selected_division);
$stmtP->execute();
$resP = $stmtP->get_result();

$semi1 = $semi2 = $final1 = $final2 = null;
$winnerPlayoff = [
    "team_name" => "Promoted Team",
    "team_logo" => "../uploads/logo/default.png"
];

// STEP 2: Pisahkan berdasarkan journey
while ($m = $resP->fetch_assoc()) {
    $n = strtolower(trim($m['notes']));
    if ($n === 'semi final 1') $semi1 = $m;
    elseif ($n === 'semi final 2') $semi2 = $m;
    elseif ($n === 'final 1') $final1 = $m;
    elseif ($n === 'final 2') $final2 = $m;
}

function getMatchScoreVisual($conn, $match_id, $team1_id, $team2_id) {
    $score1 = 0; $score2 = 0;
    $stmtP = $conn->prepare("SELECT id FROM team_pairs WHERE match_id = ? AND team_id = ?");
    $stmtS = $conn->prepare("SELECT is_winner FROM pair_scores WHERE match_id = ? AND pair_id = ?");

    // TEAM 1
    $stmtP->bind_param("ii", $match_id, $team1_id);
    $stmtP->execute(); $resPairs = $stmtP->get_result();
    while ($pair = $resPairs->fetch_assoc()) {
        $pid = (int)$pair['id']; $win = 0; $lose = 0;
        $stmtS->bind_param("ii", $match_id, $pid); $stmtS->execute(); $resSets = $stmtS->get_result();
        while ($s = $resSets->fetch_assoc()) {
            if ($s['is_winner'] == 1) $win++; else $lose++;
        }
        if ($win > $lose) $score1++;
    }

    // TEAM 2
    $stmtP->bind_param("ii", $match_id, $team2_id);
    $stmtP->execute(); $resPairs = $stmtP->get_result();
    while ($pair = $resPairs->fetch_assoc()) {
        $pid = (int)$pair['id']; $win = 0; $lose = 0;
        $stmtS->bind_param("ii", $match_id, $pid); $stmtS->execute(); $resSets = $stmtS->get_result();
        while ($s = $resSets->fetch_assoc()) {
            if ($s['is_winner'] == 1) $win++; else $lose++;
        }
        if ($win > $lose) $score2++;
    }

    $stmtS->close(); 
    $stmtP->close();
    return "$score1 - $score2";
}

// STEP 4: Tentukan pemenang playoff (jika final leg 1 & 2 selesai)
if ($final1 && $final2 && $final1['status'] == 'completed' && $final2['status'] == 'completed') {
    $score1 = getMatchScoreVisual($conn, $final1['id'], $final1['team1_id'], $final1['team2_id']);
    $score2 = getMatchScoreVisual($conn, $final2['id'], $final2['team1_id'], $final2['team2_id']);
    $s1 = array_map('trim', explode('-', $score1));
    $s2 = array_map('trim', explode('-', $score2));

    $agg1 = intval($s1[0]) + intval($s2[1]);
    $agg2 = intval($s1[1]) + intval($s2[0]);

    if ($agg1 > $agg2) {
        $winnerPlayoff['team_name'] = $final1['team1_name'];
        $winnerPlayoff['team_logo'] = !empty($final1['team1_logo']) 
            ? "../uploads/logo/" . $final1['team1_logo'] 
            : "../uploads/logo/default.png";
    } else {
        $winnerPlayoff['team_name'] = $final1['team2_name'];
        $winnerPlayoff['team_logo'] = !empty($final1['team2_logo']) 
            ? "../uploads/logo/" . $final1['team2_logo'] 
            : "../uploads/logo/default.png";
    }
}
?>
<?php
// ======================= WINNER PLAYOFF (TOP BOX) =======================

$winnerNameEsc = htmlspecialchars($winnerPlayoff['team_name'], ENT_QUOTES, 'UTF-8');
$winnerLogoEsc = htmlspecialchars($winnerPlayoff['team_logo'], ENT_QUOTES, 'UTF-8');
?>

<style>
@keyframes gentle-zoom {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.03); }
    100% { transform: scale(1); }
}
.winner-box-animated {
    animation: gentle-zoom 3.5s ease-in-out infinite;
}
</style>
 <div class="row mb-3 g-3">
                <div class="col-md-6">
                    <label for="filterDivision" class="form-label fw-bold">Filter by Division</label>
                    <form method="get" id="divisionForm">
                        <input type="hidden" name="id" value="<?= $tournament_id ?>">
        
                        <?php if ($selected_division === null && !empty($divisions)) {
                            $selected_division = $divisions[0]['id'];
                        } ?>
        
                        <select name="division" id="filterDivision" class="form-select" onchange="this.form.submit()" required>
                            <?php foreach($divisions as $div): ?>
                                <option value="<?= $div['id'] ?>" <?= ($selected_division == $div['id'] ? 'selected' : '') ?>>
                                    <?= $div['id'] . ' – ' . htmlspecialchars($div['division_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
<div class="text-center p-5 mb-4 winner-box-animated" style="
    background:#222;
    border:1px solid #555;
    border-radius:12px;
    position:relative;
">
    <!-- Silver Medal Icon Top Right -->
    <div style="
        position:absolute;
        top:15px; right:15px;
        font-size:1.8rem;
    ">🥈</div>

    <div style="font-size:1.4rem;color:#ccc;font-weight:600;letter-spacing:1px;">
        WINNER PLAYOFF
    </div>

    <img src="<?= $winnerLogoEsc ?>" alt="<?= $winnerNameEsc ?>" style="
        width:120px;height:120px;
        border-radius:50%;
        border:3px solid silver;
        margin-top:12px;
        box-shadow:0 0 10px rgba(200,200,200,0.4);
    ">

    <h2 class="mt-3" style="
        text-transform:uppercase;
        font-weight:700;
        background: linear-gradient(to right, silver, #e0e0e0);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    "><?= $winnerNameEsc ?></h2>
</div>


<hr class="tw-line">

<!-- ======================= FINAL ======================= -->
<h4 class="fw-bold text-warning mb-3">🏆 Final</h4>

<?php 
$finalLegs = [$final1, $final2]; // Kita iterasi 2 leg tanpa label
foreach ($finalLegs as $fi): 
?>
    <?php if (!$fi): ?>
        <div class="final-card p-4 text-center">
            <div class="d-flex justify-content-center align-items-center mb-2">
                <strong class="team-name me-2">Semifinal Winner</strong>
                <span class="vs mx-2">vs</span>
                <strong class="team-name ms-2">Second Place</strong>
            </div>
            <div class="score-box bg-white text-dark fw-bold d-inline-block px-3 py-1 rounded">0 - 0</div>
            <div class="mt-2"><small class="text-light">📅 TBD</small></div>
        </div>
        <?php continue; ?>
    <?php endif; ?>

    <?php
        $matchUrl = "match?id=" . (int)$fi['id'];

        if ($fi['status'] == 'completed') {
            $scoreContent = getMatchScoreVisual($conn, (int)$fi['id'], (int)$fi['team1_id'], (int)$fi['team2_id']);
        } else {
            $scoreContent = "<div class='bg-white text-dark fw-bold px-3 py-1 rounded'>0 - 0</div>";
        }

        $dateText = !empty($fi['scheduled_date']) 
            ? date('l, d M Y — H:i', strtotime($fi['scheduled_date'])) 
            : 'TBD';

        $logo1 = !empty($fi['team1_logo']) ? "../uploads/logo/".$fi['team1_logo'] : "../uploads/logo/default.png";
        $logo2 = !empty($fi['team2_logo']) ? "../uploads/logo/".$fi['team2_logo'] : "../uploads/logo/default.png";
    ?>

    <a href="<?= $matchUrl ?>" class="text-decoration-none">
        <div class="final-card p-4 text-center">
            <div class="d-flex justify-content-center align-items-center mb-2">
                <img src="<?= $logo1 ?>" class="team-logo me-2">
                <span class="team-name fs-5"><?= htmlspecialchars($fi['team1_name']) ?></span>
                <span class="vs mx-3">vs</span>
                <span class="team-name fs-5 me-2"><?= htmlspecialchars($fi['team2_name']) ?></span>
                <img src="<?= $logo2 ?>" class="team-logo ms-2">
            </div>

            <div class="score-box"><?= $scoreContent ?></div>

            <div class="mt-2"><small class="text-light">📅 <?= $dateText ?></small></div>
        </div>
    </a>

<?php endforeach; ?>

<style>
.final-card {
    background: linear-gradient(145deg, #121212, #1d1d1d);
    border: 1px solid rgba(255,215,0,0.3);
    border-radius: 18px;
    box-shadow: 0 4px 25px rgba(0,0,0,0.6), inset 0 0 20px rgba(255,215,0,0.05);
    transition: transform 0.4s ease, box-shadow 0.4s ease;
    position: relative;
    overflow: hidden;
    margin-bottom: 18px;
}
.final-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 30px rgba(255,215,0,0.2);
}

.team-logo {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    border: 3px solid #d4af37;
    object-fit: cover;
}

.bg-gradient-gold {
    background: linear-gradient(45deg, #f9e38a, #d4af37);
    color: #1a1a1a !important;
}

.team-name {
    color: #ffffff;
    font-weight: 600;
    text-shadow: 0 0 6px rgba(255,255,255,0.3);
}

.score-box {
    background: rgba(0,0,0,0.75);
    padding: 10px 22px;
    border-radius: 10px;
    font-weight: bold;
    font-size: 1.4rem;
    color: #ffd700;
    border: 1px solid rgba(255,215,0,0.4);
    text-shadow: 0 0 8px rgba(255,215,0,0.7);
    box-shadow: inset 0 0 10px rgba(255,215,0,0.2);
}

.vs {
    color: #bbb;
    font-weight: 300;
    margin: 0 5px;
}

a { color: inherit; }     
a:hover { text-decoration: none; }
</style>


<hr class="tw-line">

<!-- ======================= SEMI FINAL ======================= -->
<h4 class="fw-bold text-secondary mb-3">Semi Final</h4>

<?php 
$semiLegs = [$semi1, $semi2]; // Hilangkan Leg Label
foreach ($semiLegs as $index => $sf): 
?>
    <?php
        // ====== Placeholder jika match belum ada ======
        if (!$sf) {
            $teamA = ($index === 0) ? "Third Place" : "Fourth Place";
            $teamB = ($index === 0) ? "Fourth Place" : "Third Place";

            echo "
            <div class='semi-card p-4 text-center'>
                <div class='d-flex justify-content-center align-items-center mb-2'>
                    <strong class='team-name me-2'>$teamA</strong>
                    <span class='vs mx-2'>vs</span>
                    <strong class='team-name ms-2'>$teamB</strong>
                </div>
                <div class='score-box bg-white text-dark fw-bold d-inline-block px-3 py-1 rounded'>0 - 0</div>
                <div class='mt-2'><small class='text-light'>📅 Match not generated</small></div>
            </div>";
            continue;
        }

        $matchUrl = "match?id=" . (int)$sf['id'];
        $score = ($sf['status']=='completed')
            ? getMatchScoreVisual($conn, (int)$sf['id'], (int)$sf['team1_id'], (int)$sf['team2_id'])
            : "<div class='bg-white text-dark fw-bold px-3 py-1 rounded'>0 - 0</div>";

        $date = !empty($sf['scheduled_date']) 
            ? date('l, d M Y — H:i', strtotime($sf['scheduled_date'])) 
            : 'TBD';

        $logo1 = !empty($sf['team1_logo']) ? "../uploads/logo/".$sf['team1_logo'] : "../uploads/logo/default.png";
        $logo2 = !empty($sf['team2_logo']) ? "../uploads/logo/".$sf['team2_logo'] : "../uploads/logo/default.png";
    ?>

    <a href="<?= $matchUrl ?>" class="text-decoration-none">
        <div class="semi-card p-4 text-center">
            <div class="d-flex justify-content-center align-items-center mb-2">
                <img src="<?= $logo1 ?>" class="team-logo me-2">
                <span class="team-name fs-5">
                    <?= htmlspecialchars($sf['team1_name']) ?> 
                    <span class="pos">(Pos <?= $teamPosition[$sf['team1_id']] ?? 'TBD' ?>)</span>
                </span>
                <span class="vs mx-3">vs</span>
                <span class="team-name fs-5 me-2">
                    <?= htmlspecialchars($sf['team2_name']) ?> 
                    <span class="pos">(Pos <?= $teamPosition[$sf['team2_id']] ?? 'TBD' ?>)</span>
                </span>
                <img src="<?= $logo2 ?>" class="team-logo ms-2">
            </div>

            <div class="score-box"><?= $score ?></div>

            <div class="mt-2"><small class="text-light">📅 <?= $date ?></small></div>
        </div>
    </a>

<?php endforeach; ?>


<style>
.semi-card {
    background: linear-gradient(145deg, #1a1a1a, #262626);
    border: 1px solid rgba(200,200,200,0.3);
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5), inset 0 0 20px rgba(255,255,255,0.05);
    transition: transform 0.4s ease, box-shadow 0.4s ease;
    margin-bottom: 18px;
}
.semi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 30px rgba(200,200,200,0.2);
}

.bg-gradient-silver {
    background: linear-gradient(45deg, #d9d9d9, #bfbfbf);
    color: #1a1a1a !important;
}

.score-silver {
    color: #e0e0e0;
    border-color: rgba(200,200,200,0.4);
    text-shadow: 0 0 8px rgba(200,200,200,0.7);
}

.pos {
    font-size: 0.8rem;
    color: #bfbfbf;
    font-weight: normal;
}
</style>


</div>




        <!-- Search Filter Script -->
        <script>
            document.addEventListener("input", function(e) {
                if (e.target.id === "searchPlayer") {
                    const filter = e.target.value.toLowerCase();
                    document.querySelectorAll(".player-item").forEach(item => {
                        const content = item.innerText.toLowerCase();
                        item.classList.toggle("d-none", !content.includes(filter));
                    });
                }
            });
        </script>
    </div>
</section>


<?php require 'src/footer.php' ?>

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


<script>
  document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('maiavbar');
    const hero = document.getElementById('nav-tab'); // Pastikan ada elemen heroCarousel di halaman

    function toggleNavbarFixed() {
      if (!hero) return; // kalau heroCarousel gak ada, skip

      const scrollPos = window.scrollY;
      const heroHeight = hero.offsetHeight;

      if (scrollPos >= heroHeight) {
        navbar.classList.add('navbar-fixed');
        document.body.style.paddingTop = navbar.offsetHeight + 'px'; // supaya konten gak tertutup
      } else {
        navbar.classList.remove('navbar-fixed');
        document.body.style.paddingTop = '0';
      }
    }

    window.addEventListener('scroll', toggleNavbarFixed);
    toggleNavbarFixed(); // jalankan sekali saat load
  });
</script>


</body>
</html>
