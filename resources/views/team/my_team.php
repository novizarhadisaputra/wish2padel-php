<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Team - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
      .transfer-window-box{
        background: linear-gradient(135deg,#1a1a1a,#222);
        border-left:4px solid #f3e6b6;
        border-right:4px solid #f3e6b6;
        border-radius:12px;
        padding:22px 18px;
        box-shadow:0 0 16px rgba(243,230,182,.25);
      }
      .transfer-window-box .title{
        color:#F3E6B6;font-weight:800;letter-spacing:.5px;margin-bottom:4px;
      }
      .transfer-window-box .date{
        color:#e5e5e5;font-size:.98rem;margin-bottom:10px;
      }
      .btn-gold{background-color:#F3E6B6;font-weight:700;border:1px solid #b58f20;color:#000;}
      .btn-gold:hover{filter:brightness(.95);}
      .team-details-section { background-color:#303030; }
      .text-gold { color:#88694A; }
      .card-header.bg-dark { background-color:#1a1a1a !important; color:#F3E6B6; }
      .card-body.bg-white { background-color:#f9f9f9 !important; color:#000; }
      .card { border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.12); transition:box-shadow .3s ease, transform .2s ease; }
      .card:hover { box-shadow:0 6px 15px rgba(0,0,0,0.18); transform: translateY(-2px); }
      .member-card { background-color:#f9f9f9; border-left:4px solid #f3e6b6; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.12); transition:transform .2s ease, box-shadow .2s ease; }
      .member-card:hover { transform: translateY(-3px); box-shadow:0 6px 12px rgba(0,0,0,0.15); }
    </style>
</head>
<body>

<?php view('partials.navbar'); ?>

<section class="team-details-section py-5">
  <div class="container">

    <div class="text-center text-white mb-4">
      <h2 class="fw-bold text">Team Profile</h2>
      <p class="mb-0">Official Information & Player Roster</p>
    </div>

    <?php if ($activeWindow): 
        $s = date("M d, Y H:i", strtotime($activeWindow['start_date']));
        $e = date("M d, Y H:i", strtotime($activeWindow['end_date']));
    ?>
      <div class="transfer-window-box mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between">
          <div class="mb-2 mb-lg-0">
            <div class="title"><?= htmlspecialchars($activeWindowLabel) ?> — TRANSFER WINDOW OPEN</div>
            <div class="date">You may add or adjust team members from <strong><?= $s ?></strong> to <strong><?= $e ?></strong>.</div>
          </div>
          <div>
            <button id="scrollToMembers" class="btn btn-gold">
              Manage Squad
            </button>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($tournament['status']) && $tournament['status'] === 'completed'): ?>
      <div class="alert alert-info text-center p-4 rounded shadow-sm">
        <h4 class="fw-bold mb-2">🏆 Tournament Completed</h4>
        <p class="mb-0">
          The tournament zone <strong><?= htmlspecialchars($tournament['name']) ?></strong> has ended in <?= htmlspecialchars($tournament['league_year']) ?>.
        </p>
      </div>
    <?php else: ?>

      <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-dark text-gold">
          <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Team Info</h5>
        </div>
        <div class="card-body bg-white text-dark">
          <?php if (!empty($team_info['logo'])): ?>
            <img src="<?= asset('uploads/logo/'.htmlspecialchars($team_info['logo'])) ?>" alt="Team Logo" class="mb-3" style="width:150px;height:150px;object-fit:contain;border-radius:8px;">
          <?php else: ?>
            <div class="border border-secondary rounded p-3 mb-3 d-inline-block" style="width:150px;height:150px;display:flex;align-items:center;justify-content:center;">
              <span>No Logo Yet</span>
            </div>
          <?php endif; ?>
          <p><strong>Team Name:</strong> <?= htmlspecialchars($team_info['team_name']) ?></p>
          <p><strong>Captain Name:</strong> <?= htmlspecialchars($team_info['captain_name']) ?></p>
          <p><strong>Captain Phone:</strong> <?= htmlspecialchars($team_info['captain_phone']) ?></p>
          <p><strong>Contact Email:</strong> <?= htmlspecialchars($team_contact['contact_email'] ?? '-') ?></p>
          <p><strong>Division:</strong> <?= !empty($team_contact['division']) ? htmlspecialchars($team_contact['division']) : '-' ?></p>
          <p><strong>Level:</strong> <?= htmlspecialchars($team_contact['level'] ?? '-') ?></p>
          <p><strong>Club:</strong> <?= htmlspecialchars($team_contact['club'] ?? '-') ?></p>

          <button type="button" class="btn btn-gold btn-sm mt-3 fw-bold" data-bs-toggle="modal" data-bs-target="#updateTeamModal">
            Update Info
          </button>
        </div>
      </div>

      <div class="modal fade" id="updateTeamModal" tabindex="-1" aria-labelledby="updateTeamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
              <div class="modal-header">
                <h5 class="modal-title" id="updateTeamModalLabel">Update Team Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label>Team Name</label>
                  <input type="text" name="team_name" class="form-control" value="<?= htmlspecialchars($team_info['team_name']) ?>" required>
                </div>
                <div class="mb-3">
                  <label>Captain Name</label>
                  <input type="text" name="captain_name" readonly class="form-control" value="<?= htmlspecialchars($team_info['captain_name']) ?>" required>

                </div>
                <div class="mb-3">
                  <label>Captain Phone</label>
                  <input type="text" name="captain_phone" class="form-control" value="<?= htmlspecialchars($team_info['captain_phone']) ?>" required>
                </div>
                <div class="mb-3">
                  <label>Captain Email</label>
                  <input type="email" name="captain_email" class="form-control" value="<?= htmlspecialchars($team_info['captain_email']) ?>" required>
                </div>
                <div class="mb-3">
                  <label>Logo</label>
                  <input type="file" name="team_logo" class="form-control form-control-sm">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="update_team" class="btn btn-gold" style="color:white">Save</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0" id="team-members">
        <div class="card-header bg-dark text-gold d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-people me-2"></i>Team Members</h5>
          <?php if ($canEditMembers): ?>
            <button type="button" class="btn btn-sm btn-gold fw-bold" data-bs-toggle="modal" data-bs-target="#addMemberModal">
              <i class="bi bi-person-plus"></i> Add Member
            </button>
          <?php endif; ?>
        </div>

        <div class="card-body bg-white">
          <div class="row g-4">
            <?php foreach ($team_members as $member): ?>
              <div class="col-md-4">
                <div class="card member-card h-100">
                  <div class="card-body text-dark">
                    <?php if (!empty($member['profile'])): ?>
                      <div class="mb-3 text-center">
                        <img src="<?= asset('uploads/profile/'.htmlspecialchars($member['profile'])) ?>" alt="Profile Picture" class="rounded-circle" style="width:100px;height:100px;object-fit:cover;">
                      </div>
                    <?php endif; ?>
                    <h6 class="card-title text-gold fw-bold mb-2"><?= htmlspecialchars($member['player_name']) ?></h6>
                    <p class="mb-1"><strong>Role:</strong> <?= htmlspecialchars($member['role']) ?></p>
                    <p class="mb-1"><strong>Age:</strong> <?= htmlspecialchars($member['age'] ?? '-') ?></p>
                    <p class="mb-1"><strong>Position:</strong> <?= htmlspecialchars($member['position'] ?? '-') ?></p>
                    <small class="text-muted">Joined: <?= htmlspecialchars($member['joined_at']) ?></small>
                  </div>
                  <?php if ($canEditMembers): ?>
                    <div class="card-footer d-flex justify-content-between">
                      <button class="btn btn-sm" style="background-color:#D4EDDA;"
                              data-bs-toggle="modal" data-bs-target="#memberModal"
                              data-id="<?= $member['id'] ?>"
                              data-name="<?= htmlspecialchars($member['player_name']) ?>"
                              data-age="<?= htmlspecialchars($member['age'] ?? '-') ?>"
                              data-position="<?= htmlspecialchars($member['position'] ?? '-') ?>">
                        <i class="bi bi-pencil-square"></i> Update Profile
                      </button>
                      <form method="POST" class="m-0">
                        <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                        <button type="submit" name="delete_member" class="btn btn-sm" style="background-color:#F3E6B6">
                          <i class="bi bi-trash"></i> Remove
                        </button>
                      </form>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
              <div class="modal-header">
                <h5 class="modal-title" id="memberModalLabel">Update Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="member_id" id="modalMemberId">
                <div class="mb-3">
                  <label>Member Name</label>
                  <input type="text" name="player_name" id="modalMemberName" class="form-control">
                </div>
                <div class="mb-3">
                  <label>Age</label>
                  <input type="text" name="age" id="modalMemberage" class="form-control">
                </div>
                <div class="mb-3">
                  <label>Position</label>
                  <input type="text" name="position" id="modalMemberposition" class="form-control">
                </div>
                <div class="mb-3">
                  <label>Profile Picture</label>
                  <input type="file" name="profile" class="form-control" accept="image/*">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="update_member" class="btn btn-gold" style="color:white">Save</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>

        <div class="modal fade" id="addMemberModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Add New Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
        
              <div class="modal-body">
                <div class="mb-3">
                  <label>Player Name</label>
                  <input type="text" name="player_name" class="form-control" placeholder="e.g. Cristiano Ronaldo" required>
                </div>
        
                <div class="mb-3">
                  <label>Age</label>
                  <input type="number" name="age" class="form-control" placeholder="e.g. 28">
                </div>
        
                <div class="mb-3">
                  <label>Position</label>
                  <input type="text" name="position" class="form-control" placeholder="e.g. Right-Handed / Left-Handed">
                </div>
        
                <div class="mb-3">
                  <label>Profile Picture</label>
                  <input type="file" name="profile" class="form-control" accept="image/*">
                </div>
              </div>
        
              <div class="modal-footer">
                <button type="submit" name="add_member" class="btn btn-gold">Save</button>
              </div>
            </form>
          </div>
        </div>

    <?php endif; ?>
  </div>
</section>

<?php view('partials.footer'); ?>

<button id="scrollTopBtn" title="Go to top">↑</button>

<script>
  const scrollBtn = document.getElementById("scrollTopBtn");
  window.addEventListener('scroll', () => {
    scrollBtn.style.display = (document.documentElement.scrollTop > 200) ? "block" : "none";
  });
  scrollBtn.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));

  document.addEventListener('DOMContentLoaded', function () {
    var memberModal = document.getElementById('memberModal');
    if (memberModal) {
      memberModal.addEventListener('show.bs.modal', function (event) {
        var button   = event.relatedTarget;
        var id       = button.getAttribute('data-id');
        var name     = button.getAttribute('data-name');
        var age      = button.getAttribute('data-age');
        var position = button.getAttribute('data-position');

        document.getElementById('modalMemberId').value = id;
        document.getElementById('modalMemberName').value = name || '';
        document.getElementById('modalMemberage').value = age || '';
        document.getElementById('modalMemberposition').value = position || '';
        document.getElementById('memberModalLabel').textContent = "Update " + (name || '');
      });
    }

    var scrollBtnManage = document.getElementById('scrollToMembers');
    if (scrollBtnManage) {
      scrollBtnManage.addEventListener('click', function(){
        const section = document.getElementById('team-members');
        if (section) section.scrollIntoView({ behavior:'smooth', block:'start' });
      });
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
