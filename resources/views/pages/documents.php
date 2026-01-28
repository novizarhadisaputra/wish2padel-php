<!DOCTYPE html>
<html lang="en">
<?php view('partials.head', ['title' => 'Documents - Wish2Padel']); ?>
<body>

<?php view('partials.navbar'); ?>

<section class="document-section text-center" id="documents-hero">
  <div class="container">
    <h1 style="color:#f3e6b6; margin-bottom: 15px;">Wish2Padel Official Documents</h1>
    <p class="mb-5 text-white">Access official league rules, registration, and payment guidelines for a smooth padel experience.</p>

    <h3 class="mb-4" style="color:#f3e6b6;">Line Ups & Match Results</h3>
    <div class="row g-4 justify-content-center mb-5">
      <?php foreach ($section1 as $doc): ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 fade-in d-flex">
          <div class="document-card flex-fill d-flex flex-column justify-content-between" style="min-height: 330px;">
            <div>
              <img src="<?= getSiteLogo() ?>" alt="Document Logo">
              <?php if ($doc['id'] == 1): ?>
                <h5>Team Line Up</h5>
                <p>This document is used by teams to fill in their player lineup before the match begins.</p>
              <?php elseif ($doc['id'] == 2): ?>
                <h5>Match Results</h5>
                <p>This document is for recording the score and players who participated in each match.</p>
              <?php endif; ?>
            </div>
            <div>
              <?php if (!empty($doc['file_path'])): ?>
                <div class="d-flex flex-column gap-2 mt-3">
                  <a class="btn btn-gold" href="<?= htmlspecialchars($doc['file_path']); ?>" target="_blank">View PDF</a>
                  <a class="btn btn-danger" href="<?= htmlspecialchars($doc['file_path']); ?>" download>Download PDF</a>
                </div>
              <?php else: ?>
                <span class="text-muted mt-3">Coming Soon</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>


    <h3 class="mb-4" style="color:#f3e6b6; margin-bottom: 15px;">Rules & Regulations</h3>
    <div class="row g-4 justify-content-center">
      <?php foreach ($section2 as $doc): ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 fade-in d-flex">
          <div class="document-card flex-fill d-flex flex-column justify-content-between" style="min-height: 350px;">
            <div>
              <img src="<?= getSiteLogo() ?>" alt="Document Logo">
              <?php if ($doc['id'] == 3): ?>
                <h5>Official League Rules</h5>
                <p>All official league rules, gameplay regulations, and match guidelines.</p>
              <?php elseif ($doc['id'] == 4): ?>
                <h5>Playoff Rules</h5>
                <p>Explanation and format of the playoff phase, including qualification and match setup.</p>
              <?php elseif ($doc['id'] == 5): ?>
                <h5>Registration & Payment Rules</h5>
                <p>Learn how to register your team and understand payment procedures.</p>
              <?php elseif ($doc['id'] == 6): ?>
                <h5>Player Ranking</h5>
                <p>Includes the official player ranking and point system table.</p>
              <?php endif; ?>
            </div>
            <div>
              <?php if (!empty($doc['file_path'])): ?>
                <a class="btn btn-gold mt-3" href="<?= htmlspecialchars($doc['file_path']); ?>" target="_blank">View PDF</a>
              <?php else: ?>
                <span class="text-muted mt-3">Coming Soon</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<?php view('partials.footer'); ?>

<?php view('partials.navbar_sticky_script', ['sticky_target' => 'documents-hero']); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const faders = document.querySelectorAll('.fade-in');
  const appearOptions = { threshold: 0.1, rootMargin: "0px 0px -50px 0px" };
  const appearOnScroll = new IntersectionObserver(function(entries, observer){
    entries.forEach(entry => {
      if(!entry.isIntersecting) return;
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    });
  }, appearOptions);
  faders.forEach(fader => { appearOnScroll.observe(fader); });
</script>

</body>
</html>
