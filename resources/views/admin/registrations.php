<!DOCTYPE html>
<html lang="en">

<head>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
  <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registration - Wish2Padel</title>

  <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="admin-page">

  <?php view('partials.navbar'); ?>
  <div class="container py-5 mt-5">
    <h2 class="text-gold mb-4">
      <i class="bi bi-credit-card-2-front me-2"></i> Manage Registration Payments
    </h2>

    <!-- Search bar -->
    <div class="mb-4">
      <form method="GET" action="">
          <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="search" id="searchInput"
              class="form-control"
              placeholder="Search team name..."
              value="<?= htmlspecialchars($search) ?>">
          </div>
      </form>
    </div>

    <!-- Table Card -->
    <div class="card admin-card shadow-lg">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-dark admin-table table-hover mb-0 align-middle">
            <thead>
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
    </div>
  </div>


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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>