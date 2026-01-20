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
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
  <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
  <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Leaderboard - Padel League</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

  <?php require 'src/navbar.php' ?>

  <?php
  $tournament_id = 1;

  // Ambil nama tournament
  $tour_sql = "SELECT name, start_date, end_date FROM tournaments WHERE id = ?";
  $tour_stmt = $conn->prepare($tour_sql);
  $tour_stmt->bind_param("i", $tournament_id);
  $tour_stmt->execute();
  $tour_result = $tour_stmt->get_result()->fetch_assoc();
  $tournament_name = $tour_result['name'];
  $tournament_period = date("F Y", strtotime($tour_result['start_date'])) . " – " . date("F Y", strtotime($tour_result['end_date']));

  // Ambil leaderboard
  $sql = "
    SELECT 
        t.id AS team_id,
        t.team_name,
        COUNT(mr.id) AS P,
        SUM(CASE WHEN mr.winner_team_id = t.id THEN 1 ELSE 0 END) AS W,
        SUM(CASE WHEN mr.winner_team_id != t.id AND mr.winner_team_id IS NOT NULL THEN 1 ELSE 0 END) AS L,
        COALESCE(SUM(CASE WHEN mr.winner_team_id = t.id THEN 2 ELSE 0 END), 0) AS points
    FROM teams t
    LEFT JOIN matches m ON t.id = m.team1_id OR t.id = m.team2_id
    LEFT JOIN match_results mr ON m.id = mr.match_id
    WHERE m.tournament_id = ?
    GROUP BY t.id, t.team_name
    ORDER BY points DESC, W DESC, t.team_name ASC
";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $tournament_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $leaderboard = [];
  $rank = 1;
  while ($row = $result->fetch_assoc()) {
    $row['rank'] = $rank++;
    $leaderboard[] = $row;
  }
  ?>

  <section id="leaderboard" style="background:#f9f9f9; padding:50px 20px; font-family:Arial,sans-serif;">
    <h2 class="leaderboard-title"><?php echo htmlspecialchars($tournament_name); ?></h2>
    <p class="leaderboard-subtitle"><?php echo $tournament_period; ?></p>

    <div class="table-container">
      <table class="leaderboard-table">
        <thead>
          <tr>
            <th>Rank</th>
            <th>Team</th>
            <th>P</th>
            <th>W</th>
            <th>L</th>
            <th>Points</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leaderboard as $team): ?>
            <tr class="rank-row rank-<?php echo $team['rank']; ?>">
              <td><?php echo $team['rank']; ?></td>
              <td><?php echo htmlspecialchars($team['team_name']); ?></td>
              <td><?php echo $team['P']; ?></td>
              <td><?php echo $team['W']; ?></td>
              <td><?php echo $team['L']; ?></td>
              <td><?php echo $team['points']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <style>
    .leaderboard-title {
      text-align: center;
      font-size: 2.2rem;
      font-weight: bold;
      color: #00796B;
      margin-bottom: 5px;
      animation: slideDown 0.7s ease-in-out;
    }

    .leaderboard-subtitle {
      text-align: center;
      font-size: 1.1rem;
      color: #555;
      margin-bottom: 30px;
      animation: fadeIn 1s ease-in-out;
    }

    .table-container {
      overflow-x: auto;
      animation: fadeInUp 0.8s ease-in-out;
    }

    .leaderboard-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px;
      background: white;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border-radius: 10px;
      overflow: hidden;
    }

    .leaderboard-table th,
    .leaderboard-table td {
      padding: 14px 15px;
      text-align: center;
    }

    .leaderboard-table thead {
      background: #00796B;
      color: white;
    }

    .leaderboard-table tbody tr {
      transition: background 0.3s ease, transform 0.2s ease;
    }

    .leaderboard-table tbody tr:hover {
      background: rgba(0, 121, 107, 0.08);
      transform: scale(1.015);
    }

    /* Top 3 warna */
    .rank-1 td {
      background: #FFD70033;
      font-weight: bold;
      animation: glowGold 1.5s infinite alternate;
    }

    .rank-2 td {
      background: #C0C0C033;
      font-weight: bold;
    }

    .rank-3 td {
      background: #CD7F3233;
      font-weight: bold;
    }

    /* Animasi */
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes glowGold {
      from {
        box-shadow: 0 0 5px gold;
      }

      to {
        box-shadow: 0 0 20px gold;
      }
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.rank-row').forEach((row, index) => {
        row.style.opacity = 0;
        row.style.transform = 'translateX(-30px)';
        setTimeout(() => {
          row.style.opacity = 1;
          row.style.transform = 'translateX(0)';
          row.style.transition = 'all 0.5s ease';
        }, index * 200);
      });
    });
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
    document.addEventListener('DOMContentLoaded', function() {
      const navbar = document.getElementById('maiavbar');
      const hero = document.getElementById('about-liga'); // Pastikan ada elemen heroCarousel di halaman

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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>