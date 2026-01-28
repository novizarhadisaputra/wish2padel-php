<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Personnel - Wish2Padel</title>
    <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="admin-page">
    <?php view('partials.navbar'); ?>

    <div class="container py-5 mt-5">
        <h2 class="text-gold mb-4">All Team Personnel</h2>

        <div class="card admin-card shadow-lg">
            <div class="card-header d-flex justify-content-between align-items-center border-0">
                <h5 class="mb-0">Personnel List</h5>
                <form class="d-flex" action="<?= asset('admin/personnel') ?>" method="GET">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search Player or Team..." value="<?= htmlspecialchars($search) ?>" aria-label="Search">
                    <button class="btn btn-admin-gold" type="submit">Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="<?= asset('admin/personnel') ?>" class="btn btn-link text-warning ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-dark admin-table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Player Name</th>
                            <th>Role</th>
                            <th>Team</th>
                            <th>Tournament</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($row['player_name']) ?></td>
                                    <td>
                                        <?php if(strtolower($row['role']) === 'captain'): ?>
                                            <span class="badge bg-warning text-dark">Captain</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Member</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['team_name']) ?></td>
                                    <td>
                                        <?php if($row['league_name']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($row['league_name']) ?></small><br>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($row['tournament_name'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <?php if($row['phone']): ?>
                                            <div><i class="bi bi-telephone mb-1"></i> <small><?= htmlspecialchars($row['phone']) ?></small></div>
                                        <?php endif; ?>
                                        <?php if($row['email']): ?>
                                            <div><i class="bi bi-envelope"></i> <small><?= htmlspecialchars($row['email']) ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No personnel found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
