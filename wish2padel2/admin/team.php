<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

// (Opsional) Nyalakan error report saat debug
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// ===================
// Handle POST Actions
// ===================
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_level') {
        $team_id  = (int)($_POST['team_id'] ?? 0);
        $division = trim($_POST['division'] ?? '');
        $level    = trim($_POST['level'] ?? '');
    
        if ($team_id > 0) {
            $stmt = $conn->prepare("UPDATE team_contact_details SET division = ?, level = ? WHERE team_id = ?");
            $stmt->bind_param("ssi", $division, $level, $team_id);
            if ($stmt->execute()) {
                $flash = ["type" => "success", "msg" => "Division & Level updated successfully."];
            } else {
                $flash = ["type" => "danger", "msg" => "Failed to update: " . $stmt->error];
            }
            $stmt->close();
        } else {
            $flash = ["type" => "danger", "msg" => "Invalid Team ID."];
        }
    }


    if ($action === 'delete_team') {
    $team_id = (int)($_POST['team_id'] ?? 0);

    if ($team_id > 0) {
        $conn->begin_transaction();
        try {
            // 1) payment_transactions
            $stmt = $conn->prepare("DELETE FROM payment_transactions WHERE team_id = ?");
            $stmt->bind_param("i", $team_id);
            $stmt->execute(); $stmt->close();

            // 2) team_members_info
            $stmt = $conn->prepare("DELETE FROM team_members_info WHERE team_id = ?");
            $stmt->bind_param("i", $team_id);
            $stmt->execute(); $stmt->close();

            // 3) team_account
            $stmt = $conn->prepare("DELETE FROM team_account WHERE team_id = ?");
            $stmt->bind_param("i", $team_id);
            $stmt->execute(); $stmt->close();

            // 4) team_contact_details
            $stmt = $conn->prepare("DELETE FROM team_contact_details WHERE team_id = ?");
            $stmt->bind_param("i", $team_id);
            $stmt->execute(); $stmt->close();

            // ✅ 5) team_experience (tambahan)
            $stmt = $conn->prepare("DELETE FROM team_experience WHERE team_id = ?");
            $stmt->bind_param("i", $team_id);
            $stmt->execute(); $stmt->close();

            // 6) team_info
            $stmt = $conn->prepare("DELETE FROM team_info WHERE id = ?");
            $stmt->bind_param("i", $team_id);
            $stmt->execute(); $stmt->close();

            $conn->commit();
            $flash = ["type" => "success", "msg" => "Team and related records deleted successfully."];
        } catch (Throwable $e) {
            $conn->rollback();
            $flash = ["type" => "danger", "msg" => "Delete failed: " . $e->getMessage()];
        }
    } else {
        $flash = ["type" => "danger", "msg" => "Invalid Team ID."];
    }
}

}

// ========================
// Ambil filter dari GET
// ========================
$search        = $_GET['search']        ?? '';
$currentYear = date('Y');

$league_id = $_GET['league_id'] ?? '';

if ($league_id === '') {
    $sqlDefault = "SELECT id FROM league WHERE date = ? LIMIT 1";
    $stmtDefault = $conn->prepare($sqlDefault);
    $stmtDefault->bind_param("i", $currentYear);
    $stmtDefault->execute();
    $resDefault = $stmtDefault->get_result();
    if ($rowDefault = $resDefault->fetch_assoc()) {
        $league_id = $rowDefault['id'];
    }
    $stmtDefault->close();
}
$tournament_id = $_GET['tournament_id'] ?? '';
$team_id       = $_GET['team_id']       ?? '';

// ==========================
// Build query (basis: pt)
// ==========================
$where  = [];
$params = [];
$types  = '';

