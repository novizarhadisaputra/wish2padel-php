<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
        
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        
        <title>Club Details - Wish2Padel</title>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    </head>
    <body style="background-color:#303030">
    
        <?php view('partials.navbar'); ?>
        
        <section class="container bg-white mt-5 mb-5 p-5 shadow-lg border rounded">
            <div class="row mb-5 align-items-center">
                <div class="col-md-4 text-center">
                    <img src="uploads/club/<?= htmlspecialchars($center['logo_url']) ?>" 
                         alt="<?= htmlspecialchars($center['name']) ?>" 
                         class="img-fluid rounded shadow" style="max-height:200px; object-fit:contain;">
                </div>
                <div class="col-md-8">
                    <h1 class="fw-bold"><?= htmlspecialchars($center['name']) ?></h1>
                    <p class="text-muted">
                        <?= htmlspecialchars($center['street']) ?>, 
                        <?= htmlspecialchars($center['city']) ?>, 
                        <?= htmlspecialchars($center['postal_code']) ?>
                    </p>
                    <p><i class="bi bi-telephone"></i> <?= htmlspecialchars($center['phone']) ?> | 
                       <i class="bi bi-envelope"></i> <?= htmlspecialchars($center['email']) ?></p>
                    <p><i class="bi bi-globe"></i> <a href="<?= htmlspecialchars($center['website']) ?>" target="_blank">Website</a></p>
                </div>
            </div>
        
            <div class="mb-5">
                <h2 class="fw-bold mb-4">Field</h2>
                <div class="row g-4">
                    <?php while($p = $pistas->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body text-center">
                                    <h5 class="fw-bold"><?= htmlspecialchars($p['name']) ?></h5>
                                    <p class="text-muted">Amount: <?= htmlspecialchars($p['quantity']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        
            <div class="mb-5">
                <h2 id="deskrip" class="fw-bold mb-3">About Club</h2>
               <p class="lead"><?= $center['description'] ?></p>
            </div>
        
            <div class="mb-5">
                <h2 class="fw-bold mb-4">Schedule</h2>
                <div class="table-responsive">
                    <table class="table table-striped shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Day</th>
                                <th>Open</th>
                                <th>Close</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($s = $schedules->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['day']) ?></td>
                                    <td><?= htmlspecialchars($s['open_time']) ?></td>
                                    <td><?= htmlspecialchars($s['close_time']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        
            <div class="mb-5">
                <h2 class="fw-bold mb-4">Photo</h2>
                <div class="row g-4">
                    <?php while($ph = $photos->fetch_assoc()): ?>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm">
                                <img src="uploads/club/<?= htmlspecialchars($ph['url']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($ph['caption']) ?>" 
                                     style="object-fit:cover; height:200px;">
                                <?php if ($ph['caption']): ?>
                                    <div class="card-body text-center">
                                        <small class="text-muted"><?= htmlspecialchars($ph['caption']) ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
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
