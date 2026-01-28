<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Category - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <style>
    .gallery-card { transition: transform .25s ease, box-shadow .25s ease; background:#fff; }
    .gallery-card:hover { transform: translateY(-6px); box-shadow:0 12px 30px rgba(0,0,0,.15); }
    .img-wrap { overflow:hidden; border-top-left-radius:1rem; border-top-right-radius:1rem; height:180px; }
    .img-wrap img { width:100%; height:100%; object-fit:cover; transition: transform .35s ease; display:block; }
    .gallery-card:hover .img-wrap img { transform: scale(1.06); }
    </style>
</head>
<body style="background-color:#303030;">

<?php view('partials.navbar'); ?>

<section class="container py-5" style="min-height:90vh;" id="categori">
    <div class="text-center text-white mb-5">
        <h1 class="fw-bold"><?= htmlspecialchars($media['name'] ?? 'Media') ?></h1>
        <p style="color:#F3E6B6" class="">Select a category to view photos</p>
    </div>

    <div class="row g-4">
        <?php if($categories): ?>
            <?php while($c = $categories->fetch_assoc()): ?>
        <div class="col-12 col-sm-6 col-md-3">
            <a href="<?= asset('media/photos?category_id=' . $c['id']) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 gallery-card h-100">
                    <div class="img-wrap">
                        <?php if(!empty($c['cover_image'])): ?>
                            <img src="<?= asset('uploads/gallery/'.htmlspecialchars($c['cover_image'])) ?>" class="img-fluid" alt="<?= htmlspecialchars($c['name']) ?>">
                        <?php else: ?>
                            <img src="<?= asset('assets/default-cover.jpg') ?>" class="img-fluid" alt="No cover">
                        <?php endif; ?>
                    </div>
                    <div class="card-body text-center">
                        <h6 class="mb-0 text-dark"><?= htmlspecialchars($c['name']) ?></h6>
                    </div>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center text-white">
                <p>No categories found for this media.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php view('partials.footer'); ?>

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
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('maiavbar');
    const hero = document.getElementById('categori'); 

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
