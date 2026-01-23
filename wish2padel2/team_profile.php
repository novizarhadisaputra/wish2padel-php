<?php
session_start();
require 'config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Team Profile - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/stylee.css?=v12">
</head>
<body>

<?php require 'src/navbar.php' ?>

<?php
// Dapatkan team_id dari query param
$team_id = $_GET['id'] ?? 1;

// Ambil data tim sekaligus tournament_id
$team_sql = "
    SELECT ti.id, ti.team_name, ti.captain_name, ti.logo, ti.created_at, ti.tournament_id, t.name AS tournament_name
    FROM team_info ti
    LEFT JOIN tournaments t ON ti.tournament_id = t.id
    WHERE ti.id = ?
";
$stmt = $conn->prepare($team_sql);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$team = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$team) {
    echo "<div class='alert alert-danger'>Team not found.</div>";
    exit;
}

// Nama tournament bisa langsung pakai $team['tournament_name']
$tournament_name = $team['tournament_name'] ?? '-';


// Ambil contact details
$contact_sql = "
    SELECT 
        tcd.club, tcd.city, tcd.division, tcd.notes,
        d.division_name
    FROM team_contact_details tcd
    LEFT JOIN divisions d ON tcd.division = d.id
    WHERE tcd.team_id = ?";

$stmt = $conn->prepare($contact_sql);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$contact = $stmt->get_result()->fetch_assoc();

