<?php
session_start();
require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

$team_id = $_SESSION['team_id'] ?? null;
$match_id = $_SESSION['current_match_id'] ?? null; // ✅ Pakai dari session

if (!$team_id) {
    echo "<div class='alert alert-danger'>Team ID tidak ditemukan di session.</div>";
    exit;
}

$match_id = (int)$_SESSION['current_match_id'];


date_default_timezone_set("Asia/Riyadh");
$now = new DateTime();
$server_time_str = $now->format('d M Y, H:i:s');

$players = [];

$res2 = $conn->query("SELECT player_name AS name FROM team_members_info WHERE team_id = $team_id");
while($r = $res2->fetch_assoc()) $players[] = $r['name'];

if (isset($_POST['save_players'])) {
    $conn->autocommit(FALSE);

    try {
        $used_players = []; 

        // --- 1. Handle lineup file upload ---
        if (isset($_FILES['lineup_file']) && $_FILES['lineup_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(__DIR__) . "/uploads/letter/lineup/";


            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES['lineup_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','png','jpeg','jpg'];
            if (!in_array($ext, $allowed)) {
                throw new Exception("Invalid file type. Only PDF, PNG, JPEG, JPG allowed.");
            }

            // ✅ Pakai match_id dari session, bukan dari POST
            $new_filename = "lineup_match" . intval($match_id) . "_team" . intval($team_id) . "_" . time() . "." . $ext;
            $target_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['lineup_file']['tmp_name'], $target_path)) {
                throw new Exception("Failed to upload lineup file!");
            }

            $stmt_letter = $conn->prepare("INSERT INTO lineup_letters (match_id, team_id, letter, uploaded_at) VALUES (?, ?, ?, NOW())");
            $letter_path = "uploads/letter/lineup/" . $new_filename;
            $stmt_letter->bind_param("iis", $match_id, $team_id, $letter_path);
            $stmt_letter->execute();
        } else {
            throw new Exception("Lineup file is required!");
        }

        // --- 2. Handle players insertion ---
        foreach ($_POST['pairs'] as $pair_id => $playerData) {
            if (count($playerData['main']) !== count(array_unique($playerData['main']))) {
                throw new Exception("A player cannot be repeated within the same pair!");
            }

            foreach (['main', 'sub'] as $type) {
                foreach ($playerData[$type] as $name) {
                    if (!empty($name)) {
                        if (in_array($name, $used_players)) {
                            throw new Exception("Player $name is already assigned in another pair for this match!");
                        }
                        $used_players[] = $name;
                    }
                }
            }

            $stmt = $conn->prepare("INSERT INTO pair_players (pair_id, player_name, status, created_at) VALUES (?, ?, ?, NOW())");
            foreach ($playerData['main'] as $name) {
                if (!empty($name)) {
                    $status = 'main';
                    $stmt->bind_param("iss", $pair_id, $name, $status);
                    $stmt->execute();
                }
            }
            foreach ($playerData['sub'] as $name) {
                if (!empty($name)) {
                    $status = 'substitute';
                    $stmt->bind_param("iss", $pair_id, $name, $status);
                    $stmt->execute();
                }
            }
        }

        $conn->commit();

        // ✅ OPTIONAL: Hapus session match_id kalau hanya digunakan sekali
        unset($_SESSION['current_match_id']);

        header("Location: ../dashboard.php");
        exit;


    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>Error: ".$e->getMessage()."</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/stylee.css?=v13">
</head>
<body>


<?php

// Ambil semua turnamen yang diikuti tim ini
$tournaments_res = $conn->query("
    SELECT DISTINCT t.id, t.name, t.status
    FROM tournaments t
    JOIN matches m ON m.tournament_id = t.id
    WHERE m.team1_id = $team_id OR m.team2_id = $team_id
    ORDER BY t.id ASC
");

$active_tournaments   = [];
$finished_tournaments = [];

while($t = $tournaments_res->fetch_assoc()){
    if(strtolower($t['status']) === 'completed'){
        $finished_tournaments[] = $t;
    } else {
        $active_tournaments[] = $t;
    }
}

// Ambil semua match aktif saja (tanpa pengecekan pairs, results, alerts)
$all_matches = [];

foreach($active_tournaments as $t){
    $matches_res = $conn->query("
        SELECT 
            m.id, 
            m.scheduled_date, 
            m.status,
            t1.team_name AS team1_name,
            t2.team_name AS team2_name,
            t.name AS tournament_name
        FROM matches m
        LEFT JOIN team_info t1 ON m.team1_id = t1.id
        LEFT JOIN team_info t2 ON m.team2_id = t2.id
        LEFT JOIN tournaments t ON m.tournament_id = t.id
        WHERE m.tournament_id = {$t['id']}
          AND (m.team1_id = $team_id OR m.team2_id = $team_id)
        ORDER BY m.scheduled_date ASC
    ");

    while($m = $matches_res->fetch_assoc()){
        $all_matches[] = $m;  // sekarang langsung push data match-nya aja
    }
}

// Kalau mau ambil semua match tanpa grouping di atas:
$matches_res = $conn->query("
    SELECT 
        m.id, 
        m.scheduled_date, 
        m.status,
        t1.team_name AS team1_name,
        t2.team_name AS team2_name
    FROM matches m
    LEFT JOIN team_info t1 ON m.team1_id = t1.id
    LEFT JOIN team_info t2 ON m.team2_id = t2.id
    WHERE m.team1_id = $team_id OR m.team2_id = $team_id
    ORDER BY m.scheduled_date ASC
");

$matches = [];
while ($m = $matches_res->fetch_assoc()) {
    $matches[] = $m;
}

// Hasil akhir: variabel yang bisa kamu pakai di UI
// $active_tournaments = turnamen yang belum selesai
// $finished_tournaments = turnamen yang sudah selesai
// $all_matches = list match dari turnamen aktif (dengan nama turnamen)
// $matches = list match langsung tanpa info turnamen (praktis untuk tabel langsung)

?>


<?php
$match_id = $_SESSION['current_match_id'] ?? null;

// Ambil detail match
$match = $conn->query("
    SELECT m.id, m.scheduled_date, m.status,
           t1.team_name AS team1_name,
           t2.team_name AS team2_name,
           t.name AS tournament_name
    FROM matches m
    LEFT JOIN team_info t1 ON m.team1_id = t1.id
    LEFT JOIN team_info t2 ON m.team2_id = t2.id
    LEFT JOIN tournaments t ON m.tournament_id = t.id
    WHERE m.id = $match_id
")->fetch_assoc();

// Ambil pair untuk match ini
$pairs_res = $conn->query("
    SELECT * FROM team_pairs 
    WHERE match_id = $match_id AND team_id = $team_id 
    ORDER BY pair_number ASC
");

$pairs_array = [];
while($p = $pairs_res->fetch_assoc()) {
    $pairs_array[] = $p;
}

// Ambil player list
$players = [];

$res2 = $conn->query("SELECT player_name AS name FROM team_members_info WHERE team_id = $team_id");
while($r = $res2->fetch_assoc()) $players[] = $r['name'];
?>

<?php
// Pastikan match_id dari session
$match_id = $_SESSION['current_match_id'] ?? null;
if(!$match_id){
    echo "<div class='alert alert-danger'>Match ID not found in session.</div>";
    exit;
}

// Ambil detail match
$match = $conn->query("
    SELECT m.id, m.scheduled_date, m.status,
           t1.team_name AS team1_name,
           t2.team_name AS team2_name,
           t.name AS tournament_name
    FROM matches m
    LEFT JOIN team_info t1 ON m.team1_id = t1.id
    LEFT JOIN team_info t2 ON m.team2_id = t2.id
    LEFT JOIN tournaments t ON m.tournament_id = t.id
    WHERE m.id = $match_id
")->fetch_assoc();

// Ambil pairs utk match ini (khusus tim login)
$pairs_res = $conn->query("
    SELECT id, match_id, team_id, pair_number
    FROM team_pairs
    WHERE match_id = $match_id AND team_id = $team_id
    ORDER BY pair_number ASC
");
$pairs_array = [];
while($p = $pairs_res->fetch_assoc()){
    $pairs_array[] = $p;
}

// Ambil daftar pemain tim ini
$players = [];

$res2 = $conn->query("SELECT player_name AS name FROM team_members_info WHERE team_id = $team_id");
while($r = $res2->fetch_assoc()) $players[] = $r['name'];

$usedPlayersDB = [];
$usedQ = $conn->query("
    SELECT pp.player_name 
    FROM pair_players pp
    JOIN team_pairs tp ON tp.id = pp.pair_id
    WHERE tp.match_id = $match_id AND tp.team_id = $team_id
");
while($u = $usedQ->fetch_assoc()){ $usedPlayersDB[] = $u['player_name']; }

$timezone = new DateTimeZone('Asia/Riyadh');
$now = new DateTime('now', $timezone);
?>

<section class="container mt-5 mb-5 py-5">
    <div id="scheduled" class="mb-5 text-dark">
    <h2 class="fw-bold">Team Lineup</h2>

    <p class="mb-2">
      <strong>Hello, Captain! 👋</strong><br>
      Since the match is approaching, you are required to complete and submit your team's official lineup here.  
    </p>

    <?php
        $query = "SELECT file_path FROM documents WHERE id = 1 LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $document = mysqli_fetch_assoc($result);
            $filePath = $document['file_path'];
        } else {
            $filePath = "#"; // fallback jika data tidak ditemukan
        }
    ?>
        
    <p class="mb-2">
        <a href="<?php echo htmlspecialchars($filePath); ?>" 
            class="btn btn-danger mt-2" 
            target="_blank">
            DOWNLOAD LINEUP
        </a>
        <br>
        Don’t forget the match won’t be valid until Lineups & Results sheets are UPLOADED.
    </p>


    <p class="text-muted small">Server Time: <?= $server_time_str ?></p>

    <?php if(!empty($finished_tournaments)): ?>
        <div class="alert alert-info mt-2">
            <?php foreach($finished_tournaments as $t): ?>
                Tournament <strong><?= htmlspecialchars($t['name']) ?></strong> has finished.<br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>


    <div class="row g-4 mt-5">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card shadow-sm border-0 h-100" style="border-radius:15px;">
                <div style="background-color:#696969" class="card-header text-dark d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><?= htmlspecialchars($match['tournament_name']) ?></span>
                    <span class="badge bg-light text-dark"><?= ucfirst($match['status']) ?></span>
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <?= htmlspecialchars($match['team1_name']) ?> VS <?= htmlspecialchars($match['team2_name']) ?>
                    </h5>
                    <p class="card-text mb-1"><strong>Scheduled:</strong> <?= date('d M Y, H:i', strtotime($match['scheduled_date'])) ?></p>
                    <p class="text-muted small mb-3">Server Time: <?= $server_time_str ?></p>
                    <p class="card-text"><strong>Match ID:</strong> <?= $match['id'] ?></p>

                    <!-- FORM LANGSUNG (tanpa modal) -->
                    <form method="post" enctype="multipart/form-data" class="mt-3 border p-3 rounded">

                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">

                        <!-- Upload Lineup File -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-danger">
                                📂 Upload Lineup Declaration Letter (PDF, PNG, JPEG, JPG)
                            </label>
                            <input type="file" name="lineup_file" class="form-control" accept=".pdf,.png,.jpeg,.jpg" required>
                        </div>

                        <?php foreach($pairs_array as $p): ?>
                            <?php
                                // cek jika pair ini sudah ada pemain tersimpan; kalau ada, boleh tetap tampil (user bisa re-submit) atau di-skip
                                // Di sini kita TAMPILKAN saja untuk kesederhanaan
                            ?>
                            <div class="pair-block border p-3 mb-3 rounded">
                                <h6>Pair <?= (int)$p['pair_number'] ?></h6>
                                <div class="mb-2">
                                    <label class="mb-1">Main Players</label>
                                    <?php for($i=0; $i<2; $i++): ?>
                                        <select name="pairs[<?= (int)$p['id'] ?>][main][]" class="form-select mb-2 player-select" required>
                                            <option value="" disabled selected hidden>-- Select Player <?= $i+1 ?> --</option>

                                            <?php foreach($players as $player): ?>
                                                <option 
                                                  value="<?= htmlspecialchars($player) ?>"
                                                  <?= in_array($player, $usedPlayersDB, true) ? 'disabled data-db-disabled="true"' : '' ?>
                                                >
                                                  <?= htmlspecialchars($player) ?>
                                                </option>

                                            <?php endforeach; ?>
                                        </select>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="confirmAll" onchange="document.getElementById('saveAllBtn').disabled = !this.checked;">
                            <label class="form-check-label" for="confirmAll">
                                I, as the appointed Team Captain, solemnly declare and confirm that the players I have selected and submitted for all pairs in this match are accurate, truthful, and final.
                                I acknowledge that I am fully responsible for ensuring that the chosen players participate exactly as entered in this form, without substitutions or changes.
                                I commit to act honestly, fairly, and in the spirit of sportsmanship, upholding the integrity of my team and the competition.
                            </label>
                        </div>

                        <button type="submit" name="save_players" id="saveAllBtn" class="btn btn-gold mt-3" disabled>
                            Save Players
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Script: disable pemain antar dropdown DI PAIR YANG SAMA SAJA -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const selects = document.querySelectorAll('select.player-select');

    function updateAllSelects() {
        // kumpulkan semua pemain yang sudah dipilih
        const chosen = [];
        selects.forEach(s => { if (s.value) chosen.push(s.value); });

        // update opsi tiap select
        selects.forEach(s => {
            const currentValue = s.value;
            Array.from(s.options).forEach(opt => {
                if (!opt.value) return; // skip option kosong

                if (chosen.includes(opt.value) && opt.value !== currentValue) {
                    opt.disabled = true;
                } else {
                    // jangan re-enable pemain yang sudah disabled dari DB (PHP)
                    if (!opt.hasAttribute('data-db-disabled')) {
                        opt.disabled = false;
                    }
                }
            });
        });
    }

    selects.forEach(sel => sel.addEventListener('change', updateAllSelects));
    updateAllSelects();
});
</script>



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
(async function autoTranslatePage() {
  const lang = localStorage.getItem("lang") || "en";
  if (lang !== "ar") return;

  // Set RTL layout
  document.documentElement.setAttribute("dir", "rtl");
  document.body.style.textAlign = "right";
  // ✅ Lock logo area agar tidak kena RTL & Translasi
const brand = document.querySelector(".navbar-brand");
if (brand) {
  brand.setAttribute("data-no-translate", "true");
  brand.style.direction = "ltr";
  brand.style.textAlign = "left";
}


  // Fixed translation for specific words (agar tidak ngawur)
  const customMap = {
    "League": "دوري",
    "LEAGUE": "دوري",
    "league": "دوري",
    "Leagues": "الدوريات",
    "Regist Team": "تسجيل الفريق",
    "Sponsors": "الرعاة",
    "Media": "وسائل الإعلام",
    "News": "الأخبار",
    "Club": "النادي"
  };

  // Ambil semua text node secara agresif tapi tetap aman
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
  const nodes = [];

  while (walker.nextNode()) {
    const node = walker.currentNode;
    const text = node.nodeValue.trim();

    if (!text) continue; // Skip kosong
    if (/^[\d\s\W]+$/.test(text)) continue; // Skip angka/simbol

    const parentTag = node.parentNode?.nodeName.toLowerCase();

    // ❌ Jangan translate teks dalam logo/icon
    if (["img", "svg", "script", "style"].includes(parentTag)) continue;

    nodes.push(node);
  }

  for (const node of nodes) {
    let original = node.nodeValue.trim();

    // Skip jika sudah ada huruf Arab (tidak re-translate)
    if (/[\u0600-\u06FF]/.test(original)) continue;

    if (customMap[original]) {
      node.nodeValue = customMap[original];
      continue;
    }

    try {
      const res = await fetch("/proxy.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "text=" + encodeURIComponent(original)
      });

      const data = await res.json();
      if (data?.translatedText) {
        node.nodeValue = data.translatedText;
      }
    } catch (e) {
      console.warn("Translate failed for:", original);
    }
  }
})();
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
