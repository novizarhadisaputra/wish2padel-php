<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
        <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
        
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        
        <title>Club - Wish2Padel</title>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
        <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    </head>
    <body style="background-color:#303030;">
        
        
        <?php view('partials.navbar'); ?>
        
        <section class="container text-white mt-3 mb-5 py-5" id="clubs">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Discover Our Clubs</h2>
                <p style="color:#F3E6B6">Find all the information about our padel clubs easily</p>
            </div>
        
            <div class="row mb-5">
                <div class="col-md-6 mb-2">
                    <select id="filterCity" class="form-select">
                        <option value="">-- Filter by City --</option>
                        <?php while($city = $cities->fetch_assoc()): ?>
                            <option value="<?= strtolower(htmlspecialchars($city['city'])) ?>">
                                <?= htmlspecialchars($city['city']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" id="searchName" class="form-control" placeholder="Search by club name...">
                </div>
            </div>
            
            <div class="col-md-12 text-end mb-3">
                <a href="<?= asset('regis-club') ?>" class="btn btn-gold">Register Club</a>
            </div>
        
            <div class="card shadow-lg border-0 p-3 mt-2" id="clubList">
                <div id="clubItems" class="row g-3">
                    <?php 
                    $allClubs = [];
                    while ($club = $centers->fetch_assoc()) {
                        $allClubs[] = $club;
                    }
                    foreach ($allClubs as $club): ?>
                        <div class="col-12 col-sm-6 col-md-4 fade-in">
                            <a href="<?= asset('club-detail?id=' . $club['id']) ?>" class="text-decoration-none text-dark">
                                <div class="border rounded p-3 h-100 text-center club-item">
                                    <div class="d-flex justify-content-center align-items-center bg-white rounded-3 mx-auto mb-3 shadow-sm" style="width:140px; height:140px; padding:10px;">
                                        <img src="<?= asset('uploads/club/' . htmlspecialchars($club['logo_url'])) ?>"
                                             alt="<?= htmlspecialchars($club['name']) ?>"
                                             class="img-fluid"
                                             style="max-height:100%; max-width:100%; object-fit:contain;">
                                    </div>
                                    <h6 class="mb-1 text-truncate" title="<?= htmlspecialchars($club['name']) ?>"><?= htmlspecialchars($club['name']) ?></h6>
                                    <p class="mb-0 text-muted" style="font-size:0.85rem;">
                                        <?= htmlspecialchars($club['street']) ?> | <?= htmlspecialchars($club['postal_code']) ?> | <?= htmlspecialchars($club['city']) ?>
                                    </p>
                                    <p class="mb-0 text-muted" style="font-size:0.85rem;">
                                        <?= htmlspecialchars($club['email']) ?> | <?= htmlspecialchars($club['phone']) ?>
                                    </p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="noResult" class="text-center text-muted p-3" style="display:none;">
                    No clubs found.
                </div>
            </div>
        
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                const cityFilter = document.getElementById("filterCity");
                const nameSearch = document.getElementById("searchName");
                const clubItemsWrapper = document.getElementById("clubItems");
                const noResult = document.getElementById("noResult");
            
                const allClubs = <?php echo json_encode($allClubs); ?>;
            
                function renderClubs(clubs) {
                    clubItemsWrapper.innerHTML = ''; 
                    if (clubs.length === 0) {
                        noResult.style.display = 'block';
                        return;
                    }
                    noResult.style.display = 'none';
            
                    clubs.forEach((club, index) => {
                        const col = document.createElement('div');
                        col.className = 'col-12 col-sm-6 col-md-4 fade-in';
                        col.style.animationDelay = `${index * 0.1}s`;
            
                        col.innerHTML = `
                            <a href="<?= asset('club-detail') ?>?id=${club.id}" class="text-decoration-none text-dark">
                                <div class="border rounded p-3 h-100 text-center club-item">
                                    <div class="d-flex justify-content-center align-items-center bg-white rounded-3 mx-auto mb-3 shadow-sm" style="width:140px; height:140px; padding:10px;">
                                        <img src="<?= asset('uploads/club/') ?>${club.logo_url}"
                                             alt="${club.name}"
                                             class="img-fluid"
                                             style="max-height:100%; max-width:100%; object-fit:contain;">
                                    </div>
                                    <h6 class="mb-1 text-truncate" title="${club.name}">${club.name}</h6>
                                    <p class="mb-0 text-muted" style="font-size:0.85rem;">
                                        ${club.street} | ${club.postal_code} | ${club.city}
                                    </p>
                                    <p class="mb-0 text-muted" style="font-size:0.85rem;">
                                        ${club.email} | ${club.phone}
                                    </p>
                                </div>
                            </a>
                        `;
                        clubItemsWrapper.appendChild(col);
                    });
                }
            
                function filterClubs() {
                    const city = cityFilter.value.trim().toLowerCase();
                    const name = nameSearch.value.trim().toLowerCase();
            
                    const filtered = allClubs.filter(club => {
                        const matchCity = !city || club.city.toLowerCase().includes(city);
                        const matchName = !name || club.name.toLowerCase().includes(name);
                        return matchCity && matchName;
                    });
            
                    renderClubs(filtered);
                }
            
                cityFilter.addEventListener("change", filterClubs);
                nameSearch.addEventListener("input", filterClubs);
            
                // renderClubs(allClubs); // Already rendered by PHP
            });
            </script>
        
            <style>
                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(20px) scale(0.98);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
            
                .fade-in {
                    animation: fadeInUp 0.5s ease forwards;
                }
            
                .club-item {
                   
                    transition: transform 0.25s ease, box-shadow 0.25s ease;
                    border: 1px solid #2e2e2e;
                }
                .club-item:hover {
                    transform: translateY(-5px) scale(1.03);
                    box-shadow: 0 6px 16px rgba(0,0,0,0.3);
                    
                }
            </style>
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
            const hero = document.getElementById('clubs');
        
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
