<nav id="maiavbar" class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <?php
    $username = $_SESSION['username'] ?? null;
    if (!isset($conn)) $conn = getDBConnection();
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
      data-bs-target="#mainNavbar"
      aria-controls="mainNavbar"
      aria-expanded="false"
      aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        
        <!-- Language -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img id="currentFlag" src="https://flagcdn.com/16x12/us.png" width="20" alt="English">
          </a>
          <ul class="dropdown-menu" aria-labelledby="langDropdown">
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

        <!-- Note: Admin links removed from here as they are now in navbar_admin.php -->

        <li class="nav-item dropdown position-static">
          <a class="nav-link dropdown-toggle" href="#" id="ligaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            League
          </a>
          <div class="dropdown-menu mega-dropdown shadow" aria-labelledby="ligaDropdown">
            <div class="row">
              <div class="col-6 col-md-3">
                <h6>About League</h6>
                <a class="dropdown-item" href="<?= asset('about-league') ?>">Presentation</a>
                <a class="dropdown-item" href="<?= asset('documents') ?>">Documents</a>
              </div>
              <div class="col-6 col-md-3">
                <h6>League</h6>
                <a class="dropdown-item" href="<?= asset('league') ?>">League Hub</a>
              </div>
            </div>
          </div>
        </li>
        
        <?php if (!empty($_SESSION['team_id'])): ?>
          <?php
          // CEK TRANSFER WINDOW
          $now = date('Y-m-d H:i:s');
          $showAddMember = false;
          if ($conn) {
            $stmt = $conn->prepare("SELECT * FROM transfer_windows WHERE start_date <= ? AND end_date >= ? ORDER BY start_date DESC");
            if ($stmt) {
              $stmt->bind_param("ss", $now, $now);
              $stmt->execute();
              $result = $stmt->get_result();
              if ($result && $result->num_rows > 0) {
                $activeWindow = $result->fetch_assoc();
                $showAddMember = true;
              }
              $stmt->close();
            }
          }
          ?>
          <li class="nav-item dropdown position-static">
            <a class="nav-link dropdown-toggle" href="#" id="myTeamDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              My Team
              <?php if ($showAddMember): ?>
                <span class="badge bg-danger ms-1">!</span>
              <?php endif; ?>
            </a>
            <div class="dropdown-menu mega-dropdown shadow" aria-labelledby="myTeamDropdown">
              <div class="row">
                <div class="col-6 col-md-3">
                  <h6>Match</h6>
                  <a class="dropdown-item" href="<?= asset('scheduled') ?>">Scheduled & Results</a>
                </div>
                <div class="col-6 col-md-3">
                  <h6>Team</h6>
                  <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?= asset('myteam') ?>">
                    My Team
                    <?php if ($showAddMember): ?>
                      <?php
                      $start = date("M d", strtotime($activeWindow['start_date']));
                      $end   = date("M d", strtotime($activeWindow['end_date']));
                      ?>
                      <span class="badge bg-danger ms-2">OPEN <?= $start ?> → <?= $end ?></span>
                    <?php endif; ?>
                  </a>
                  <a class="dropdown-item" href="windows">Transfer Windows</a>
                </div>
              </div>
            </div>
          </li>
        <?php endif; ?>

        <li class="nav-item">
          <a class="btn btn-gold" href="<?= asset('regis') ?>">Register Team</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= asset('club') ?>">Club</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= asset('news') ?>">News</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= asset('media/gallery') ?>">Media</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= asset('sponsors') ?>">Sponsors</a>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if ($username): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-light" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= htmlspecialchars($username) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
              <li><hr class="dropdown-divider" /></li>
              
              <?php if (!empty($_SESSION['team_id'])): ?>
                <li><a class="dropdown-item" href="<?= asset('dashboard') ?>">Dashboard</a></li>
              <?php endif; ?>

              <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <li><a class="dropdown-item text-warning fw-bold" href="<?= asset('admin/dashboard') ?>">Admin Dashboard</a></li>
              <?php endif; ?>
              
              <li><hr class="dropdown-divider" /></li>
              <li><a class="dropdown-item text-danger" href="<?= asset('logout') ?>">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="<?= asset('login') ?>" class="nav-link text-light" title="Login">
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
    localStorage.setItem("lang", lang); 
    location.reload(); 
  }

  document.addEventListener("DOMContentLoaded", function() {
    const flag = document.getElementById("currentFlag");
    const currentLang = localStorage.getItem("lang") || "en";

    if (currentLang === "ar") {
      flag.src = "https://flagcdn.com/16x12/sa.png";
      flag.alt = "Arabic";
    } else {
      flag.src = "https://flagcdn.com/16x12/us.png";
      flag.alt = "English";
    }
  });
  
  // Translation script block
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
      "League": "دوري", "LEAGUE": "دوري", "league": "دوري", "Leagues": "الدوريات",
      "Regist Team": "تسجيل الفريق", "Sponsors": "الرعاة", "Media": "وسائل الإعلام",
      "News": "الأخبار", "Club": "النادي"
    };

    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
    const nodes = [];
    while (walker.nextNode()) {
      const node = walker.currentNode;
      const text = node.nodeValue.trim();
      if (!text || /^[\d\s\W]+$/.test(text)) continue;
      const parentTag = node.parentNode?.nodeName.toLowerCase();
      if (["img", "svg"].includes(parentTag)) continue;
      nodes.push(node);
    }

    for (const node of nodes) {
      let original = node.nodeValue.trim();
      if (/[\u0600-\u06FF]/.test(original)) continue;
      if (customMap[original]) { node.nodeValue = customMap[original]; continue; }
      try {
        const res = await fetch("<?= asset('proxy.php') ?>", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "text=" + encodeURIComponent(original)
        });
        const data = await res.json();
        if (data?.translatedText) node.nodeValue = data.translatedText;
      } catch (e) {}
    }
  })();
</script>
<style>
  nav .navbar-brand { margin-left: 30px; margin-right: 70px; }
  .navbar-brand img { height: 120px; width: auto; }
  nav .nav-item { margin-left: 40px; }
</style>
