<?php
// Note: $conn, $currentLeagues, $tournaments, $team_id, $today, $currentYear passed from controller
use App\Core\SimplePaymentSystem;
?>
<!DOCTYPE html>
<html lang="en">
<?php view('partials.head', ['title' => 'Registrations - Wish2Padel']); ?>
<body style="background-color:#303030">

<?php view('partials.navbar'); ?>

<section class="py-5" id="scheduleList">
    <div class="container">
   
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
                        $start_date = new DateTime($tournament['start_date']);
                        $end_date   = new DateTime($tournament['end_date']);

                        // Check team registration and payment status using payment_transactions
                        $registration_status = null;
                        $payment_info = null;
                        $paymentSystem = new SimplePaymentSystem();
                        
                        if($team_id){
                            // Check if team is registered in team_info table
                            $stmt = $conn->prepare("SELECT id FROM team_info WHERE id = ? AND tournament_id = ?");
                            $stmt->bind_param("ii", $team_id, $tournament['id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->fetch_assoc()) {
                                // Team is registered, check payment status using payment_transactions
                                $is_paid = $paymentSystem->isTeamPaid($team_id, $tournament['id']);
                                
                                if ($is_paid) {
                                    $registration_status = 'paid';
                                    $payment_info = $paymentSystem->getTeamPaymentInfo($team_id, $tournament['id']);
                                } else {
                                    // Team registered but not paid - redirect to payment
                                    $registration_status = 'payment_required';
                                }
                            } else {
                                // Team not registered for this tournament
                                $registration_status = 'not_registered';
                            }
                        } else {
                            $registration_status = 'not_logged_in';
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
                    // Check registration closing date
                    $reg_until   = new DateTime($tournament['registration_until']);
                    $is_closed   = $today > $reg_until;

                    // Determine Status
                    $status = 'not_registered';
                    
                    if ($team_id) {
                        // Check if registered
                        $stmt = $conn->prepare("SELECT id FROM team_info WHERE id = ? AND tournament_id = ?");
                        $stmt->bind_param("ii", $team_id, $tournament['id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->fetch_assoc()) {
                            // Check payment
                            $paymentSystem = new SimplePaymentSystem();
                            if ($paymentSystem->isTeamPaid($team_id, $tournament['id'])) {
                                $status = 'paid';
                            } else {
                                $status = 'payment_required';
                            }
                        }
                    }
                    ?>

                    <!-- 🟡 BUTTON DISPLAY -->
                    <?php if ($is_closed): ?>
                        <button class="btn btn-secondary fw-bold px-4 py-3 rounded-pill w-100" disabled>
                            REGISTRATION CLOSED
                        </button>

                    <?php elseif ($status === 'payment_required'): ?>
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
                        <a href="<?= asset('payment') ?>?team_id=<?= $team_id ?>&tournament_id=<?= $tournament['id'] ?>"
                           class="btn btn-danger fw-bold w-100 rounded-pill">
                            <i class="bi bi-credit-card-fill"></i> COMPLETE PAYMENT
                        </a>

                    <?php elseif ($status === 'paid'): ?>
                        <button class="btn btn-outline-success fw-bold px-4 py-3 rounded-pill w-100" disabled>
                            YOU'VE ALREADY REGISTERED
                        </button>

                    <?php else: ?>
                        <a href="<?= asset('tournament-register') ?>?tournament_id=<?= $tournament['id'] ?>"
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
                    <div class="alert alert-warning text-center">No tournaments found for <?= $currentYear ?>.</div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <!-- Leader Login Box (Hidden to match legacy design) -->
    <!--
    <div class="container py-5">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="leader-box p-4 h-100 rounded shadow-sm animate-zoom">
            <h4 class="fw-bold text-gold mb-3">Team Leader Access</h4>
            <p class="text-silver mb-3">
              Already registered your team?  
              Log in to manage your roster, update player info.
            </p>
            <p class="text-silver mb-3">
              If you were redirected from the payment page or your transaction was interrupted, you can log in with your account first. After logging in, go to the team registration page and continue with your payment.
            </p>
            <a href="<?= asset('login') ?>" class="btn btn-gold btn-outline-gold fw-bold px-4">Leader Login</a>
          </div>
        </div>

        <div class="col-md-4" style="display:none">
          <div class="leader-box p-4 h-100 rounded shadow-sm animate-zoom">
            <h4 class="fw-bold text-gold mb-3">Sports Club / Organization</h4>
            <p class="text-silver mb-3">
              You can register your club for the next league by filling out the registration form. 
              Access your club profile in our app to update your photo gallery, contact information, club logo, and other details.
            </p>
            <p class="text-silver mb-3">
              To submit your club registration, our team will assist you with the process.
            </p>
            <a href="<?= asset('regis-club') ?>" class="btn btn-gold btn-outline-gold fw-bold px-4">Register Club</a>
          </div>
        </div>

        <div class="col-md-4" style="display:none">
          <div class="leader-box p-4 h-100 rounded shadow-sm animate-zoom">
            <h4 class="fw-bold text-gold mb-3">Player</h4>
            <p class="text-silver mb-3">
              Are you a player looking to join a league like ours but don’t have a regular club? 
              Tell us about yourself, and we will help you connect with a club where you can participate and be part of the excitement in forming a team!
            </p>
            <a href="<?= asset('register-player') ?>" class="btn btn-gold btn-outline-gold fw-bold px-4">Register</a>
          </div>
        </div>

      </div>
    </div>
    -->

    </div>
</section>

<!-- 🌟 SPONSORS SECTION -->
<section class="sponsors-page py-5">
    <div class="container-fluid text-center">
      <h5 class="fw-bold text-white tournament-name text-uppercase mb-4">
        Brought to you By
      </h5>

      <?php
      $allSponsors = array_merge($partnerSponsors ?? [], $premiumSponsors ?? []);
      ?>

      <?php if (!empty($allSponsors)): ?>
        <div class="sponsor-category mb-5">
          <?php foreach ($allSponsors as $row): ?>
            <div class="sponsor-item">
              <div class="sponsor-logo-box">
                <img src="<?= asset('uploads/sponsor/' . $row['sponsor_logo']) ?>"
                  alt="<?= htmlspecialchars($row['sponsor_name']) ?>">
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

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
    .text-gold {
      color: var(--gold-main);
    }

    .text-silver {
      color: var(--silver);
    }

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
    .tournament-box, .leader-box {
      background: linear-gradient(135deg, var(--dark-bg) 80%, var(--dark-soft));
      border: 1px solid rgba(212, 175, 55, 0.45);
      width: 100%;
      padding: 22px;
      border-radius: 14px;
      text-align: left;
      transition: transform 0.35s ease, box-shadow 0.35s ease;
    }

    .tournament-box:hover, .leader-box:hover {
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
      0% {
        transform: scale(0.92);
        opacity: 0;
      }

      100% {
        transform: scale(1);
        opacity: 1;
      }
    }

    /* ===================== SPONSOR GRID ===================== */
    .sponsor-category {
      background-color: transparent;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 24px;
      padding: 20px 0;
    }

    /* ===================== SPONSOR CARD (MATCH LEAGUE) ===================== */
    .sponsor-item {
      max-width: 400px;
      background: linear-gradient(135deg, var(--dark-bg) 80%, var(--dark-soft));
      flex-direction: row;
      /* side-by-side logo + text */
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
      filter: drop-shadow(0 0 8px rgba(212, 175, 55, 0.35));
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
      box-shadow: 0 0 18px rgba(212, 175, 55, 0.7);
      transform: translateY(-2px);
    }

    /* ===================== PREMIUM SPONSOR ===================== */
    .premium-item {
      border: 1px solid rgba(212, 175, 55, 0.7);
      box-shadow: inset 0 0 15px rgba(212, 175, 55, 0.15);
    }

    /* ===================== RESPONSIVE ===================== */
    @media (max-width: 1180px) {
      .league-title {
        font-size: 1.5rem;
      }

      .join-banner {
        font-size: 1.1rem;
      }

      .tournament-name {
        font-size: 1.1rem;
      }
    }

    @media (max-width: 992px) {
      .league-title {
        font-size: 1.3rem;
      }

      .join-banner {
        font-size: 1rem;
      }

      .tournament-name {
        font-size: 1rem;
      }
    }

    @media (max-width: 768px) {
      .league-title {
        font-size: 1.2rem;
      }

      .join-banner {
        font-size: 0.9rem;
      }
    }

    @media (max-width: 576px) {
      .league-title {
        font-size: 1.1rem;
      }

      .join-banner {
        font-size: 0.85rem;
      }

      .tournament-name {
        font-size: 0.9rem;
      }

      .gold-line {
        width: 60px;
        height: 2px;
      }
    }
</style>

<?php view('partials.footer'); ?>

<?php view('partials.scroll_top'); ?>
<?php view('partials.navbar_sticky_script', ['sticky_target' => 'scheduleList']); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
