<?php
$current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// ================== DB SAFE GUARD ==================
if (!isset($conn)) {
  $conn = getDBConnection();
}

// ================== FETCH SPONSORS ==================
$result = $conn ? $conn->query("SELECT * FROM sponsors ORDER BY sponsor_id DESC") : null;

$partnerSponsors  = [];
$premiumSponsors  = [];
$goldSponsors     = [];
$standardSponsors = [];
$collaborates     = [];

if ($result) {
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
  // Check if page should show sponsors
  $hideOnPages = ['sponsor', 'sponsor.php', 'regis', 'regis.php', 'sponsors'];
  $shouldShowSponsors = !in_array($current_page, $hideOnPages);
  
  // Merge main sponsors for display
  $allSponsors = array_merge($partnerSponsors, $premiumSponsors, $goldSponsors);
  ?>

  <?php if ($shouldShowSponsors && !empty($allSponsors)): ?>
    <section class="sponsors-page">
      <div class="container text-center">
        <h5 class="sponsor-title">Brought to you by</h5>

        <div class="sponsor-category">
          <?php foreach ($allSponsors as $row): ?>
            <div class="sponsor-item">

              <div class="sponsor-logo-box">
                <?php if($row['sponsor_logo']): ?>
                <img
                  src="<?= asset('uploads/sponsor/' . htmlspecialchars($row['sponsor_logo'])) ?>"
                  alt="<?= htmlspecialchars($row['sponsor_name']) ?>"
                  loading="lazy">
                <?php else: ?>
                    <span class="text-white fw-bold"><?= htmlspecialchars($row['sponsor_name']) ?></span>
                <?php endif; ?>
              </div>

              <div class="sponsor-info">
                <h5><?= htmlspecialchars($row['sponsor_name']) ?></h5>
                <?php if (!empty($row['description'])): ?>
                  <p><?= htmlspecialchars(substr($row['description'], 0, 100)) ?>...</p>
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
        
        <!-- Standard Sponsors (Small) -->
         <?php if(!empty($standardSponsors)): ?>
          <div class="mt-5 d-flex justify-content-center flex-wrap gap-4">
            <?php foreach($standardSponsors as $row): ?>
                <?php if($row['sponsor_logo']): ?>
                    <img src="<?= asset('uploads/sponsor/' . $row['sponsor_logo']) ?>" 
                         style="height: 50px; opacity: 0.7; filter: grayscale(100%); transition: .3s;" 
                         onmouseover="this.style.opacity=1;this.style.filter='none'" 
                         onmouseout="this.style.opacity=0.7;this.style.filter='grayscale(100%)'"
                         alt="<?= $row['sponsor_name'] ?>">
                <?php endif; ?>
            <?php endforeach; ?>
          </div>
         <?php endif; ?>

      </div>
    </section>
  <?php endif; ?>

  <hr style="border-color: #333;">

  <!-- ================= COPYRIGHT ================= -->
  <div class="footer-bottom container">
    &copy; <?= date('Y') ?> Wish2Padel. All rights reserved.
  </div>

</footer>

<!-- ================= FOOTER STYLES (LEGACY PORTED) ================= -->
<style>
  /* Footer stays at bottom */
  .footer-main {
    background: linear-gradient(90deg, #000, #1a1a1a);
    color: white;
    padding: 70px 20px 30px;
    margin-top: auto; /* Push to bottom if flex container */
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
  
  .footer-info a:hover {
      color: #c5a369;
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
    width: 100%;
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
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    padding: 10px;
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
    font-size: 1.1rem;
    font-weight: bold;
  }

  .sponsor-info p {
    color: #bbb;
    font-size: 14px;
    line-height: 1.4;
  }

  .visit-btn.gold {
    display: inline-block;
    padding: 10px 22px;
    border-radius: 30px;
    background: linear-gradient(90deg, #c8ab6b, #88694A);
    color: #000;
    font-weight: 600;
    text-decoration: none;
    margin-top: 15px;
    transition: all 0.3s;
  }

  .visit-btn.gold:hover {
    box-shadow: 0 0 18px rgba(212, 175, 55, .7);
    transform: translateY(-2px);
    color: #000;
  }

  .footer-bottom {
    text-align: center;
    font-size: .9rem;
    color: #9f9f9f;
    padding-top: 20px;
  }
</style>

<script>
// Translation Helper Logic (Preserved)
(async function autoTranslatePage() {
  const lang = localStorage.getItem("lang") || "en";
  if (lang !== "ar") return;

  // Set RTL layout
  document.documentElement.setAttribute("dir", "rtl");
  document.body.style.textAlign = "right";
  
  const brand = document.querySelector(".navbar-brand");
  if (brand) {
    brand.setAttribute("data-no-translate", "true");
    brand.style.direction = "ltr";
    brand.style.textAlign = "left";
  }
})();
</script>
