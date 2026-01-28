<!DOCTYPE html>
<html lang="en">
    <?php view('partials.head', ['title' => 'Gallery - Wish2Padel']); ?>
    <style>
    /* Gallery grid cards */
    section { display:flex; flex-direction:column; }
    .gallery-card { transition: transform .25s ease, box-shadow .25s ease; background:#fff; }
    .gallery-card:hover { transform: translateY(-6px); box-shadow:0 12px 30px rgba(0,0,0,.15); }
    .img-wrap { overflow:hidden; border-top-left-radius:1rem; border-top-right-radius:1rem; height:180px; }
    .img-wrap img { width:100%; height:100%; object-fit:cover; transition: transform .35s ease; display:block; }
    .gallery-card:hover .img-wrap img { transform: scale(1.06); }
    
    /* Optional: pastikan grid stretch ke bawah */
    .row.flex-fill { flex:1; }
    </style>
</head>
<body style="background-color:#303030;">


<?php view('partials.navbar'); ?>

<section class="container py-5 d-flex flex-column" style="min-height:90vh;" id="media">
    <div class="text-center text-white mb-5">
        <h1 class="fw-bold">Gallery</h1>
        <p style="color:#F3E6B6">Welcome to your gallery</p>
    </div>

    <div class="row g-4 flex-fill">
        <?php if($medias): ?>
            <?php while($m = $medias->fetch_assoc()): ?>
        <div class="col-12 col-sm-6 col-md-3">
            <a href="<?= asset('media/categories?media_id=' . $m['id']) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 gallery-card h-100">
                    <div class="img-wrap">
                        <?php if(!empty($m['cover_image'])): ?>
                            <img src="<?= asset('uploads/gallery/'.htmlspecialchars($m['cover_image'])) ?>" class="img-fluid" alt="<?= htmlspecialchars($m['name']) ?>">
                        <?php else: ?>
                            <img src="<?= asset('assets/default-cover.jpg') ?>" class="img-fluid" alt="No cover">
                        <?php endif; ?>
                    </div>
                    <div class="card-body text-center">
                        <h6 class="mb-0 text-dark"><?= htmlspecialchars($m['name']) ?></h6>
                    </div>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center text-white">
                <p>No gallery items found.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php view('partials.footer'); ?>

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


<?php view('partials.navbar_sticky_script', ['sticky_target' => 'media']); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
