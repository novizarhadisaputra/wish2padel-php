<?php
session_start();
require 'config.php';

$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

date_default_timezone_set('Asia/Riyadh');

$centers = $conn->query("SELECT id, name FROM centers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone     = trim($_POST['phone']);
    $email     = trim($_POST['email']);
    $gender    = $_POST['gender'];
    $address   = trim($_POST['address']);
    $center_id = $_POST['center_id'];

    $errors = [];
    if (empty($full_name)) $errors[] = "Full Name is required.";
    if (empty($phone)) $errors[] = "Phone Number is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($center_id)) $errors[] = "Club selection is required.";

    if (empty($errors)) {
        // Ambil timestamp Riyadh
        $created_at = date('Y-m-d H:i:s');
    
        $stmt = $conn->prepare("
            INSERT INTO individuals (full_name, phone, email, gender, address, center_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssis", $full_name, $phone, $email, $gender, $address, $center_id, $created_at);
    
        if ($stmt->execute()) {
            echo "<script>
                    alert('Your registration was successful. The club will contact you if they form a team.');
                    window.location.href = 'index.php';
                  </script>";
            exit;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
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
    
    
        <div class="container-fluid mt-5">
            <div class="form-box">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach($errors as $err) echo "<li>$err</li>"; ?>
                        </ul>
                    </div>
                <?php endif; ?>
        
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success_msg) ?>
                    </div>
                <?php endif; ?>
        
                <form method="POST" id="individualForm" novalidate>
                    <section id="individualRegistration" class="p-5">
                        <h4>Individual Registration</h4>
        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                        </div>
        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                        </div>
        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
        
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select name="gender" id="gender" class="form-select" required>
                                <option value="">-- Select Gender --</option>
                                <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" id="address" class="form-control" rows="3" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
        
                        <div class="mb-3">
                            <label for="center_id" class="form-label">Club</label>
                            <select name="center_id" id="center_id" class="form-select" required>
                                <option value="">-- Select Club --</option>
                                <?php foreach ($centers as $center): ?>
                                    <option value="<?= htmlspecialchars($center['id']) ?>" <?= (($_POST['center_id'] ?? '') == $center['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($center['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
    
        
                        <button type="submit" class="btn btn-gold">Register Individual</button>
                    </section>
                </form>
            </div>
        </div>
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