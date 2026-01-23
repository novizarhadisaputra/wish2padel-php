<?php
session_start();
require 'config.php';
require_once 'SimplePaymentSystem.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
?>
  <?php
  // Ambil data sponsor dan collaborator
  $resultSponsors = $conn->query("SELECT * FROM sponsors ORDER BY sponsor_id DESC");

  $resultCollaborators = $conn->query("SELECT * FROM sponsors WHERE status = 'collaborate' ORDER BY sponsor_id DESC");

  #Tiers Highest to Lowest
  $partnerSponsors = [];
  $premiumSponsors = [];
  $goldSponsors = [];
  $standardSponsors = [];

  #Collaborators
  $collaborators = [];

  while ($row = $resultSponsors->fetch_assoc()) {
    switch ($row['type'] ?? '') {
      case 'partner':
        $partnerSponsors[] = $row;
        break;

      case 'premium':
        $premiumSponsors[] = $row;
        break;

      case 'gold':
        $goldSponsors[] = $row;
        break;

      case 'standard':
        $standardSponsors[] = $row;
        break;
    }
  }

  // Separate collaborators by status
  while ($row = $resultCollaborators->fetch_assoc()) {
    $collaborators[] = $row;
  }
  ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registrations - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/stylee.css?v=12">
</head>

<body style="background-color:#303030">

  <?php require 'src/navbar.php' ?>

  <section class="py-5">
    <div class="container">
      <?php
      $team_id = $_SESSION['team_id'] ?? null;
      date_default_timezone_set("Asia/Riyadh");
      $today = new DateTime();
      $currentYear = (int)$today->format('Y');

      // Ambil semua liga di tahun ini
      $leagueStmt = $conn->prepare("SELECT id, name, date FROM league WHERE YEAR(date) = ? ORDER BY date DESC");
      $leagueStmt->bind_param("i", $currentYear);
      $leagueStmt->execute();
      $leagueRes = $leagueStmt->get_result();
      $currentLeagues = $leagueRes->fetch_all(MYSQLI_ASSOC);
      $leagueIds = array_column($currentLeagues, 'id');

      // Ambil semua turnamen dari liga tsb
      $tournaments = [];
      if ($leagueIds) {
        $in = implode(',', $leagueIds);
        $tournamentResult = $conn->query("
              SELECT id, name, description, start_date, end_date, registration_until, id_league 
              FROM tournaments 
              WHERE id_league IN ($in)
              ORDER BY start_date DESC
          ");
        $tournaments = $tournamentResult->fetch_all(MYSQLI_ASSOC);
      }
      ?>

      <?php foreach ($currentLeagues as $league): ?>
        <div class="text-center mb-5">
          <h2 class="fw-bold league-title text-gold mb-0" style="letter-spacing:1px;">
            <?= strtoupper(htmlspecialchars($league['name'])) ?>
          </h2>
          <div class="gold-line mx-auto my-2"></div>
          <h5 class="text-light fw-semibold"><?= $currentYear ?></h5>
        </div>

        <div class="row g-4 mb-5">
          <?php
          $leagueTournaments = array_filter($tournaments, fn($t) => $t['id_league'] == $league['id']);
          if ($leagueTournaments):
            foreach ($leagueTournaments as $tournament):
              $start_date  = new DateTime($tournament['start_date']);
              $end_date    = new DateTime($tournament['end_date']);
              $reg_until   = new DateTime($tournament['registration_until']);
              $is_closed   = $today > $reg_until;

              // ✅ Cek apakah tim sudah terdaftar
              $alreadyRegistered = false;
              if (!empty($team_id)) {
                $stmt = $conn->prepare("
                        SELECT COUNT(*) AS total 
                        FROM team_info 
                        WHERE id = ? AND tournament_id = ?
                    ");
                $stmt->bind_param("ii", $team_id, $tournament['id']);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $alreadyRegistered = ($res['total'] ?? 0) > 0;
                $stmt->close();
              }

          ?>
              <div class="col-12 col-md-6 col-lg-4">
                <div class="tournament-box p-4 rounded shadow-lg h-100 animate-zoom">

                  <!-- Join Header -->
                  <div class="join-banner text-uppercase fw-bold mb-4">
                    Join the Battle
                  </div>

                  <!-- Tournament Title -->
                  <h5 class="fw-bold text-white tournament-name text-uppercase mb-3">
                    <?= htmlspecialchars($tournament['name']) ?>
                  </h5>

                  <!-- Unified Date -->
                  <div class="fw-bold text-gold mb-4">
                    <?= strtoupper($start_date->format('j F Y')) ?> <br> UNTIL <?= strtoupper($end_date->format('j F Y')) ?>
                  </div>

                  <!-- 🟡 Register Button Logic -->
                  <?php
                  // 🟡 LOGIC: REGISTER / PAYMENT STATUS
                  $paymentSystem = new SimplePaymentSystem();
                  $registration_status = 'not_registered'; // default

                  if (!empty($team_id)) {
                    // Cek apakah tim sudah daftar di turnamen ini
                    $stmt = $conn->prepare("
        SELECT id FROM team_info WHERE id = ? AND tournament_id = ?
    ");
                    $stmt->bind_param("ii", $team_id, $tournament['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $is_registered = $result->fetch_assoc() ? true : false;
                    $stmt->close();

                    if ($is_registered) {
                      // Cek pembayaran via payment_transactions
                      $is_paid = $paymentSystem->isTeamPaid($team_id, $tournament['id']);
                      if ($is_paid) {
                        $registration_status = 'paid';
                      } else {
                        $registration_status = 'payment_required';
                      }
                    }
                  }
                  ?>

                  <!-- 🟡 REGISTER / PAYMENT BUTTON DISPLAY -->
                  <?php if ($is_closed): ?>
                    <button class="btn btn-secondary fw-bold px-4 py-3 rounded-pill w-100" disabled>
                      REGISTRATION CLOSED
                    </button>

                  <?php elseif ($registration_status === 'payment_required'): ?>
                    <h5 class="fw-bold text-danger mb-3">
                      <i class="bi bi-exclamation-triangle-fill"></i> PAYMENT REQUIRED
                    </h5>
                    <p class="text-silver small mb-3">
                      Your team is already registered for
                      <strong><?= htmlspecialchars($tournament['name']) ?></strong>.<br>
                      Please complete the payment to confirm your spot.<br>
                      <span class="text-gold">Battle Dates:</span>
                      <?= $start_date->format('F j, Y') ?> – <?= $end_date->format('F j, Y') ?>
                    </p>
                    <a href="payment.php?team_id=<?= $team_id ?>&tournament_id=<?= $tournament['id'] ?>"
                      class="btn btn-danger fw-bold w-100 rounded-pill">
                      <i class="bi bi-credit-card-fill"></i> COMPLETE PAYMENT
                    </a>

                  <?php elseif ($registration_status === 'paid'): ?>
                    <button class="btn btn-outline-success fw-bold px-4 py-3 rounded-pill w-100" disabled>
                      YOU'VE ALREADY REGISTERED
                    </button>

                  <?php else: ?>
                    <a href="tournament_regis?tournament_id=<?= $tournament['id'] ?>"
                      class="btn btn-gold fw-bold px-4 py-3 rounded-pill w-100">
                      REGISTER NOW
                    </a>
                  <?php endif; ?>


                  <!-- Deadline -->
                  <div class="mt-4 border-top border-secondary pt-3">
                    <span class="fw-semibold text-gold small text-uppercase">
                      Registration until <?= strtoupper($reg_until->format('j F Y')) ?>
                    </span>
                  </div>

                </div>
              </div>
            <?php endforeach;
          else: ?>
            <div class="col-12">
              <div class="alert alert-warning text-center">
                No tournaments found for <?= $currentYear ?>.
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <!-- 🌟 SPONSORS SECTION -->
<section class="sponsors-page py-5">
  <div class="container-fluid text-center"> <!-- full-width container -->
    <h5 class="fw-bold text-white tournament-name text-uppercase mb-4">
      Brought to you By
    </h5>

    <?php 
    // Merge all sponsors + collaborators into one array
    $allSponsors = array_merge($partnerSponsors, $premiumSponsors);
    ?>

    <?php if (!empty($allSponsors)): ?>
      <div class="sponsor-category mb-5">
        <?php foreach ($allSponsors as $row): ?>
          <div class="sponsor-item">
            <div class="sponsor-logo-box">
              <img src="uploads/sponsor/<?= $row['sponsor_logo'] ?>" 
                   alt="<?= htmlspecialchars($row['sponsor_name']) ?>">
            </div>
            <div class="sponsor-info">
              <h5><?= htmlspecialchars($row['sponsor_name']) ?></h5>
              <?php if(!empty($row['description'])): ?>
                <p><?= htmlspecialchars($row['description']) ?></p>
              <?php endif; ?>
            </div>
            <?php if (!empty($row['website'])): ?>
              <div class="sponsor-action">
                <a href="<?= htmlspecialchars($row['website']) ?>" class="visit-btn gold" target="_blank">
                  Visit Website
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

  </div>
</section>


  <style>
/* ===================== ROOT COLORS ===================== */
:root {
  --gold-main: #c5a369;
  --gold-dark: #88694A;
  --gold-light: #d6b880;
  --silver: #9f9f9f;
  --dark-bg: #000;
  --dark-soft: #1a1a1a;
}

/* ===================== TEXT COLORS ===================== */
.text-gold { color: var(--gold-main); }
.text-silver { color: var(--silver); }

/* ===================== TITLES ===================== */
.league-title {
  font-size: 2rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.gold-line {
  width: 80px;
  height: 3px;
  background: linear-gradient(90deg, var(--gold-dark), var(--gold-main));
  border-radius: 10px;
  margin-top: 8px;
}

/* ===================== TOURNAMENT / LEAGUE BOX ===================== */
.tournament-box {
  background: linear-gradient(135deg, var(--dark-bg) 80%, var(--dark-soft));
  border: 1px solid rgba(212, 175, 55, 0.45);
  width: 100%;
  padding: 22px;
  border-radius: 14px;
  text-align: left;
  transition: transform 0.35s ease, box-shadow 0.35s ease;
}

.tournament-box:hover {
  transform: scale(1.03);
  box-shadow: 0 0 25px rgba(212, 175, 55, 0.3);
}

/* ===================== GOLD BACKGROUND ===================== */
.bg-gold {
  background: linear-gradient(90deg, #c8ab6b, var(--gold-dark));
  color: #000;
}

/* ===================== CONTENT ===================== */
.join-banner {
  font-size: 1.4rem;
  letter-spacing: 2px;
  color: var(--gold-light);
  text-transform: uppercase;
}

.tournament-name {
  font-size: 1.25rem;
  letter-spacing: 0.6px;
  color: #fff;
}

/* ===================== ANIMATION ===================== */
.animate-zoom {
  animation: zoomIn 0.6s ease forwards;
}

@keyframes zoomIn {
  0% { transform: scale(0.92); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

/* ===================== SPONSOR GRID ===================== */
.sponsor-category {
  display: flex;
  flex-wrap: wrap;           /* wrap to next line if too many */
  justify-content: center;   /* center horizontally */
  gap: 24px;                 /* spacing between cards */
  padding: 20px 0;
}


/* ===================== SPONSOR CARD (MATCH LEAGUE) ===================== */
.sponsor-item {
  max-width: 400px;
  background: linear-gradient(135deg, var(--dark-bg) 80%, var(--dark-soft));
  flex-direction: row;         /* side-by-side logo + text */
  border: 1px solid rgba(212, 175, 55, 0.45);
  border-radius: 14px;
  padding: 22px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: transform 0.35s ease, box-shadow 0.35s ease;
}

.sponsor-item:hover {
  transform: scale(1.03);
  box-shadow: 0 0 25px rgba(212, 175, 55, 0.3);
}

/* ===================== SPONSOR LOGO ===================== */
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
  filter: drop-shadow(0 0 8px rgba(212,175,55,0.35));
}

/* ===================== SPONSOR TEXT ===================== */
.sponsor-info h5 {
  color: var(--gold-main);
  font-weight: 600;
  margin-bottom: 10px;
  letter-spacing: 0.7px;
  text-transform: uppercase;
}

.sponsor-info p {
  font-size: 14px;
  color: #bbb;
  line-height: 1.7;
}

/* ===================== BUTTON ===================== */
.sponsor-action {
  margin-top: 18px;
}

.visit-btn.gold {
  display: inline-block;
  padding: 10px 22px;
  border-radius: 30px;
  background: linear-gradient(90deg, #c8ab6b, var(--gold-dark));
  color: #000;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

.visit-btn.gold:hover {
  box-shadow: 0 0 18px rgba(212,175,55,0.7);
  transform: translateY(-2px);
}

/* ===================== PREMIUM SPONSOR ===================== */
.premium-item {
  border: 1px solid rgba(212,175,55,0.7);
  box-shadow: inset 0 0 15px rgba(212,175,55,0.15);
}

/* ===================== RESPONSIVE ===================== */
@media (max-width: 1180px) {
  .league-title { font-size: 1.5rem; }
  .join-banner { font-size: 1.1rem; }
  .tournament-name { font-size: 1.1rem; }
}

@media (max-width: 992px) {
  .league-title { font-size: 1.3rem; }
  .join-banner { font-size: 1rem; }
  .tournament-name { font-size: 1rem; }
}

@media (max-width: 768px) {
  .league-title { font-size: 1.2rem; }
  .join-banner { font-size: 0.9rem; }
}

@media (max-width: 576px) {
  .league-title { font-size: 1.1rem; }
  .join-banner { font-size: 0.85rem; }
  .tournament-name { font-size: 0.9rem; }
  .gold-line { width: 60px; height: 2px; }
}
  </style>

  <?php require 'src/footer.php' ?>

  <!-- Scroll to Top Button -->
  <button id="scrollTopBtn" title="Go to top">↑</button>

  <script>
    const scrollBtn = document.getElementById("scrollTopBtn");

    // Show/hide button on scroll
    window.onscroll = function() {
      if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        scrollBtn.style.display = "block";
      } else {
        scrollBtn.style.display = "none";
      }
    };

    // Scroll to top smoothly
    scrollBtn.addEventListener("click", function() {
      window.scrollTo({
        top: 0,
        behavior: "smooth"
      });
    });
  </script>


  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const navbar = document.getElementById('maiavbar');
      const hero = document.getElementById('scheduleList'); // Pastikan ada elemen heroCarousel di halaman

      function toggleNavbarFixed() {
        if (!hero) return; // kalau heroCarousel gak ada, skip

        const scrollPos = window.scrollY;
        const heroHeight = hero.offsetHeight;

        if (scrollPos >= heroHeight) {
          navbar.classList.add('navbar-fixed');
          document.body.style.paddingTop = navbar.offsetHeight + 'px'; // supaya konten gak tertutup
        } else {
          navbar.classList.remove('navbar-fixed');
          document.body.style.paddingTop = '0';
        }
      }

      window.addEventListener('scroll', toggleNavbarFixed);
      toggleNavbarFixed(); // jalankan sekali saat load
    });
  </script>

  <style>
    /* Navbar default (sudah ada background dan shadow dari kamu) */
    nav#maiavbar {

      width: 100%;
      transition: all 0.3s ease;
      z-index: 9999;
    }

    /* Navbar jadi fixed dan muncul dengan animasi */
    nav#maiavbar.navbar-fixed {
      position: fixed;
      top: 0;
      left: 0;
      background: linear-gradient(90deg, #00796B, #004D40);
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
      animation: fadeInDown 0.4s ease forwards;
    }

    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>