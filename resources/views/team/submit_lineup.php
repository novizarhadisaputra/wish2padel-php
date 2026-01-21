<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?=v13') ?>">
</head>
<body>

<?php view('partials.navbar'); ?>

<section class="container mt-5 mb-5 py-5">
    <div id="scheduled" class="mb-5 text-dark">
    <h2 class="fw-bold">Team Lineup</h2>

    <p class="mb-2">
      <strong>Hello, Captain! 👋</strong><br>
      Since the match is approaching, you are required to complete and submit your team's official lineup here.
    </p>

    <?php
        // Ideally pass document path from controller, but keeping legacy query for now if needed or assume hardcoded.
        // Legacy: "SELECT file_path FROM documents WHERE id = 1 LIMIT 1"
        // I'll assume '#' if not passed, or use asset if known.
        $filePath = "#";
    ?>

    <p class="mb-2">
        <a href="<?= $filePath ?>"
            class="btn btn-danger mt-2"
            target="_blank">
            DOWNLOAD LINEUP
        </a>
        <br>
        Don’t forget the match won’t be valid until Lineups & Results sheets are UPLOADED.
    </p>

    <div class="row g-4 mt-5">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card shadow-sm border-0 h-100" style="border-radius:15px;">
                <div style="background-color:#696969" class="card-header text-dark d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><?= htmlspecialchars($match['tournament_name']) ?></span>
                    <span class="badge bg-light text-dark"><?= ucfirst($match['status']) ?></span>
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <?= htmlspecialchars($match['team1_name']) ?> VS <?= htmlspecialchars($match['team2_name']) ?>
                    </h5>
                    <p class="card-text mb-1"><strong>Scheduled:</strong> <?= date('d M Y, H:i', strtotime($match['scheduled_date'])) ?></p>
                    <p class="card-text"><strong>Match ID:</strong> <?= $match['id'] ?></p>

                    <form method="post" enctype="multipart/form-data" class="mt-3 border p-3 rounded" action="<?= asset('submit_lineup') ?>">

                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold text-danger">
                                📂 Upload Lineup Declaration Letter (PDF, PNG, JPEG, JPG)
                            </label>
                            <input type="file" name="lineup_file" class="form-control" accept=".pdf,.png,.jpeg,.jpg" required>
                        </div>

                        <?php foreach($pairs_array as $p): ?>
                            <div class="pair-block border p-3 mb-3 rounded">
                                <h6>Pair <?= (int)$p['pair_number'] ?></h6>
                                <div class="mb-2">
                                    <label class="mb-1">Main Players</label>
                                    <?php for($i=0; $i<2; $i++): ?>
                                        <select name="pairs[<?= (int)$p['id'] ?>][main][]" class="form-select mb-2 player-select" required>
                                            <option value="" disabled selected hidden>-- Select Player <?= $i+1 ?> --</option>

                                            <?php foreach($players as $player): ?>
                                                <option
                                                  value="<?= htmlspecialchars($player) ?>"
                                                  <?= in_array($player, $usedPlayersDB, true) ? 'disabled data-db-disabled="true"' : '' ?>
                                                >
                                                  <?= htmlspecialchars($player) ?>
                                                </option>

                                            <?php endforeach; ?>
                                        </select>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="confirmAll" onchange="document.getElementById('saveAllBtn').disabled = !this.checked;">
                            <label class="form-check-label" for="confirmAll">
                                I, as the appointed Team Captain, solemnly declare and confirm that the players I have selected and submitted for all pairs in this match are accurate, truthful, and final.
                                I acknowledge that I am fully responsible for ensuring that the chosen players participate exactly as entered in this form, without substitutions or changes.
                                I commit to act honestly, fairly, and in the spirit of sportsmanship, upholding the integrity of my team and the competition.
                            </label>
                        </div>

                        <button type="submit" name="save_players" id="saveAllBtn" class="btn btn-gold mt-3" disabled>
                            Save Players
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const selects = document.querySelectorAll('select.player-select');

    function updateAllSelects() {
        const chosen = [];
        selects.forEach(s => { if (s.value) chosen.push(s.value); });

        selects.forEach(s => {
            const currentValue = s.value;
            Array.from(s.options).forEach(opt => {
                if (!opt.value) return;

                if (chosen.includes(opt.value) && opt.value !== currentValue) {
                    opt.disabled = true;
                } else {
                    if (!opt.hasAttribute('data-db-disabled')) {
                        opt.disabled = false;
                    }
                }
            });
        });
    }

    selects.forEach(sel => sel.addEventListener('change', updateAllSelects));
    updateAllSelects();
});
</script>

<?php view('partials.footer'); ?>

<button id="scrollTopBtn" title="Go to top">↑</button>
<script>
  const scrollBtn = document.getElementById("scrollTopBtn");
  window.onscroll = function() {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
      scrollBtn.style.display = "block";
    } else {
      scrollBtn.style.display = "none";
    }
  };
  scrollBtn.addEventListener("click", function() {
    window.scrollTo({
      top: 0,
      behavior: "smooth"
    });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
