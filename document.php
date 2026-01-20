<?php
session_start();
require 'config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
$team_id = $_SESSION['team_id'] ?? null;

$query = "SELECT id, file_path FROM documents ORDER BY id ASC";
$result = mysqli_query($conn, $query);

$section1 = []; 
$section2 = []; 

while ($row = mysqli_fetch_assoc($result)) {
    if ($row['id'] <= 2) {
        $section1[] = $row;
    } else {
        $section2[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Documents - Wish2Padel</title>
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/stylee.css?v=12">

  <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">

  <style>
    body { background-color: #303030; }
    .document-section { padding: 60px 0; }
    .document-card {
      background-color: #fff;
      border-radius: 15px;
      padding: 20px 20px;
      text-align: center;
      box-shadow: 0 6px 25px rgba(0,0,0,0.12);
      transition: transform 0.3s, box-shadow 0.3s;
      position: relative;
      overflow: hidden;
    }
    h5 {
        font-size: 18px;
    }
 
    .document-card::after {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: linear-gradient(135deg, rgba(255,193,7,0.15), rgba(255,193,7,0));
      opacity: 0;
      transition: opacity 0.3s;
      pointer-events: none;
    }
    .document-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 40px rgba(0,0,0,0.2);
    }
    .document-card:hover::after { opacity: 1; }
    .document-card img { width: 140px; height: auto; margin-bottom: 20px; transition: transform 0.3s; }
    .document-card:hover img { transform: scale(1.1); }
    .document-card h5 { font-weight: 700; color: #222; margin-bottom: 10px; }
    .document-card p { font-size: 0.95rem; color: #555; margin-bottom: 15px; }
    @media (max-width: 768px) { .document-card img { width: 70px; } }
    .fade-in { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease-out, transform 0.6s ease-out; }
    .fade-in.visible { opacity: 1; transform: translateY(0); }
  </style>
</head>
<body>

<?php require 'src/navbar.php'; ?>

<section class="document-section text-center">
  <div class="container">
    <h1 style="color:#f3e6b6; margin-bottom: 15px;">Wish2Padel Official Documents</h1>
    <p class="mb-5 text-white">Access official league rules, registration, and payment guidelines for a smooth padel experience.</p>

    <h3 class="mb-4" style="color:#f3e6b6;">Line Ups & Match Results</h3>
    <div class="row g-4 justify-content-center mb-5">
      <?php foreach ($section1 as $doc): ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 fade-in d-flex">
          <div class="document-card flex-fill d-flex flex-column justify-content-between" style="min-height: 330px;">
            <div>
              <img src="assets/image/w2p.png" alt="Document Logo">
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
              <img src="assets/image/w2p.png" alt="Document Logo">
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

<?php require 'src/footer.php'; ?>

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
