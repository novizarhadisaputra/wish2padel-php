<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Penalties - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
</head>
<body class="admin-page">

<?php view('partials.navbar'); ?>

<div class="container py-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-gold">Team Penalties</h2>
        <button class="btn btn-admin-gold" data-bs-toggle="modal" data-bs-target="#addPenaltyModal">
            <i class="bi bi-plus-lg"></i> Add Penalty
        </button>
    </div>

    <div class="card admin-card shadow-lg">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark admin-table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>Tournament</th>
                            <th>Points Deduction</th>
                            <th>Fine Amount</th>
                            <th>Reason</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($penalties && $penalties->num_rows > 0): ?>
                            <?php while($row = $penalties->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold text-white"><?= htmlspecialchars($row['team_name']) ?></td>
                                    <td><?= htmlspecialchars($row['tournament_name']) ?></td>
                                    <td class="text-danger fw-bold">-<?= $row['points_deduction'] ?> pts</td>
                                    <td class="text-warning">$<?= number_format($row['fine_amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['reason']) ?></td>
                                    <td class="text-muted small"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td class="text-end">
                                        <form method="POST" action="/admin/penalties" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this penalty?');">
                                            <input type="hidden" name="penalty_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="delete_penalty" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No penalties found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Penalty Modal -->
<div class="modal fade" id="addPenaltyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mt-5 modal-dark">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title">Add New Penalty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/admin/penalties">
                <div class="modal-body">
                    <input type="hidden" name="store_penalty" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Team</label>
                        <select class="form-select" name="team_id" required>
                            <option value="">Select Team</option>
                            <?php 
                            if ($teams) {
                                $teams->data_seek(0);
                                while($t = $teams->fetch_assoc()): 
                            ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['team_name']) ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tournament</label>
                        <select class="form-select" name="tournament_id" required>
                            <option value="">Select Tournament</option>
                            <?php 
                            if ($tournaments) {
                                $tournaments->data_seek(0);
                                while($tor = $tournaments->fetch_assoc()): 
                            ?>
                                <option value="<?= $tor['id'] ?>"><?= htmlspecialchars($tor['name']) ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>

                    <div class="sys-grid-2">
                        <div class="mb-3">
                            <label class="form-label">Points Deduction</label>
                            <input type="number" class="form-control" name="points_deduction" value="0" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fine Amount ($)</label>
                            <input type="number" step="0.01" class="form-control" name="fine_amount" value="0.00" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="e.g. Unsportsmanlike conduct during match..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-admin-gold">Save Penalty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
