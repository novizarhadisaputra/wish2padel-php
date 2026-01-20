<nav id="maiavbar" class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="index">
      <img
        src="../assets/image/w2p.png"
        alt="Logo"
        class="d-inline-block align-text-top"
      />
    </a>
    <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#mainNavbar"
      aria-controls="mainNavbar"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!--<?php if (empty($_SESSION['team_id'])): ?>-->
        <!--  <li class="nav-item">-->
        <!--    <a class="nav-link" href="documentation">Main Page</a>-->
        <!--  </li>-->
        <!--<?php endif ?>-->

        <li class="nav-item dropdown position-static">
          <a
            class="nav-link dropdown-toggle"
            href="#"
            id="ligaDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            League
          </a>
          <div
            class="dropdown-menu mega-dropdown shadow"
            aria-labelledby="ligaDropdown"
          >
            <div class="row">
              <div class="col-6 col-md-3">
                <h6>About League</h6>
                <a class="dropdown-item" href="about-league">Presentation</a>
                <a class="dropdown-item" href="document">Documents</a>
              </div>
              <div class="col-6 col-md-3">
                <h6>League</h6>
                <a class="dropdown-item" href="league">League Hub</a>
              </div>
            </div>
          </div>
        </li>
       

        <li class="nav-item">
          <a href="regis.php" class="btn btn-gold">Regist Team</a>
        </li>

        <?php 
            // Initialize variables safely
          $username = $_SESSION['username'] ?? null;
          $team_id = $_SESSION['team_id'] ?? null;
        ?>
        <?php if ($team_id): ?> 
            <?php 
              $payment_system = getPaymentSystem();
              $payment_details = $payment_system->getTeamPaymentDetails($team_id, 1);
              $payment_paid = $payment_details['is_paid'];
            ?>
            <li class="nav-item">
                <?php if (!$payment_paid): ?>
                    <?php if ($payment_details['exists'] && in_array($payment_details['status'], ['pending', 'processing'])): ?>
                        <a class="nav-link text-warning" href="payment.php?team_id=<?= $team_id ?>&tournament_id=1">
                            <i class="bi bi-clock"></i> Payment Processing
                        </a>
                    <?php elseif ($payment_details['exists'] && in_array($payment_details['status'], ['failed', 'expired'])): ?>
                        <a class="nav-link text-danger" href="payment.php?team_id=<?= $team_id ?>&tournament_id=1">
                            <i class="bi bi-exclamation-triangle"></i> Payment Failed
                        </a>
                    <?php else: ?>
                        <a class="nav-link text-warning" href="payment.php?team_id=<?= $team_id ?>&tournament_id=1">
                            <i class="bi bi-exclamation-circle"></i> Payment Required
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="nav-link text-success">
                        <i class="bi bi-check-circle"></i> Payment Completed
                    </span>
                <?php endif ?>
            </li>
        <?php endif; ?>

        <li class="nav-item">
          <a class="nav-link" href="club.php">Club</a>
        </li>

        <?php if (empty($_SESSION['team_id'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="news">News</a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="media/gallery">Media</a>
          </li>
        <?php endif ?>

        <li class="nav-item">
          <a class="nav-link" href="sponsor">Sponsors</a>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if ($username): ?>
        <li class="nav-item dropdown">
          <a
            class="nav-link dropdown-toggle text-light"
            href="#"
            id="profileDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <?= htmlspecialchars($username) ?>
          </a>
          <ul
            class="dropdown-menu dropdown-menu-end"
            aria-labelledby="profileDropdown"
          >
            <li><hr class="dropdown-divider" /></li>
            <li>
              <a class="dropdown-item text-danger" href="logout">Logout</a>
            </li>
          </ul>
        </li>
        <?php else: ?>
        <li class="nav-item">
          <a href="login/login.php" class="nav-link text-light" title="Login">
            <i class="bi bi-person-circle" style="font-size: 1.5rem"></i>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<style>
  nav .navbar-brand {
    margin-left: 50px;
    margin-right: 80px;
  }
  .navbar-brand img {
    height: 120px;
    width: auto;
  }
  nav .nav-item {
    margin-left: 40px;
  }
</style>
