<?php
    session_start();
    require 'config.php';
    $conn = getDBConnection();
    $username = $_SESSION['username'] ?? null;
    $current_page = basename($_SERVER['PHP_SELF']);
    
    $query = "
    SELECT c.*, COALESCE(SUM(p.quantity),0) AS total_pistas
    FROM centers c
    LEFT JOIN pistas p ON c.id = p.center_id
    GROUP BY c.id
    ";
    $centers = $conn->query($query);
    
    $cities = $conn->query("SELECT DISTINCT city FROM centers ORDER BY city ASC");
    
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        
        <title>Club - Wish2Padel</title>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/stylee.css?v=12">
    </head>
    <body style="background-color:#303030;">
        
        
        <?php require 'src/navbar.php' ?>
        
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
                <a href="regis-club.php" class="btn btn-gold">Register Club</a>
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
                            <a href="club-detail.php?id=<?= $club['id'] ?>" class="text-decoration-none text-dark">
                                <div class="border rounded p-3 h-100 text-center club-item">
                                    <img src="uploads/club/<?= htmlspecialchars($club['logo_url']) ?>" 
                                         alt="<?= htmlspecialchars($club['name']) ?>" 
                                         class="img-fluid mb-3"
                                         style="width:140px; height:140px; object-fit:contain;">
                                    <h6 class="mb-1"><?= htmlspecialchars($club['name']) ?></h6>
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
                            <a href="club-detail.php?id=${club.id}" class="text-decoration-none text-dark">
                                <div class="border rounded p-3 h-100 text-center club-item">
                                    <img src="uploads/club/${club.logo_url}" 
                                         alt="${club.name}" 
                                         class="img-fluid mb-3"
                                         style="width:140px; height:140px; object-fit:contain;">
                                    <h6 class="mb-1">${club.name}</h6>
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
            
                renderClubs(allClubs);
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
     
        <section style="display:none" class="container text-white mt-3 mb-5 py-5" id="clubs">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Discover Our Clubs</h2>
                <p style="color:#F3E6B6">Find all the information about our padel clubs easily</p>
            </div>
        
            <div class="row mb-5">
                <div class="col-md-6 mb-2">
                    <select id="filterCity" class="form-select">
                        <option value="">-- Filter by City --</option>
                        <?php while($city = $cities->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($city['city']) ?>">
                                <?= htmlspecialchars($city['city']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" id="searchName" class="form-control" placeholder="Search by club name...">
                </div>
            </div>
        
            <div class="col-md-12 text-end">
                <a href="regis-club.php" class="btn btn-gold">Register Club</a>
            </div>
        
            <div class="row g-4 mt-1" id="clubList">
              <?php while($club = $centers->fetch_assoc()): ?>
                <div class="col-md-12 club-item" 
                    data-city="<?= htmlspecialchars($club['city']) ?>" 
                    data-name="<?= strtolower(htmlspecialchars($club['name'])) ?>"
                    data-aos="fade-up" 
                    data-aos-duration="800">
        
                    <a href="club-detail.php?id=<?= $club['id'] ?>" class="text-decoration-none text-dark">
                        <div class="card shadow-lg border-0 position-relative h-100" 
                            style="border-radius: 15px; overflow: hidden; min-height: 200px;">
                           
                            <div style="background-color:#303030" class="position-absolute top-0 end-0 m-3 text-white px-3 py-1 rounded-3 shadow">
                                <?= $club['total_pistas'] ?> Field
                            </div>
        
                            <div class="row g-0 h-100">
                                <div class="col-md-4 d-flex align-items-center justify-content-center bg-light p-3">
                                  <img id="club" src="uploads/club/<?= htmlspecialchars($club['logo_url']) ?>" 
                                  alt="<?= htmlspecialchars($club['name']) ?>" class="img-fluid w-100 h-100"  style="object-fit: contain;">
                                </div>
                                <div class="col-md-8 p-4 d-flex flex-column justify-content-center">
                                    <h5 class="fw-bold mb-3"><?= htmlspecialchars($club['name']) ?></h5>
                                    <p class="text-muted mb-2">
                                        <?= htmlspecialchars($club['street']) ?> | <?= htmlspecialchars($club['postal_code']) ?> | <?= htmlspecialchars($club['city']) ?>
                                    </p>
                                    <p class="text-muted mb-0">
                                        <?= htmlspecialchars($club['email']) ?> | <?= htmlspecialchars($club['phone']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
              <?php endwhile; ?>
            </div>
        </section>
        
        <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
        <script>
            AOS.init();
            
            document.addEventListener("DOMContentLoaded", function() {
                const cityFilter = document.getElementById("filterCity");
                const nameSearch = document.getElementById("searchName");
                const items = document.querySelectorAll(".club-item");
            
                function filterClubs() {
                    const city = cityFilter.value.toLowerCase();
                    const name = nameSearch.value.toLowerCase();
            
                    items.forEach(item => {
                        const itemCity = item.getAttribute("data-city").toLowerCase();
                        const itemName = item.getAttribute("data-name");
            
                        const matchCity = !city || itemCity === city;
                        const matchName = !name || itemName.includes(name);
            
                        if (matchCity && matchName) {
                            item.style.display = "block";
                            item.setAttribute("data-aos", "fade-up"); 
                        } else {
                            item.style.display = "none";
                        }
                    });
                    AOS.refresh();
                }
            
                cityFilter.addEventListener("change", filterClubs);
                nameSearch.addEventListener("input", filterClubs);
            });
        </script>
        
        <?php require 'src/footer.php' ?>
        
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
            const hero = document.getElementById('club');
        
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
