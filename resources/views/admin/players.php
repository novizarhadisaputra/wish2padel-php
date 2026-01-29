<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Players List - Wish2Padel</title>
    <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="admin-page">

<?php view('partials.navbar'); ?>

<div class="container py-5 mt-5">
    <!-- Header -->
    <h2 class="text-gold mb-4">Players List</h2>

        <!-- Search Bar -->
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search player name...">

        <!-- Table -->
    <div class="card admin-card shadow-lg">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark admin-table table-hover mb-0 align-middle" id="playersTable">
                    <thead>
                        <tr>
                                <th style="width:60px;">No</th>
                                <th>Player Name</th>
                                <th>Age</th>
                                <th>Role</th>
                                <th>Position</th>
                                <th>Point</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['player_name']) ?></td>
                                    <td><?= $row['age'] ?: '-' ?></td>
                                    <td><?= $row['role'] ?: '-' ?></td>
                                    <td><?= $row['position'] ?: '-' ?></td>
                                    <td><?= $row['point'] ?: '-' ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>

<script>
// Search filter
document.getElementById("searchInput").addEventListener("keyup", function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll("#playersTable tbody tr");
    rows.forEach(row => {
        const name = row.children[1].textContent.toLowerCase();
        row.style.display = name.includes(filter) ? "" : "none";
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
