<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: ../login/login.php");
  exit();
}
require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch payment transactions
$search = $_GET['search'] ?? '';
if ($search) {
  $stmt = $conn->prepare("
        SELECT pt.*, ti.team_name, ti.created_at AS team_created_at, t.name AS tournament_name
        FROM payment_transactions pt
        JOIN team_info ti ON pt.team_id = ti.id
        LEFT JOIN tournaments t ON pt.tournament_id = t.id
        WHERE ti.team_name LIKE ? AND pt.status = 'paid'
        ORDER BY pt.created_at DESC
    ");
  $like = "%$search%";
  $stmt->bind_param('s', $like);
} else {
  $stmt = $conn->prepare("
        SELECT pt.*, ti.team_name, ti.created_at AS team_created_at, t.name AS tournament_name
        FROM payment_transactions pt
        JOIN team_info ti ON pt.team_id = ti.id
        LEFT JOIN tournaments t ON pt.tournament_id = t.id
        WHERE pt.status = 'paid'
        ORDER BY pt.created_at DESC
    ");
}

$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
  <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
  <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registration - Wish2Padel</title>

  <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet"
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body style="background-color: #303030">

  <?php require 'src/navbar.php' ?>
  <section class="container py-5">
    <h2 class="fw-bold mb-3 text-white">
      <i class="bi bi-credit-card-2-front me-2 text-white"></i> Manage Registration Payments
    </h2>

    <!-- Search bar -->
    <div class="mb-4">
      <div class="input-group input-group-lg">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" id="searchInput"
          class="form-control"
          placeholder="Search team name..."
          value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm">
      <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th scope="col">Team</th>
              <th scope="col">Team Created</th>
              <th scope="col">Payment ID</th>
              <th scope="col">Status</th>
              <th scope="col">Method</th>
              <th scope="col" class="text-end">Amount</th>
              <th scope="col">Currency</th>
              <th scope="col">Tournament</th>
              <th scope="col">Created At</th>
            </tr>
          </thead>
          <tbody id="paymentTable">
            <?php foreach ($transactions as $t): ?>
              <tr>
                <td class="fw-semibold"><?= htmlspecialchars($t['team_name']) ?></td>
                <td><span class="badge bg-secondary"><?= $t['team_created_at'] ?></span></td>
                <td><span class="text-monospace"><?= htmlspecialchars($t['payment_id']) ?></span></td>
                <td>
                  <?php if ($t['status'] === 'paid'): ?>
                    <span class="badge bg-success">Paid</span>
                  <?php elseif ($t['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                  <?php else: ?>
                    <span class="badge bg-danger"><?= ucfirst($t['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td><i class="bi bi-wallet2 me-1 text-muted"></i><?= htmlspecialchars($t['payment_method']) ?></td>
                <td class="fw-bold text-end text-success"><?= number_format($t['amount'], 2) ?></td>
                <td><?= htmlspecialchars($t['currency']) ?></td>
                <td>
                  <?php if (!empty($t['tournament_name'])): ?>
                    <span class="badge bg-primary"><?= htmlspecialchars($t['tournament_name']) ?></span>
                  <?php else: ?>
                    <span class="text-muted">#<?= htmlspecialchars($t['tournament_id']) ?></span>
                  <?php endif; ?>
                </td>
                <td><small class="text-muted"><?= $t['created_at'] ?></small></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>


  <script>
    // Search realtime
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', () => {
      const val = searchInput.value.toLowerCase();
      document.querySelectorAll('#paymentTable tr').forEach(row => {
        const team = row.cells[0].innerText.toLowerCase();
        row.style.display = team.includes(val) ? '' : 'none';
      });
    });
  </script>

  <!-- Scroll to Top Button -->
  <button id="scrollTopBtn" title="Go to top">↑</button>

  <script>
    const scrollBtn = document.getElementById("scrollTopBtn");
    window.onscroll = function() {
      if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        scrollBtn.style.display = "block";
      } else {
        scrollBtn.style.display = "none";
      }
    };
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
      const hero = document.getElementById('about-liga');

      function toggleNavbarFixed() {
        if (!hero) return;
        const scrollPos = window.scrollY;
        const heroHeight = hero.offsetHeight;

        if (scrollPos >= heroHeight) {
          navbar.classList.add('navbar-fixed');
          document.body.style.paddingTop = navbar.offsetHeight + 'px';
        } else {
          navbar.classList.remove('navbar-fixed');
          document.body.style.paddingTop = '0';
        }
      }

      window.addEventListener('scroll', toggleNavbarFixed);
      toggleNavbarFixed();
    });
  </script>

  <style>
    nav#maiavbar {
      width: 100%;
      transition: all 0.3s ease;
      z-index: 9999;
    }

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