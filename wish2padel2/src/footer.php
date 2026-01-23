<?php
$current_page = basename($_SERVER['PHP_SELF']);


// ================== DB SAFE GUARD ==================
if (!isset($conn)) {
  require_once __DIR__ . '/../config.php';
  $conn = getDBConnection();
}

// ================== FETCH SPONSORS ==================
$result = $conn->query("SELECT * FROM sponsors ORDER BY sponsor_id DESC");

$partnerSponsors  = [];
$premiumSponsors  = [];
$goldSponsors     = [];
$standardSponsors = [];
$collaborates     = [];

while ($row = $result->fetch_assoc()) {
  if ($row['status'] === 'collaborate') {
    $collaborates[] = $row;
    continue;
  }

  switch ($row['type'] ?? 'standard') {
    case 'partner':
      $partnerSponsors[] = $row;
      break;
    case 'premium':
      $premiumSponsors[] = $row;
      break;
    case 'gold':
      $goldSponsors[] = $row;
      break;
    default:
      $standardSponsors[] = $row;
  }
}
?>

<footer class="footer-main">

  <!-- ================= FOOTER TOP ================= -->
  <div class="footer-top container">
    <p class="footer-tagline">
      Elevate Your Game with Wish2Padel Team League
    </p>
  </div>

  <!-- ================= FOOTER INFO ================= -->
  <div class="footer-info container">

    <div>
      <p>
        Welcome to <b>Wish2Padel Team League</b>, your all-in-one platform where
        players, groups, and companies come together for leagues, tournaments,
        training, and gear.
      </p>
    </div>

    <div>
      <h6>Office</h6>
      <p>Riyadh (Saudi Arabia)</p>
      <p>
        <a href="mailto:info@wish2padel.com">info@wish2padel.com</a>
      </p>
      <p>Phone: +966 55 322 4559</p>
    </div>

    <div>
      <h6>Socials</h6>
      <a href="https://www.instagram.com/wish2padel" target="_blank" rel="noopener">
        <i class="bi bi-instagram"></i> Instagram
      </a>
      <a href="https://www.tiktok.com/@wish2padel.ksa" target="_blank" rel="noopener">
        <i class="bi bi-tiktok"></i> TikTok
      </a>
    </div>

  </div>

  <?php
  $hasSponsors =
    !empty($partnerSponsors) ||
    !empty($premiumSponsors) ||
    !empty($goldSponsors) ||
    !empty($standardSponsors);
  ?>

  <?php if (
    $hasSponsors &&
    $current_page !== 'sponsor.php' &&
    $current_page !== 'sponsor' &&
    $current_page !== 'regis.php' &&
    $current_page !== 'regis'
  ): ?>
    <section class="sponsors-page">
      <div class="container text-center">
        <h5 class="sponsor-title">Brought to you by</h5>

        <div class="sponsor-category">
          <?php
          $allSponsors = array_merge(
            $partnerSponsors,
            $premiumSponsors
          );
          ?>

          <?php foreach ($allSponsors as $row): ?>
            <div class="sponsor-item">

              <div class="sponsor-logo-box">
                <img
                  src="uploads/sponsor/<?= htmlspecialchars($row['sponsor_logo']) ?>"
                  alt="<?= htmlspecialchars($row['sponsor_name']) ?>"
                  loading="lazy">
              </div>

              <div class="sponsor-info">
                <h5><?= htmlspecialchars($row['sponsor_name']) ?></h5>

                <?php if (!empty($row['description'])): ?>
                  <p><?= htmlspecialchars($row['description']) ?></p>
                <?php endif; ?>
              </div>

              <?php if (!empty($row['website'])): ?>
                <div class="sponsor-action">
                  <a
                    href="<?= htmlspecialchars($row['website']) ?>"
                    target="_blank"
                    rel="noopener"
                    class="visit-btn gold">
                    Visit Website
                  </a>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>



  <hr>

  <!-- ================= COPYRIGHT ================= -->
  <div class="footer-bottom container">
    &copy; <?= date('Y') ?> Wish2Padel. All rights reserved.
  </div>

</footer>

<!-- ================= FOOTER STYLES (FROM FIRST CODE) ================= -->
<style>
  /* ===================== STICKY FOOTER FIX ===================== */
  html,
  body {
    height: 100%;
    margin: 0;
  }

  body.page-wrapper {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding-bottom: 72px;
    /* adjust to navbar height */
  }

  /* Main content expands to push footer down */
  .page-content {
    flex: 1 0 auto;
  }

  /* Footer stays at bottom */
  .footer-main {
    flex-shrink: 0;
  }

  .footer-main {
    background: linear-gradient(90deg, #000, #1a1a1a);
    color: white;
    padding: 70px 20px 30px;
  }

  /* ---------- TEXT ---------- */
  .footer-tagline {
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 3px;
    font-size: 2rem;
    color: #c5a369;
    max-width: 520px;
  }

  .footer-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 40px 60px;
    margin-bottom: 70px;
  }

  .footer-info h6 {
    color: #c5a369;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 15px;
  }

  .footer-info a {
    color: white;
    text-decoration: none;
    display: block;
    margin-bottom: 10px;
  }

  /* ---------- SPONSORS ---------- */
  .sponsors-page {
    padding: 60px 0;
  }

  .sponsor-title {
    color: #fff;
    text-transform: uppercase;
    margin-bottom: 40px;
    letter-spacing: 2px;
  }

  .sponsor-category {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 26px;
  }

  .sponsor-item {
    max-width: 360px;
    background: linear-gradient(135deg, #000 80%, #1a1a1a);
    border: 1px solid rgba(212, 175, 55, 0.45);
    border-radius: 14px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform .35s, box-shadow .35s;
  }

  .sponsor-item:hover {
    transform: scale(1.03);
    box-shadow: 0 0 25px rgba(212, 175, 55, .3);
  }

  .sponsor-logo-box {
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
  }

  .sponsor-logo-box img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
    filter: drop-shadow(0 0 8px rgba(212, 175, 55, .35));
  }

  .sponsor-info h5 {
    color: #c5a369;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .sponsor-info p {
    color: #bbb;
    font-size: 14px;
  }

  .visit-btn.gold {
    display: inline-block;
    padding: 10px 22px;
    border-radius: 30px;
    background: linear-gradient(90deg, #c8ab6b, #88694A);
    color: #000;
    font-weight: 600;
    text-decoration: none;
  }

  .visit-btn.gold:hover {
    box-shadow: 0 0 18px rgba(212, 175, 55, .7);
    transform: translateY(-2px);
  }

  .footer-bottom {
    text-align: center;
    font-size: .9rem;
    color: #9f9f9f;
  }
</style>