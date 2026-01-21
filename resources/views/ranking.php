<!DOCTYPE html>
<html lang="en">
<?php view('partials.head', ['title' => 'Ranking - Padel League', 'css' => 'assets/css/stylee.css']); ?>
<body style="background-color:#303030">

<?php view('partials.navbar'); ?>

<div class="container" style="color:white">
    <!-- Judul Leaderboard -->
    <div class="mt-5" id="ranking-title">
        <h3 class="fw-bold">Ranking Players</h3>
        <small>Updated at: <?= date("d M Y") ?></small>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body"  style="background-color:#303030">
            <!-- Filters -->
            <form method="get" class="row g-2 mb-4">
                <div class="col-auto">
                    <select name="gender" class="form-select">
                        <option value="Pria" <?= $gender_filter=='Pria'?'selected':'' ?>>Male</option>
                        <option value="Wanita" <?= $gender_filter=='Wanita'?'selected':'' ?>>Female</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" name="search_name" class="form-control" placeholder="Search player" value="<?= htmlspecialchars($search_name) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn" style="background-color:#FFC107; font-weight:700">Filter</button>
                </div>
            </form>

            <!-- Leaderboard Table -->
            <div class="table-responsive">
                <table class="table table-striped mt-4 table-bordered text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Team</th>
                            <th>Point Match</th>
                            <th>Match Won</th>
                            <th>Match Lost</th>
                            <th>Total Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach($leaderboard as $row): 
                            $point_match = round($row['point_match_total'],1);
                            $total_points = $point_match; // total points = sum of point per match
                        ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= htmlspecialchars($row['player_name']) ?></td>
                                <td><?= htmlspecialchars($row['team_name']) ?></td>
                                <td><?= $point_match ?></td>
                                <td><?= $row['match_won'] ?></td>
                                <td><?= $row['match_lost'] ?></td>
                                <td><?= $total_points ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($leaderboard)): ?>
                            <tr><td colspan="7">No players found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<?php view('partials.footer'); ?>

<?php view('partials.scroll_top'); ?>
<?php view('partials.navbar_sticky_script', ['sticky_target' => 'ranking-title']); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
