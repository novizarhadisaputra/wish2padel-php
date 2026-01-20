<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../login/login.php");
    exit();
}
require '../../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

// Handle payment status update
if (isset($_POST['update_status'], $_POST['team_id'], $_POST['status'])) {
    $team_id = intval($_POST['team_id']);
    $status = in_array($_POST['status'], ['verified','rejected']) ? $_POST['status'] : 'pending';
    $stmt = $conn->prepare("UPDATE payment_transactions SET status=? WHERE team_id=?");
    $stmt->bind_param('si', $status, $team_id);
    $stmt->execute();
}

// Fetch registrations
$search = $_GET['search'] ?? '';
$search_sql = $search ? "WHERE ti.team_name LIKE ?" : "";

if ($search) {
    $stmt = $conn->prepare("
        SELECT pt.*, ti.team_name, ti.created_at, tcd.level, tcd.club, tcd.city, tcd.notes,
               tcd.contact_phone, tcd.contact_email, ti.captain_name, ti.captain_phone, ti.captain_email
        FROM payment_transactions pt
        JOIN team_info ti ON pt.team_id=ti.id
        JOIN team_contact_details tcd ON tcd.team_id=ti.id
        WHERE ti.team_name LIKE ?
        ORDER BY pt.id DESC
    ");
    $like = "%$search%";
    $stmt->bind_param('s', $like);
} else {
    $stmt = $conn->prepare("
        SELECT pt.*, ti.team_name, ti.created_at, tcd.level, tcd.club, tcd.city, tcd.notes,
               tcd.contact_phone, tcd.contact_email, ti.captain_name, ti.captain_phone, ti.captain_email
        FROM payment_transactions pt
        JOIN team_info ti ON pt.team_id=ti.id
        JOIN team_contact_details tcd ON tcd.team_id=ti.id
        ORDER BY pt.id DESC
    ");
}
$stmt->execute();
$result = $stmt->get_result();
$registrations = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Club - Wish2Padel</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/stylee.css?v=12">
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
  </head>
  <body style="background-color: #303030">
    <?php require '../src/navbar2.php' ?>

    <?php
      // Pagination settings
      $limit = 10; // jumlah data per halaman
      $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
      $offset = ($page - 1) * $limit;

      // Hitung total data
      $totalResult = $conn->query("SELECT COUNT(*) AS total FROM centers"); $totalRows
          = $totalResult->fetch_assoc()['total']; $totalPages = ceil($totalRows /
          $limit); 
          // Ambil data sesuai halaman 
          $sql = "SELECT id, name, city,
          postal_code, phone, website FROM centers ORDER BY id ASC LIMIT $limit OFFSET
          $offset"; $result = $conn->query($sql); 
    ?>

    <section class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-white mb-0">
      <i class="bi bi-building me-2 text-warning"></i> Manage Club
    </h2>
    <a href="add_process.php" class="btn-gold" style="text-decoration:none">
      <i class="bi bi-plus-circle me-2"></i> Add Club
    </a>
  </div>

  <div class="card shadow border-0 rounded-3">
    <div class="card-header py-3" style="background:#212529;">
      <h5 class="mb-0 text-white"><i class="bi bi-list-ul me-2"></i> Club List</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead style="background:#343a40; color:#fff;">
            <tr>
              <th>#</th>
              <th>Club Name</th>
              <th>City</th>
              <th>Postal Code</th>
              <th>Phone</th>
              <th>Website</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0): ?>
            <?php $no = $offset + 1; while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $no++; ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($row['name']); ?></td>
              <td><?= htmlspecialchars($row['city']); ?></td>
              <td><?= htmlspecialchars($row['postal_code']); ?></td>
              <td><?= htmlspecialchars($row['phone']); ?></td>
              <td>
                <a href="<?= htmlspecialchars($row['website']); ?>" target="_blank" class="text-decoration-none text-warning fw-semibold">
                  <i class="bi bi-box-arrow-up-right me-1"></i> Visit
                </a>
              </td>
              <td class="text-center">
                <a href="view.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="View">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="update.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-warning me-1" title="Update">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <a href="delete.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No clubs found.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Pagination -->
  <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center mt-4">
      <?php if($page > 1): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?= $page-1 ?>">Previous</a>
      </li>
      <?php endif; ?>

      <?php for($i=1; $i<=$totalPages; $i++): ?>
      <li class="page-item <?= $i==$page?'active':'' ?>">
        <a class="page-link" style="<?= $i==$page?'background:#d4af37; border-color:#d4af37; color:#fff;':'' ?>" href="?page=<?= $i ?>">
          <?= $i ?>
        </a>
      </li>
      <?php endfor; ?>

      <?php if($page < $totalPages): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?= $page+1 ?>">Next</a>
      </li>
      <?php endif; ?>
    </ul>
  </nav>
</section>


    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" title="Go to top">↑</button>

    <script>
      const scrollBtn = document.getElementById("scrollTopBtn");

      // Show/hide button on scroll
      window.onscroll = function () {
        if (
          document.body.scrollTop > 200 ||
          document.documentElement.scrollTop > 200
        ) {
          scrollBtn.style.display = "block";
        } else {
          scrollBtn.style.display = "none";
        }
      };

      // Scroll to top smoothly
      scrollBtn.addEventListener("click", function () {
        window.scrollTo({
          top: 0,
          behavior: "smooth",
        });
      });
    </script>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const navbar = document.getElementById("maiavbar");
        const hero = document.getElementById("about-liga"); // Pastikan ada elemen heroCarousel di halaman

        function toggleNavbarFixed() {
          if (!hero) return; // kalau heroCarousel gak ada, skip

          const scrollPos = window.scrollY;
          const heroHeight = hero.offsetHeight;

          if (scrollPos >= heroHeight) {
            navbar.classList.add("navbar-fixed");
            document.body.style.paddingTop = navbar.offsetHeight + "px"; // supaya konten gak tertutup
          } else {
            navbar.classList.remove("navbar-fixed");
            document.body.style.paddingTop = "0";
          }
        }

        window.addEventListener("scroll", toggleNavbarFixed);
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
        background: linear-gradient(90deg, #00796b, #004d40);
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
