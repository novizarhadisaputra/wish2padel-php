<nav id="maiavbar" class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <?php
    $username = $_SESSION['username'] ?? 'Admin';
    ?>
    <a class="navbar-brand d-flex align-items-center p-0" href="<?= asset('/') ?>">
      <img
        src="<?= getSiteLogo() ?>"
        alt="Logo"
        class="me-2" />
    </a>
    
    <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#adminNavContent"
      aria-controls="adminNavContent"
      aria-expanded="false"
      aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="adminNavContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?= asset('admin/dashboard') ?>">Dashboard</a>
        </li>

        <!-- League Management Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="leagueDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            League
          </a>
          <ul class="dropdown-menu" aria-labelledby="leagueDropdown">
            <li><a class="dropdown-item" href="<?= asset('admin/club') ?>">Clubs</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/team') ?>">Teams</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/players') ?>">Players</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/division') ?>">Divisions</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/windows') ?>">Transfer Windows</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/registrations') ?>">Registrations</a></li>
          </ul>
        </li>

        <!-- Tournament Management Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="tournamentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Tournaments
          </a>
          <ul class="dropdown-menu" aria-labelledby="tournamentDropdown">
            <li><a class="dropdown-item" href="<?= asset('admin/tournament') ?>">Manage Tournaments</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/matches') ?>">Matches</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/result') ?>">Match Results</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/pair') ?>">Pairs</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/playoff') ?>">Playoffs</a></li>
          </ul>
        </li>

        <!-- Content & Media Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="contentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Content
          </a>
          <ul class="dropdown-menu" aria-labelledby="contentDropdown">
            <li><a class="dropdown-item" href="<?= asset('admin/news') ?>">News</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/sponsors') ?>">Sponsors</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/gallery') ?>">Gallery</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/documents') ?>">Documents</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/presentation') ?>">Presentations</a></li>
          </ul>
        </li>

        <!-- System & Settings Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="systemDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            System
          </a>
          <ul class="dropdown-menu" aria-labelledby="systemDropdown">
            <li><a class="dropdown-item" href="<?= asset('admin/users') ?>">Users</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/personnel') ?>">Personnel</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/penalties') ?>">Penalties</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/payment_settings') ?>">Payment Settings</a></li>
            <li><a class="dropdown-item" href="<?= asset('admin/settings') ?>">General Settings</a></li>
          </ul>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <!-- Language Switcher -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
             <img id="currentFlagAdmin" src="https://flagcdn.com/16x12/us.png" width="20" alt="English">
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
            <li>
                 <a class="dropdown-item" href="#" onclick="changeLang('en')">
                    <img src="https://flagcdn.com/16x12/us.png" width="20" class="me-2"> English
                 </a>
            </li>
            <li>
                <a class="dropdown-item" href="#" onclick="changeLang('ar')">
                    <img src="https://flagcdn.com/16x12/sa.png" width="20" class="me-2"> العربية
                </a>
            </li>
          </ul>
        </li>

        <!-- Profile / Logout -->
        <li class="nav-item dropdown">
          <a
            class="nav-link dropdown-toggle text-light d-flex align-items-center"
            href="#"
            id="adminProfileDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false">
            <i class="bi bi-person-circle me-2" style="font-size: 1.2rem;"></i>
            <?= htmlspecialchars($username) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminProfileDropdown">
            <li><a class="dropdown-item" href="<?= asset('/') ?>" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i> View Site</a></li>
            <li><hr class="dropdown-divider" /></li>
            <li><a class="dropdown-item text-danger" href="<?= asset('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<script>
  function changeLang(lang) {
    localStorage.setItem("lang", lang);
    location.reload();
  }

  document.addEventListener("DOMContentLoaded", function() {
    const flag = document.getElementById("currentFlagAdmin");
    const currentLang = localStorage.getItem("lang") || "en";

    if (currentLang === "ar") {
      flag.src = "https://flagcdn.com/16x12/sa.png";
      flag.alt = "Arabic";
    } else {
      flag.src = "https://flagcdn.com/16x12/us.png";
      flag.alt = "English";
    }
  });

  // Basic Translation (Optional for Admin but good for consistency)
  (async function autoTranslatePage() {
    const lang = localStorage.getItem("lang") || "en";
    if (lang !== "ar") return;
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