// Hanya tim yang ada di payment_transactions
// Join ke tabel lain mengikuti relasi team_id
$sql = "
WITH ranked_payments AS (
    SELECT 
        ti.id AS team_id,
        ti.team_name,
        ti.created_at,
        tcd.division AS division_id,      -- ✅ ambil ID divisinya
        d.division_name AS division_name, -- ✅ ambil nama divisinya
        tcd.club,
        tcd.city,
        tcd.notes,
        tcd.contact_phone,
        tcd.contact_email,
        ti.captain_name,
        ti.captain_phone,
        ti.captain_email,
        t.id AS tournament_id,
        t.name AS tournament_name,
        l.id AS league_id,
        l.name AS league_name,
        pt.status AS payment_status,
        ROW_NUMBER() OVER (
            PARTITION BY ti.id
            ORDER BY 
                CASE pt.status 
                    WHEN 'paid' THEN 1
                    WHEN 'pending' THEN 2
                    ELSE 3
                END
        ) AS rn
    FROM payment_transactions pt
    JOIN team_info ti                  ON pt.team_id = ti.id
    LEFT JOIN team_contact_details tcd ON tcd.team_id = ti.id
    LEFT JOIN divisions d              ON tcd.division = d.id   -- ✅ tetap JOIN ke tabel divisions
    LEFT JOIN tournaments t            ON pt.tournament_id = t.id
    LEFT JOIN league l                 ON t.id_league = l.id
)
SELECT * FROM ranked_payments";



if ($league_id !== '') {
    $where[]  = 'league_id = ?';   // ✅ Ganti pakai ID
    $params[] = $league_id;
    $types   .= 'i';
}
if ($tournament_id !== '') {
    $where[]  = 'tournament_id = ?';  // ✅ Ganti pakai ID
    $params[] = $tournament_id;
    $types   .= 'i';
}

if ($team_id !== '') {
    $where[]  = 'team_id = ?';
    $params[] = $team_id;
    $types   .= 'i';
}
if ($search !== '') {
    $where[]  = 'team_name LIKE ?';
    $params[] = "%$search%";
    $types   .= 's';
}

if (!empty($where)) {
    $sql .= " WHERE rn = 1 AND " . implode(' AND ', $where);
} else {
    $sql .= " WHERE rn = 1";
}

$sql .= " ORDER BY team_id ASC";


