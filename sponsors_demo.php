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
  <title>Sponsors - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/stylee.css?v=12">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body style="background-color:#303030;">

<?php require 'src/navbar.php' ?>

<?php
// Ambil data sponsor dan collaborator
$sponsors = $conn->query("SELECT * FROM sponsors_demo WHERE status = 'sponsor' ORDER BY sponsor_id DESC");
$collaborators = $conn->query("SELECT * FROM sponsors WHERE status = 'collaborate' ORDER BY sponsor_id DESC");
?>

<?php
// Ambil data sponsor dan collaborator
$resultSponsors = $conn->query("SELECT * FROM sponsors_demo WHERE status = 'sponsor' ORDER BY sponsor_id DESC");
$resultCollaborators = $conn->query("SELECT * FROM sponsors WHERE status = 'collaborate' ORDER BY sponsor_id DESC");

$premiumSponsors = [];
$standardSponsors = [];

while ($row = $resultSponsors->fetch_assoc()) {
    if (isset($row['type']) && $row['type'] === 'premium') {
        $premiumSponsors[] = $row;
    } else {
        $standardSponsors[] = $row;
    }
}
?>

<section class="sponsors-page py-5">
  <div class="container">

    <h1 class="tour-title mb-5">PARTNERS</h1>

    <!-- 🌟 PREMIUM SPONSORS -->
    <?php if (!empty($premiumSponsors)): ?>
    <div class="sponsor-category premium mb-5">
      <h3 class="category-title" style="font-size:30px">Premium Sponsors</h3>
      <?php foreach ($premiumSponsors as $row): ?>
      <div class="sponsor-item premium-item">
        <div class="sponsor-logo-box">
          <img src="uploads/sponsor/<?= $row['sponsor_logo'] ?>" alt="<?= htmlspecialchars($row['sponsor_name']) ?>">
        </div>
        <div class="sponsor-info">
          <h5><?= htmlspecialchars($row['sponsor_name']) ?></h5>
          <?php if (!empty($row['description'])): ?>
          <p><?= htmlspecialchars($row['description']) ?></p>
          <?php endif; ?>
        </div>
        <?php if (!empty($row['website'])): ?>
        <div class="sponsor-action">
          <a href="<?= htmlspecialchars($row['website']) ?>" target="_blank" class="visit-btn gold">Visit Website</a>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>


    <!-- ⚪ OFFICIAL SPONSORS -->
    <?php if (!empty($standardSponsors)): ?>
    <div class="sponsor-category mb-5">
      <h3 class="category-title text-gradient-silver mb-4">Official Sponsors</h3>
      <div class="sponsor-grid">
        <?php foreach ($standardSponsors as $row): ?>
        <div class="grid-logo">
          <img src="uploads/sponsor/<?= $row['sponsor_logo'] ?>" alt="<?= htmlspecialchars($row['sponsor_name']) ?>">
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>


    <!-- 🤝 COLLABORATORS -->
    <?php if ($resultCollaborators->num_rows > 0): ?>
    <div class="sponsor-category collab mb-5">
      <h3 class="category-title text-gradient-silver mb-4">Collaborators</h3>
      <div class="sponsor-grid">
        <?php while($row = $resultCollaborators->fetch_assoc()): ?>
        <div class="grid-logo">
          <img src="uploads/sponsor/<?= $row['sponsor_logo'] ?>" alt="<?= htmlspecialchars($row['sponsor_name']) ?>">
        </div>
        <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<style>
/* === BASE === */
.sponsors-page {
  background: #0c0c0c;
  color: #fff;
  font-family: 'Poppins', sans-serif;
}
.tour-title {
  font-weight: 800;
  font-size: 80px;
  letter-spacing: 1px;
  color: white;
  text-align: left;
}

/* 📱 Ukuran layar tablet ke bawah */
@media (max-width: 768px) {
  .tour-title {
    font-size: 48px;
    text-align: left; /* Optional: Biar lebih pas di HP */
  }
}

/* 📱 Layar HP kecil */
@media (max-width: 480px) {
  .tour-title {
    font-size: 36px;
    text-align: left;
  }
}

.category-title {
  font-size: 1.3rem;
  font-weight: 600;
  margin-bottom: 25px;
}

/* === PREMIUM (3 column style) === */
.premium-item {
  display: grid;
  grid-template-columns: 220px 1fr auto;
  align-items: center;
  gap: 25px;
  padding: 20px 0;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}
.premium-item:last-child {
  border-bottom: none;
}
.sponsor-logo-box {
  background: #fff;
  padding: 30px;
  border-radius: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
}
.sponsor-logo-box img {
  max-width: 220px;
  max-height: 140px;
  object-fit: contain;
}
.sponsor-info h5 {
  font-size: 1.25rem;
  font-weight: 800;
  margin-bottom: 6px;
}
.sponsor-info p {
  color: #ccc;
  font-size: 0.9rem;
  line-height: 1.4;
  margin-bottom: 0;
}
.sponsor-action {
  text-align: right;
}
.visit-btn {
  display: inline-block;
  padding: 6px 14px;
  border: 1px solid rgba(255,255,255,0.3);
  border-radius: 20px;
  color: #fff;
  font-size: 0.85rem;
  text-decoration: none;
  transition: 0.3s;
}
.visit-btn.gold {
  border-color: #d4af37;
  color: #d4af37;
}
.visit-btn.gold:hover {
  background: rgba(212,175,55,0.1);
}

/* === STANDARD & COLLABORATORS (grid logos only) === */
.sponsor-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 30px;
  justify-items: center;
  align-items: center;
}
.grid-logo img {
  max-width: 140px;
  max-height: 80px;
  object-fit: contain;
  filter: brightness(0.9);
  transition: .3s;
}
.grid-logo img:hover {
  filter: brightness(1.1);
}

/* === GRADIENTS === */
.text-gradient-gold {
  background: linear-gradient(90deg, #FFD700, #D4AF37);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.text-gradient-silver {
  background: linear-gradient(90deg, #EDEDED, #AFAFAF);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.text-gradient-blue {
  background: linear-gradient(90deg, #7EB2FF, #4A6AFF);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
  .premium-item {
    grid-template-columns: 1fr;
    text-align: center;
  }
  .sponsor-action {
    text-align: center;
    margin-top: 10px;
  }
  .sponsor-logo-box {
    margin: 0 auto;
  }
}
</style>





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
    const hero = document.getElementById('sponsor'); // Pastikan ada elemen heroCarousel di halaman

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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
