<!DOCTYPE html>
<html lang="en">
    <?php view('partials.head', ['title' => 'Presentation - Wish2Padel']); ?>
    <body>
    
        <?php view('partials.navbar'); ?>
        
        <section class="py-5" style="background-color: #303030;">
          <div class="container">
            <div class="text-center mb-4" data-aos="fade-down">
              <img src="<?= getSiteLogo() ?>" alt="Wish2Padel Logo"
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
        
        <?php view('partials.scroll_top'); ?>
        <?php view('partials.navbar_sticky_script', ['sticky_target' => 'about-liga']); ?>
        
        <style>
          h2, p {
            color:white;
          }
        </style>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
