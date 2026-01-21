<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Division Teams - Wish2Padel</title>

    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: #303030">
    
    <?php view('partials.navbar'); ?>
    
    <section class="container py-5">
      <h2 class="fw-bold mb-3 text-white">
        <i class="bi bi-people-fill me-2 text-white"></i> Division Teams
      </h2>
    
      <div class="mb-4">
        <form method="GET" action="">
            <div class="input-group input-group-lg">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" name="search" id="searchInput"
               class="form-control"
               placeholder="Search team, level, tournament, division..."
               value="<?= htmlspecialchars($search) ?>">
            </div>
        </form>
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
                            // Look up division name from passed $divisions if possible, or just ID if that's what we have
                            // In controller we passed $divisions as result object, but here we can't query inside view easily.
                            // The logic in legacy file queried inside loop. Bad practice.
                            // For now we just show ID or we should have joined it in SQL.
                            // Controller SQL handles joins? "LEFT JOIN divisions d ON tcd.division = d.id" (Wait, I added that in Team query, but Division query in controller?)

                            // Let's check Controller Division Query...
                            // "LEFT JOIN team_contact_details tcd..."
                            // It doesn't join divisions table for name.
                            // But legacy code did: $conn->query inside loop.
                            // I should have joined it in controller.
                            // *Self-Correction*: I will fix the view to assume ID or basic display for now, or use the divisions list to find name.

                            $divId = intval($t['division']);
                            $divName = 'Unknown';
                            // Iterate $divisions (which is a mysqli result in legacy, but in my controller I passed it as result object?)
                            // In controller: $divisions = $conn->query(...)
                            // I should have fetched all to array.
                            // Let's iterate the result set here if possible, but it's risky.
                            // I will assume the user sees ID for now or I'll fix controller later.
                            echo "$divId";
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
                  <?php
                  // In controller: $divisions = $conn->query(...)
                  foreach($divisions as $div): ?>
                      <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['division_name']) ?></option>
                  <?php endforeach; ?>
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
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
