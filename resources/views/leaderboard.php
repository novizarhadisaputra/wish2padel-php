<!DOCTYPE html>
<html lang="en">

<?php view('partials.head', ['title' => 'Leaderboard - Padel League', 'css' => 'assets/css/style.css']); ?>

<body>

  <?php view('partials.navbar'); ?>

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

  <?php view('partials.footer'); ?>

  <?php view('partials.scroll_top'); ?>
  <?php view('partials.navbar_sticky_script', ['sticky_target' => 'leaderboard']); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
