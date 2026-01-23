<?php
session_start();
require 'config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

$team_id = $_SESSION['team_id'] ?? null;
if (!$team_id) {
    echo "<div class='alert alert-danger'>Team ID tidak ditemukan di session.</div>";
    exit;
}

$players = [];
$res1 = $conn->query("SELECT username AS name FROM team_account WHERE team_id = $team_id");
while($r = $res1->fetch_assoc()) $players[] = $r['name'];

$res2 = $conn->query("SELECT player_name AS name FROM team_members_info WHERE team_id = $team_id");
while($r = $res2->fetch_assoc()) $players[] = $r['name'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Match Schedule & Result - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/stylee.css?=v12">
</head>
<body>

<?php require 'src/navbar.php' ?>


<?php
// Ambil semua match untuk team ini (baik scheduled maupun completed)
$matches_res = $conn->query("
    SELECT 
        m.id, m.journey, m.status, m.scheduled_date,
        m.team1_id, m.team2_id,
        t1.team_name AS team1_name, t1.logo AS team1_logo,
        t2.team_name AS team2_name, t2.logo AS team2_logo,
        tour.name AS tournament_name
    FROM matches m
    LEFT JOIN team_info t1 ON m.team1_id = t1.id
    LEFT JOIN team_info t2 ON m.team2_id = t2.id
    LEFT JOIN tournaments tour ON m.tournament_id = tour.id
    WHERE m.team1_id = $team_id OR m.team2_id = $team_id
    ORDER BY m.scheduled_date ASC
");
?>

<section class="mt-4 container">
    <div class="row justify-content-center">
        <div class="col-lg-10 mt-4 mb-5">

            <h4 class="fw-bold mb-4 text-center text-uppercase" style="letter-spacing:1px;">
                Schedule & Results
            </h4>

            <?php if ($matches_res->num_rows === 0): ?>
                <div class="alert alert-info text-center py-3">
                    No matches available for your team.
                </div>
            <?php endif; ?>

            <?php 
            $highlightSet = false;
            while ($m = $matches_res->fetch_assoc()):
                $dateStr = date("l, d M Y, H:i", strtotime($m['scheduled_date']));
                $logo1 = !empty($m['team1_logo']) ? "uploads/logo/".$m['team1_logo'] : "uploads/logo/default.png";
                $logo2 = !empty($m['team2_logo']) ? "uploads/logo/".$m['team2_logo'] : "uploads/logo/default.png";

                $team1Badge = $team2Badge = "";
                if ($m['status'] === 'completed') {
                    // Ambil hasil dari kedua tim
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
                } else {
                    $scoreText = "0 - 0";
                }

                $badgeClass = match($m['status']) {
                    'completed' => 'bg-success',
                    'pending'   => 'bg-warning text-dark',
                    'scheduled' => 'bg-primary',
                    default     => 'bg-secondary'
                };

                $lineup = $conn->query("SELECT letter FROM lineup_letters WHERE match_id = {$m['id']} AND team_id = $team_id ORDER BY uploaded_at DESC LIMIT 1")->fetch_assoc()['letter'] ?? null;
                $result = $conn->query("SELECT letter FROM match_results WHERE match_id = {$m['id']} AND team_id = $team_id ORDER BY updated_at DESC LIMIT 1")->fetch_assoc()['letter'] ?? null;

                $isNext = (!$highlightSet && $m['status'] != 'completed');
                if ($isNext) $highlightSet = true;
            ?>

            <a href="match?id=<?= $m['id'] ?>" class="text-decoration-none text-dark d-block mb-3">
                <div class="p-3 rounded shadow-sm match-card <?= $isNext ? 'next-match' : '' ?>">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-dark">Journey <?= $m['journey'] ?></span>
                        <span class="badge <?= $badgeClass ?> rounded-pill px-3"><?= ucfirst($m['status']) ?></span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <!-- Team 1 -->
                        <div class="d-flex align-items-center">
                            <img src="<?= $logo1 ?>" style="width:45px;height:45px;object-fit:contain;background:#fff;">
                            <div class="ms-2">
                                <strong><?= $m['team1_name'] ?> <?= $team1Badge ?></strong><br>
                                <small class="text-muted"><?= ($m['team1_name'] == $team_name) ? 'Your Team' : 'Home' ?></small>
                            </div>
                        </div>

                        <!-- Score & Date -->
                        <div class="text-center px-2">
                            <div class="score-box fw-bold mb-1"><?= $scoreText ?></div>
                            <div class="small text-muted"><?= $dateStr ?></div>
                        </div>

                        <!-- Team 2 -->
                        <div class="text-end d-flex align-items-center">
                            <div class="me-2">
                                <strong><?= $m['team2_name'] ?> <?= $team2Badge ?></strong><br>
                                <small class="text-muted"><?= ($m['team2_name'] == $team_name) ? 'Your Team' : 'Away' ?></small>
                            </div>
                            <img src="<?= $logo2 ?>" style="width:45px;height:45px;object-fit:contain;background:#fff;">
                        </div>
                    </div>
                </div>
            </a>

            <!-- Action Buttons -->
            <div class="d-flex mb-4 gap-2">
                <?php if ($lineup): ?>
                    <a href="<?= $lineup ?>" target="_blank" class="btn btn-sm btn-outline-primary flex-fill">📄 Lineup</a>
                <?php else: ?>
                    <button class="btn btn-sm btn-outline-secondary flex-fill" disabled>Lineup N/A</button>
                <?php endif; ?>

                <?php if ($m['status'] === 'completed'): ?>
                    <?php if ($result): ?>
                        <a href="<?= $result ?>" target="_blank" class="btn btn-sm btn-outline-success flex-fill">📄 Result</a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary flex-fill" disabled>Result N/A</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php endwhile; ?>

        </div>
    </div>
</section>



<style>
.match-card {
    background:#fff;
    border:1px solid #eee;
    transition:all .2s ease-in-out;
}
.match-card:hover {
    transform:translateY(-3px);
    box-shadow:0 6px 15px rgba(0,0,0,.12);
}
.next-match {
    border:2px solid #f3e6b6 !important;
    background:#fff5f5;
}
.score-box {
    background:#222;
    color:#fff;
    padding:5px 14px;
    border-radius:6px;
    font-size:1.2rem;
    display:inline-block;
}
</style>






<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.rank-row').forEach((row, index) => {
            row.style.opacity = 0;
            row.style.transform = 'translateX(-30px)';
            setTimeout(() => {
                row.style.opacity = 1;
                row.style.transform = 'translateX(0)';
                row.style.transition = 'all 0.5s ease';
            }, index * 200);
        });
    });
