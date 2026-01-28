<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>News Detail - Wish2Padel</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    </head>
    <body style="background-color:#303030">
    
        <?php view('partials.navbar'); ?>
        
        <section class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-lg border-0 rounded-4 p-4">
                    
                        <?php if($news): ?>
                        <h2 class="fw-bold text-dark mb-3"><?= htmlspecialchars($news['title']) ?></h2>
                       
                        <small class="text-muted d-block mb-4">
                            <?= date("F d, Y", strtotime($news['created_at'])) ?>
                        </small>
        
                        <div class="news-detail-img mb-4">
                            <img src="uploads/news/<?= htmlspecialchars($news['image']) ?>" class="img-fluid rounded-3 w-100" alt="news image">
                        </div>
        
                        <div id="deskrip" class="news-content text-muted fs-6">
                            <?= $news['description'] ?>
                        </div>
                        <?php else: ?>
                            <div class="alert alert-info py-4">
                                <h4 class="alert-heading">News Not Found</h4>
                                <p>This news article may have been removed or there is a temporary connection issue. Please try again later.</p>
                                <hr>
                                <a href="<?= asset('news') ?>" class="btn btn-outline-dark">Back to News</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        
        <style>
            .text-orange {
                color: orange;
              }
            .news-detail-img img {
                max-height: 450px;
                object-fit: cover;
            }
            .news-content {
                line-height: 1.8;
            }
        </style>
        
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
        <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
        <script>
          AOS.init({
            duration: 800,
            once: true
          });
        </script>
    
        
        <?php view('partials.footer'); ?>
        
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
            const hero = document.getElementById('deskrip');
        
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
