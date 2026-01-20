<?php
// Note: $conn, $currentLeagues, $tournaments, $team_id, $today, $currentYear passed from controller
use App\Core\SimplePaymentSystem;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registrations - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
</head>
<body style="background-color:#303030">

<?php view('partials.navbar'); ?>

<section class="py-5">
    <div class="container">
   
    <?php foreach ($currentLeagues as $league): ?>
        <h3 class="text-white mb-4 fw-bold"><?= htmlspecialchars($league['name']) ?> <?= $currentYear ?></h3>
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
            <div class="col-12 col-md-12 mb-5 col-lg-4">
                <div class="tournament-box p-4 rounded shadow-lg h-100 animate-zoom">

                    <?php if ($registration_status === 'paid'): ?>
                        <h4 class="fw-bold text-gold mb-4">
                            <i class="bi bi-check-circle-fill"></i> Successfully Registered
                        </h4>
                        <p class="text-silver mb-3">
                            Your team has successfully registered and paid for <strong><?= htmlspecialchars($tournament['name']) ?></strong>.<br>
                            Battle Dates: <?= $start_date->format('F j, Y') ?> – <?= $end_date->format('F j, Y') ?>
                            <?php if ($payment_info): ?>
                                <br><small class="text-muted">
                                    Payment ID: <?= htmlspecialchars($payment_info['payment_id']) ?><br>
                                    Amount: <?= htmlspecialchars($payment_info['amount']) ?> <?= htmlspecialchars($payment_info['currency']) ?><br>
                                    Paid on: <?= date('M j, Y g:i A', strtotime($payment_info['created_at'])) ?>
                                </small>
                            <?php endif; ?>
                        </p>
                     
                    <?php elseif ($registration_status === 'payment_pending'): ?>
                        <h4 class="fw-bold text-warning mb-4">
                            <i class="bi bi-clock-fill"></i> Payment Pending
                        </h4>
                        <p class="text-silver mb-3">
                            Your team is registered for <strong><?= htmlspecialchars($tournament['name']) ?></strong>.<br>
                            Payment Status: <?= htmlspecialchars($payment_info['status'] ?? 'Pending') ?><br>
                            Battle Dates: <?= $start_date->format('F j, Y') ?> – <?= $end_date->format('F j, Y') ?>
                            <?php if ($payment_info): ?>
                                <br><small class="text-muted">
                                    Payment ID: <?= htmlspecialchars($payment_info['payment_id']) ?><br>
                                    Amount: <?= htmlspecialchars($payment_info['amount']) ?> <?= htmlspecialchars($payment_info['currency']) ?>
                                </small>
                            <?php endif; ?>
                        </p>
                        <a href="payment_verify_integrated.php?payment_id=<?= $payment_info['payment_id'] ?? '' ?>&tournament_id=<?= $tournament['id'] ?>" 
                           class="btn btn-warning btn-sm fw-bold px-4">
                            <i class="bi bi-credit-card-fill"></i> Check Payment Status
                        </a>

                    <?php elseif (in_array($registration_status, ['payment_required', 'payment_failed'])): ?>
                        <h4 class="fw-bold text-danger mb-4">
                            <i class="bi bi-exclamation-triangle-fill"></i> Payment Required
                        </h4>
                        <p class="text-silver mb-3">
                            Your team is registered for <strong><?= htmlspecialchars($tournament['name']) ?></strong>.<br>
                            <!-- <?php if ($payment_details): ?>
                                Payment Status: <?= htmlspecialchars($payment_details['message']) ?><br>
                            <?php endif; ?> -->
                            Complete payment to secure your spot.<br>
                            Battle Dates: <?= $start_date->format('F j, Y') ?> – <?= $end_date->format('F j, Y') ?>
                        </p>
                        <a href="payment.php?team_id=<?= $team_id ?>&tournament_id=<?= $tournament['id'] ?>" 
                           class="btn btn-danger btn-sm fw-bold px-4">
                            <i class="bi bi-credit-card-fill"></i> Complete Payment
                        </a>

                    <?php elseif ($today >= $start_date && $today <= $end_date): ?>
                        <h4 class="fw-bold text-gold mb-4">Tournament in Progress</h4>
                        <p class="text-silver mb-0">
                            <strong style="font-size: 1.5em; color:white">
                                <?= htmlspecialchars($tournament['name']) ?>
                            </strong><br> is currently ongoing!<br>
                            Dates: <?= $start_date->format('F j, Y') ?> – <?= $end_date->format('F j, Y') ?><br>
                            Prepare for next year!
                        </p>

                    <?php elseif ($today > $end_date): ?>
                        <h4 class="fw-bold text-gold mb-4">Tournament Completed</h4>
                        <p class="text-silver mb-0">
                            <strong style="font-size: 1.5em; color:white">
                                <?= htmlspecialchars($tournament['name']) ?>
                            </strong><br> has ended.<br>
                            Ended on: <?= $end_date->format('F j, Y') ?><br>
                            Prepare for next year!
                        </p>

                    <?php else: ?>
    <?php 
        // Registration closes 7 days before start date
        $registration_close_date = (clone $start_date)->modify('-7 days');
    ?>
    
    <h4 class="fw-bold text-gold mb-4">Join the Battle</h4>
    <p class="text-silver mb-4">
        <strong style="font-size: 1.5em; color:white">
            <?= htmlspecialchars($tournament['name']) ?>
        </strong><br>

        <?php if ($today > $registration_close_date): ?>
            <span class="badge bg-danger px-3 py-2 rounded-pill">Registration Closed</span><br>
            Registration closed on: <?= $registration_close_date->format('F j, Y') ?><br>
            Battle Dates: <?= $start_date->format('F j, Y') ?> – <?= $end_date->format('F j, Y') ?>
        <?php else: ?>
            is open for registration!<br>
            Registration closes: <?= $registration_close_date->format('F j, Y') ?><br>
            Battle Dates: <?= $start_date->format('F j, Y') ?> – <?= $end_date->format('F j, Y') ?>
        <?php endif; ?>
    </p>

    <?php if ($today <= $registration_close_date): ?>
        <a href="tournament_regis?tournament_id=<?= $tournament['id'] ?>" class="btn btn-gold btn-sm fw-bold px-4">REGISTER</a>
    <?php else: ?>
        <button class="btn btn-secondary btn-sm fw-bold px-4" disabled>REGISTRATION CLOSED</button>
    <?php endif; ?>
