<?php
// Cek apakah ada team yang belum punya division
$has_unassigned_division = false;
if (isset($conn)) {
    $check = $conn->query("
        SELECT 1 
        FROM team_contact_details tcd
        INNER JOIN team_info ti ON ti.id = tcd.team_id
        INNER JOIN payment_transactions pt 
            ON pt.team_id = ti.id 
            AND pt.tournament_id = ti.tournament_id
            AND pt.status = 'paid'   -- ganti sesuai status real payment kamu
        WHERE tcd.division IS NULL 
        LIMIT 1
    ");
    $has_unassigned_division = ($check && $check->num_rows > 0);
}

?>

<nav id="maiavbar" class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="../index">
      <img src="../assets/image/w2p.png" alt="Logo" class="d-inline-block align-text-top">
    </a>
    <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#mainNavbar"
      aria-controls="mainNavbar"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!-- Home -->
        <li class="nav-item">
          <a class="nav-link" href="dashboard">Dashboard</a>
        </li>

        <!-- Liga Mega Dropdown -->
        <li class="nav-item dropdown position-static">
          <a
              class="nav-link dropdown-toggle"
              href="#"
              id="ligaDropdown"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              League
              <?php if ($has_unassigned_division): ?>
                <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">!</span>
              <?php endif; ?>
            </a>

          <div class="dropdown-menu mega-dropdown shadow" aria-labelledby="ligaDropdown">
            <div class="row">
              <div class="col-6 col-md-3">
                <h6>Team</h6>
                <a class="dropdown-item" href="team">Manage Teams</a>
                <a class="dropdown-item" href="division">Manage Division
                  <?php if ($has_unassigned_division): ?>
                    <span class="badge bg-danger ms-2">!</span>
                  <?php endif; ?>
                </a>
              </div>
              <div class="col-6 col-md-3">
                <h6>League</h6>
                <a class="dropdown-item" href="tournament">Manage League</a>
                <a class="dropdown-item" href="windows">Transfer Windows</a>
                <a class="dropdown-item" href="registrations">Registrations</a>
              </div>
              <div class="col-6 col-md-3">
                <h6>Document</h6>
                <a class="dropdown-item" href="document">Manage Document</a>
              </div>
            </div>
          </div>
        </li>

        <?php
// ================================
// PLAYOFF ALERT — Semua Tournament
// ================================
$playoffAlerts = []; // simpan nama tournament siap generate playoff

$tournaments = $conn->query("SELECT id, name FROM tournaments");
if ($tournaments) {
    while ($t = $tournaments->fetch_assoc()) {
        $tid = (int)$t['id'];
        $tournamentName = $t['name'];

        // 1. Hitung total REGULAR match (Notes NULL atau bukan playoff)
        $resTotalRegular = $conn->query("
            SELECT COUNT(*) AS total_regular
            FROM matches
            WHERE tournament_id = $tid
              AND (notes IS NULL OR notes NOT IN ('Semi Final 1','Semi Final 2','Final 1','Final 2'))
        ");
        $totalRegular = ($resTotalRegular) ? $resTotalRegular->fetch_assoc()['total_regular'] ?? 0 : 0;

        // 2. Hitung pending REGULAR match
        $resPending = $conn->query("
            SELECT COUNT(*) AS pending_count
            FROM matches
            WHERE tournament_id = $tid
              AND (notes IS NULL OR notes NOT IN ('Semi Final 1','Semi Final 2','Final 1','Final 2'))
              AND status != 'completed'
        ");
        $pendingCount = ($resPending) ? $resPending->fetch_assoc()['pending_count'] ?? 0 : 0;

        // 3. Hitung jumlah playoff match yang sudah ada
        $resPlayoff = $conn->query("
            SELECT COUNT(*) AS playoff_count
            FROM matches
            WHERE tournament_id = $tid
              AND notes IN ('Semi Final 1','Semi Final 2','Final 1','Final 2')
        ");
        $playoffCount = ($resPlayoff) ? $resPlayoff->fetch_assoc()['playoff_count'] ?? 0 : 0;

        // 4. Tambahkan alert HANYA jika:
        // - Sudah ada REGULAR match
        // - Semua REGULAR complete
        // - Belum ada playoff sama sekali
        if ($totalRegular > 0 && $pendingCount == 0 && $playoffCount == 0) {
            $playoffAlerts[] = $tournamentName;
        }
    }
}
?>

<!-- ================= Navbar Matches ================= -->
<?php
// ==== CEK ANOMALI MATCH ====
$anomalies = [];
$sql = "
    SELECT 
        m.id AS match_id,
        t1.team_name AS team1_name,
        t2.team_name AS team2_name,
        r1.pairs_won AS team1_won,
        r2.pairs_won AS team2_won
    FROM matches m
    JOIN match_results r1 ON r1.match_id = m.id AND r1.team_id = m.team1_id
    JOIN match_results r2 ON r2.match_id = m.id AND r2.team_id = m.team2_id
    JOIN team_info t1 ON t1.id = m.team1_id
    JOIN team_info t2 ON t2.id = m.team2_id
";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        // Cek kondisi anomali (kedua tim sama-sama menang atau skor tidak valid)
        if ($row['team1_won'] === $row['team2_won'] && $row['team1_won'] !== null) {
            $anomalies[] = [
                'match_id' => $row['match_id'],
                'team1' => $row['team1_name'],
                'team2' => $row['team2_name'],
                'score' => "{$row['team1_won']} - {$row['team2_won']}"
            ];
            
        }
    }
}

// Jika ada alert playoff ATAU anomaly → tampilkan tanda seru
$hasAlerts = !empty($playoffAlerts) || !empty($anomalies);
?>

<li class="nav-item dropdown position-static">
  <a class="nav-link dropdown-toggle" href="#" id="teamsDropdown" role="button"
     data-bs-toggle="dropdown" aria-expanded="false">
    Matches
    <?php if ($hasAlerts): ?>
      <span class="badge bg-danger ms-1" style="font-size:10px;">!</span>
    <?php endif; ?>
  </a>

  <div class="dropdown-menu mega-dropdown shadow" aria-labelledby="teamsDropdown">
    <div class="row">
      <!-- Bagian kiri -->
      <div class="col-6 col-md-4">
        <h6>Match</h6>
        <a class="dropdown-item" href="match">Manage Match</a>
        <a class="dropdown-item" href="playoff">
          Playoff Match
          <?php if (!empty($playoffAlerts)): ?>
            <span class="badge bg-danger ms-1">GENERATE</span>
          <?php endif; ?>
        </a>
      </div>

      <!-- Bagian kanan -->
      <div class="col-6 col-md-8">
        <h6>Overview</h6>
        <a class="dropdown-item" href="pair">
          Overview
          <?php if (!empty($anomalies)): ?>
            <span class="badge bg-warning text-dark ms-1">
              <?= count($anomalies) ?> ANOMALY
            </span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </div>
</li>



        <li class="nav-item dropdown position-static">
          <a
            class="nav-link dropdown-toggle"
            href="#"
            id="ligaDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            Gallery
          </a>
          <div class="dropdown-menu mega-dropdown shadow" aria-labelledby="ligaDropdown">
            <div class="row">
              <div class="col-6 col-md-3">
                <h6>Gallery</h6>
                <a class="dropdown-item" href="gallery">Manage Gallery</a>
              </div>
              <div class="col-6 col-md-3">
                <h6>Presentation</h6>
                <a class="dropdown-item" href="presentasion">Manage Presentation</a>
              </div>
            </div>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="club/club">Club</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="news">News</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="sponsors">Sponsor</a>
        </li>
      </ul>

      <!-- Profile/Login Section -->
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if ($username): ?>
          <li class="nav-item dropdown">
            <a
              class="nav-link dropdown-toggle text-light"
              href="#"
              id="profileDropdown"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <?= htmlspecialchars($username) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
              <li><hr class="dropdown-divider" /></li>
              <li><a class="dropdown-item text-danger" href="src/logout">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="login/login" class="btn btn-login">Login</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<style>
  nav .navbar-brand {
    margin-left: 50px;
    margin-right: 80px;
  }
  .navbar-brand img {
    height: 120px;
    width: auto;
  }
  nav .nav-item {
    margin-left: 40px;
  }

</style>
