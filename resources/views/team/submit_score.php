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

<section class="container mt-5 mb-5 py-5 text-black" id="scores-section">
    <h2 class="fw-bold">Match Scores</h2>
<p class="mb-2">
  <strong>Hello, Captain! 👋</strong><br>
  This page is provided for you to officially submit the results of your completed match.
  Please upload the signed <strong>Match Result Letter</strong> as valid proof of the final score.
</p>
<p>
  Make sure both teams review and agree on the result before submitting.
  Any unclear, incomplete, or conflicting scores may be rejected or require revalidation.
</p>

    <div class="card shadow-sm border-0 p-4" style="border-radius:15px;">
        <div style="background-color:#696969; color:#F3E6B6" class="card-header d-flex justify-content-between align-items-center">
            <p class="text-bold mb-0"><?= htmlspecialchars($match['tournament_name']) ?></p>
            <span class="badge bg-light text-dark"><?= htmlspecialchars($match['status']) ?></span>
        </div>
        <div class="card-body mt-3 mb-3">
            <h2 class="fw-bold mt-3 mb-3">
                <?= htmlspecialchars($match['team1_name']) ?> vs <?= htmlspecialchars($match['team2_name']) ?>
            </h2>
            <p class="card-text mb-3">
                <strong>Match ID:</strong> <?= $match['id'] ?><br>
                <strong>Scheduled:</strong> <?= date('d M Y, H:i', strtotime($match['scheduled_date'])) ?>
            </p>

            <?php if ($status_result === 'accept'): ?>
                <div class="alert alert-success mt-3">Scores already accepted ✅</div>
            <?php else: ?>

                <form method="post" enctype="multipart/form-data" action="<?= asset('submit_score') ?>">

                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">

                    <div class="mb-4">
                        <label for="scoreFile<?= $match['id'] ?>" class="form-label fw-bold text-danger">
                            📂 Upload Match Result / Score (PDF, PNG, JPEG, JPG)
                        </label>
                        <input type="file" name="score_file" id="scoreFile<?= $match['id'] ?>"
                               class="form-control"
                               accept=".pdf,.png,.jpeg,.jpg" required>
                        <div class="form-text">
                            Upload the completed & signed minutes sheet as official proof.
                        </div>
                    </div>

                    <?php foreach($pairs as $p): ?>
                    <div class="border p-3 mb-3" style="border-radius:10px;">
                        <h6>Pair <?= $p['pair_number'] ?></h6>

                        <?php for($set = 1; $set <= 3; $set++): ?>
                        <div class="mb-2 set-wrapper set-<?= $set ?>">
                            <label>Set <?= $set ?> <small class="text-muted">(Home - Away)</small></label>
                            <input type="text"
                                   name="scores[<?= $p['id'] ?>][<?= $set ?>][score]"
                                   class="form-control score-input <?= ($set <= 2 ? 'set-input' : 'set3') ?>"
                                   placeholder="Home-Away (e.g. 6-4)"
                                   pattern="^\d+-\d+$"
                                   maxlength="4"
                                   title="Format: HOME-AWAY (Left = Home, Right = Away)">
                            <small class="text-muted">Example: <strong>6-4</strong> means Home scored 6, Away scored 4.</small>
                            <div class="invalid-feedback">⚠️ Incomplete format. Please use the format <strong>6-4</strong>.</div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <?php endforeach; ?>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirmScores<?= $match['id'] ?>" onchange="document.getElementById('saveBtn<?= $match['id'] ?>').disabled = !this.checked;">
                        <label class="form-check-label" for="confirmScores<?= $match['id'] ?>">
                            <strong>TEAM CAPTAIN’S DECLARATION OF RESPONSIBILITY</strong><br>
                            I, as the <strong>Team Captain</strong>, hereby declare that all match scores I have entered are <strong>truthful, accurate, and made in good faith</strong>.
                        </label>
                    </div>

                    <button type="submit" name="save_scores" class="btn btn-gold w-100" id="saveBtn<?= $match['id'] ?>" disabled>
                        Save Scores
                    </button>

                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.score-input').forEach(input => {
        input.addEventListener('input', function () {
            let val = this.value.replace(/[^0-9-]/g, '');
            if (val.length === 1 && !val.includes('-')) val = val + '-';
            let match = val.match(/^(\d{1,2})-?(\d{0,2})$/);
            if (match) {
                val = match[1] + (match[2] !== undefined ? '-' + match[2] : '-');
            } else {
                val = val.slice(0, val.length - 1);
            }
            this.value = val;
        });
    });

    document.querySelectorAll('.border').forEach(pairBlock => {
        const setInputs = pairBlock.querySelectorAll('.set-input');
        const set3Wrapper = pairBlock.querySelector('.set3').closest('.set-wrapper');

        set3Wrapper.style.display = 'none';

        function checkSets() {
            const s1 = setInputs[0].value.trim();
            const s2 = setInputs[1].value.trim();

            if (!s1.match(/^\d+-\d+$/) || !s2.match(/^\d+-\d+$/)) {
                set3Wrapper.style.display = 'none';
                return;
            }

            const [a1, b1] = s1.split('-').map(Number);
            const [a2, b2] = s2.split('-').map(Number);

            const winner1 = a1 > b1 ? 'A' : 'B';
            const winner2 = a2 > b2 ? 'A' : 'B';

            if (winner1 !== winner2) {
                set3Wrapper.style.display = 'block';
            } else {
                set3Wrapper.style.display = 'none';
            }
        }

        setInputs.forEach(input => input.addEventListener('input', checkSets));
    });
});
</script>

<?php view('partials.footer'); ?>

<!-- Scroll to Top Button -->
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
