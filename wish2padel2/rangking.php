<?php
session_start();
require 'config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

$team_id = $_SESSION['team_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Rangking - Padel League</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/stylee.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

</head>
<body style="background-color:#303030">

<?php require 'src/navbar.php' ?>

<?php
// Ambil filter dari query string
$gender_filter = $_GET['gender'] ?? 'Pria';
$search_name = $_GET['search_name'] ?? '';

// Ambil data semua match per pemain dengan sets_played
$sql = "
    SELECT pp.id AS player_id, pp.player_name, tm.gender, t.team_name,
           ps.match_id, ps.team_score, ps.is_winner,
           (SELECT COUNT(*) FROM pair_scores ps2 WHERE ps2.match_id = ps.match_id AND ps2.pair_id = ps.pair_id) AS sets_played
    FROM pair_players pp
    INNER JOIN team_members_info tm ON pp.id = tm.id
    INNER JOIN team_pairs tp ON pp.pair_id = tp.id
    INNER JOIN pair_scores ps ON tp.id = ps.pair_id
    INNER JOIN team_info t ON tp.team_id = t.id
    WHERE tm.gender = ?
";

$params = [$gender_filter];
$types = "s";

if (!empty($search_name)) {
    $sql .= " AND pp.player_name LIKE ?";
    $params[] = "%$search_name%";
    $types .= "s";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);

// Hitung point per match dan total per pemain
$leaderboard = [];
foreach ($rows as $row) {
    $pid = $row['player_id'];
    $point_per_player = $row['team_score'] / 2 / max($row['sets_played'],1); // point proporsional
    if (!isset($leaderboard[$pid])) {
        $leaderboard[$pid] = [
            'player_name' => $row['player_name'],
            'team_name' => $row['team_name'],
            'point_match_total' => 0,
            'match_won' => 0,
            'match_lost' => 0,
            'total_matches' => 0
        ];
    }
    $leaderboard[$pid]['point_match_total'] += $point_per_player;
    $leaderboard[$pid]['match_won'] += $row['is_winner'];
    $leaderboard[$pid]['match_lost'] += (1 - $row['is_winner']);
    $leaderboard[$pid]['total_matches'] += 1;
}

// Urutkan berdasarkan point_match_total
usort($leaderboard, function($a,$b){
    return $b['point_match_total'] <=> $a['point_match_total'];
});
?>

<div class="container" style="color:white">
    <!-- Judul Leaderboard -->
    <div class="mt-5">
        <h3 class="fw-bold">Ranking Players</h3>
        <small>Updated at: <?= date("d M Y") ?></small>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body"  style="background-color:#303030">
            <!-- Filters -->
            <form method="get" class="row g-2 mb-4">
                <div class="col-auto">
                    <select name="gender" class="form-select">
                        <option value="Pria" <?= $gender_filter=='Pria'?'selected':'' ?>>Male</option>
                        <option value="Wanita" <?= $gender_filter=='Wanita'?'selected':'' ?>>Female</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" name="search_name" class="form-control" placeholder="Search player" value="<?= htmlspecialchars($search_name) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn" style="background-color:#FFC107; font-weight:700">Filter</button>
                </div>
            </form>

            <!-- Leaderboard Table -->
            <div class="table-responsive">
                <table class="table table-striped mt-4 table-bordered text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Team</th>
                            <th>Point Match</th>
                            <th>Match Won</th>
                            <th>Match Lost</th>
                            <th>Total Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach($leaderboard as $row): 
                            $point_match = round($row['point_match_total'],1);
                            $total_points = $point_match; // total points = sum of point per match
                        ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= htmlspecialchars($row['player_name']) ?></td>
                                <td><?= htmlspecialchars($row['team_name']) ?></td>
                                <td><?= $point_match ?></td>
                                <td><?= $row['match_won'] ?></td>
                                <td><?= $row['match_lost'] ?></td>
                                <td><?= $total_points ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($leaderboard)): ?>
                            <tr><td colspan="7">No players found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


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
    const hero = document.getElementById('scheduleList'); // Pastikan ada elemen heroCarousel di halaman

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

<style>
  /* Navbar default (sudah ada background dan shadow dari kamu) */
  nav#maiavbar {

    width: 100%;
    transition: all 0.3s ease;
    z-index: 9999;
  }

  /* Navbar jadi fixed dan muncul dengan animasi */
  nav#maiavbar.navbar-fixed {
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(90deg, #00796B, #004D40);
    box-shadow: 0 3px 8px rgba(0,0,0,0.25);
    animation: fadeInDown 0.4s ease forwards;
  }

  @keyframes fadeInDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
