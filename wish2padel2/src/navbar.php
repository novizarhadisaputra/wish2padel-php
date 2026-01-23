<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$username = $_SESSION['username'] ?? null;
$role     = $_SESSION['role'] ?? null;
?>

<nav id="maiavbar" class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="index">
      <img src="assets/image/w2p.png" alt="Logo" class="d-inline-block align-text-top" />
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
      data-bs-target="#mainNavbar" aria-controls="mainNavbar"
      aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <!-- 🌐 Language Switcher -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
            id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img id="currentFlag" src="https://flagcdn.com/16x12/us.png" width="20" alt="English">
          </a>
          <ul class="dropdown-menu" aria-labelledby="langDropdown">
            <li><a class="dropdown-item" href="#" onclick="changeLang('en')">
                <img src="https://flagcdn.com/16x12/us.png" width="20" class="me-2"> English</a>
            </li>
            <li><a class="dropdown-item" href="#" onclick="changeLang('ar')">
                <img src="https://flagcdn.com/16x12/sa.png" width="20" class="me-2"> العربية</a>
            </li>
          </ul>
        </li>

        <!-- ✅ Dashboard untuk Captain -->
        <?php if (!empty($_SESSION['team_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="dashboard">Dashboard</a></li>
        <?php endif ?>

        <!-- ✅ League Dropdown -->
        <li class="nav-item dropdown position-static">
          <a class="nav-link dropdown-toggle" href="#" id="ligaDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">League</a>
          <div class="dropdown-menu mega-dropdown shadow" aria-labelledby="ligaDropdown">
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

        <!-- ✅ My Team Section -->
        <?php if (!empty($_SESSION['team_id'])): ?>
          <?php
          $now = date('Y-m-d H:i:s');
          $showAddMember = false;
          $stmt = $conn->prepare("SELECT * FROM transfer_windows WHERE start_date <= ? AND end_date >= ? ORDER BY start_date DESC");
          $stmt->bind_param("ss", $now, $now);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($result->num_rows > 0) $activeWindow = $result->fetch_assoc();
          $showAddMember = $result->num_rows > 0;
          ?>
          <li class="nav-item dropdown position-static">
            <a class="nav-link dropdown-toggle" href="#" id="myTeamDropdown"
              data-bs-toggle="dropdown">My Team
              <?php if ($showAddMember): ?><span class="badge bg-danger ms-1">!</span><?php endif; ?>
            </a>
            <div class="dropdown-menu mega-dropdown shadow" aria-labelledby="myTeamDropdown">
              <div class="row">
                <div class="col-6 col-md-3">
                  <h6>Match</h6>
                  <a class="dropdown-item" href="scheduled">Scheduled & Results</a>
                </div>
                <div class="col-6 col-md-3">
                  <h6>Team</h6>
                  <a class="dropdown-item d-flex justify-content-between align-items-center" href="myteam">
                    My Team
                    <?php if ($showAddMember): ?>
                      <?php $start = date("M d", strtotime($activeWindow['start_date']));
                      $end = date("M d", strtotime($activeWindow['end_date'])); ?>
                      <span class="badge bg-danger ms-2">OPEN <?= $start ?> → <?= $end ?></span>
                    <?php endif; ?>
                  </a>
                  <a class="dropdown-item" href="windows">Transfer Windows</a>
                </div>
              </div>
            </div>
          </li>
        <?php endif; ?>

        <!-- ✅ Default Links -->
        <li class="nav-item"><a href="regis" class="btn btn-gold">Register Team</a></li>
        <li class="nav-item"><a class="nav-link" href="club.php">Club</a></li>
        <?php if (empty($_SESSION['team_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="news">News</a></li>
          <li class="nav-item"><a class="nav-link" href="media/gallery">Media</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="sponsor">Sponsors</a></li>

      </ul>

      <!-- ✅ Profile / Login with Back Buttons Inside -->
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if ($username): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-light" href="#" id="profileDropdown"
              data-bs-toggle="dropdown" aria-expanded="false">
              <?= htmlspecialchars($username) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">

              <!-- 🧭 Back to Role -->
              <?php if ($role === 'admin'): ?>
                <li>
                  <a class="dropdown-item d-flex align-items-center" href="admin/dashboard">
                    <i class="bi bi-arrow-left-circle text-warning me-2"></i> Back to Admin
                  </a>
                </li>
              <?php elseif ($role === 'club'): ?>
                <li>
                  <a class="dropdown-item d-flex align-items-center" href="club/dashboard">
                    <i class="bi bi-arrow-left-circle text-primary me-2"></i> Back to Club
                  </a>
                </li>
              <?php elseif ($role === 'captain'): ?>
                <li>
                  <a class="dropdown-item d-flex align-items-center" href="dashboard">
                    <i class="bi bi-arrow-left-circle text-success me-2"></i> Back to Captain
                  </a>
                </li>
              <?php endif; ?>

              <li>
                <hr class="dropdown-divider" />
              </li>
              <li>
                <a class="dropdown-item text-danger d-flex align-items-center" href="logout">
                  <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="login/login" class="nav-link text-light" title="Login">
              <i class="bi bi-person-circle" style="font-size:1.5rem"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- 🌍 Language Scripts -->
<script>
  function changeLang(lang) {
    localStorage.setItem("lang", lang);
    location.reload();
  }
  document.addEventListener("DOMContentLoaded", () => {
    const f = document.getElementById("currentFlag"),
      l = localStorage.getItem("lang") || "en";
    if (l === "ar") {
      f.src = "https://flagcdn.com/16x12/sa.png";
      f.alt = "Arabic"
    } else {
      f.src = "https://flagcdn.com/16x12/us.png";
      f.alt = "English"
    }
  });
</script>

<script>
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
    const customMap = {
      "League": "دوري",
      "Register Team": "تسجيل الفريق",
      "Sponsors": "الرعاة",
      "Media": "وسائل الإعلام",
      "News": "الأخبار",
      "Club": "النادي"
    };
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
    const nodes = [];
    while (walker.nextNode()) {
      const n = walker.currentNode;
      const t = n.nodeValue.trim();
      if (!t || /^[\d\s\W]+$/.test(t)) continue;
      const p = n.parentNode?.nodeName.toLowerCase();
      if (["img", "svg"].includes(p)) continue;
      nodes.push(n);
    }
    for (const n of nodes) {
      let o = n.nodeValue.trim();
      if (/[\u0600-\u06FF]/.test(o)) continue;
      if (customMap[o]) {
        n.nodeValue = customMap[o];
        continue;
      }
      try {
        const res = await fetch("/proxy.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: "text=" + encodeURIComponent(o)
        });
        const data = await res.json();
        if (data?.translatedText) n.nodeValue = data.translatedText;
      } catch (e) {
        console.warn("Translate failed:", o);
      }
    }
  })();
</script>

<style>
  nav .navbar-brand {
    margin-left: 30px;
    margin-right: 70px;
  }

  .navbar-brand img {
    height: 120px;
    width: auto;
  }

  nav .nav-item {
    margin-left: 40px;
  }

  .dropdown-item i {
    font-size: 1.1rem;
  }
</style>