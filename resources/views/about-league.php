<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        
        <title>Presentation - Wish2Padel</title>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    </head>
    <body>
    
        <?php view('partials.navbar'); ?>
        
        <section class="py-5" style="background-color: #303030;">
          <div class="container">
            <div class="text-center mb-4" data-aos="fade-down">
              <img src="<?= asset('assets/image/w2p.png') ?>" alt="Wish2Padel Logo" 
                   style="max-height: 190px; width: auto;">
            </div>
        
            <div class="text-center mb-5" data-aos="fade-down">
              <h2 class="fw-bold" style="color: #D5D5D5;">About the League</h2>
              <p class="lead" style="color: #F3E6B6;">
                Discover the story, passion, and community spirit that drive the Wish2Padel Team League.
              </p>
            </div>
        
            <div class="mb-5 text-center" data-aos="zoom-in-up">
              <h4 class="fw-bold" style="color: #D5D5D5;">Presentation</h4>
              <p class="fs-5 mx-auto" style="max-width: 700px; color: #F3E6B6;">
                The Wish2Padel Team League is a premier league experience for <b>players, groups, and companies</b> 
                to form teams, compete across the season, and connect with the <b>vibrant Wish2Padel community</b> 
                where <b>top performance is recognized</b>.
              </p>
            </div>
        
            <div id="about-liga" class="row g-4 mb-5">
                <?php
                    $delay = 100;
                    if ($result):
                    while ($row = $result->fetch_assoc()): 
                ?>
                  <div class="col-md-4" data-aos="zoom-in" data-aos-delay="<?= $delay ?>">
                    <div class="position-relative overflow-hidden rounded shadow-sm">
                      <img src="<?= htmlspecialchars($row['file_path']) ?>" 
                           class="img-fluid" 
                           alt="<?= htmlspecialchars($row['description']) ?>">
                      <div class="position-absolute bottom-0 start-0 bg-dark bg-opacity-50 text-white p-3">
                        <?= htmlspecialchars($row['description']) ?>
                      </div>
                    </div>
                  </div>
                <?php 
                  $delay += 200;
                  endwhile; 
                  endif;
                ?>
            </div>
        
            <div class="mb-5" data-aos="fade-right">
              <h4 class="fw-bold" style="color: #D5D5D5;">Our Mission</h4>
              <p style="color: #F3E6B6;">
                Our Mission is to provide an <b>innovative and inclusive</b> platform where 
                <b>individuals, groups, and companies</b> can create their own teams and enjoy a professionally organized competitive experience. 
                We deliver an environment where players can develop skills, <b>connect</b>, and <b>grow</b> both on and off the court—while pursuing their <b>dreams</b> 
                and aiming for <b>recognition and rewards</b>.
              </p>
            </div>
          </div>
        </section>
        
        <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
        <script>
          AOS.init({
            duration: 1000,
            easing: 'ease-in-out',
            once: true
          });
        </script>
        
        
        <?php view('partials.footer'); ?>
        
        <button id="scrollTopBtn" title="Go to top">↑</button>
        
        <style>
          h2, p {
            color:white;
          }
        </style>
        
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
          document.addEventListener('DOMContentLoaded', function () {
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
            toggleNavbarFixed(); // jalankan sekali saat load
          });
        </script>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
