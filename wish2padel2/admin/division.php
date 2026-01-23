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
    $divisions = $conn->query("SELECT id, division_name FROM divisions ORDER BY id ASC");
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_id'], $_POST['division'])) {
        $team_id = intval($_POST['team_id']);
        $division = intval($_POST['division']);
    
        $stmt = $conn->prepare("UPDATE team_contact_details SET division = ? WHERE team_id = ?");
        $stmt->bind_param('ii', $division, $team_id);
        $stmt->execute();
        $stmt->close();
    
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $search = $_GET['search'] ?? '';
    
    $orderBy = "
      ORDER BY
        CASE 
          WHEN tcd.division IS NULL OR tcd.division = '' THEN 0 
          ELSE 1 
        END ASC,
        tcd.level ASC,
        ti.id ASC
    ";
    
    if ($search) {
        $stmt = $conn->prepare("
            SELECT 
                ti.id AS team_id,
                ti.team_name,
                t.name AS tournament_name,
                tcd.level,
                tcd.division,
                te.experience,
                te.competed,
                te.regional
            FROM team_info ti
            LEFT JOIN tournaments t ON ti.tournament_id = t.id
            LEFT JOIN team_contact_details tcd ON ti.id = tcd.team_id
            LEFT JOIN team_experience te ON ti.id = te.team_id
            INNER JOIN payment_transactions pt 
                ON pt.team_id = ti.id 
                AND pt.tournament_id = ti.tournament_id
                AND pt.status = 'paid'
            WHERE ti.team_name LIKE ?
            $orderBy
        ");
        $like = "%$search%";
        $stmt->bind_param('s', $like);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                ti.id AS team_id,
                ti.team_name,
                t.name AS tournament_name,
                tcd.level,
                tcd.division,
                te.experience,
                te.competed,
                te.regional
            FROM team_info ti
            LEFT JOIN tournaments t ON ti.tournament_id = t.id
            LEFT JOIN team_contact_details tcd ON ti.id = tcd.team_id
            LEFT JOIN team_experience te ON ti.id = te.team_id
            INNER JOIN payment_transactions pt 
                ON pt.team_id = ti.id 
                AND pt.tournament_id = ti.tournament_id
                AND pt.status = 'paid'
            $orderBy
        ");
    }
    
    
    $stmt->execute();
    $result = $stmt->get_result();
    $teams = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Division Teams - Wish2Padel</title>

    <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: #303030">
    
    <?php require 'src/navbar.php' ?>
    
    <section class="container py-5">
      <h2 class="fw-bold mb-3 text-white">
        <i class="bi bi-people-fill me-2 text-white"></i> Division Teams
      </h2>
    
      <div class="mb-4">
        <div class="input-group input-group-lg">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" id="searchInput" 
           class="form-control" 
           placeholder="Search team, level, tournament, division..." 
           value="<?= htmlspecialchars($search) ?>">
    
        </div>
      </div>
    
      <div class="card shadow-sm">
        <div class="card-body table-responsive">
          <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
              <tr>
                <th scope="col">Team Name</th>
                <th scope="col">Tournament</th>
                <th scope="col">Division</th>
                <th scope="col">Experience</th>
                <th scope="col">Competed</th>
                <th scope="col">Regional</th>
                <th scope="col" class="text-center">Action</th>
              </tr>
            </thead>
            <tbody id="teamTable">
              <?php foreach($teams as $t): ?>
                <?php
                  $hasDivision = !empty($t['division']);
                  $hasExperience = !empty($t['experience']);
                  if ($hasDivision && !$hasExperience) continue;
                ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($t['team_name']) ?></td>
                    <td><?= htmlspecialchars($t['tournament_name'] ?? '-') ?></td>
                    <td>
                      <?php
                        if (!empty($t['division'])) {
                            $divId = intval($t['division']);
                            $divNameRes = $conn->query("SELECT division_name FROM divisions WHERE id = $divId LIMIT 1");
                            $divName = $divNameRes->fetch_assoc()['division_name'] ?? 'Unknown';
                            echo "$divId – " . htmlspecialchars($divName);
                        } else {
                            echo '<span class="text-muted">No Division</span>';
                        }
                      ?>
                    </td>
            
                    <td>
                        <?php 
                        $exp = $t['experience'] ?? '-';
                        echo $exp !== '-' ? "How much time your team member played padel: " . htmlspecialchars($exp) : '<span class="text-muted">-</span>';
                        ?>
                    </td>
            
                    <td>
                        <?php 
                        $comp = $t['competed'] ?? '-';
                        echo $comp !== '-' ? "Have you ever competed?: " . htmlspecialchars($comp) : '<span class="text-muted">-</span>';
                        ?>
                    </td>
            
                    <td>
                        <?php 
                        $reg = $t['regional'] ?? '-';
                        echo $reg !== '-' ? "Do you compete in regional tournaments?: " . htmlspecialchars($reg) : '<span class="text-muted">-</span>';
                        ?>
                    </td>
            
                    <td class="text-center">
                        <?php if (empty($t['division'])): ?>
                            <button class="btn btn-sm btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#divisionModal"
                                onclick="setTeamId(<?= $t['team_id'] ?>)">
                                Set Division
                            </button>
                        <?php else: ?>
                        <span class="text-success">Done</span>
                        <?php endif; ?>
                    </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            
          </table>
        </div>
      </div>
    </section>
    
    <div class="modal fade" id="divisionModal" tabindex="-1" aria-labelledby="divisionModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="margin-top:150px">
        <form method="POST">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Set Division</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="team_id" id="team_id_input">
              <label class="form-label">Select Division</label>
              <select name="division" class="form-select" required>
                  <option value="">-- Select Division --</option>
                  <?php while($div = $divisions->fetch_assoc()): ?>
                      <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['division_name']) ?></option>
                  <?php endwhile; ?>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success">Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>
    
    
    <script>
        function setTeamId(id) {
          document.getElementById('team_id_input').value = id;
        }
        
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', () => {
          const val = searchInput.value.toLowerCase();
          document.querySelectorAll('#teamTable tr').forEach(row => {
            const rowText = row.innerText.toLowerCase();
            row.style.display = rowText.includes(val) ? '' : 'none';
          });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
