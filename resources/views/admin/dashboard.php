<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Wish2Padel</title>
    <!-- Adjusted paths for assets -->
    <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-dashboard-card { 
            position: relative; 
            background: rgba(255, 255, 255, 0.05); 
            backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 1rem; 
            padding: 1.5rem 1rem; 
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35); 
            transition: all 0.25s ease; 
            overflow: hidden; 
        }
        .admin-dashboard-card:hover { 
            transform: translateY(-5px); 
            border-color: #d4af37;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.55); 
        }
        .admin-dashboard-card i { font-size: 2rem; margin-bottom: 15px; display: block; }
        .admin-dashboard-card h5 { font-weight: 500; color: #aaa; margin-bottom: 10px; font-size: 0.9rem; }
        .admin-dashboard-card h2 { font-weight: 700; font-size: 2rem; color: #fff; margin-bottom: 15px; }
        .learn-more-btn { 
            display: inline-block; 
            font-size: 0.8rem; 
            color: #d4af37; 
            text-decoration: none; 
            font-weight: 600; 
            padding: 5px 15px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 20px;
            transition: all 0.2s;
        }
        .learn-more-btn:hover { 
            background: #d4af37;
            color: #000;
            text-decoration: none; 
        }
        .welcome-header h2 { font-weight: 700; color: #d4af37; }
        .welcome-header p { font-size: 0.95rem; color: #aaa; }
    </style>
</head>
<body class="admin-page">
    <?php view('partials.navbar'); ?>
    
    <div class="container py-5 mt-5">
        <div class="welcome-header mb-5">
            <h2>Welcome back, <?= htmlspecialchars($username) ?></h2>
            <p class="mb-0">
                <i class="bi bi-shield-lock me-1"></i> Role: <span class="text-white"><?= htmlspecialchars($role) ?></span> |
                <i class="bi bi-clock me-1 ms-3"></i> <span id="server-time"><?= $server_datetime ?></span>
            </p>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="row row-cols-2 row-cols-md-3 g-4">
                    <div class="col"><div class="admin-dashboard-card text-center"><i class="bi bi-trophy-fill text-warning"></i><h5>Total Leagues</h5><h2><?= $stats['leagues'] ?></h2><a href="<?= asset('admin/tournament') ?>" class="learn-more-btn">Manage →</a></div></div>
                    <div class="col"><div class="admin-dashboard-card text-center"><i class="bi bi-geo-alt-fill text-danger"></i><h5>Total Zones</h5><h2><?= $stats['zones'] ?></h2><a href="<?= asset('admin/tournament') ?>" class="learn-more-btn">Manage →</a></div></div>
                    <div class="col"><div class="admin-dashboard-card text-center"><i class="bi bi-newspaper text-primary"></i><h5>Total News</h5><h2><?= $stats['news'] ?></h2><a href="<?= asset('admin/news') ?>" class="learn-more-btn">Manage →</a></div></div>
                    <div class="col"><div class="admin-dashboard-card text-center"><span class="iconify text-success mx-auto" data-icon="mdi:tennis" style="font-size:32px; margin-bottom:15px; display:block;"></span>
                    <h5>Total Matches</h5><h2><?= $stats['matches'] ?></h2><a href="<?= asset('admin/match') ?>" class="learn-more-btn">Manage →</a></div></div>
                    <div class="col"><div class="admin-dashboard-card text-center"><i class="bi bi-cash-coin text-warning"></i><h5>Total Sponsors</h5><h2><?= $stats['sponsors'] ?></h2><a href="<?= asset('admin/sponsors') ?>" class="learn-more-btn">Manage →</a></div></div>
                    <div class="col"><div class="admin-dashboard-card text-center"><i class="bi bi-people-fill text-info"></i><h5>Total Teams</h5><h2><?= $stats['teams'] ?></h2><a href="<?= asset('admin/team') ?>" class="learn-more-btn">Manage →</a></div></div>
                    <div class="col"><div class="admin-dashboard-card text-center"><i class="bi bi-building text-secondary"></i><h5>Total Clubs</h5><h2><?= $stats['clubs'] ?></h2><a href="<?= asset('admin/club') ?>" class="learn-more-btn">Manage →</a></div></div>
                    <div class="col"><div class="admin-dashboard-card text-center"><i class="bi bi-person-badge text-white"></i><h5>Total Players</h5><h2><?= $stats['players'] ?></h2><a href="<?= asset('admin/players') ?>" class="learn-more-btn">Manage →</a></div></div>
                    <div class="col"><div class="admin-dashboard-card text-center"><i class="bi bi-check-circle-fill text-success"></i><h5>Completed Matches</h5><h2><?= $stats['completed_matches'] ?></h2><a href="<?= asset('admin/pair') ?>" class="learn-more-btn">Manage →</a></div></div>
                </div>
            </div>
    
            <div class="col-lg-4">
                <div class="admin-dashboard-card mb-4">
                    <h5 class="text-gold mb-3"><i class="bi bi-calendar-event me-2"></i>Next Match</h5>
                    <?php if($next_match): ?>
                        <div class="text-white">
                            <p class="mb-2"><strong>#<?= $next_match['id'] ?></strong> - <?= htmlspecialchars($next_match['team1_name']) ?> vs <?= htmlspecialchars($next_match['team2_name']) ?></p>
                            <p class="mb-1 small"><i class="bi bi-trophy me-2 text-warning"></i><?= htmlspecialchars($next_match['tournament_name']) ?></p>
                            <p class="mb-0 small"><i class="bi bi-clock me-2 text-info"></i><?= date('d M Y H:i', strtotime($next_match['scheduled_date'])) ?></p>
                        </div>
                    <?php else: ?>
                    <p class="text-white-50 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> No upcoming matches
                    </p>
                    <?php endif; ?>
                </div>
    
                <div class="admin-dashboard-card mb-4">
                    <h5 class="text-gold mb-3"><i class="bi bi-cash-stack me-2"></i>Recent Transactions</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle small">
                          <thead><tr><th>Team</th><th>Tournament</th><th>Status</th></tr></thead>
                          <tbody>
                            <?php if($transactions): foreach($transactions as $tr): ?>
                              <tr>
                                <td class="text-white"><?= htmlspecialchars($tr['team_name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($tr['tournament_name']) ?></td>
                                <td><span class="badge bg-<?= $tr['status']=='paid'?'success':($tr['status']=='pending'?'warning':'danger') ?>"><?= ucfirst($tr['status']) ?></span></td>
                              </tr>
                            <?php endforeach; else: ?>
                              <tr><td colspan="3" class="text-center py-4 text-white-50">
                                   <i class="bi bi-info-circle me-1"></i> No transactions
                               </td></tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                    </div>
                </div>
              
                <div class="admin-dashboard-card text-center">
                    <i class="bi bi-credit-card text-gold"></i>
                    <h5>Payment Settings</h5>
                    <h2 class="text-gold">SAR <?= number_format($amountSAR, 2) ?></h2>
                    <p class="small text-muted mb-3">(<?= number_format($amountHalalah) ?> halalah)</p>
                    <a href="<?= asset('admin/payment_settings') ?>" class="learn-more-btn">Manage →</a>
                </div>
            </div>
          </div>
    <script>
        // Update server time display
        setInterval(function() {
            const timeEl = document.getElementById('server-time');
            if (timeEl) {
                let currentTime = new Date(timeEl.innerText);
                currentTime.setSeconds(currentTime.getSeconds() + 1);
                
                // Format: YYYY-MM-DD HH:MM:SS
                const y = currentTime.getFullYear();
                const m = String(currentTime.getMonth() + 1).padStart(2, '0');
                const d = String(currentTime.getDate()).padStart(2, '0');
                const h = String(currentTime.getHours()).padStart(2, '0');
                const min = String(currentTime.getMinutes()).padStart(2, '0');
                const s = String(currentTime.getSeconds()).padStart(2, '0');
                
                timeEl.innerText = `${y}-${m}-${d} ${h}:${min}:${s}`;
            }
        }, 1000);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
