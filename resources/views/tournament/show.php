<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>League - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
</head>
<body>

<?php view('partials.navbar'); ?>


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
                        // Logic pulled from tournament.php for Teams Tab
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
            <div class="row mb-3 g-3">
                <div class="col-md-6">
                    <label for="filterDivision" class="form-label fw-bold">Filter by Division</label>
                    <form method="get" id="divisionForm">
                        <input type="hidden" name="id" value="<?= $tournament_id ?>">
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
            $championTeamNameEsc = htmlspecialchars($championTeamName, ENT_QUOTES, 'UTF-8');
            $championLogoEsc     = htmlspecialchars($championLogo, ENT_QUOTES, 'UTF-8');
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
                            $pos = 1;
                            foreach($leaderboard as $row): 
                                $isChampion = ($pos === 1 && $allJourneysDone);
                                $rowStyle = "";
                                if ($isChampion) $rowStyle = "background-color: #fff9c4;"; // highlight gold
                            ?>
                            <tr style="<?= $rowStyle ?>">
                                <td><?= $pos++ ?></td>
                                <td><?= htmlspecialchars($row['team_name']) ?></td>
                                <td><?= $row['matches_played'] ?></td>
                                <td><?= $row['matches_won'] ?></td>
                                <td><?= $row['matches_lost'] ?></td>
                                <td><?= $row['pairs_won'] ?> - <?= $row['pairs_lost'] ?></td>
                                
                                <td><?= $row['sets_won'] ?> - <?= $row['sets_lost'] ?></td>
                                <td><strong><?= $row['points'] ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="tab-pane fade" id="playoff">
            <div class="alert alert-info">Playoff information coming soon.</div>
        </div>

        <div class="tab-pane fade" id="ranking">
            <div class="alert alert-info">Player stats coming soon.</div>
        </div>

    </div>
</section>

<?php view('partials.footer'); ?>

</body>
</html>
