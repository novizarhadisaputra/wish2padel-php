<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>Dashboard - Wish2Padel</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
      <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=13') ?>">
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
        
        <?php view('partials.navbar'); ?>
        
        <?php
            // Pop-up logic (keep simplified for now, ideally moved to middleware or view composer)
            $showModal = false;
            $todayStr = date('Y-m-d');
            $team_id = $team['id'];
            $conn = getDBConnection();
            
            $res = $conn->query("
                SELECT t.start_date 
                FROM tournaments t 
                JOIN team_info tm ON tm.tournament_id = t.id
                WHERE tm.id = $team_id 
                LIMIT 1
            ");
        
            if ($conn && $res && $row = $res->fetch_assoc()) {
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
        ?>
        
        <?php if($showModal): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function(){
                var myModal = new bootstrap.Modal(document.getElementById('popupModal'));
                myModal.show();
            });
        </script>
        <?php endif; ?>
      
        <div class="modal fade" id="popupModal" tabindex="-1">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius:15px;">
              
                <div class="modal-header text-white fw-bold" style="background-color:#696969">
                Important Match Preparation Notice
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
        
                <?php
                    $lineup_file = null;
                    $match_file  = null;
                    if ($conn) {
                        $doc_lineup = $conn->query("SELECT file_path FROM documents WHERE id = 1 LIMIT 1");
                        if ($doc_lineup) {
                            $row_lineup = $doc_lineup->fetch_assoc();
                            $lineup_file = $row_lineup['file_path'] ?? null;
                        }
                        
                        $doc_match = $conn->query("SELECT file_path FROM documents WHERE id = 2 LIMIT 1");
                        if ($doc_match) {
                            $row_match = $doc_match->fetch_assoc();
                            $match_file = $row_match['file_path'] ?? null;
                        }
                    }
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
                              <a href="<?= $lineup_file ? asset($lineup_file) : '#' ?>" 
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
                              <a href="<?= $match_file ? asset($match_file) : '#' ?>" 
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
            
            <section class="container py-4">
                <div class="mb-3">
                    <h1 class="fw-bold mb-1" style="color:#f3e6b6">
                        Welcome Captain, <?= htmlspecialchars($team['captain_name']) ?>
                    </h1>
                    <div style="color:#88694A">
                        Server time: <?= date('l, d M Y, H:i:s') ?>
                    </div>
                    <div class="mt-2 small" style="color:#f3e6b6">
                        Zone: <strong><?= htmlspecialchars($tournament['name'] ?? 'Tournament') ?></strong>
                        • Division:
                        <strong>
                            <?= htmlspecialchars(!empty($selected_division) ? $selected_division . " – " . $divisionLabel : '-') ?>
                        </strong>
                    </div>
                </div>
            
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
                                                            <a href="<?= asset('team_profile?id=' . $row['team_id']) ?>" class="text-decoration-none text-dark d-flex align-items-center gap-2">
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
                    $currentTeamId = $team['id'];
                    $userRank = null;
                    $userStats = null;
                    foreach ($leaderboard as $index => $row) {
                        if ($row['team_id'] == $currentTeamId) {
                            $userRank = $index + 1;
                            $userStats = $row;
                            break;
                        }
                    }
                    
                    if ($userStats):
                        $totalTeams = count($leaderboard);
                        $rankSuffix = ($userRank==1?'st':($userRank==2?'nd':($userRank==3?'rd':'th')));
                        $progressPercent = ($totalTeams > 0) ? round((($totalTeams - $userRank + 1) / $totalTeams) * 100) : 0;
                        $isPlayoff = ($userRank <= 4);
                        
                        $logoPath = htmlspecialchars($userStats['logo'] ? 'uploads/logo/'.$userStats['logo'] : 'uploads/logo/default.png');
                ?>
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
                
                    <a href="<?= asset('scheduled') ?>" class="btn-gold w-100 mt-3 text-decoration-none">View Match Schedule</a>
                </div>
                <?php endif; ?>
            
            
                <div class="row mt-4">
                
                    <div class="col-lg-8">
                        <div class="card card-soft">
                            <div class="card-header bg-dark text-white fw-bold">Your Schedule</div>
                            <div class="card-body">
                                <?php
                                    if (!$resSchedule || $resSchedule->num_rows === 0) {
                                        echo "<div class='text-muted'>There is no schedule for this team yet.</div>";
                                    }
                                    while ($resSchedule && $m = $resSchedule->fetch_assoc()):
                                        $dateStr = date("l, d M Y, H:i", strtotime($m['scheduled_date']));
                                        $isHome = ((int)$m['team1_id'] === $team_id);
                        
                                        $logo1 = !empty($m['team1_logo']) ? "uploads/logo/".$m['team1_logo'] : "uploads/logo/default.png";
                                        $logo2 = !empty($m['team2_logo']) ? "uploads/logo/".$m['team2_logo'] : "uploads/logo/default.png";
                        
                                        $scoreText = "0 - 0";
                                        $team1Badge = $team2Badge = "";
                        
                                        if ($m['status'] === 'completed') {
                                            if ($conn) {
                                                $team1ResQuery = $conn->query("
                                                    SELECT pairs_won, pairs_lost 
                                                    FROM match_results 
                                                    WHERE match_id = {$m['id']} AND team_id = {$m['team1_id']}
                                                    LIMIT 1
                                                ");
                                                $team1Res = $team1ResQuery ? $team1ResQuery->fetch_assoc() : null;
                            
                                                $team2ResQuery = $conn->query("
                                                    SELECT pairs_won, pairs_lost 
                                                    FROM match_results 
                                                    WHERE match_id = {$m['id']} AND team_id = {$m['team2_id']}
                                                    LIMIT 1
                                                ");
                                                $team2Res = $team2ResQuery ? $team2ResQuery->fetch_assoc() : null;
                            
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
                                        }
                        
                                        $badgeClass = "bg-secondary";
                                        if ($m['status'] === "completed") $badgeClass = "bg-success";
                                        elseif ($m['status'] === "scheduled") $badgeClass = "bg-primary";
                                        elseif ($m['status'] === "pending") $badgeClass = "bg-warning text-dark";
                                    ?>
                        
                                    <div class="schedule-item">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-secondary">Journey <?= (int)$m['journey'] ?></span>
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($m['status']) ?></span>
                                        </div>
                                        <div class="schedule-teams">
                                            <div class="schedule-team">
                                                <img src="<?= $logo1 ?>" class="player-avatar">
                                                <div>
                                                    <h6 class="fw-bold"><?= htmlspecialchars($m['team1']) ?></h6>
                                                </div>
                                            </div>
                        
                                            <div class="schedule-center">
                                                <div class="fs-4"><?= $scoreText ?></div>
                                                <small class="text-muted d-block mt-1" style="font-size:.75rem;"><?= $dateStr ?></small>
                                            </div>
                        
                                            <div class="schedule-team justify-content-end text-end">
                                                <div>
                                                    <h6 class="fw-bold"><?= htmlspecialchars($m['team2']) ?></h6>
                                                </div>
                                                <img src="<?= $logo2 ?>" class="player-avatar">
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                
                    <div class="col-lg-4">
                        <div class="card card-soft">
                            <div class="card-header bg-dark text-white fw-bold">Top Players (MVP)</div>
                            <div class="card-body">
                                
                                <?php
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
                                    LIMIT 5
                                ";
                                if ($conn) {
                                    $stmt = $conn->prepare($sqlPlayers);
                                    if ($stmt) {
                                        $stmt->bind_param("i", $tournament['id']);
                                        $stmt->execute();
                                        $resPlayers = $stmt->get_result();
                                        
                                        while($resPlayers && $p = $resPlayers->fetch_assoc()): 
                                            $profile = !empty($p['profile']) ? "uploads/profile/".$p['profile'] : "uploads/profile/default.png";
                                ?>
                                    <div class="player-card">
                                        <img src="<?= $profile ?>" class="player-avatar">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($p['player_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($p['team_name']) ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; 
                                    }
                                } ?>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">Coming soon: Full player stats.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </section>
        
        <?php view('partials.footer'); ?>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
