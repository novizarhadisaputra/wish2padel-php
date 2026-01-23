<?php
    session_start();
    require 'config.php';
    
    $conn = getDBConnection();
    $current_page = basename($_SERVER['PHP_SELF']);
    $username = $_SESSION['username'] ?? null;
    
    date_default_timezone_set('Asia/Riyadh');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name     = trim($_POST['name']);
        $username = trim($_POST['username']);
        $email    = trim($_POST['email']);
        $phone    = trim($_POST['phone']);
        $password = $_POST['password'];
        $created_at = date('Y-m-d H:i:s');
    
        $errors = [];
        if (empty($name)) $errors[] = "Name is required.";
        if (empty($username)) $errors[] = "Username is required.";
        if (empty($email)) $errors[] = "Email is required.";
        if (empty($phone)) $errors[] = "Phone is required.";
        if (empty($password)) $errors[] = "Password is required.";
    
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
            $stmt = $conn->prepare("
                INSERT INTO centers
                (name, username, password, street, postal_code, city, zone, phone, email, website, description, logo_url, created_at, updated_at)
                VALUES (?, ?, ?, NULL, NULL, NULL, NULL, ?, ?, NULL, NULL, NULL, ?, NULL)
            ");
            
            $stmt->bind_param("ssssss", $name, $username, $hashedPassword, $phone, $email, $created_at);
    
    
            if ($stmt->execute()) {
                header("Location: login/login.php");
                exit;
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
        }
    }
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Player Regist - Wish2Padel</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="assets/css/stylee.css?=v12">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    </head>
    <body>
    
    
        <?php require 'src/navbar.php' ?>
    
    
        <div class="container mt-5 p-4">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach($errors as $err) echo "<li>$err</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>
    
            <form method="POST" novalidate id="clubForm">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Register Club</h4>
        
                        <!-- Club Information -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Club Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                        </div>
        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
        
                        <!-- Club Agreement -->
                        <div class="mb-4 p-3" style="background-color:#eaf7ff; border-left:5px solid #007bff; border-radius:5px;">
                            <h5>Club Agreement</h5>
                            <p style="font-size:14px; color:#004085; margin-bottom:0.5rem;">
                                By registering this club, I acknowledge and agree that:<br>
                                1. This club serves as the official guardian for its affiliated teams and individual players.<br>
                                2. The club is responsible for providing access to its facilities, including courts, practice sessions, and related resources.<br>
                                3. The club ensures that all teams and players under its umbrella comply with the rules, schedules, and regulations of the facility and events.<br>
                                4. The club acts as the primary contact for all matters related to its teams and players in tournaments or events.
                            </p>
                            <div class="form-check mt-3">
                                <input type="checkbox" class="form-check-input" id="contractCheck">
                                <label class="form-check-label" for="contractCheck">
                                    I have read and fully agree to the terms above.
                                </label>
                            </div>
                        </div>
        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-gold" id="submitBtn" disabled>Register Club</button>
                    </div>
                </div>
            </form>
        </div>
        <script>
            const checkbox = document.getElementById('contractCheck');
            const submitBtn = document.getElementById('submitBtn');
        
            checkbox.addEventListener('change', function() {
                submitBtn.disabled = !this.checked;
            });
        </script>
    
    
        <?php require 'src/footer.php' ?>
    
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
    
    
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const navbar = document.getElementById('maiavbar');
                const hero = document.getElementById('about-liga'); // Pastikan ada elemen heroCarousel di halaman
    
                function toggleNavbarFixed() {
                    if (!hero) return; // kalau heroCarousel gak ada, skip
    
                    const scrollPos = window.scrollY;
                    const heroHeight = hero.offsetHeight;
    
                    if (scrollPos >= heroHeight) {
                        navbar.classList.add('navbar-fixed');
                        document.body.style.paddingTop = navbar.offsetHeight + 'px'; // supaya konten gak tertutup
                    } else {
                        navbar.classList.remove('navbar-fixed');
                        document.body.style.paddingTop = '0';
                    }
                }
    
                window.addEventListener('scroll', toggleNavbarFixed);
                toggleNavbarFixed(); // jalankan sekali saat load
            });
        </script>
    
        <style>
            /* Navbar default (sudah ada background dan shadow dari kamu) */
            nav#maiavbar {
    
                width: 100%;
                transition: all 0.3s ease;
                z-index: 9999;
            }
    
            /* Navbar jadi fixed dan muncul dengan animasi */
            nav#maiavbar.navbar-fixed {
                position: fixed;
                top: 0;
                left: 0;
                background: linear-gradient(90deg, #00796B, #004D40);
                box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
                animation: fadeInDown 0.4s ease forwards;
            }
    
            @keyframes fadeInDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
    
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>