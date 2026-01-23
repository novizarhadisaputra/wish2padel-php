<?php
session_start();
require 'config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <link rel="icon" type="image/png" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sponsors - Wish2Padel</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/stylee.css?v=12">
</head>

<body style="background-color:#303030;">

<?php require 'src/navbar.php'; ?>

<?php
$resultSponsors = $conn->query("SELECT * FROM sponsors ORDER BY sponsor_id DESC");
$resultCollaborators = $conn->query("SELECT * FROM sponsors WHERE status = 'collaborate' ORDER BY sponsor_id DESC");

$partnerSponsors = [];
$premiumSponsors = [];
$goldSponsors = [];
$standardSponsors = [];
$collaborators = [];

while ($row = $resultSponsors->fetch_assoc()) {
  switch ($row['type'] ?? '') {
    case 'partner':  $partnerSponsors[] = $row; break;
    case 'premium':  $premiumSponsors[] = $row; break;
    case 'gold':     $goldSponsors[] = $row; break;
    case 'standard': $standardSponsors[] = $row; break;
  }
}

while ($row = $resultCollaborators->fetch_assoc()) {
  $collaborators[] = $row;
}
?>

<section class="sponsors-page py-5">
  <div class="container">

    <h1 class="tour-title mb-5">PARTNERS</h1>
    <h3 class="category-title text-gradient-gold mb-4">Premium Sponsors</h3>

    <?php if (!empty($partnerSponsors)): ?>
      <div class="sponsor-category mb-5">

        <?php foreach ($partnerSponsors as $row): ?>
          <div class="pp-partner-card">
            <div class="pp-partner-row">

              <div class="pp-logo">
                <img src="uploads/sponsor/<?= htmlspecialchars($row['sponsor_logo']) ?>"
                     alt="<?= htmlspecialchars($row['sponsor_name']) ?>">
              </div>

              <div class="pp-content">
                <h4><?= htmlspecialchars($row['sponsor_name']) ?></h4>
                <p><?= htmlspecialchars($row['description']) ?></p>
              </div>

              <?php if (!empty($row['website'])): ?>
                <div class="pp-action">
                  <a href="<?= htmlspecialchars($row['website']) ?>" target="_blank">
                    VISIT
                  </a>
                </div>
              <?php endif; ?>

            </div>
          </div>
        <?php endforeach; ?>

      </div>
    <?php endif; ?>

    <?php if (!empty($goldSponsors) || !empty($standardSponsors)): ?>
      <h3 class="category-title text-gradient-gold mb-4">Standard & Gold Sponsors</h3>

      <div class="sponsor-category mb-5">
        <div class="sponsor-grid merged-sponsors">

          <?php foreach ($goldSponsors as $row): ?>
            <div class="grid-logo gold-logo">
              <img src="uploads/sponsor/<?= htmlspecialchars($row['sponsor_logo']) ?>">
            </div>
          <?php endforeach; ?>

          <?php foreach ($standardSponsors as $row): ?>
            <div class="grid-logo standard-logo">
              <img src="uploads/sponsor/<?= htmlspecialchars($row['sponsor_logo']) ?>">
            </div>
          <?php endforeach; ?>

        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($collaborators)): ?>
      <h3 class="category-title text-gradient-silver mb-4">Collaborate</h3>

      <div class="sponsor-category mb-5">
        <div class="sponsor-grid merged-sponsors">
          <?php foreach ($collaborators as $row): ?>
            <div class="grid-logo collaborator-logo">
              <img src="uploads/sponsor/<?= htmlspecialchars($row['sponsor_logo']) ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>

<style>
/* ================= BASE ================= */
.sponsors-page {
  background:#0c0c0c;
  color:#fff;
  font-family:Poppins,sans-serif;
}

.tour-title {
  font-weight:800;
  font-size:80px;
}

.category-title {
  font-size:1.3rem;
  font-weight:600;
}

/* ================= PARTNER LONG CARDS ================= */
.pp-partner-card {
  background: linear-gradient(135deg,#000 70%,#141414);
  border:1px solid rgba(212,175,55,.45);
  border-radius:18px;
  padding:16px 40px;
  margin-bottom:30px;
  transition:.35s;
  margin-top: 24px;
  margin-bottom: 24px;

  padding:16px 40px;
  transition:.35s;
}

.pp-partner-card:hover {
  transform:translateY(-4px);
  box-shadow:0 18px 45px rgba(212,175,55,.25);
}

.pp-partner-row {
  display:grid;
  grid-template-columns:260px 1fr 120px;
  align-items:center;
  gap:32px;
  padding-top: 0.1vh;
  padding-bottom: 0.1vh;
  transition:.35s;
}

.pp-logo img {
  max-width:220px;
  max-height:120px;
  filter:drop-shadow(0 0 10px rgba(212,175,55,.3));
}

.pp-content h4 {
  font-weight:700;
}

.pp-content p {
  color:#bdbdbd;
}

.pp-action a {
  padding:8px 24px;
  border-radius:30px;
  border:1px solid rgba(212,175,55,.6);
  color:#fff;
  text-decoration:none;
  transition:.3s;
}

.pp-action a:hover {
  background:linear-gradient(135deg,#d4af37,#b8962e);
  color:#000;
}

/* ================= MERGED LOGOS ================= */
.merged-sponsors {
  display:flex;
  gap:32px;
  justify-content:center;
}

.grid-logo img {
  max-height:120px;
  opacity:.9;
}

/* ================= RESPONSIVE ================= */
@media (max-width:768px) {
  .pp-partner-row {
    grid-template-columns:1fr;
    text-align:center;
  }
}
</style>

<?php require 'src/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
