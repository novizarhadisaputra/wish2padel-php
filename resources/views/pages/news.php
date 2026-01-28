<!DOCTYPE html>
<html lang="en">
    <?php view('partials.head', ['title' => 'News - Wish2Padel']); ?>
    <body style="background-color:#303030;">
        <?php view('partials.navbar'); ?>
        
        <section class="container py-5">
            <div class="text-center text-white mb-5">
                <h2 class="fw-bold">Latest News</h2>
                <p style="color:#F3E6B6">Stay updated with the newest announcements and highlights</p>
            </div>
        
            <div class="row g-4">
                <?php if($result): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-3">
                        <a href="<?= asset('news-detail?id=' . $row['id']) ?>" class="text-decoration-none text-dark">
                            <div class="card h-100 shadow-sm border-0 rounded-3 news-card custom-card">
                                <!-- Image -->
                                <div id="news" class="news-img-wrapper">
                                    <img src="uploads/news/<?= htmlspecialchars($row['image']) ?>" class="card-img-top" alt="news image">
                                </div>
        
                                <div class="card-body d-flex flex-column">
                                    <!-- Date -->
                                    <small class="text-muted mb-2">
                                        <?= date("F d, Y", strtotime($row['created_at'])) ?>
                                    </small>
        
                                    <!-- Title -->
                                    <h5 class="fw-bold mb-2">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </h5>  
        
                                    <!-- Highlight -->
                                    <p class="text-muted small flex-grow-1">
                                        <?= substr(strip_tags($row['description']), 0, 80) ?>...
                                    </p>
        
                                    <span class="fw-bold text-black mt-auto">Read More →</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-white">
                        <p>No news available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <style>
            .text-orange {
                color: orange;
              }
            /* Card hover effect */
            .news-card {
                transition: all 0.3s ease;
            }
            .news-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 12px 25px rgba(0,0,0,0.18);
            }
            
            /* Image wrapper with smooth zoom */
            .news-img-wrapper {
                overflow: hidden;
                border-top-left-radius: .5rem;
                border-top-right-radius: .5rem;
            }
            .news-img-wrapper img {
                transition: transform .4s ease;
            }
            .news-card:hover .news-img-wrapper img {
                transform: scale(1.1);
            }
            
            /* Tambahan custom card biar nggak bentrok sama body */
            .custom-card {
                border: 4px solid #b3b3b3ff; /* border tipis abu-abu */
                box-shadow: 0 4px 12px rgba(0,0,0,0.08); /* shadow halus */
                background: #f3f3f3ff; /* pastikan bg putih */
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
        
        
        
        <?php view('partials.navbar_sticky_script', ['sticky_target' => 'news']); ?>
        
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
