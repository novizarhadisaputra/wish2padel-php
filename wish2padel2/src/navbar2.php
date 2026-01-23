<nav id="maiavbar" class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="../index">
      <img
        src="../assets/image/w2p.png"
        alt="Logo"
        class="d-inline-block align-text-top" />
    </a>
    <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#mainNavbar"
      aria-controls="mainNavbar"
      aria-expanded="false"
      aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!--  <?php if (empty($_SESSION['team_id'])): ?>-->
        <!--  <li class="nav-item">-->
        <!--    <a class="nav-link" href="../index">Main Page</a>-->
        <!--  </li>-->
        <!--<?php endif ?>-->

        <!-- <li class="nav-item dropdown">
          <a
            class="nav-link dropdown-toggle"
            href="#"
            id="langDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            🌐
          </a>
          <ul class="dropdown-menu" aria-labelledby="langDropdown">
            <li>
              <a class="dropdown-item" href="#" onclick="changeLang('en')">English</a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="changeLang('ar')">العربية</a>
            </li>
          </ul>
        </li> -->

        <!-- ✅ League Dropdown -->
        <li class="nav-item dropdown position-static">
          <a class="nav-link dropdown-toggle" href="#" id="ligaDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">League</a>
          <div class="dropdown-menu mega-dropdown shadow" aria-labelledby="ligaDropdown">
            <div class="row">
              <div class="col-6 col-md-3">
                <h6>About League</h6>
                <a class="dropdown-item" href="../about-league">Presentation</a>
                <a class="dropdown-item" href="../document">Documents</a>
              </div>
              <div class="col-6 col-md-3">
                <h6>League</h6>
                <a class="dropdown-item" href="../league">League Hub</a>
              </div>
            </div>
          </div>
        </li>

        <?php if (!empty($_SESSION['team_id'])): ?>
          <?php
          // ==========================
          // CEK TRANSFER WINDOW AKTIF
          // ==========================

          $now = date('Y-m-d H:i:s');
          $showAddMember = false;

          $stmt = $conn->prepare("
    SELECT * FROM transfer_windows 
    WHERE start_date <= ? AND end_date >= ?
    ORDER BY start_date DESC
");
          $stmt->bind_param("ss", $now, $now);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result->num_rows > 0) {
            $activeWindow = $result->fetch_assoc();
            $showAddMember = true;
          }
          ?>

          <!-- ==========================
     NAVBAR MY TEAM
     ========================== -->

          <li class="nav-item dropdown position-static">
            <a class="nav-link dropdown-toggle" href="#" id="ligaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              My Team
              <?php if ($showAddMember): ?>
                <span class="badge bg-danger ms-1">!</span>
              <?php endif; ?>
            </a>
            <div class="dropdown-menu mega-dropdown shadow" aria-labelledby="ligaDropdown">
              <div class="row">
                <div class="col-6 col-md-3">
                  <h6>Match</h6>
                  <a class="dropdown-item" href="../scheduled">Scheduled & Results</a>
                </div>
                <div class="col-6 col-md-3">
                  <h6>Team</h6>
                  <a class="dropdown-item d-flex justify-content-between align-items-center" href="../myteam">
                    My Team
                    <?php if ($showAddMember): ?>
                      <?php
                      $start = date("M d", strtotime($activeWindow['start_date']));
                      $end   = date("M d", strtotime($activeWindow['end_date']));
                      ?>
                      <span class="badge bg-danger ms-2">OPEN <?= $start ?> → <?= $end ?></span>
                    <?php endif; ?>
                  </a>

                  <a class="dropdown-item" href="../windows">Transfer Windows</a>
                </div>
              </div>
            </div>
          </li>
        <?php endif; ?>

        <li class="nav-item">
          <a href="../regis" class="btn btn-gold">Register Team</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="../club.php">Club</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="../news">News</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="gallery">Media</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="../sponsor">Sponsors</a>
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
              aria-expanded="false">
              <?= htmlspecialchars($username) ?>
            </a>
            <ul
              class="dropdown-menu dropdown-menu-end"
              aria-labelledby="profileDropdown">
              <li>
                <hr class="dropdown-divider" />
              </li>
              <li>
                <a class="dropdown-item text-danger" href="../logout">Logout</a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="../login/login" class="nav-link text-light" title="Login">
              <i class="bi bi-person-circle" style="font-size: 1.5rem"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<script>
  function changeLang(lang) {
    if (!lang) return;
    const baseURL = "https://wish2padel.com";
    window.location.href =
      "https://translate.google.com/translate?hl=" +
      lang +
      "&sl=auto&u=" +
      encodeURIComponent(baseURL);
  }
</script>
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

  .lang-switcher {
    position: fixed;
    top: 10px;
    right: 10px;
    font-family: sans-serif;
    z-index: 9999;
  }

  .lang-btn {
    background: white;
    border: 1px solid #ccc;
    border-radius: 50%;
    padding: 5px 8px;
    cursor: pointer;
    font-size: 18px;
  }

  .lang-options {
    display: none;
    /* awalnya tersembunyi */
    margin-top: 5px;
    background: white;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 3px;
  }

  .lang-options select {
    border: none;
    background: transparent;
    font-size: 14px;
    cursor: pointer;
  }
</style>
<link rel="stylesheet" href="../css/stylee.css" />