</script>

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
        // Cegah Enter langsung submit form
        document.addEventListener("keydown", function(e) {
            if (e.key === "Enter" && e.target.tagName === "INPUT") {
                e.preventDefault();

                // Cari input berikutnya
                const inputs = Array.from(document.querySelectorAll("input"));
                const index = inputs.indexOf(e.target);
                if (index > -1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
        });
    </script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('maiavbar');
    const hero = document.getElementById('about-liga'); // Pastikan ada elemen heroCarousel di halaman

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

<script>
document.addEventListener('DOMContentLoaded', function() {

    function updateAllSelects() {
        const allSelects = document.querySelectorAll('.modal select');

        // Kumpulkan semua pemain yang sudah dipilih di semua modal
        let selectedPlayers = [];
        allSelects.forEach(s => {
            if(s.value) selectedPlayers.push(s.value);
        });

        // Update opsi setiap select
        allSelects.forEach(s => {
            const currentValue = s.value;
            Array.from(s.options).forEach(opt => {
                if(opt.value === "") return; // opsi kosong selalu aktif
                if(opt.value === currentValue) {
                    opt.disabled = false; // biarkan nilai saat ini tetap dipilih
                } else {
                    opt.disabled = selectedPlayers.includes(opt.value);
                }
            });

            // Update tombol save per modal
            const modal = s.closest('.modal');
            const pairId = modal.id.replace('addPlayersModal','');
            const saveBtn = document.getElementById('saveBtn' + pairId);
            
            const selectsInModal = modal.querySelectorAll('select');
            const values = Array.from(selectsInModal).map(x => x.value).filter(x => x);
            const hasDuplicate = (new Set(values).size !== values.length);
            const hasEmpty = values.length !== selectsInModal.length;

            saveBtn.disabled = hasDuplicate || hasEmpty;
        });
    }

    // Pasang event listener ke semua select
    document.querySelectorAll('.modal select').forEach(sel => {
        sel.addEventListener('change', updateAllSelects);
    });

    // Jalankan sekali saat load
    updateAllSelects();

});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
