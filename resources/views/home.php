<?php
    // Logic moved to HomeController
?>

<!DOCTYPE html>
<html lang="en">
    <?php view('partials.head', ['title' => 'Wish2Padel']); ?>
    <style>
        .carousel-item.active .animate-text.delay-1 {
          animation: fadeSlideIn 1.2s 1s ease-out forwards;  
        }
        .carousel-item.active .animate-text.delay-2 {
          animation: fadeSlideIn 1.2s 1.5s ease-out forwards; 
        }
        .container {
          max-width: 1200px;
          margin: 0 auto;
        }
        p b {
            color:#88604A;
        }
        h3 {
          border-bottom: 3px solid #F3E6B6;
          padding-bottom: 8px;
          margin-bottom: 20px;
          font-weight: 700;
          font-size: 1.6rem;
          color: #444;
        }
        .grid-tournament {
          display: grid;
          grid-template-columns: 1.5fr 1fr 2fr;
          gap: 40px;
        }
        .tournament-title {
          color:#444; 
          margin-bottom: 40px; 
          text-align:center; 
          font-weight: 700; 
          font-size: 2.5rem;
        }
    
        @media (max-width: 1024px) {
          .grid-tournament {
            grid-template-columns: 1fr; 
            gap: 30px;
          }
        
          .tournament-title {
            font-size: 2.3rem;
          }
        }
    
        @media (max-width: 600px) {
          .grid-tournament {
            grid-template-columns: 1fr; 
            gap: 20px;
          }
        
          .tournament-title {
            font-size: 1.4rem;
            text-align: center;
            line-height: 1.4;
          }
        }
        ul {
          list-style: none;
          padding-left: 0;
          margin: 0;
        }
    
        li {
          font-weight: 600;
        }
    
        /* Responsive */
        @media (max-width: 900px) {
          .grid-tournament {
            grid-template-columns: 1fr;
          }
        }
      </style>
    </head>
    <body>
    
        <?php view('partials.navbar'); ?>
    
        <section id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3500" aria-label="Hero Image Carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                  <div class="hero" style="background-image: url('<?= asset('assets/image/mainpage.jpg') ?>');">
                    <div class="hero-text text-end">
                      <h6 class="animate-text delay-1 text-bold" style="color:#F3E6B6">PREMIUM PADEL CLUB</h6>
                      <h1 class="animate-text delay-2">PLAY. LEARN. LEVEL UP.</h1>
                      <p class="animate-text delay-3">Where passion meets the court — from your first swing to unforgettable matches, this is your home for padel.</p>
                    </div>
                  </div>
                </div>
    
      </div>
    
            <div class="carousel-indicators custom-indicators" aria-label="Carousel slide indicators">
            </div>
        </section>
    
        <section class="welcome-section">
          <div class="welcome-container">
            <div class="welcome-image-box">
              <img src="<?= asset('assets/image/dark.jpg') ?>" 
                   alt="Padel Image">
              <div class="welcome-caption">
                Discover the thrill of the Wish2Padel Teams League — where teamwork, competition, and community make every match count.
              </div>
            </div>
            <div class="welcome-text p-5">
              <!--<h6>Hello,</h6>-->
              <h2 style="color: #F3E6B6;">Welcome to Wish2Padel Teams League</h2>
              <p>
                <!-- The Wish2Padel Teams League is -->  A premier league experience for <b>players, groups, and companies</b> to form teams, compete across the season, and connect with the <b>vibrant Wish2Padel community</b> where <b>top performance is recognized</b>.
              </p>
              <button style="background-color: #F3E6B6;" onclick="window.location.href='about-league'">About Us</button>
            </div>
        
          </div>
        </section>
    
        <section id="tournament-invite" class="py-5" style="background-color: #000000;">
          <div class="container-fluid" style="width:95%">
            <div class="row ">
              <div class="col-md-6 mb-4 mb-lg-0">
                <div class="row g-3">
                    <img src="<?= asset('assets/image/tournament.jpg') ?>"
                         alt="Tournament 1"
                         class="w-100 h-100"
                         style="object-fit: cover;">
                  </div>
              </div>
        
              <div class="col-lg-6 text-white">
                <h6 class="text-uppercase fw-bold mb-2" style="color: #88604A; letter-spacing: 2px;">
                  Join the League
                </h6>
                <h1 class="fw-bold mb-3" style="color:#F3E6B6">Register Your Teams & Compete for Glory!</h1>
                <p class="mb-4" style="color: #f5f5f5;">
                  Step onto the court and feel the adrenaline rush of competitive padel!  
                  Build your ultimate teams, take on fierce rivals, and prove you have what it takes to be the best.  
                  This is your chance to turn passion into glory, and skills into unforgettable victories.
                </p>
                <p class="mb-4" style="color: #f5f5f5;">
                  Whether you're chasing trophies, building your reputation, or simply enjoying the thrill of the game,  
                  our tournaments are the perfect stage to make it happen. Don’t just play <strong>make your mark!</strong>
                </p>
                
                <h5 style="color: #F3E6B6; font-weight: 700;">Awards & Prizes</h5>
                <p style="color: #f5f5f5;">
                <br> - Prestigious titles: League Champion, Runner-up, MVP, Top-Ranked Teams Pair  
                <br>  - Attractive rewards: cash prizes, medals, and exclusive merchandise  
                <br>  - Potential sponsorship opportunities for standout players and teams  
                <br>  - Connect and compete with the nation’s top padel athletes
                </p>
        
                <a href="regis" class="btn fw-bold px-4 py-2 mt-5"
                  style="background-color: #F3E6B6; border-radius: 8px; text-decoration: none;"
                  onmouseover="this.style.backgroundColor='#FFC107';"
                  onmouseout="this.style.backgroundColor='orange';">
                  REGISTER NOW
                </a>
              </div>
            </div>
          </div>
        </section>
    
        <section class="p-5 tournament-section" style="background-color: #303030;">
        <div class="container mt-5 mb-5">
            <h2 style="color:#F3E6B6" class="text-center fw-bold">League <?= date("Y") ?></h2>
            <p class="text-center text-white ">
                Explore past and upcoming league zones, matches, and results.
            </p>
    
            <div class="row g-4 mt-5">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="col-12 col-md-4">
                            <div class="card text-center shadow-lg border-0" 
                                 style="background-color: #000; color: #fff; min-height: 220px; border-radius: 12px;">
                               
                                <div class="card-header fw-bold text-white" 
                                     style="background:#222; border-radius:12px 12px 0 0;">
                                    <?= htmlspecialchars($row['league_name']) ?>
                                </div>
        
                                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                                    <h3 style="color:white" class="fw-bold mb-2">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </h3>
                                    <a href="tournament?id=<?= $row['id'] ?>" 
                                       class="btn btn-gold"
                                       style="color: black; font-weight: bold; border-radius: 8px; padding: 10px 30px;">
                                        Learn More
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-white">
                        <p>No leagues found for this year.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
        <?php view('partials.footer'); ?>
    
        <?php view('partials.scroll_top'); ?>
        <?php view('partials.navbar_sticky_script', ['sticky_target' => 'heroCarousel']); ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    </body>
</html>
