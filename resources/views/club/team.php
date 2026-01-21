<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Dashboard - Wish2Padel</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
</head>
<body style="background-color:#303030;">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<?php view('partials.club_navbar'); ?>

<section class="container bg-white mt-5 mb-5 p-5 shadow-lg border rounded">
    <h1 class="fw-bold mb-5">Manage Team - <?= htmlspecialchars($center['name']) ?></h1>

    <!-- TEAMS -->
    <div class="mb-5">
        <h2 class="fw-bold mb-3">Teams</h2>
        <?php if (!empty($teams)): ?>
            <?php foreach ($teams as $team): 
                $collapseId = "teamCollapse" . $team['id'];
            ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= htmlspecialchars($team['team_name']) ?></h5>
                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                            Show / Hide
                        </button>
                    </div>
                    <div class="collapse" id="<?= $collapseId ?>">
                        <div class="card-body">
                            <p><strong>Captain:</strong> <?= htmlspecialchars($team['captain_name']) ?> (<?= htmlspecialchars($team['captain_phone']) ?>)</p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($team['captain_email']) ?></p>
                            <p><strong>Contact:</strong> <?= htmlspecialchars($team['contact_phone'] ?? '-') ?> | <?= htmlspecialchars($team['contact_email'] ?? '-') ?></p>
                            <p><strong>City:</strong> <?= htmlspecialchars($team['city'] ?? '-') ?> | <strong>Level:</strong> <?= htmlspecialchars($team['level'] ?? '-') ?></p>

                            <!-- TEAM MEMBERS -->
                            <?php if (!empty($team_members[$team['id']])): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Number</th>
                                                <th>Gender</th>
                                                <th>Role</th>
                                                <th>Joined</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($team_members[$team['id']] as $member): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($member['player_name']) ?></td>
                                                    <td><?= htmlspecialchars($member['player_number']) ?></td>
                                                    <td><?= htmlspecialchars($member['gender']) ?></td>
                                                    <td><?= htmlspecialchars($member['role']) ?></td>
                                                    <td><?= htmlspecialchars($member['joined_at']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No members found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No teams registered for this club.</p>
        <?php endif; ?>
    </div>

    <!-- INDIVIDUALS -->
    <div>
        <h2 class="fw-bold mb-3">Players without Club</h2>
        <?php if (!empty($individuals)): ?>
            <div class="table-responsive">
                <table class="table table-striped shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Full Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Gender</th>
                            <th>Address</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($individuals as $ind): ?>
                            <tr>
                                <td><?= htmlspecialchars($ind['full_name']) ?></td>
                                <td><?= htmlspecialchars($ind['phone']) ?></td>
                                <td><?= htmlspecialchars($ind['email']) ?></td>
                                <td><?= htmlspecialchars($ind['gender']) ?></td>
                                <td><?= htmlspecialchars($ind['address']) ?></td>
                                <td><?= htmlspecialchars($ind['created_at']) ?></td>
                                <td>
                                    <a href="<?= asset('club/team') ?>?remove_individual=<?= $ind['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure this player already has a team?')">
                                       Mark as Has Team
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No players without club.</p>
        <?php endif; ?>
    </div>
</section>


<?php view('partials.footer'); ?>

<!-- Scroll to Top Button -->
<button id="scrollTopBtn" title="Go to top">↑</button>

<script>
AOS.init();

// Scroll to Top Button
const scrollBtn = document.getElementById("scrollTopBtn");
window.onscroll = () => {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        scrollBtn.style.display = "block";
    } else {
        scrollBtn.style.display = "none";
    }
};
scrollBtn.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
});

// Navbar Fixed on Scroll
document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('maiavbar');
    const hero = document.getElementById('club'); 
    if (!navbar) return;

    function toggleNavbarFixed() {
        if (!hero) return;
        const scrollPos = window.scrollY;
        const heroHeight = hero.offsetHeight;

        if (scrollPos >= heroHeight) {
            navbar.classList.add('navbar-fixed');
            document.body.style.paddingTop = navbar.offsetHeight + 'px';
        } else {
            navbar.classList.remove('navbar-fixed');
            document.body.style.paddingTop = '0';
        }
    }
    window.addEventListener('scroll', toggleNavbarFixed);
    toggleNavbarFixed();
});
</script>
</body>
</html>
