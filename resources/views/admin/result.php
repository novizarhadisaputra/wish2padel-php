<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Match Result - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

<?php view('partials.navbar'); ?>

<section class="container-section">
    <h2 class="section-title">Match Results</h2>
    <table class="table-custom">
        <thead>
            <tr>
                <th>ID</th>
                <th>Match ID</th>
                <th>Team ID</th>
                <th>Pairs Won</th>
                <th>Pairs Lost</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $results->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['match_id'] ?></td>
                    <td><?= $row['team_id'] ?></td>
                    <td><?= $row['pairs_won'] ?></td>
                    <td><?= $row['pairs_lost'] ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;" action="<?= asset('admin/result') ?>">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="status" value="accept">
                            <button type="submit" name="update_status" class="btn btn-success btn-sm btn-anim">Accept</button>
                        </form>
                        <form method="POST" style="display:inline;" action="<?= asset('admin/result') ?>">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="status" value="reject">
                            <button type="submit" name="update_status" class="btn btn-danger btn-sm btn-anim">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>


<?php view('partials.footer'); ?>


<style>
.container-section {
    max-width: 1000px;
    margin: 30px auto;
    padding: 20px;
    border-radius: 15px;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    animation: fadeIn 0.5s ease-in-out;
}
.section-title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 15px;
}
.table-custom {
    width: 100%;
    border-collapse: collapse;
}
.table-custom th, .table-custom td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}
.btn-anim {
    transition: transform 0.2s ease;
}
.btn-anim:hover {
    transform: scale(1.1);
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.badge {
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: bold;
}
.badge.pending { background: #ffc107; color: #000; }
.badge.acc { background: #28a745; color: #fff; }
.badge.tolak { background: #dc3545; color: #fff; }
</style>

<!-- Scroll to Top Button -->
<button id="scrollTopBtn" title="Go to top">↑</button>

<style>
  /* Scroll to Top Button Styles */
  #scrollTopBtn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 999;
    background-color: orange;
    color: white;
    border: none;
    outline: none;
    width: 50px;
    height: 50px;
    font-size: 1.5rem;
    border-radius: 50%;
    cursor: pointer;
    display: none; /* hidden by default */
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: background-color 0.3s ease, transform 0.3s ease;
  }

  #scrollTopBtn:hover {
    background-color: #cc8400;
    transform: scale(1.1);
  }
</style>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
