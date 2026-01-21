<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Wish2Padel</title>
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: #303030;">
    <?php view('partials.navbar'); ?>

    <div class="container py-5">
        <h2 class="text-white mb-4"><i class="bi bi-people-fill text-primary"></i> User Management</h2>

        <!-- Team Accounts -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Team Accounts</h5>
                <span class="badge bg-secondary"><?= $teams->num_rows ?> Teams</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Team Name</th>
                                <th>Username</th>
                                <th>Team ID</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($team = $teams->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($team['team_name']) ?></td>
                                <td><?= htmlspecialchars($team['username']) ?></td>
                                <td><?= $team['team_id'] ?></td>
                                <td>
                                    <form method="POST" action="<?= asset('admin/impersonate') ?>" onsubmit="return confirm('Are you sure you want to log in as this team?');">
                                        <input type="hidden" name="id" value="<?= $team['team_id'] ?>">
                                        <input type="hidden" name="type" value="team">
                                        <button class="btn btn-sm btn-warning fw-bold">
                                            <i class="bi bi-box-arrow-in-right"></i> Login As
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Admin/System Users -->
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">System Users</h5>
                <span class="badge bg-secondary"><?= $users->num_rows ?> Users</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><span class="badge bg-<?= $u['role']=='admin'?'danger':'info' ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td>
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="<?= asset('admin/impersonate') ?>" onsubmit="return confirm('Login as this user?');">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="type" value="user">
                                        <button class="btn btn-sm btn-warning fw-bold">
                                            <i class="bi bi-box-arrow-in-right"></i> Login As
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Current Session</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
