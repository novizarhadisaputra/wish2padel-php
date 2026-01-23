<?php
session_start();
require '../config.php';
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
  <title>Category - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
</head>
<body style="background-color:#303030;">


<?php require '../src/navbar2.php' ?>

<?php
$uploadDir = "../uploads/gallery/";
$media_id = isset($_GET['media_id']) ? intval($_GET['media_id']) : 0;

// Fetch media info
$stmt = $conn->prepare("SELECT * FROM media WHERE id=?");
$stmt->bind_param("i", $media_id);
$stmt->execute();
$media = $stmt->get_result()->fetch_assoc();

if (!$media) {
    echo "<p>Media not found.</p>";
    exit;
}

// Fetch categories for this media
$stmt = $conn->prepare("SELECT * FROM category WHERE media_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $media_id);
$stmt->execute();
$categories = $stmt->get_result();
?>

<section class="container py-5" style="min-height:90vh;">
    <div class="text-center text-white mb-5">
        <h1 class="fw-bold"><?= htmlspecialchars($media['name']) ?></h1>
        <p style="color:#F3E6B6" class="">Select a category to view photos</p>
    </div>

    <div class="row g-4">
        <?php while($c = $categories->fetch_assoc()): ?>
        <div class="col-12 col-sm-6 col-md-3">
            <a id="categori" href="photos?category_id=<?= $c['id'] ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 gallery-card h-100">
                    <div class="img-wrap">
                        <?php if(!empty($c['cover_image']) && file_exists($uploadDir.$c['cover_image'])): ?>
                            <img src="<?= htmlspecialchars($uploadDir.$c['cover_image']) ?>" class="img-fluid" alt="<?= htmlspecialchars($c['name']) ?>">
                        <?php else: ?>
                            <img src="../assets/default-cover.jpg" class="img-fluid" alt="No cover">
                        <?php endif; ?>
                    </div>
                    <div class="card-body text-center">
                        <h6 class="mb-0 text-dark"><?= htmlspecialchars($c['name']) ?></h6>
                    </div>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<style>
.gallery-card { transition: transform .25s ease, box-shadow .25s ease; background:#fff; }
.gallery-card:hover { transform: translateY(-6px); box-shadow:0 12px 30px rgba(0,0,0,.15); }
.img-wrap { overflow:hidden; border-top-left-radius:1rem; border-top-right-radius:1rem; height:180px; }
.img-wrap img { width:100%; height:100%; object-fit:cover; transition: transform .35s ease; display:block; }
.gallery-card:hover .img-wrap img { transform: scale(1.06); }
</style>


<?php require '../src/footer2.php' ?>

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
    const hero = document.getElementById('categori'); // Pastikan ada elemen heroCarousel di halaman

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
