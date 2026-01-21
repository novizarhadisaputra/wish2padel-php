<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
  <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Team - Wish2Padel</title>
  <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="background-color: #303030">

<?php view('partials.navbar'); ?>

<section class="container p-4 text-white">
  <h2 class="mb-4">Manage Team</h2>

  <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <!-- Filter Form -->
  <form method="get" class="mb-3 row g-2" action="<?= asset('admin/team') ?>">
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
        <?php while($tm = $teams_list->fetch_assoc()): ?>
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
          <button class="btn btn-sm btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#updateLevelModal<?= (int)$r['team_id'] ?>">Update Level</button>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this team?');" action="<?= asset('admin/team') ?>">
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
              <form method="post" action="<?= asset('admin/team') ?>">
                <div class="modal-header">
                  <h5 class="modal-title">Update Division & Level - <?= htmlspecialchars($r['team_name'] ?? '-') ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="action" value="update_level">
                  <input type="hidden" name="team_id" value="<?= $teamId ?>">
        
                  <div class="mb-3">
                      <label class="form-label">Division</label>
                      <select name="division" class="form-select" required>
                        <option value="">-- Select Division --</option>
                        <?php foreach($divisions_all as $div): ?>
                            <option value="<?= $div['id'] ?>" <?= ($r['division_id'] ?? '') == $div['id'] ? 'selected' : '' ?>>
                                <?= $div['id'] ?> - <?= htmlspecialchars($div['division_name']) ?>
                            </option>
                        <?php endforeach; ?>
                      </select>
                  </div>
                  
                  <div class="mb-3">
                      <label class="form-label">Level</label>
                      <select name="level" class="form-select" required>
                        <option value="">-- Select Level --</option>
                        <?php 
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
                        foreach($levels as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($r['level'] ?? '') == $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                      </select>
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
                      // Using global connection here because logic is embedded in view loop.
                      // Ideally this should be eager loaded.
                      $conn = getDBConnection();
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
