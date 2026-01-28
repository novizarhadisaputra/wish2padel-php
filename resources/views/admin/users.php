<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Wish2Padel</title>
    <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="admin-page">
    <?php view('partials.navbar'); ?>

    <div class="container py-5 mt-5">
        <h2 class="text-gold mb-4">User Management</h2>

        <!-- Team Accounts -->
        <div class="card admin-card shadow-lg mb-5">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Team Accounts</h5>
                <span class="badge bg-secondary"><?= $teams->num_rows ?> Teams</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark admin-table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Team Name</th>
                                <th>Username</th>
                                <th>Team ID</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($team = $teams->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-white"><?= htmlspecialchars($team['team_name']) ?></td>
                                <td><?= htmlspecialchars($team['username']) ?></td>
                                <td><?= $team['team_id'] ?></td>
                                <td class="text-end">
                                    <form method="POST" action="<?= asset('admin/impersonate') ?>" onsubmit="return confirm('Are you sure you want to log in as this team?');">
                                        <input type="hidden" name="id" value="<?= $team['team_id'] ?>">
                                        <input type="hidden" name="type" value="team">
                                        <button class="btn btn-admin-gold btn-sm">
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
        <div class="card admin-card shadow-lg">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">System Users</h5>
                <span class="badge bg-secondary"><?= $users->num_rows ?> Users</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark admin-table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-white"><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><span class="badge bg-<?= $u['role']=='admin'?'danger':'info' ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td class="text-end">
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="<?= asset('admin/impersonate') ?>" onsubmit="return confirm('Login as this user?');">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="type" value="user">
                                        <button class="btn btn-admin-gold btn-sm">
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