?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background:#303030;
    color:black;
    margin:0; padding:0;
}
.container {
    max-width:1100px;
    margin:auto;
    padding:1rem;
}
.section-title {
    font-weight:700;
    font-size:1.6rem;
    border-bottom:3px solid #fff9c4;
    padding-bottom:0.3rem;
    margin-bottom:1.5rem;
    color:black;
}
.card-info {
    background:#fff;
    border-radius:12px;
    padding:2rem;
    box-shadow:0 3px 12px rgba(0,0,0,0.1);
    margin-bottom:2rem;
}
.info-row {
    display:flex;
    justify-content:space-between;
    padding:0.6rem 0;
    border-bottom:1px solid #eee;
    transition: background 0.3s ease;
}
.info-row:last-child { border-bottom:none; }
.info-row:hover { background:#fff9c4; }
.info-label { font-weight:600; color:black; }
.info-value { font-weight:600; color:black; text-align:right; }
.table-members {
    width:100%;
    border-collapse:collapse;
    margin-top:1rem;
}
.table-members th, .table-members td {
    border:1px solid #ddd;
    padding:0.7rem;
    text-align:left;
}
.table-members th {
    background:#fff9c4;
    color:#000;
}
.table-members tbody tr:hover {
    background:#fff8e1;
    transform:scale(1.01);
    transition:0.2s;
}
</style>


<div class="container mt-5">
    <!-- Profil Tim -->

    <section class="card-info">
        <h2 class="section-title">Team Profile</h2>
        <img src="uploads/logo/<?= htmlspecialchars($team['logo']); ?>" 
         alt="<?= htmlspecialchars($team['team_name']); ?>" 
         style="width:150px; height:150px; object-fit:contain; background-color:#fff; border-radius:8px; padding:3px;">

        <div class="info-row"><div class="info-label">Tournament</div><div class="info-value"><?= htmlspecialchars($team['tournament_name']); ?></div></div>
        <div  class="info-row"><div class="info-label">Team Name</div><div class="info-value"><?= htmlspecialchars($team['team_name']); ?></div></div>
        <div class="info-row"><div class="info-label">Captain</div><div class="info-value"><?= htmlspecialchars($team['captain_name']); ?></div></div>
        <div class="info-row"><div class="info-label">Created At</div><div class="info-value"><?= date('j M Y', strtotime($team['created_at'])); ?></div></div>
        <div class="info-row"><div class="info-label">Club</div><div class="info-value"><?= htmlspecialchars($contact['club'] ?? '-'); ?></div></div>
        <div class="info-row"><div class="info-label">City</div><div class="info-value"><?= htmlspecialchars($contact['city'] ?? '-'); ?></div></div>
        <div class="info-row">
    <div class="info-label">Division</div>
    <div class="info-value">
        <?= htmlspecialchars($contact['division'] . " – " . ($contact['division_name'] ?? '-')) ?>
    </div>
</div>

        <div id="profile" class="info-row"><div class="info-label">Notes</div><div class="info-value"><?= htmlspecialchars($contact['notes'] ?? '-'); ?></div></div>
    </section>

<?php
$team_id = $_GET['id'] ?? null;

if (!$team_id) {
    echo "<p>Team not found (no id given).</p>";
    exit;
}
$sql_members = "SELECT player_name, age, profile, role, position 
                FROM team_members_info 
                WHERE team_id = ? 
                ORDER BY (role = 'Captain') DESC, player_name ASC";
$stmt = $conn->prepare($sql_members);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$res_members = $stmt->get_result();
$final_members = $res_members ? $res_members->fetch_all(MYSQLI_ASSOC) : [];
?>

<?php
// --- MATCH HISTORY SECTION ---
// Ambil semua match berdasarkan team_id
$sql_schedule = "
    SELECT 
        m.id, m.journey, m.scheduled_date, m.status,
        t1.team_name AS team1, t1.logo AS team1_logo, m.team1_id,
        t2.team_name AS team2, t2.logo AS team2_logo, m.team2_id
    FROM matches m
    LEFT JOIN team_info t1 ON m.team1_id = t1.id
    LEFT JOIN team_info t2 ON m.team2_id = t2.id
    WHERE m.team1_id = ? OR m.team2_id = ?
    ORDER BY m.scheduled_date ASC
";
$stmt = $conn->prepare($sql_schedule);
$stmt->bind_param("ii", $team_id, $team_id);
$stmt->execute();
$resSchedule = $stmt->get_result();
?>

<section class="card-info">
    <h2 class="section-title">Match History</h2>

    <?php if ($resSchedule->num_rows === 0): ?>
        <p class="text-muted">No matches available for this team.</p>
    <?php else: ?>
        <?php while ($m = $resSchedule->fetch_assoc()):
            
            $dateStr = date("l, d M Y, H:i", strtotime($m['scheduled_date']));
            $isHome = ($m['team1_id'] == $team_id);

            $logo1 = !empty($m['team1_logo']) ? "uploads/logo/".$m['team1_logo'] : "uploads/logo/default.png";
            $logo2 = !empty($m['team2_logo']) ? "uploads/logo/".$m['team2_logo'] : "uploads/logo/default.png";

            // Default score
            $scoreText = "0 - 0";
            $team1Badge = $team2Badge = "";

            // Jika completed, ambil hasil dari match_results
            if ($m['status'] === "completed") {
                $res1 = $conn->query("SELECT pairs_won FROM match_results WHERE match_id = {$m['id']} AND team_id = {$m['team1_id']} LIMIT 1")->fetch_assoc();
                $res2 = $conn->query("SELECT pairs_won FROM match_results WHERE match_id = {$m['id']} AND team_id = {$m['team2_id']} LIMIT 1")->fetch_assoc();
                
                if ($res1 && $res2) {
                    $scoreText = "{$res1['pairs_won']} - {$res2['pairs_won']}";

                    if ($res1['pairs_won'] > $res2['pairs_won']) {
                        $team1Badge = '<span class="badge bg-success ms-1">WIN</span>';
                        $team2Badge = '<span class="badge bg-danger ms-1">LOSS</span>';
                    } elseif ($res1['pairs_won'] < $res2['pairs_won']) {
                        $team1Badge = '<span class="badge bg-danger ms-1">LOSS</span>';
                        $team2Badge = '<span class="badge bg-success ms-1">WIN</span>';
                    } else {
                        $team1Badge = $team2Badge = '<span class="badge bg-secondary ms-1">DRAW</span>';
                    }
                }
            }

            $badgeClass = "bg-secondary";
            if ($m['status'] === "completed") $badgeClass = "bg-success";
            elseif ($m['status'] === "scheduled") $badgeClass = "bg-primary";
            elseif ($m['status'] === "pending") $badgeClass = "bg-warning text-dark";
        ?>

        <a href="match?id=<?= (int)$m['id'] ?>" class="text-decoration-none text-dark d-block mb-3">
            <div class="p-3 border rounded bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small text-muted">Journey <?= (int)$m['journey'] ?></div>
                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($m['status']) ?></span>
                </div>

                <div class="d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <img src="<?= htmlspecialchars($logo1) ?>" 
             class="me-2"
             style="width:45px; height:45px; object-fit:contain; background-color:#fff; border-radius:50%; padding:3px;">
        <div>
            <strong><?= htmlspecialchars($m['team1']) ?> <?= $team1Badge ?></strong>
            <div class="text-muted small"><?= $isHome ? 'Your Team' : 'Home' ?></div>
        </div>
    </div>

    <div class="text-center">
        <div class="fw-bold" style="font-size:1.4rem;"><?= $scoreText ?></div>
        <div class="small text-muted"><?= $dateStr ?></div>
    </div>

    <div class="d-flex align-items-center text-end">
        <div class="me-2">
            <strong><?= htmlspecialchars($m['team2']) ?> <?= $team2Badge ?></strong>
            <div class="text-muted small"><?= !$isHome ? 'Your Team' : 'Away' ?></div>
        </div>
        <img src="<?= htmlspecialchars($logo2) ?>" 
             style="width:45px; height:45px; object-fit:contain; background-color:#fff; border-radius:50%; padding:3px;">
    </div>
</div>

            </div>
        </a>

        <?php endwhile; ?>
    <?php endif; ?>
</section>

<style>
.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.member-card {
    padding: 18px;
    border-radius: 12px;
    background: #f8f9fa;
    border: 1px solid #eee;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.member-card img {
    width: 120px;
    height: 120px;
    border-radius: 8px; /* Tidak bulat */
    object-fit: cover;
    margin-bottom: 12px;
}

.member-name {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 4px;
}

.member-role {
    font-size: 14px;
    color: #444;
    margin-bottom: 6px;
}

.member-meta {
    font-size: 14px;
    color: #777;
}
</style>

<section class="card-info">
    <h2 class="section-title mb-3">Team Members</h2>
    <div class="members-grid">
        <?php foreach($final_members as $m): ?>
            <div class="member-card">
                <img src="<?= $m['profile'] ? '../uploads/profile/'.htmlspecialchars($m['profile']) : '../uploads/profile/default.png' ?>">
                <div class="member-name"><?= htmlspecialchars($m['player_name']) ?></div>
                <div class="member-role"><?= htmlspecialchars($m['role'] ?: '—') ?></div>
                <div class="member-meta">
                    <?= $m['position'] ? htmlspecialchars($m['position']) : '—' ?> 
                    • Age: <?= $m['age'] ?: '—' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>


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
    const hero = document.getElementById('profile'); // Pastikan ada elemen heroCarousel di halaman

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


<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