<?php endif; ?>


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

    <!-- Leader Login Box -->
    <div class="container py-5">
  <div class="row g-4">

    <!-- Grid 1: Team Leader -->
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

    <!-- Grid 2: Sports Club / Organization -->
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
        <a href="regis-club.php" class="btn btn-gold btn-outline-gold fw-bold px-4">Register Club</a>
      </div>
    </div>

    <!-- Grid 3: Player -->
    <div class="col-md-4" style="display:none">
      <div class="leader-box p-4 h-100 rounded shadow-sm animate-zoom">
        <h4 class="fw-bold text-gold mb-3">Player</h4>
        <p class="text-silver mb-3">
          Are you a player looking to join a league like ours but don’t have a regular club? 
          Tell us about yourself, and we will help you connect with a club where you can participate and be part of the excitement in forming a team!
        </p>
        <a href="player.php" class="btn btn-gold btn-outline-gold fw-bold px-4">Register</a>
      </div>
    </div>

  </div>
</div>

    </div>
</section>




<style>
    /* Colors */
    .text-gold { color: #88694A; }
    .text-silver { color: #696969; }

    

    /* Boxes */
    .tournament-box,
    .leader-box {
        background: linear-gradient(135deg, #000 80%, #1a1a1a);
        border: 1px solid rgba(212,175,55,0.4);
        width: 100%;
    }

    /* Animation */
    .animate-zoom {
        animation: zoomIn 0.6s ease forwards;
    }
    @keyframes zoomIn {
        0% { transform: scale(0.9); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
    .tournament-box:hover,
    .leader-box:hover {
        transform: scale(1.02);
        transition: transform 0.3s ease;
    }
</style>

<?php view('partials.footer'); ?>

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
  document.addEventListener('DOMContentLoaded', function () {
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
    box-shadow: 0 3px 8px rgba(0,0,0,0.25);
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
