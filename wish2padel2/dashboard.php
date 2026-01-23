<?php
    session_start();
    require 'config.php';
    require 'validate_team_session.php';
    $conn = getDBConnection();
    
    $username = $_SESSION['username'] ?? null;
    $team_id = $_SESSION['team_id'] ?? null;
    
       if (!$team_id) {
        header("Location: login/login");
        exit();
    }

    
    $t_stmt = $conn->prepare("SELECT tournament_id FROM team_info WHERE id = ?");
    $t_stmt->bind_param("i", $team_id);
    $t_stmt->execute();
    $tournament_id = $t_stmt->get_result()->fetch_assoc()['tournament_id'] ?? 1;
    $t_stmt->close();
    
    $paymentSystem = new SimplePaymentSystem();
    $is_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);
    
    if (!$is_paid) {
        header("Location: regis.php");
        exit();
    }
    
    date_default_timezone_set("Asia/Riyadh");
    $now = new DateTime();
    
    $matchResult = $conn->prepare("SELECT id, scheduled_date FROM matches WHERE team1_id = ? OR team2_id = ?");
    $matchResult->bind_param("ii", $team_id, $team_id);
    $matchResult->execute();
    $matchData = $matchResult->get_result();
    
    while ($match = $matchData->fetch_assoc()) {
        $match_id = $match['id'];
        $matchTime = new DateTime($match['scheduled_date']);
    
        $startWindow  = (clone $matchTime)->modify('-20 minutes');
        $midWindow    = (clone $matchTime)->modify('+40 minutes');
        $resultWindow = (clone $matchTime)->modify('+90 minutes');
    
        if ($now >= $startWindow && $now <= $midWindow) {
            $pairCheck = $conn->prepare("
                SELECT COUNT(*) AS total_players 
                FROM pair_players
                WHERE pair_id IN (
                    SELECT id FROM team_pairs WHERE match_id = ? AND team_id = ?
                )
            ");
            $pairCheck->bind_param("ii", $match_id, $team_id);
            $pairCheck->execute();
            $pairCount = $pairCheck->get_result()->fetch_assoc()['total_players'];
            $pairCheck->close();
    
            if ($pairCount == 0) {
                $_SESSION['current_match_id'] = $match_id;
                header("Location: auth/auth_scheduled.php");
                exit();
            }
        }
    
        if ($now >= $resultWindow) {
            $resStmt = $conn->prepare("
                SELECT status FROM match_results WHERE match_id = ? AND team_id = ?
            ");
            $resStmt->bind_param("ii", $match_id, $team_id);
            $resStmt->execute();
            $res = $resStmt->get_result()->fetch_assoc();
            $resStmt->close();
    
            if (!$res || strtolower($res['status']) !== 'accept') {
                $_SESSION['current_match_id'] = $match_id;
                header("Location: auth/auth_result.php");
                exit();
            }
        }
    }
    
    $server_time_str = $now->format('d M Y, H:i:s');

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>Dashboard - Wish2Padel</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
      <link rel="stylesheet" href="assets/css/stylee.css?=v13">
    </head>
    <style>
        .card-soft { border: 0; border-radius: 16px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
        .leader-item { display:flex; align-items:center; gap:12px; padding:12px; border-radius:12px; border:1px solid #eee; margin-bottom:10px; background:#fff; }
        .leader-item.highlight-top { background: #e7f1ff; }  
        .leader-item.highlight-bottom { background: #ffe7e7; }
        .rank-badge { width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:700; background:#f6f6f6; }
        .team-logo { width:36px; height:36px; object-fit:contain; }
        .stat-pill { font-size:.8rem; padding:.2rem .5rem; border-radius:8px; background:#f7f7f7; margin-left:4px; }
        .player-card { display:flex; align-items:center; gap:12px; padding:10px; border:1px solid #eee; border-radius:12px; background:#fff; margin-bottom:10px; }
        .player-avatar { width:48px; height:48px; border-radius:50%; object-fit:cover; }
        .schedule-item { border:1px solid #eee; background:#fff; border-radius:14px; padding:14px; margin-bottom:12px; }
        .schedule-teams { display:flex; align-items:center; justify-content:space-between; }
        .schedule-team { display:flex; align-items:center; gap:10px; width:40%; }
        .schedule-team h6 { margin:0; font-size:1rem; }
        .schedule-center { width:20%; text-align:center; font-weight:700; }
        .modal.show .modal-dialog {
          margin-top: 200px;
        }
    </style>
    <body style="background-color: #303030">
        
        <?php require 'src/navbar.php' ?>
        
        
        <?php
        
            $showModal = false;
            $todayStr = date('Y-m-d');
            
            if (isset($_SESSION['team_id'])) {
                $team_id = $_SESSION['team_id'];
            
                $res = $conn->query("
                    SELECT t.start_date 
                    FROM tournaments t 
                    JOIN team_info tm ON tm.tournament_id = t.id
                    WHERE tm.id = $team_id 
                    LIMIT 1
                ");
            
                if ($res && $row = $res->fetch_assoc()) {
                    $startDate = new DateTime($row['start_date']);
                    $threeDaysBefore = (clone $startDate)->modify('-3 days');
                    $today = new DateTime();
            
                    if ($today >= $threeDaysBefore && $today < $startDate) {
                        if (!isset($_SESSION['popup_shown_date']) || $_SESSION['popup_shown_date'] !== $todayStr) {
                            $showModal = true;
                            $_SESSION['popup_shown_date'] = $todayStr; 
                        }
                    }
                }
            }
        ?>
      
        <div class="modal fade" id="popupModal" tabindex="-1">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius:15px;">
              
                <div class="modal-header text-white fw-bold" style="background-color:#696969">
                Important Match Preparation Notice
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
        
                <?php
                    $doc_lineup = $conn->query("SELECT file_path FROM documents WHERE id = 1 LIMIT 1")->fetch_assoc();
                    $doc_match  = $conn->query("SELECT file_path FROM documents WHERE id = 2 LIMIT 1")->fetch_assoc();
                    
                    $lineup_file = $doc_lineup['file_path'] ?? null;
                    $match_file  = $doc_match['file_path'] ?? null;
                ?>
        
                <div class="modal-body">
                    <section class="container mt-3">
                      <div class="row g-4">
                        <div class="col-12 col-md-6" id="pairs-section">
                          <div class="card shadow-sm border-0 h-100" style="border-radius:15px;">
                            <div class="card-header text-white fw-bold" style="background-color:#696969">
                              Team Lineup Declaration
                            </div>
                            <div class="card-body">
                              <p>
                                This lineup declaration letter must be printed and filled in by the Team Captain, 
                                then uploaded in the <strong>Lineup Team Form</strong> provided below.  
                                This serves as the physical proof that the players listed are the actual participants.
                              </p>
                              <ul class="mt-3">
                                <li>Warning or written notice.</li>
                                <li>Loss of points in the standings.</li>
                                <li>Match forfeiture (automatic loss).</li>
                                <li>Disqualification or ban from the league for repeated infractions.</li>
                              </ul>
                              <a href="<?= $lineup_file ? '../'.$lineup_file : '#' ?>" 
                                class="btn btn-gold mt-3" <?= $lineup_file ? 'download' : 'disabled' ?>>
                                Download Lineup Template (PDF)
                              </a>
                            </div>
                          </div>
                        </div>
            
                        <div class="col-12 col-md-6">
                          <div class="card shadow-sm border-0 h-100" style="border-radius:15px;">
                            <div class="card-header text-white fw-bold" style="background-color:#696969">
                              Elimination Round Minutes
                            </div>
                            <div class="card-body">
                              <p>
                                The <strong>Elimination Round Minutes Sheet</strong> must be <strong>printed and brought</strong> to the match venue.  
                                All scores must be recorded <strong>accurately</strong>, <strong>signed by both team captains</strong>,  
                                and then uploaded through the <strong>Match Scores Form</strong> provided below.
                              </p>
                              <p class="text-danger fw-bold">
                                ⚠️ Match Score Form will only be accessible <u>90 minutes after match start</u>.
                              </p>
                              <ul class="mt-3">
                                <li>Warning or written notice.</li>
                                <li>Loss of points in the standings.</li>
                                <li>Match forfeiture (automatic loss).</li>
                                <li>Disqualification or ban from the league for repeated infractions.</li>
                              </ul>
                              <a href="<?= $match_file ? '../'.$match_file : '#' ?>" 
                               class="btn btn-gold mt-3" <?= $match_file ? 'download' : 'disabled' ?>>
                               Download Match Result / Score (PDF)
                              </a>
            
                            </div>
                          </div>
                        </div>
            
                      </div>
                    </section>
                </div>
            
            </div>
          </div>
        </div>
            
            <?php
                date_default_timezone_set('Asia/Riyadh');
                
                if (!isset($_SESSION['team_id'])) {
                    die("<div class='alert alert-danger m-4'>Team ID tidak ditemukan. Silakan login kembali.</div>");
                }
                
                $team_id = (int) $_SESSION['team_id'];
               
                $sqlTeam = "
                    SELECT ti.id, ti.team_name, ti.captain_name, ti.logo, ti.tournament_id
                    FROM team_info ti
                    WHERE ti.id = ?
                ";
                $stmt = $conn->prepare($sqlTeam);
                $stmt->bind_param("i", $team_id);
                $stmt->execute();
                $resTeam = $stmt->get_result();
                $team = $resTeam->fetch_assoc();
                
                if (!$team) {
                    die("<div class='alert alert-warning m-4'>Data team tidak ditemukan.</div>");
                }
                
                $tournament_id = (int) $team['tournament_id'];
                
                $sqlDetail = "SELECT division FROM team_contact_details WHERE team_id = ? LIMIT 1";
                $stmt = $conn->prepare($sqlDetail);
                $stmt->bind_param("i", $team_id);
                $stmt->execute();
                $resDetail = $stmt->get_result();
                $rowDetail = $resDetail->fetch_assoc();
                $selected_division = $rowDetail['division'] ?? null;
                
                
                $sqlT = "SELECT id, name, description, start_date FROM tournaments WHERE id = ?";
                $stmt = $conn->prepare($sqlT);
                $stmt->bind_param("i", $tournament_id);
                $stmt->execute();
                $resT = $stmt->get_result();
                $tournament = $resT->fetch_assoc() ?: ['name' => 'Tournament'];
                
                
                $sqlDivisions = "
                    SELECT DISTINCT tcd.division 
                    FROM team_contact_details tcd
                    JOIN team_info ti ON tcd.team_id = ti.id
                    WHERE ti.tournament_id = ?
                ";
                $stmt = $conn->prepare($sqlDivisions);
                $stmt->bind_param("i", $tournament_id);
                $stmt->execute();
                $resDivisions = $stmt->get_result();
                
                $divisions = [];
                while ($row = $resDivisions->fetch_assoc()) {
                    $divisions[] = $row['division'];
                }
                
                if (!empty($selected_division)) {
                
                    $sqlTeams = "
                        SELECT ti.id AS team_id, ti.team_name, ti.logo, tcd.division
                        FROM team_info ti
                        JOIN team_contact_details tcd ON tcd.team_id = ti.id
                        JOIN payment_transactions tt ON tt.team_id = ti.id
                        WHERE tcd.division = ?
                          AND ti.tournament_id = ?
                          AND tt.status = 'paid'
                        ORDER BY ti.team_name ASC
                    ";

                    $stmt = $conn->prepare($sqlTeams);
                    $stmt->bind_param("ii", $selected_division, $tournament_id);
                
                } else {
                
                    $sqlTeams = "
                        SELECT ti.id AS team_id, ti.team_name, ti.logo, tcd.division
                        FROM team_info ti
                        JOIN team_contact_details tcd ON tcd.team_id = ti.id
                        JOIN payment_transactions tt ON tt.team_id = ti.id
                        WHERE ti.tournament_id = ?
                          AND tt.status = 'paid'
                        ORDER BY ti.team_name ASC
                    ";

                    $stmt = $conn->prepare($sqlTeams);
                    $stmt->bind_param("i", $tournament_id);
                }
                
                $stmt->execute();
                $resTeams = $stmt->get_result();
                
                $leaderboard = [];
                
                while ($t = $resTeams->fetch_assoc()) {
                    $tid = (int) $t['team_id'];
                
                $sqlMatches = "
                    SELECT DISTINCT m.id AS match_id
                    FROM matches m
                    JOIN team_pairs tp ON tp.match_id = m.id
                    WHERE tp.team_id = ? 
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

                
                    $stmtM = $conn->prepare($sqlMatches);
                    $stmtM->bind_param("ii", $tid, $tournament_id);
                    $stmtM->execute();
                    $resMatches = $stmtM->get_result();
                
                    $matches_played = 0;
                    $matches_won    = 0;
                    $matches_lost   = 0;
                    $pairs_won_total = 0;
                    $pairs_lost_total = 0;
                    $sets_won_total  = 0;
                    $sets_lost_total = 0;
                    $points = 0;
                
                    while ($m = $resMatches->fetch_assoc()) {
                        $match_id = (int) $m['match_id'];
                
                        // Ambil semua pair tim
                        $sqlPairs = "SELECT id AS pair_id FROM team_pairs WHERE match_id = ? AND team_id = ?";
                        $stmtP = $conn->prepare($sqlPairs);
                        $stmtP->bind_param("ii", $match_id, $tid);
                        $stmtP->execute();
                        $resPairs = $stmtP->get_result();
                
                        $pairs_won = 0;
                        $pairs_lost = 0;
                        $sets_won = 0;
                        $sets_lost = 0;
                        $has_result = false;
                
                        while ($pair = $resPairs->fetch_assoc()) {
                            $pair_id = (int) $pair['pair_id'];
                
                            $sqlSets = "SELECT is_winner FROM pair_scores WHERE pair_id = ? AND match_id = ?";
                            $stmtS = $conn->prepare($sqlSets);
                            $stmtS->bind_param("ii", $pair_id, $match_id);
                            $stmtS->execute();
                            $resSets = $stmtS->get_result();
                
                            $pair_sets_won = 0;
                            $pair_sets_lost = 0;
                
                            while ($set = $resSets->fetch_assoc()) {
                                $has_result = true;
                                if ((int)$set['is_winner'] === 1) {
                                    $pair_sets_won++;
                                    $sets_won++;
                                } else {
                                    $pair_sets_lost++;
                                    $sets_lost++;
                                }
                            }
                
                            if ($pair_sets_won > $pair_sets_lost) $pairs_won++;
                            elseif ($pair_sets_won < $pair_sets_lost) $pairs_lost++;
                        }
                
                        if ($has_result) {
                            $matches_played++;
                
                            // Skema poin
                            if ($pairs_won == 3) {
                                $matches_won++; $points += 3;
                            } elseif ($pairs_won == 2 && $pairs_lost == 1) {
                                $matches_won++; $points += 2;
                            } elseif ($pairs_won == 1 && $pairs_lost == 2) {
                                $matches_lost++; $points += 1;
                            } elseif ($pairs_won == 0 && $pairs_lost == 3) {
                                $matches_lost++; $points += 0;
                            }
                
                            $pairs_won_total += $pairs_won;
                            $pairs_lost_total += $pairs_lost;
                            $sets_won_total  += $sets_won;
                            $sets_lost_total += $sets_lost;
                        }
                    }
                
                    $leaderboard[] = [
                        'team_id'        => $tid,
                        'team_name'      => $t['team_name'],
                        'logo'           => $t['logo'] ?? '',
                        'division'       => $t['division'] ?? '-',
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
                $__H2H_CACHE = []; 
                
                usort($leaderboard, function($a, $b) use ($tournament_id, $__H2H_STMT, &$__H2H_CACHE) {
                    if ($b['points'] !== $a['points']) {
                        return $b['points'] - $a['points'];
                    }
                
                    // Head-to-Head
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
                        $diff = $pw[$A] - $pw[$B];           //
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
                
                    // Pairs won
                    if ($b['pairs_won'] !== $a['pairs_won']) return $b['pairs_won'] - $a['pairs_won'];
                
                    return strcasecmp($a['team_name'], $b['team_name']);
                });
                
                $sqlPlayers = "
                    SELECT 
                        pp.player_name,
                        MAX(tmi.profile) AS profile,
                        MAX(ti.team_name) AS team_name,
                        MAX(ti.logo) AS logo,
                        0 AS points
                    FROM pair_players pp
                    JOIN team_pairs tp ON pp.pair_id = tp.id
                    JOIN team_info ti ON tp.team_id = ti.id
                    LEFT JOIN team_members_info tmi 
                        ON tmi.team_id = ti.id AND tmi.player_name = pp.player_name
                    WHERE ti.tournament_id = ?
                    GROUP BY pp.player_name
                    ORDER BY points DESC, pp.player_name ASC
                ";
                $stmt = $conn->prepare($sqlPlayers);
                $stmt->bind_param("i", $tournament_id);
                $stmt->execute();
                $resPlayers = $stmt->get_result();
                
                $sqlSchedule = "
                    SELECT 
                        m.id, m.journey, m.team1_id, m.team2_id, m.scheduled_date, m.status,
                        t1.team_name AS team1, t1.logo AS team1_logo,
                        t2.team_name AS team2, t2.logo AS team2_logo
                    FROM matches m
                    JOIN team_info t1 ON t1.id = m.team1_id
                    JOIN team_info t2 ON t2.id = m.team2_id
                    WHERE m.tournament_id = ?
                      AND (m.team1_id = ? OR m.team2_id = ?)
                    ORDER BY m.scheduled_date ASC
                ";
                $stmt = $conn->prepare($sqlSchedule);
                $stmt->bind_param("iii", $tournament_id, $team_id, $team_id);
                $stmt->execute();
                $resSchedule = $stmt->get_result();
                
                $server_time_str = date('l, d M Y, H:i:s');
                
                $divisionLabel = '-';
                if (!empty($selected_division)) {
                    $stmtDiv = $conn->prepare("SELECT division_name FROM divisions WHERE id = ?");
                    $stmtDiv->bind_param("i", $selected_division);
                    $stmtDiv->execute();
                    $resDiv = $stmtDiv->get_result()->fetch_assoc();
                    $divisionLabel = $resDiv['division_name'] ?? '-';
                }
                
            ?>
            
            <section class="container py-4">
                <div class="mb-3">
                    <h1 class="fw-bold mb-1" style="color:#f3e6b6">
                        Welcome Captain, <?= htmlspecialchars($team['captain_name']) ?>
                    </h1>
                    <div style="color:#88694A">
                        Server time: <?= htmlspecialchars($server_time_str) ?>
                    </div>
                    <div class="mt-2 small" style="color:#f3e6b6">
                        Zone: <strong><?= htmlspecialchars($tournament['name'] ?? 'Tournament') ?></strong>
                        • Division:
                        <strong>
                            <?= htmlspecialchars(!empty($selected_division) ? $selected_division . " – " . $divisionLabel : '-') ?>
                        </strong>
                    </div>
                </div>
                <!--</div>-->
            
                <div class="row g-3">
                    <div class="col-lg-12">
                        <div class="card card-soft">
                            <div class="card-header bg-dark text-white fw-bold">Leaderboard</div>
                            <div class="card-body">
                                <?php
                                $filtered_leaderboard = array_filter($leaderboard, function($row) {
                                    return isset($row['division']) 
                                        && $row['division'] !== '' 
                                        && $row['division'] !== '-' 
                                        && $row['division'] !== '0' 
                                        && $row['division'] !== 0;
                                });
                
                                if (count($filtered_leaderboard) === 0) {
                                    echo "<div class='alert alert-warning text-center'>
                                            Please wait, divisions will be adjusted first.
                                          </div>";
                                } else {
                                ?>
                                    <div class="table-responsive">
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
                                                    $pos = 1;
                                                    foreach ($filtered_leaderboard as $row):
                                                        $row_class = ""; 
                
                                                        if ($pos == 1) {
                                                            $row_class = "table-primary fw-bold"; // juara
                                                        } elseif ($pos >= 2 && $pos <= 4) {
                                                            $row_class = "table-info"; // playoff contender
                                                        } elseif ($pos > count($filtered_leaderboard) - 2) {
                                                            $row_class = "table-danger"; // zona degradasi
                                                        }
                                                ?>
                                                <tr class="<?= $row_class ?>">
                                                        <td><?= $pos++ ?></td>
                                                        <td>
                                                            <a href="team_profile?id=<?= $row['team_id'] ?>" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                                                                <img class="team-logo"
                                                                 src="<?= htmlspecialchars($row['logo'] ? 'uploads/logo/'.$row['logo'] : 'uploads/logo/default.png') ?>"
                                                                 alt="<?= htmlspecialchars($row['team_name']) ?>"
                                                                 style="width: 50px; height: 50px; object-fit: contain; background-color: #fff; border-radius: 8px; padding: 3px;">
                
                                                                <?= htmlspecialchars($row['team_name']) ?>
                                                            </a>
                                                        </td>
                                                        <td><?= $row['matches_played'] ?></td>
                                                        <td><?= $row['matches_won'] ?></td>
                                                        <td><?= $row['matches_lost'] ?></td>
                                                        <td><?= $row['pairs_won'] ?></td>
                                                        <td><?= $row['pairs_lost'] ?></td>
                                                        <td><?= $row['sets_won'] ?></td>
                                                        <td><?= $row['sets_lost'] ?></td>
                                                        <td class="fw-bold"><?= $row['points'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                
                <?php
                    $currentTeamId = $_SESSION['team_id'] ?? null;
                    
                    if (!$currentTeamId) {
                        echo "<div class='alert alert-danger'>No team session found.</div>";
                        return;
                    }
                    
                    // Cari posisi tim user di leaderboard
                    $userRank = null;
                    $userStats = null;
                    foreach ($leaderboard as $index => $row) {
                        if ($row['team_id'] == $currentTeamId) {
                            $userRank = $index + 1;
                            $userStats = $row;
                            break;
                        }
                    }
                    
                    if (!$userStats) {
                        echo "<div class='alert alert-warning'>Your team is not listed in leaderboard.</div>";
                        return;
                    }
                    
                    $totalTeams = count($leaderboard);
                    $rankSuffix = ($userRank==1?'st':($userRank==2?'nd':($userRank==3?'rd':'th')));
                    $progressPercent = round((($totalTeams - $userRank + 1) / $totalTeams) * 100);
                    $isPlayoff = ($userRank <= 4);
                    
                    $logoPath = htmlspecialchars($userStats['logo'] ? 'uploads/logo/'.$userStats['logo'] : 'uploads/logo/default.png');
                ?>
                <?php
// Ambil data division tim dari database
$team_id = $_SESSION['team_id'] ?? null;
$division = null;

if ($team_id) {
    $stmt = $conn->prepare("SELECT division FROM team_contact_details WHERE team_id = ? LIMIT 1");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $division = $res['division'] ?? null;
    $stmt->close();
}
?>

<?php if (!empty($division)): ?>
    <!-- ✅ Leaderboard box tampil jika sudah punya division -->
    <div class="leaderboard-box text-center shadow-sm mt-4">
        <img src="<?= $logoPath ?>" alt="<?= htmlspecialchars($userStats['team_name']) ?>" 
             style="width:70px; height:70px; border-radius:50%; border:3px solid #034694;">
        
        <h3 class="mt-2 fw-bold text-dark"><?= htmlspecialchars($userStats['team_name']) ?></h3>
    
        <span class="leaderboard-rank btn-gold">#<?= $userRank.$rankSuffix ?> of <?= $totalTeams ?></span>
        <?php if ($isPlayoff): ?>
            <span class="badge bg-success ms-2">Playoff Zone</span>
        <?php endif; ?>
    
        <div class="progress my-3" style="height: 8px;">
            <div class="progress-bar progress-bar-chelsea" role="progressbar" style="width: <?= $progressPercent ?>%;"></div>
        </div>
        <small class="text-muted">Progress to Top: <?= $progressPercent ?>%</small>
        <hr>
    
        <div class="row text-start">
            <div class="col-6"><strong>Matches Played:</strong></div>
            <div class="col-6 text-end"><?= $userStats['matches_played'] ?></div>
    
            <div class="col-6"><strong>Wins:</strong></div>
            <div class="col-6 text-end"><?= $userStats['matches_won'] ?></div>
    
            <div class="col-6"><strong>Losses:</strong></div>
            <div class="col-6 text-end"><?= $userStats['matches_lost'] ?></div>
    
            <div class="col-6"><strong>Pairs (W-L):</strong></div>
            <div class="col-6 text-end"><?= $userStats['pairs_won'] ?> - <?= $userStats['pairs_lost'] ?></div>
    
            <div class="col-6"><strong>Sets (W-L):</strong></div>
            <div class="col-6 text-end"><?= $userStats['sets_won'] ?> - <?= $userStats['sets_lost'] ?></div>
    
            <div class="col-6"><strong>Total Points:</strong></div>
            <div class="col-6 text-end fw-bold"><?= $userStats['points'] ?></div>
        </div>
    
        <a href="scheduled" class="btn-gold w-100 mt-3 text-decoration-none">View Match Schedule</a>
    </div>

<?php else: ?>
    <!-- ⚠️ Pesan tampil jika division masih NULL -->
    <div class="alert alert-warning text-center shadow-sm mt-4" role="alert" style="max-width:600px; margin:auto;">
        <i class="bi bi-clock-history me-2"></i>
        <strong>Please wait,</strong> divisions will be adjusted first.
    </div>
<?php endif; ?>

            
            
                <div class="row mt-4">
                
                    <div class="col-lg-8">
                        <div class="card card-soft">
                            <div class="card-header bg-dark text-white fw-bold">Your Schedule</div>
                            <div class="card-body">
                                <?php
                                    if ($resSchedule->num_rows === 0) {
                                        echo "<div class='text-muted'>There is no schedule for this team yet.</div>";
                                    }
                                    while ($m = $resSchedule->fetch_assoc()):
                                        $dateStr = date("l, d M Y, H:i", strtotime($m['scheduled_date']));
                                        $isHome = ((int)$m['team1_id'] === $team_id);
                        
                                        $logo1 = !empty($m['team1_logo']) ? "uploads/logo/".$m['team1_logo'] : "uploads/logo/default.png";
                                        $logo2 = !empty($m['team2_logo']) ? "uploads/logo/".$m['team2_logo'] : "uploads/logo/default.png";
                        
                                        $scoreText = "0 - 0";
                                        $team1Badge = $team2Badge = "";
                        
                                        if ($m['status'] === 'completed') {
                                            $team1Res = $conn->query("
                                                SELECT pairs_won, pairs_lost 
                                                FROM match_results 
                                                WHERE match_id = {$m['id']} AND team_id = {$m['team1_id']}
                                                LIMIT 1
                                            ")->fetch_assoc();
                        
                                            $team2Res = $conn->query("
                                                SELECT pairs_won, pairs_lost 
                                                FROM match_results 
                                                WHERE match_id = {$m['id']} AND team_id = {$m['team2_id']}
                                                LIMIT 1
                                            ")->fetch_assoc();
                        
                                            if ($team1Res && $team2Res) {
                                                $scoreText = "{$team1Res['pairs_won']} - {$team2Res['pairs_won']}";
                        
                                                if ($team1Res['pairs_won'] > $team2Res['pairs_won']) {
                                                    $team1Badge = '<span class="text-success fw-bold">WIN</span>';
                                                    $team2Badge = '<span class="text-danger fw-bold">LOSS</span>';
                                                } elseif ($team1Res['pairs_won'] < $team2Res['pairs_won']) {
                                                    $team1Badge = '<span class="text-danger fw-bold">LOSS</span>';
                                                    $team2Badge = '<span class="text-success fw-bold">WIN</span>';
                                                } else {
                                                    $team1Badge = $team2Badge = '<span class="text-secondary fw-bold">DRAW</span>';
                                                }
                                            } else {
                                                $scoreText = "N/A";
                                            }
                                        }
                        
                                        $badgeClass = "bg-secondary";
                                        if ($m['status'] === "completed") $badgeClass = "bg-success";
                                        elseif ($m['status'] === "scheduled") $badgeClass = "bg-primary";
                                        elseif ($m['status'] === "pending") $badgeClass = "bg-warning text-dark";
                                ?>
                                <a href="match?id=<?= (int)$m['id'] ?>" class="text-decoration-none text-dark d-block">
                                    <div class="schedule-item">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="small text-muted">Journey <?= (int)$m['journey'] ?></div>
                                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($m['status'])) ?></span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between flex-wrap py-2">
                                            <div class="d-flex align-items-center flex-grow-1" style="min-width: 120px;">
                                                <img src="<?= htmlspecialchars($logo1) ?>" 
                                                     alt="Logo <?= htmlspecialchars($row['team_name']) ?>" 
                                                     class="border me-2"
                                                     style="
                                                       width: 40px; 
                                                       height: 40px; 
                                                       object-fit: contain; 
                                                       background-color: #fff; 
                                                       border-radius: 50%; 
                                                       padding: 3px; 
                                                     ">
    
                                                <div>
                                                    <h6 class="mb-0 small"><?= htmlspecialchars($m['team1']) ?> <?= $team1Badge ?></h6>
                                                    <small class="text-muted"><?= $isHome ? 'Your Team' : 'Home' ?></small>
                                                </div>
                                            </div>
                    
                                            <div class="text-center flex-grow-1 my-2" style="min-width:100px;">
                                                <strong><?= htmlspecialchars($scoreText) ?></strong>
                                                <div class="small text-muted"><?= htmlspecialchars($dateStr) ?></div>
                                            </div>
                    
                                            <div class="d-flex align-items-center flex-grow-1 justify-content-end" style="min-width: 120px;">
                                                <div class="text-end me-2">
                                                    <h6 class="mb-0 small"><?= htmlspecialchars($m['team2']) ?> <?= $team2Badge ?></h6>
                                                    <small class="text-muted"><?= !$isHome ? 'Your Team' : 'Away' ?></small>
                                                </div>
                                                <img src="<?= htmlspecialchars($logo2) ?>" 
                                                     alt="Logo <?= htmlspecialchars($row['team_name']) ?>" 
                                                     class="border me-2"
                                                     style="
                                                       width: 40px; 
                                                       height: 40px; 
                                                       object-fit: contain; 
                                                       background-color: #fff; 
                                                       border-radius: 50%; 
                                                       padding: 3px; 
                                                     ">
    
                                            </div>
                    
                                        </div>
                    
                                    </div>
                                </a>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                
                    <div class="col-lg-4">
                        <div class="card card-soft h-100">
                            <div class="card-header bg-dark text-white fw-bold">Player Ranking</div>
                            <div class="card-body">
                                <?php
                                $sqlDivisions = "
                                    SELECT tcd.division
                                    FROM team_contact_details tcd
                                    JOIN team_info ti ON ti.id = tcd.team_id
                                    WHERE ti.tournament_id = ?
                                    AND ti.id = ?
                                    LIMIT 1
                                ";
                                $stmtDiv = $conn->prepare($sqlDivisions);
                                $stmtDiv->bind_param("ii", $tournament_id, $team_id);
                    
                                $stmtDiv->execute();
                                $resDiv = $stmtDiv->get_result();
                                $division_id = ($resDiv->num_rows > 0) ? $resDiv->fetch_assoc()['division'] : null;
                    
                                $sqlPlayers = "
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
                                    AND tcd.division = ?
                                    ORDER BY tmi.point DESC, tmi.player_name ASC
                                ";
                    
                                $stmtPlayers = $conn->prepare($sqlPlayers);
                                $stmtPlayers->bind_param("ii", $tournament_id, $division_id);
                                $stmtPlayers->execute();
                                $resPlayers = $stmtPlayers->get_result();
                    
                                $rank = 1;
                                if ($resPlayers->num_rows === 0) {
                                    echo "<div class='text-muted'>There is no player data yet for this division.</div>";
                                }
                    
                                while($p = $resPlayers->fetch_assoc()):
                                    $profile = !empty($p['profile']) ? "uploads/profile/".$p['profile'] : "uploads/profile/default.png";
                                    $logo    = !empty($p['logo']) ? "uploads/logo/".$p['logo'] : "uploads/logo/default.png";
                                ?>
                                <div class="player-card d-flex align-items-center justify-content-between py-2 border-bottom">
                                    <div class="fw-bold" style="width:28px; text-align:center;"><?= $rank++ ?></div>
                                    <img src="<?= htmlspecialchars($profile) ?>" class="player-avatar rounded-circle me-2" alt="profile" style="width:40px;height:40px;object-fit:cover;">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?= htmlspecialchars($p['player_name']) ?></div>
                                        <div class="d-flex align-items-center gap-2 text-muted small">
                                            <img src="<?= htmlspecialchars($logo) ?>" style="width:16px;height:16px;object-fit:contain;">
                                            <span><?= htmlspecialchars($p['team_name']) ?></span>
                                        </div>
                                    </div>
                                    <div class="fw-bold"><?= (int)($p['points'] ?? 0) ?> pts</div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                
                </div>
            
            </section>
        
            <?php if ($showModal): ?>
                <script>
                  document.addEventListener("DOMContentLoaded", function(){
                      var popup = new bootstrap.Modal(document.getElementById('popupModal'));
                      popup.show();
                  });
                </script>
            <?php endif; ?>
        
        
            <?php require 'src/footer.php' ?>
                <style>
                    .leaderboard-box {
                        border: 1px solid #dfe3e8;
                        border-radius: 10px;
                        padding: 20px;
                        background: #ffffff;
                    }
                    .leaderboard-rank-badge {
                        display: inline-block;
                        padding: 6px 14px;
                        font-weight: bold;
                        border-radius: 20px;
                        background: <?= ($userRank==1) ? '#034694' : '#6caad9' ?>;
                        color: white;
                    }
                    .progress-bar-chelsea {
                        background-color: #1A73E8;
                    }
                </style>
       
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
            const hero = document.getElementById('about-liga');
        
            function toggleNavbarFixed() {
              if (!hero) return;
        
              const scrollPos = window.scrollY;
              const heroHeight = hero.offsetHeight;
        
              if (scrollPos >= heroHeight) {
                navbar.classList.add('navbar-fixed');
                document.body.style.paddingTop = navbar.offsetHeight + 'px'; 
              } else {
                navbar.classList.remove('navbar-fixed');
                document.body.style.paddingTop = '0';
              }
            }
        
            window.addEventListener('scroll', toggleNavbarFixed);
            toggleNavbarFixed(); 
          });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    </body>
</html>
