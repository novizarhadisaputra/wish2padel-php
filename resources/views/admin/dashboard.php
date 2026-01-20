<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p.png') ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Wish2Padel</title>
    <!-- Adjusted paths for assets -->
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Extract existing styles from original dashboard.php */
        .dashboard-card { position: relative; background: #ffffff; border-radius: 1rem; padding: 1.5rem 1rem; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35); transition: transform 0.25s ease, box-shadow 0.25s ease; overflow: hidden; }
        .dashboard-card:hover { transform: translateY(-6px) scale(1.02); box-shadow: 0 12px 35px rgba(0, 0, 0, 0.55); }
        .dashboard-card i { font-size: 2rem; margin-bottom: 10px; }
        .dashboard-card h5 { font-weight: 500; color: #444; margin-bottom: 5px; }
        .dashboard-card h2 { font-weight: 700; font-size: 2.2rem; color: #111; }
        .learn-more { display: inline-block; margin-top: 8px; font-size: 0.85rem; color: black; text-decoration: none; font-weight: 700; }
        .learn-more:hover { text-decoration: underline; }
        .welcome-box { margin-bottom: 2rem; text-align: left; }
        .welcome-box h2 { font-weight: 700; }
        .welcome-box p { font-size: 0.95rem; opacity: 0.8; }
    </style>
</head>
<body style="background-color: #303030">
    <?php view('partials.navbar'); ?>
    
    <section class="container py-4">
        <div class="welcome-box mb-4 text-white">
            <h2>Welcome, <?= htmlspecialchars($username) ?></h2>
            <p>
                <i class="bi bi-person-circle"></i> Role: <?= htmlspecialchars($role) ?> |
                <i class="bi bi-clock"></i> <span id="server-time"><?= $server_datetime ?></span>
            </p>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="row row-cols-2 row-cols-md-3 g-4">
                    <div class="col"><div class="dashboard-card text-center"><i class="bi bi-trophy-fill text-warning fs-2"></i><h5>Total Leagues</h5><h2><?= $stats['leagues'] ?></h2><a href="<?= asset('admin/tournament') ?>" class="learn-more">Learn More →</a></div></div>
                    <div class="col"><div class="dashboard-card text-center"><i class="bi bi-geo-alt-fill text-danger fs-2"></i><h5>Total Zones</h5><h2><?= $stats['zones'] ?></h2><a href="<?= asset('admin/tournament') ?>" class="learn-more">Learn More →</a></div></div>
                    <div class="col"><div class="dashboard-card text-center"><i class="bi bi-newspaper text-primary fs-2"></i><h5>Total News</h5><h2><?= $stats['news'] ?></h2><a href="<?= asset('admin/news') ?>" class="learn-more">Learn More →</a></div></div>
                    <div class="col"><div class="dashboard-card text-center"><span class="iconify text-success" data-icon="mdi:tennis" style="font-size:50px;"></span>
                    <h5>Total Matches</h5><h2><?= $stats['matches'] ?></h2><a href="<?= asset('admin/match') ?>" class="learn-more">Learn More →</a></div></div>
                    <div class="col"><div class="dashboard-card text-center"><i class="bi bi-cash-coin text-warning fs-2"></i><h5>Total Sponsors</h5><h2><?= $stats['sponsors'] ?></h2><a href="<?= asset('admin/sponsors') ?>" class="learn-more">Learn More →</a></div></div>
                    <div class="col"><div class="dashboard-card text-center"><i class="bi bi-people-fill text-info fs-2"></i><h5>Total Teams</h5><h2><?= $stats['teams'] ?></h2><a href="<?= asset('admin/team') ?>" class="learn-more">Learn More →</a></div></div>
                    <div class="col"><div class="dashboard-card text-center"><i class="bi bi-building text-secondary fs-2"></i><h5>Total Clubs</h5><h2><?= $stats['clubs'] ?></h2><a href="<?= asset('admin/club') ?>" class="learn-more">Learn More →</a></div></div>
                    <div class="col"><div class="dashboard-card text-center"><i class="bi bi-person-badge text-dark fs-2"></i><h5>Total Players</h5><h2><?= $stats['players'] ?></h2><a href="<?= asset('admin/players') ?>" class="learn-more">Learn More →</a></div></div>
                    <div class="col"><div class="dashboard-card text-center"><i class="bi bi-check-circle-fill text-success fs-2"></i><h5>Completed Matches</h5><h2><?= $stats['completed_matches'] ?></h2><a href="<?= asset('admin/pair') ?>" class="learn-more">Learn More →</a></div></div>
                </div>
            </div>
    
            <div class="col-lg-4">
                <div class="dashboard-card mb-4">
                    <h5><i class="bi bi-calendar-event text-warning"></i> Next Match</h5>
                    <?php if($next_match): ?>
                        <p class="mt-2"><strong>#<?= $next_match['id'] ?></strong> - <?= htmlspecialchars($next_match['team1_name']) ?> vs <?= htmlspecialchars($next_match['team2_name']) ?></p>
                        <p><i class="bi bi-trophy"></i> <?= htmlspecialchars($next_match['tournament_name']) ?> (Journey: <?= $next_match['journey'] ?>)</p>
                        <p><i class="bi bi-clock"></i> <?= date('d M Y H:i', strtotime($next_match['scheduled_date'])) ?></p>
                    <?php else: ?>
                    <p class="text-muted">No upcoming matches</p>
                    <?php endif; ?>
                </div>
    
                <div class="dashboard-card mb-4">
                    <h5><i class="bi bi-cash-stack text-success"></i> Recent Transactions</h5>
                    <table class="table table-sm mt-2">
                      <thead><tr><th>Team</th><th>Zone</th><th>Status</th></tr></thead>
                      <tbody>
                        <?php if($transactions): foreach($transactions as $tr): ?>
                          <tr>
                            <td><?= htmlspecialchars($tr['team_name']) ?></td>
                            <td><?= htmlspecialchars($tr['tournament_name']) ?></td>
                            <td><span class="badge bg-<?= $tr['status']=='paid'?'success':($tr['status']=='pending'?'warning':'danger') ?>"><?= ucfirst($tr['status']) ?></span></td>
                          </tr>
                        <?php endforeach; else: ?>
                          <tr><td colspan="4" class="text-muted">No transactions</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                </div>
              
                <div class="dashboard-card text-center"><i class="bi bi-credit-card text-primary fs-2"></i>
                    <h5>Payment Settings</h5>
                    <h2>SAR <?= number_format($amountSAR, 2) ?></h2>
                    <small class="text-muted">(<?= number_format($amountHalalah) ?> halalah)</small><br><a href="<?= asset('admin/payment_settings') ?>" class="learn-more">Manage →</a>
                </div>
            </div>
          </div>
    </section>
    
    <script>
        let serverTime = new Date("<?= $server_time ?>");
        setInterval(() => {
          serverTime.setSeconds(serverTime.getSeconds() + 1); 
          const options = { weekday:'long', year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false, timeZone: "Asia/Riyadh" };
          document.getElementById('server-time').innerHTML = `Role: <?= htmlspecialchars($role) ?> | Server Time: ${serverTime.toLocaleString('en-GB', options)}`;
        }, 1000);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
