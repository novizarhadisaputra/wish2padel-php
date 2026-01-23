<?php
// ================= FETCH SPONSORS =================
$result = $conn->query("SELECT * FROM sponsors ORDER BY sponsor_id DESC");

$premiumSponsors = [];
$standardSponsors = [];
$collaborates = [];

while ($row = $result->fetch_assoc()) {
  if ($row['status'] === 'sponsor') {
    if (($row['type'] ?? '') === 'premium') {
      $premiumSponsors[] = $row;
    } else {
      $standardSponsors[] = $row;
    }
  } elseif ($row['status'] === 'collaborate') {
    $collaborates[] = $row;
  }
}
?>

<footer class="footer-main">

  <!-- ================= HEADING ================= -->
  <div class="footer-container footer-heading">
    <p class="footer-tagline">
      Elevate Your Game with Wish2Padel Team League
    </p>
  </div>

  <!-- ================= INFO ================= -->
  <div class="footer-container footer-info">

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
      <p><a href="mailto:info@wish2padel.com">info@wish2padel.com</a></p>
      <p>Phone: +966 55 322 4559</p>
    </div>

    <div>
      <h6>Socials</h6>
      <a href="https://www.instagram.com/wish2padel" target="_blank">
        <i class="bi bi-instagram"></i> Instagram
      </a>
      <a href="https://www.tiktok.com/@wish2padel.ksa" target="_blank">
        <i class="bi bi-tiktok"></i> TikTok
      </a>
    </div>

  </div>


  <hr>

  <!-- ================= COPYRIGHT ================= -->
  <div class="footer-container footer-bottom">
    &copy; <?= date('Y') ?> Wish2Padel. All rights reserved.
  </div>

</footer>

<style>
  /* ========== STICKY FOOTER BASE ========== */
  html,
  body {
    height: 100%;
    margin: 0;
  }

  body.page-wrapper {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }

  .page-content {
    flex: 1;
  }

  /* ========== FOOTER CORE ========== */
  .footer-main {
    background: linear-gradient(90deg, #000, #1a1a1a);
    color: #fff;
    padding: 70px 20px 30px;
  }

  .footer-container {
    max-width: 1200px;
    margin: 0 auto;
  }

  /* ========== TEXT ========== */
  .footer-tagline {
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 3px;
    font-size: 2rem;
    color: #c5a369;
    max-width: 520px;
    margin-bottom: 50px;
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
    color: #fff;
    text-decoration: none;
    display: block;
    margin-bottom: 10px;
  }

  .footer-info a:hover {
    color: #c5a369;
  }

  /* ========== SPONSORS ========== */
  .sponsor-block {
    margin: 60px auto;
    display: flex;
    flex-direction: column;
    gap: 50px;
  }

  .sponsor-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 40px;
    flex-wrap: wrap;
  }

  .sponsor-row img {
    height: 90px;
    max-width: 200px;
    object-fit: contain;
    transition: transform .3s, opacity .3s;
    opacity: .9;
  }

  .sponsor-row.premium img {
    height: 120px;
  }

  .sponsor-row img:hover {
    transform: scale(1.08);
    opacity: 1;
  }

  /* ========== FOOTER BOTTOM ========== */
  .footer-bottom {
    text-align: center;
    font-size: .9rem;
    color: #9f9f9f;
    margin-top: 20px;
  }

  .footer-main hr {
    border-color: rgba(255, 255, 255, .15);
    margin: 40px 0 20px;
  }
</style>