<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Photo - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <style>
    .gallery-card { transition: transform .25s ease, box-shadow .25s ease; background:#fff; }
    .gallery-card:hover { transform: translateY(-6px); box-shadow:0 12px 30px rgba(0,0,0,.15); }
    .img-wrap { overflow:hidden; border-radius:1rem; height:180px; }
    .img-wrap img { width:100%; height:100%; object-fit:cover; transition: transform .35s ease; display:block; }
    .gallery-card:hover .img-wrap img { transform: scale(1.06); }
    </style>
</head>
<body style="background-color:#303030;">

<?php view('partials.navbar'); ?>

<section class="container py-5 text-white" style="min-height:90vh;" id="photo">
    <div class="text-center mb-5">
        <h1 class="fw-bold"><?= htmlspecialchars($category['media_name'] ?? 'Media') ?></h1>
        <h3 class=""><?= htmlspecialchars($category['name'] ?? 'Category') ?></h3>
        <p style="color:#F3E6B6">Explore all photos in this category</p>
    </div>

    <div class="row g-4">
    <?php while($p = $photos->fetch_assoc()): 
        $imgSrc = (!empty($p['file_name'])) 
                    ? asset('uploads/gallery/'.$p['file_name']) 
                    : asset('assets/default-photo.jpg');
        $videoUrl = $p['video_url'] ?? null;
        $isVideo = !empty($videoUrl);
    ?>
    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 gallery-card h-100">
            <div class="img-wrap position-relative">
                <img src="<?= $imgSrc ?>" class="img-fluid" alt="Photo"
                     data-bs-toggle="modal" data-bs-target="#photoModal<?= $p['id'] ?>" style="cursor:pointer;">
                <?php if($isVideo): ?>
                <div class="position-absolute top-50 start-50 translate-middle pointer-events-none" style="pointer-events:none;">
                    <i class="bi bi-play-circle-fill text-white" style="font-size: 3rem; opacity: 0.8; text-shadow: 0 0 10px black;"></i>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal untuk full size -->
    <div class="modal fade" id="photoModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
          <div class="modal-body p-0 position-relative">
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal"></button>
            <?php if ($isVideo): ?>
                <div class="ratio ratio-16x9">
                    <?php
                        $embedUrl = $videoUrl;
                        if (strpos($videoUrl, 'youtube.com/watch?v=') !== false) {
                            $parts = parse_url($videoUrl);
                            parse_str($parts['query'] ?? '', $query);
                            if(isset($query['v'])) $embedUrl = "https://www.youtube.com/embed/" . $query['v'];
                        } elseif (strpos($videoUrl, 'youtu.be/') !== false) {
                            $path = parse_url($videoUrl, PHP_URL_PATH);
                            $embedUrl = "https://www.youtube.com/embed" . $path;
                        }
                    ?>
                    <iframe src="<?= $embedUrl ?>" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <img src="<?= $imgSrc ?>" class="img-fluid w-100" alt="Photo">
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
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
    const hero = document.getElementById('photo'); 

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
