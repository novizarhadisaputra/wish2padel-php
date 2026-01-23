<?php
session_start();
require '../config.php';
$conn = getDBConnection();

$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

$team_id = $_SESSION['team_id'] ?? null;
if (!$team_id) {
    echo "<div class='alert alert-danger'>Team ID tidak ditemukan di session.</div>";
    exit;
}

date_default_timezone_set("Asia/Riyadh");
$now = new DateTime();
$server_time_str = $now->format('d M Y, H:i:s');

// --- Ambil daftar pemain ---
$players = [];
$res1 = $conn->query("SELECT username AS name FROM team_account WHERE team_id = $team_id");
while ($r = $res1->fetch_assoc()) $players[] = $r['name'];

$res2 = $conn->query("SELECT player_name AS name FROM team_members_info WHERE team_id = $team_id");
while ($r = $res2->fetch_assoc()) $players[] = $r['name'];

// --- Proses simpan skor ---
if (isset($_POST['save_scores'])) {
    $match_id = $_SESSION['current_match_id'] ?? null;
    if (!$match_id) {
        echo "<div class='alert alert-danger m-3'>Match ID tidak ditemukan dari session!</div>";
        exit;
    }

    $scores = $_POST['scores'] ?? [];

    try {
        $conn->autocommit(false);

        // ============================================================
        // 1. Upload Score Letter
        // ============================================================
        if (!isset($_FILES['score_file']) || $_FILES['score_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Score letter is required!");
        }

        $upload_dir = dirname(__DIR__) . "/uploads/letter/elimination/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['score_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'png', 'jpeg', 'jpg'];
        if (!in_array($ext, $allowed)) {
            throw new Exception("Invalid file type!");
        }

        $new_filename = "score_match{$match_id}_team{$team_id}_" . time() . "." . $ext;
        $target_path = $upload_dir . $new_filename;
        if (!move_uploaded_file($_FILES['score_file']['tmp_name'], $target_path)) {
            throw new Exception("Failed to upload file!");
        }
        $letter_path = "uploads/letter/elimination/" . $new_filename;

        // ============================================================
        // 2. Insert/Update Pair Scores
        // ============================================================
        $stmt = $conn->prepare("
            INSERT INTO pair_scores (match_id, pair_id, set_number, team_id, team_score, is_winner)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                team_score = VALUES(team_score),
                is_winner = VALUES(is_winner)
        ");

        foreach ($scores as $pair_id => $sets) {
            foreach ($sets as $set_number => $data) {
                if (!empty($data['score'])) {
                    $parts = explode('-', $data['score']);
                    if (count($parts) === 2) {
                        $score_self  = (int) trim($parts[0]);
                        $score_enemy = (int) trim($parts[1]);
                        $winner_self = ($score_self > $score_enemy) ? 1 : 0;

                        $stmt->bind_param("iiiiii", $match_id, $pair_id, $set_number, $team_id, $score_self, $winner_self);
                        $stmt->execute();
                    }
                }
            }
        }

        // ============================================================
        // 3. Ambil info match dan divisi team
        // ============================================================
        $res_team = $conn->query("
            SELECT d.division AS division_id, m.journey, m.notes
            FROM team_contact_details d
            JOIN matches m ON m.id = $match_id
            WHERE d.team_id = $team_id
        ");
        if ($res_team->num_rows === 0) {
            throw new Exception("Team division/journey data not found!");
        }
        $team_info = $res_team->fetch_assoc();
        $division_id = (int)$team_info['division_id'];
        $journey     = (int)$team_info['journey'];
        $notes       = $team_info['notes']; // <- Cek notes

$pointsTable = [
    1 => [650,604,558,511,464,418,372,372,418,464,511,558,604,650], // Advanced B
    2 => [540,501,463,424,386,348,309,309,348,386,424,463,501,540], // Intermediate A
    3 => [450,417,386,354,321,289,257,257,289,321,354,386,417,450], // Intermediate B
    4 => [375,348,320,294,268,241,214,214,241,268,294,320,348,375], // Developing
];

$pairs_won = 0;
$pairs_lost = 0;

$pairs_res = $conn->query("SELECT id FROM team_pairs WHERE match_id = $match_id AND team_id = $team_id");
if (!$pairs_res || $pairs_res->num_rows === 0) {
    throw new Exception("No team pairs found for this match!");
}

while ($pair = $pairs_res->fetch_assoc()) {
    $pair_id = (int)$pair['id'];

    // Hitung jumlah set dimenangkan oleh pair ini
    $res_set = $conn->query("
        SELECT SUM(is_winner) AS won_sets 
        FROM pair_scores 
        WHERE match_id = $match_id AND pair_id = $pair_id AND team_id = $team_id
    ")->fetch_assoc();
    $won_sets = (int)$res_set['won_sets'];

    // =======================================================
    // CEK JENIS MATCH
    // =======================================================
    if (!empty($notes)) {
        // === PLAYOFF ===
        if ($won_sets >= 2) $pairs_won++;
        else $pairs_lost++;
        continue; // tidak beri poin individu
    }

    // === REGULAR SEASON ===
    if ($journey > 14) {
        // Journey di atas 14, tidak beri poin individu lagi
        if ($won_sets >= 2) $pairs_won++;
        else $pairs_lost++;
        continue;
    }

    // =======================================================
    // JIKA MASIH REGULAR SEASON DAN JOURNEY <= 14
    // =======================================================
    if ($won_sets >= 2) {
        $pairs_won++;
        $pair_point = $pointsTable[$division_id][$journey - 1] ?? 0;
    } elseif ($won_sets === 1) {
        $pairs_lost++;
        $pair_point = 15;
    } else {
        $pairs_lost++;
        $pair_point = 10;
    }

    // =======================================================
    // UPDATE POINT UNTUK MASING-MASING PEMAIN DALAM PAIR
    // =======================================================
    if ($pair_point > 0) {
        $res_players = $conn->query("SELECT player_name FROM pair_players WHERE pair_id = $pair_id");
        while ($p = $res_players->fetch_assoc()) {
            $player_name = $conn->real_escape_string($p['player_name']);
            $conn->query("
                UPDATE team_members_info 
                SET point = COALESCE(point, 0) + $pair_point 
                WHERE team_id = $team_id AND player_name = '$player_name'
            ");
        }
    }
}

        // ============================================================
        // 5. Update/Insert match_results
        // ============================================================
        $check_res = $conn->query("SELECT id FROM match_results WHERE match_id = $match_id AND team_id = $team_id");
        if ($check_res->num_rows > 0) {
            $stmt_result = $conn->prepare("
                UPDATE match_results
                SET pairs_won = ?, 
                    pairs_lost = ?, 
                    letter = ?, 
                    status = 'accept', 
                    updated_at = NOW()
                WHERE match_id = ? AND team_id = ?
            ");
            $stmt_result->bind_param("iissi", $pairs_won, $pairs_lost, $letter_path, $match_id, $team_id);
        } else {
            $stmt_result = $conn->prepare("
                INSERT INTO match_results 
                    (match_id, team_id, pairs_won, pairs_lost, status, letter, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'accept', ?, NOW(), NOW())
            ");
            $stmt_result->bind_param("iiiis", $match_id, $team_id, $pairs_won, $pairs_lost, $letter_path);
        }
        $stmt_result->execute();

        // ============================================================
        // 6. Commit & redirect
        // ============================================================
        $conn->commit();
        unset($_SESSION['current_match_id']);
        header("Location: ../dashboard.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger m-3'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
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
    $all_matches = [];
    
    foreach($active_tournaments as $t){
        $matches_res = $conn->query("
            SELECT 
                m.id, m.scheduled_date, m.status,
                t1.team_name AS team1_name,
                t2.team_name AS team2_name,
                t.name AS tournament_name
            FROM matches m
            LEFT JOIN team_info t1 ON m.team1_id = t1.id
            LEFT JOIN team_info t2 ON m.team2_id = t2.id
            LEFT JOIN tournaments t ON m.tournament_id = t.id
            WHERE m.tournament_id = ".$t['id']."
              AND (m.team1_id = $team_id OR m.team2_id = $team_id)
            ORDER BY m.scheduled_date ASC
        ");
    
        while($m = $matches_res->fetch_assoc()){
            $pairs_result = $conn->query("
                SELECT * 
                FROM team_pairs 
                WHERE match_id = ".$m['id']." 
                  AND team_id = $team_id 
                ORDER BY pair_number ASC
            ");
    
            $has_pending  = false;
            $pairs_array  = [];
    
            while($p = $pairs_result->fetch_assoc()){
                // cek apakah pair punya player
                $players_check = $conn->query("
                    SELECT COUNT(*) AS total 
                    FROM pair_players 
                    WHERE pair_id = ".$p['id']
                );
                $total_players = $players_check->fetch_assoc()['total'];
    
                if($total_players == 0){
                    $has_pending = true;
                }
    
                $pairs_array[] = $p;
            }
    
            // simpan ke list match
            $all_matches[] = [
                'data'        => $m,
                'pairs'       => $pairs_array,
                'has_pending' => $has_pending
            ];
        }
    }
    
    
    $matches_res = $conn->query("
    SELECT m.id, m.scheduled_date, m.status, 
           t1.team_name AS team1, t2.team_name AS team2
    FROM matches m
    LEFT JOIN team_info t1 ON m.team1_id = t1.id
    LEFT JOIN team_info t2 ON m.team2_id = t2.id
    WHERE m.team1_id = $team_id OR m.team2_id = $team_id
    ORDER BY m.scheduled_date ASC
");

while ($m = $matches_res->fetch_assoc()) {
    $scheduled_time = new DateTime($m['scheduled_date'], $timezone);

    // hitung selisih menit
    $diff_minutes = ($scheduled_time->getTimestamp() - $now->getTimestamp()) / 60;
    $elapsed_minutes = ($now->getTimestamp() - $scheduled_time->getTimestamp()) / 60;

    // cek status match_results utk tim ini
    $res_check = $conn->query("
        SELECT status 
        FROM match_results 
        WHERE match_id={$m['id']} AND team_id=$team_id
        ORDER BY updated_at DESC LIMIT 1
    ")->fetch_assoc();

    $status_result = $res_check['status'] ?? null;

   
}

?>



<?php
date_default_timezone_set("Asia/Riyadh");
$now = new DateTime();
$server_time_str = $now->format('d M Y, H:i:s');

$match_id = $_SESSION['current_match_id'] ?? null;
if (!$match_id) {
    header("Location: ../login.php");
    exit;
}

// Ambil data match tunggal
$m = $conn->query("
    SELECT m.id, m.scheduled_date, m.status,
           t1.team_name AS team1_name,
           t2.team_name AS team2_name,
           tour.name AS tournament_name
    FROM matches m
    LEFT JOIN team_info t1 ON m.team1_id = t1.id
    LEFT JOIN team_info t2 ON m.team2_id = t2.id
    LEFT JOIN tournaments tour ON m.tournament_id = tour.id
    WHERE m.id = $match_id
")->fetch_assoc();

if (!$m) {
    echo "<div class='alert alert-danger'>Match not found.</div>";
    exit;
}

$pairs = [];
$pairs_res = $conn->query("
    SELECT id, pair_number
    FROM team_pairs
    WHERE match_id = $match_id AND team_id = $team_id
    ORDER BY pair_number ASC
");
while($p = $pairs_res->fetch_assoc()) $pairs[] = $p;

$res_check = $conn->query("
    SELECT status FROM match_results
    WHERE match_id = $match_id AND team_id = $team_id
    ORDER BY updated_at DESC LIMIT 1
")->fetch_assoc();
$status_result = $res_check['status'] ?? null;

$scheduled_time = new DateTime($m['scheduled_date']);
$scheduled_time->setTimezone(new DateTimeZone('Asia/Riyadh'));
?>

<section class="container mt-5 mb-5 py-5 text-black" id="scores-section">
    <h2 class="fw-bold">Match Scores</h2>
<p class="mb-2">
  <strong>Hello, Captain! 👋</strong><br>
  This page is provided for you to officially submit the results of your completed match.  
  Please upload the signed <strong>Match Result Letter</strong> as valid proof of the final score.
</p>
<p>
  Make sure both teams review and agree on the result before submitting.  
  Any unclear, incomplete, or conflicting scores may be rejected or require revalidation.
</p>

    <p class="text-muted small">Server Time: <?= $server_time_str ?></p>

    <div class="card shadow-sm border-0 p-4" style="border-radius:15px;">
        <div style="background-color:#696969; color:#F3E6B6" class="card-header d-flex justify-content-between align-items-center">
            <p class="text-bold mb-0"><?= htmlspecialchars($m['tournament_name']) ?></p>
            <span class="badge bg-light text-dark"><?= htmlspecialchars($m['status']) ?></span>
        </div>
        <div class="card-body mt-3 mb-3">
            <h2 class="fw-bold mt-3 mb-3">
                <?= htmlspecialchars($m['team1_name']) ?> vs <?= htmlspecialchars($m['team2_name']) ?>
            </h2>
            <p class="card-text mb-3">
                <strong>Match ID:</strong> <?= $m['id'] ?><br>
                <strong>Scheduled:</strong> <?= $scheduled_time->format('d M Y, H:i') ?>
            </p>

            <div class="mb-3">
                <strong>Pairs & Players:</strong>
                <ul class="list-unstyled mt-2">
                    <?php foreach($pairs as $p): ?>
                        <?php
                        $players = [];
                        $players_result = $conn->query("
                            SELECT player_name FROM pair_players WHERE pair_id = {$p['id']} ORDER BY id ASC
                        ");
                        while($pl = $players_result->fetch_assoc()) $players[] = $pl['player_name'];
                        ?>
                        <li>
                            <span class="fw-bold">Pair <?= $p['pair_number'] ?>:</span>
                            <?= !empty($players) ? implode(' & ', $players) : '<span class="text-danger">[Players not assigned]</span>' ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($status_result === 'accept'): ?>
                <div class="alert alert-success mt-3">Scores already accepted ✅</div>
            <?php else: ?>

                <!-- FORM ASLI DIPINDAHKAN KELUAR TANPA MODAL -->
                <form method="post" enctype="multipart/form-data">

                    <input type="hidden" name="match_id" value="<?= $m['id'] ?>">

                    <div class="mb-4">
                        <label for="scoreFile<?= $m['id'] ?>" class="form-label fw-bold text-danger">
                            📂 Upload Match Result / Score (PDF, PNG, JPEG, JPG)
                        </label>
                        <input type="file" name="score_file" id="scoreFile<?= $m['id'] ?>"
                               class="form-control"
                               accept=".pdf,.png,.jpeg,.jpg" required>
                        <div class="form-text">
                            Upload the completed & signed minutes sheet as official proof.
                        </div>
                    </div>

                    <?php foreach($pairs as $p): ?>
    <div class="border p-3 mb-3" style="border-radius:10px;">
        <h6>Pair <?= $p['pair_number'] ?></h6>

      <?php for($set = 1; $set <= 3; $set++): ?>
  <div class="mb-2 set-wrapper set-<?= $set ?>">
      <label>Set <?= $set ?> <small class="text-muted">(Home - Away)</small></label>
      <input type="text" 
             name="scores[<?= $p['id'] ?>][<?= $set ?>][score]" 
             class="form-control score-input <?= ($set <= 2 ? 'set-input' : 'set3') ?>" 
             placeholder="Home-Away (e.g. 6-4)"
             pattern="^\d+-\d+$"
             maxlength="4"
             title="Format: HOME-AWAY (Left = Home, Right = Away)">
      <small class="text-muted">Example: <strong>6-4</strong> means Home scored 6, Away scored 4.</small>
      <div class="invalid-feedback">⚠️ Incomplete format. Please use the format <strong>6-4</strong>.</div>
  </div>
<?php endfor; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const inputs = document.querySelectorAll('.score-input');

  inputs.forEach(input => {
    input.addEventListener('input', () => {
      const value = input.value.trim();
      // Regex checks if both sides of the dash are numbers (e.g. 6-4)
      const valid = /^\d+-\d+$/.test(value);

      if (value && !valid) {
        // Mark as invalid if partially filled (like "6-" or "-4")
        input.classList.add('is-invalid');
      } else {
        // Remove invalid style when corrected
        input.classList.remove('is-invalid');
      }
    });
  });
});
</script>


    </div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.border').forEach(pairBlock => {
    const setInputs = pairBlock.querySelectorAll('.set-input'); // Set 1 & 2
    const set3Wrapper = pairBlock.querySelector('.set3').closest('.set-wrapper'); // Set 3

    set3Wrapper.style.display = 'none'; // Default hide

    function checkSets() {
        const s1 = setInputs[0].value.trim();
        const s2 = setInputs[1].value.trim();

        if (!s1.match(/^\d+-\d+$/) || !s2.match(/^\d+-\d+$/)) {
            set3Wrapper.style.display = 'none';
            return;
        }

        const [a1, b1] = s1.split('-').map(Number);
        const [a2, b2] = s2.split('-').map(Number);

        const winner1 = a1 > b1 ? 'A' : 'B';
        const winner2 = a2 > b2 ? 'A' : 'B';

        if (winner1 !== winner2) {
            set3Wrapper.style.display = 'block';
        } else {
            set3Wrapper.style.display = 'none';
        }
    }

    setInputs.forEach(input => input.addEventListener('input', checkSets));
});
</script>


                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirmScores<?= $m['id'] ?>" onchange="document.getElementById('saveBtn<?= $m['id'] ?>').disabled = !this.checked;">
                        <label class="form-check-label" for="confirmScores<?= $m['id'] ?>">
                            <strong>TEAM CAPTAIN’S DECLARATION OF RESPONSIBILITY</strong><br>
                            I, as the <strong>Team Captain</strong>, hereby declare that all match scores I have entered are <strong>truthful, accurate, and made in good faith</strong>.
                        </label>
                    </div>

                    <button type="submit" name="save_scores" class="btn btn-gold w-100" id="saveBtn<?= $m['id'] ?>" disabled>
                        Save Scores
                    </button>

                </form>
            <?php endif; ?>
        </div>
    </div>
</section>


<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.score-input').forEach(input => {
        input.addEventListener('input', function () {
            let val = this.value.replace(/[^0-9-]/g, '');
            if (val.length === 1 && !val.includes('-')) val = val + '-';
            let match = val.match(/^(\d{1,2})-?(\d{0,2})$/);
            if (match) {
                val = match[1] + (match[2] !== undefined ? '-' + match[2] : '-');
            } else {
                val = val.slice(0, val.length - 1);
            }
            this.value = val;
        });
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
