<?php
session_start();
require 'config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Team - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require 'src/navbar.php' ?>

<?php
// Ambil semua tim dari team_info
$sql = "SELECT id, team_name FROM team_info ORDER BY team_name ASC";
$result = $conn->query($sql);

$teams = [];
while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}

// Buat daftar kategori lengkap
$levels = [
    'Advanced: B+', 'Advanced: B', 'Advanced: B-',
    'U.Intermediate: C+', 'Intermediate: C', 'L. Intermediate: C-',
    'U.Beginner: D+', 'Beginner: D', 'L. Beginner: D-'
];
?>

<section style="background:#f5f5f5; padding:40px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <div class="container">
        <h1 class="text-center mb-4" style="color:#004d40; font-weight:700;">Team List</h1>

        <div class="d-flex justify-content-center mb-4 gap-3 flex-wrap">
            <!-- Search bar -->
            <input type="text" id="teamSearch" placeholder="Search team..." 
                   class="form-control" 
                   style="max-width:300px; border:2px solid #FFC107; box-shadow:0 0 6px #FFC107; font-weight:600; letter-spacing:0.05em;" />

            <!-- Dropdown kategori lengkap -->
            <select id="levelFilter" class="form-select" style="max-width:200px; border:2px solid #FFC107; box-shadow:0 0 6px #FFC107; font-weight:600;">
                <option value="">All Categories</option>
                <?php foreach ($levels as $lvl): ?>
                    <option value="<?= htmlspecialchars($lvl); ?>"><?= htmlspecialchars($lvl); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Table -->
        <div class="table-responsive shadow-sm" style="border-radius:12px; overflow:hidden;">
            <table class="table table-hover align-middle" id="teamTable" style="background:#fff;">
                <thead style="background:#FFD700; color:#000;">
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Team Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teams as $index => $team): ?>
                        <?php
                        // Ambil level tim dari team_contact_details
                        $teamId = $team['id'];
                        $lvlRes = $conn->query("SELECT level FROM team_contact_details WHERE team_id = $teamId LIMIT 1");
                        $lvlRow = $lvlRes->fetch_assoc();
                        $teamLevel = $lvlRow['level'] ?? '';
                        ?>
                        <tr class="team-row" data-level="<?= htmlspecialchars($teamLevel); ?>" 
                            style="transition: transform 0.2s ease, box-shadow 0.2s ease; cursor:pointer;">
                            <td><?= $index + 1; ?></td>
                            <td><a href="team_profile.php?id=<?= $team['id']; ?>" style="text-decoration:none; color:#000; display:block; width:100%;"><?= htmlspecialchars($team['team_name']); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<style>
    /* Hover row zoom in */
    .team-row:hover {
        transform: scale(1.02);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        background-color:#fff8e1;
    }

    /* Table link hover */
    .team-row a:hover {
        color:#FFD700;
        text-decoration: underline;
    }
</style>

<script>
    const searchInput = document.getElementById('teamSearch');
    const levelSelect = document.getElementById('levelFilter');
    const tableRows = document.querySelectorAll('#teamTable tbody .team-row');

    function filterTeams() {
        const searchValue = searchInput.value.toLowerCase();
        const levelValue = levelSelect.value;

        tableRows.forEach(row => {
            const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const rowLevel = row.getAttribute('data-level');

            // Check if matches search and/or level
            const matchesSearch = name.includes(searchValue);
            const matchesLevel = !levelValue || rowLevel === levelValue;

            row.style.display = (matchesSearch && matchesLevel) ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterTeams);
    levelSelect.addEventListener('change', filterTeams);
</script>


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
