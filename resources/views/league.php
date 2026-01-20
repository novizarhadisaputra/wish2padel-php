<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>League - Wish2Padel</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    </head>
    <body>
        
        <?php view('partials.navbar'); ?>
        
        <section class="p-5" style="background-color: #303030">
          <div class="container mb-5">
            <div class="text-center mb-5" style="color:#f3e6b6">
              <h2 class="fw-bold">League Overview</h2>
              <p style="color:#88694A">Explore past and upcoming league zones, matches, and results.</p>
            </div>
        
            <div id="filter" class="d-flex container justify-content-center mb-5">
              <div class="btn-group">
                <button style="background-color:#f3e6b6; font-weight:700" class="btn dropdown-toggle px-4 py-2" type="button" id="yearDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                  Select Year
                </button>
                <ul style="background-color:#f3e6b6;" class="dropdown-menu" aria-labelledby="yearDropdown">
                  <li><a class="dropdown-item year-option" href="#" data-year="all">All Years</a></li>
                  <?php 
                  $years = array_unique(array_column($leagues, 'date'));
                  rsort($years);
                  foreach($years as $y): ?>
                    <li><a class="dropdown-item year-option" href="#" data-year="<?= $y ?>"><?= $y ?></a></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
        
            <?php foreach($leagues as $league): ?>
                <div class="league-group" data-year="<?= $league['date'] ?>">
                  <h4 style="color:#f3e6b6;" class="mb-3 fw-bold"><?= htmlspecialchars($league['name']) ?> <?= $league['date'] ?></h4>
                  <div class="row g-4 mb-5">
                    <?php 
                    $leagueTournaments = $tournaments[$league['date']][$league['id']] ?? [];
                    foreach($leagueTournaments as $t): ?>
                      <div class="col-md-4 tournament-item" data-year="<?= $league['date'] ?>" data-aos="fade-up">
                        <div class="card shadow-lg h-100 border-0" 
                             style="border-radius: 15px; background-color: #ffffffff; color: #000000ff; min-height: 250px; display: flex; flex-direction: column; justify-content: center;">
                          <div class="card-body d-flex flex-column justify-content-center align-items-center text-center h-100">
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($t['name']) ?></h5>
                            <p class="card-text">Start Date: <?= date('d M Y', strtotime($t['start_date'])) ?></p>
                            <a href="<?= asset('tournament?id=' . $t['id']) ?>" 
                               class="btn mt-3 fw-bold btn-gold">Learn More</a>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
            <?php endforeach; ?>
          </div>
        </section>
        
        <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
        <script>
            AOS.init({ duration: 800, once: true });
          
            document.querySelectorAll('.year-option').forEach(item => {
              item.addEventListener('click', e => {
                e.preventDefault();
                const year = item.dataset.year;
                document.querySelector('#yearDropdown').textContent = year === 'all' ? 'All Years' : year;
                document.querySelectorAll('.league-group').forEach(group => {
                  group.style.display = (year === 'all' || group.dataset.year === year) ? 'block' : 'none';
                });
              });
            });
            
            const currentYear = new Date().getFullYear().toString();
            document.querySelectorAll('.league-group').forEach(group => {
              group.style.display = group.dataset.year === currentYear ? 'block' : 'none';
            });
            document.querySelector('#yearDropdown').textContent = currentYear;
        </script>
        
        <style>
            .card:hover { transform: translateY(-7px); transition: transform 0.3s ease; }
            .dropdown-menu { min-width: 150px; font-size: 1rem; }
        </style>
        
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
            window.scrollTo({
              top: 0,
              behavior: "smooth"
            });
          });
        </script>
        
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            const navbar = document.getElementById('maiavbar');
            const hero = document.getElementById('filter'); 
        
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
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