$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Dropdown data
$leagues = $conn->query("SELECT id, name, date FROM league ORDER BY name ASC");
if (!empty($league_id)) {
    // Filter Tournament berdasarkan League yang dipilih
    $tournaments = $conn->query("
        SELECT 
            t.id, 
            t.name AS tournament_name, 
            l.name AS league_name, 
            date AS league_year
        FROM tournaments t
        LEFT JOIN league l ON t.id_league = l.id
        WHERE l.id = {$league_id}
        ORDER BY t.name ASC
    ");
} else {
    // Kalau belum pilih league, tampilkan semua
    $tournaments = $conn->query("
        SELECT 
            t.id, 
            t.name AS tournament_name, 
            l.name AS league_name, 
            date AS league_year
        FROM tournaments t
        LEFT JOIN league l ON t.id_league = l.id
        ORDER BY t.name ASC
    ");
}



$teams       = $conn->query("SELECT id, team_name FROM team_info ORDER BY team_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
  <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
  <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Team - Wish2Padel</title>
  <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="background-color: #303030">

<?php require 'src/navbar.php' ?>

<section class="container p-4 text-white">
  <h2 class="mb-4">Manage Team</h2>

  <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <!-- Filter Form -->
  <form method="get" class="mb-3 row g-2">
    <div class="col-12 col-md-3">
      <select name="league_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- All Leagues --</option>
  <?php while($l = $leagues->fetch_assoc()): ?>
    <option value="<?= $l['id'] ?>" 
            <?= ($league_id == $l['id'] ? 'selected' : '') ?>>
        <?= htmlspecialchars($l['name']) ?> (<?= $l['date'] ?>)
    </option>
  <?php endwhile; ?>
            
        
      </select>
    </div>

    <div class="col-12 col-md-3">
  <select name="tournament_id" class="form-select" onchange="this.form.submit()">
  <option value="">-- All Zone --</option>
  <?php while($t = $tournaments->fetch_assoc()): ?>
    <option value="<?= $t['id'] ?>" <?= (string)$t['id'] === (string)$tournament_id ? 'selected' : '' ?>>
      <?= htmlspecialchars($t['tournament_name']) ?> (<?= $t['league_name'] ?> / <?= $t['league_year'] ?>)
    </option>
  <?php endwhile; ?>
</select>

</div>



    <div class="col-12 col-md-3">
      <select name="team_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- All Teams --</option>
        <?php while($tm = $teams->fetch_assoc()): ?>
          <option value="<?= $tm['id'] ?>" <?= (string)$tm['id'] === (string)$team_id ? 'selected' : '' ?>>
            <?= htmlspecialchars($tm['team_name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="col-12 col-md-3">
      <input type="text" name="search" id="searchInput" class="form-control"
             placeholder="Search Team Name..." value="<?= htmlspecialchars($search) ?>">
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th>League</th>
            <th>Zona</th>
            <th>Team Name</th>
            <th>Created At</th>
            <th>Division</th>
            <th>Club</th>
            <th>City</th>
            <th>Payment Status</th>
            <th style="width:170px;">Actions</th>
          </tr>
        </thead>
        <tbody id="registrationTable">
  <?php 
  $no=1; 
  $updateModals = []; 
  $detailModals = [];
  ?>

  <?php if ($result->num_rows > 0): ?>
    <?php while($r = $result->fetch_assoc()): ?>

      <tr>
        <td><?= htmlspecialchars($r['league_name'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['tournament_name'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['team_name'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['created_at'] ?? '-') ?></td>
        <td>
          <?= htmlspecialchars(($r['division_id'] ?? '')) ?>
          <?= isset($r['division_name']) ? ' - ' . htmlspecialchars($r['division_name']) : ' - No Division' ?>
        </td>
        <td><?= htmlspecialchars($r['club'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['city'] ?? '-') ?></td>
        <td>
          <?php
            $status = strtolower($r['payment_status'] ?? '-');
            $badge  = 'secondary';
            if ($status === 'paid' || $status === 'success') $badge = 'success';
            else if ($status === 'pending' || $status === 'process') $badge = 'warning';
            else if ($status === 'failed' || $status === 'cancelled') $badge = 'danger';
          ?>
          <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($r['payment_status'] ?? '-') ?></span>
        </td>
        <td>
          <button class="btn btn-sm btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#updateLevelModal<?= (int)$r['team_id'] ?>">Update Level/Division</button>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this team?');">
            <input type="hidden" name="action" value="delete_team">
            <input type="hidden" name="team_id" value="<?= (int)$r['team_id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger mb-1">Delete</button>
          </form>
          <button class="btn btn-sm btn-info mb-1" data-bs-toggle="modal" data-bs-target="#detailModal<?= (int)$r['team_id'] ?>">View</button>
        </td>
      </tr>

      <?php 
      $updateModals[] = $r; 
      $detailModals[] = $r; 
      ?>

    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="9" class="text-center text-muted">No data found.</td></tr>
  <?php endif; ?>
</tbody>
      </table>
      <?php 
// Reset pointer agar bisa loop lagi untuk modal
$result->data_seek(0); 
while($r = $result->fetch_assoc()): 
    $teamId = (int)$r['team_id'];
?>
    <!-- Modal: Update Level -->
    <div class="modal fade" id="updateLevelModal<?= $teamId ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">
            Update Division & Level - <?= htmlspecialchars($r['team_name'] ?? '-') ?>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="action" value="update_level">
          <input type="hidden" name="team_id" value="<?= $teamId ?>">

          <!-- Division Dropdown -->
          <div class="mb-3">
            <label class="form-label">Division</label>
            <select name="division" class="form-select" required>
              <option value="">-- Select Division --</option>
              <?php 
              $currentDivision = $r['division'] ?? ''; 
              $divisions = $conn->query("SELECT id, division_name FROM divisions ORDER BY id ASC");
              while ($div = $divisions->fetch_assoc()):
                $selected = ($currentDivision == $div['id']) ? 'selected' : '';
              ?>
                <option value="<?= $div['id'] ?>" <?= $selected ?>>
                  <?= $div['id'] ?> - <?= htmlspecialchars($div['division_name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
            <?php if($currentDivision): ?>
              <small class="text-muted">
                Current: <?= htmlspecialchars($currentDivision) ?> (<?= htmlspecialchars($r['division_name'] ?? '-') ?>)
              </small>
            <?php endif; ?>
          </div>

          <!-- Level Dropdown -->
          <div class="mb-3">
            <label class="form-label">Level</label>
            <select name="level" class="form-select" required>
              <option value="">-- Select Level --</option>
              <?php 
              $currentLevel = $r['level'] ?? '';
              $levels = [
                  'Advanced B+' => 'Advanced: B+ (4.5-4)',
                  'Advanced B'  => 'Advanced: B (4-4.5)',
                  'Advanced B-' => 'Advanced: B- (3.5-4)',
                  'U.Intermediate C+' => 'U.Intermediate: C+ (3-3.5)',
                  'Intermediate C' => 'Intermediate: C (2.5-3)',
                  'L. Intermediate C-' => 'L. Intermediate: C- (2-2.5)',
                  'U. Beginner D+' => 'U. Beginner: D+ (1.5-2)',
                  'Beginner D' => 'Beginner: D (1-1.5)',
                  'L. Beginner D-' => 'L. Beginner: D- (<1)'
              ];
              foreach ($levels as $value => $label):
                $selected = ($currentLevel == $value) ? 'selected' : '';
              ?>
                <option value="<?= $value ?>" <?= $selected ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
            <?php if($currentLevel): ?>
              <small class="text-muted">Current: <?= htmlspecialchars($currentLevel) ?></small>
            <?php endif; ?>
          </div>

        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>


    <!-- Modal: Detail Members -->
    <div class="modal fade" id="detailModal<?= $teamId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="margin-top:150px">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Team Details - <?= htmlspecialchars($r['team_name'] ?? '-') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p><strong>League:</strong> <?= htmlspecialchars($r['league_name'] ?? '-') ?></p>
                <p><strong>Tournament (Zona):</strong> <?= htmlspecialchars($r['tournament_name'] ?? '-') ?></p>
                <hr>
                <p><strong>Captain:</strong> <?= htmlspecialchars($r['captain_name'] ?? '-') ?> | <?= htmlspecialchars($r['captain_phone'] ?? '-') ?> | <?= htmlspecialchars($r['captain_email'] ?? '-') ?></p>
                <p><strong>Contact:</strong> <?= htmlspecialchars($r['contact_phone'] ?? '-') ?> | <?= htmlspecialchars($r['contact_email'] ?? '-') ?></p>
                <p><strong>Division:</strong> <?= htmlspecialchars($r['division_name'] ?? '-') ?></p>
                <p><strong>Club:</strong> <?= htmlspecialchars($r['club'] ?? '-') ?></p>
                <p><strong>City:</strong> <?= htmlspecialchars($r['city'] ?? '-') ?></p>
                <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($r['notes'] ?? '-')) ?></p>
                <hr>
                <p><strong>Members:</strong></p>
                <ul class="mb-0">
                    <?php
                      $stmt2 = $conn->prepare("SELECT player_name, role FROM team_members_info WHERE team_id = ?");
                      $stmt2->bind_param("i", $teamId);
                      $stmt2->execute();
                      $members = $stmt2->get_result();
                      if ($members->num_rows > 0) {
                          while($m = $members->fetch_assoc()){
                              echo "<li>" . htmlspecialchars($m['player_name']) . " (" . htmlspecialchars($m['role']) . ")</li>";
                          }
                      } else {
                          echo "<li>-</li>";
                      }
                      $stmt2->close();
                    ?>
                </ul>
              </div>
            </div>
        </div>
    </div>

<?php endwhile; ?>

    </div>
  </div>
</section>

<!-- Scroll to Top Button -->
<button id="scrollTopBtn" title="Go to top">↑</button>


<script>
  // Scroll to Top
  const scrollBtn = document.getElementById("scrollTopBtn");
  window.addEventListener('scroll', function() {
    scrollBtn.style.display = (document.documentElement.scrollTop > 200) ? "block" : "none";
  });
  scrollBtn.addEventListener("click", function() {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  // Search realtime di tabel (client-side)
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const val = this.value.toLowerCase();
      document.querySelectorAll('#registrationTable tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(val) ? '' : 'none';
      });
    });
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
