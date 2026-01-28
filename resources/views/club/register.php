<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Club Registration - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        nav#maiavbar {
            width: 100%;
            transition: all 0.3s ease;
            z-index: 9999;
        }
        nav#maiavbar.navbar-fixed {
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(90deg, #00796B, #004D40);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
            animation: fadeInDown 0.4s ease forwards;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <?php view('partials.navbar'); ?>

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
                        <label for="city" class="form-label">City</label>
                        <input type="text" name="city" id="city" class="form-control" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" placeholder="e.g. Riyadh" required>
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

    <?php view('partials.footer'); ?>

    <button id="scrollTopBtn" title="Go to top">↑</button>

    <script>
        const checkbox = document.getElementById('contractCheck');
        const submitBtn = document.getElementById('submitBtn');
    
        checkbox.addEventListener('change', function() {
            submitBtn.disabled = !this.checked;
        });

        const scrollBtn = document.getElementById("scrollTopBtn");
        window.onscroll = function() {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                scrollBtn.style.display = "block";
            } else {
                scrollBtn.style.display = "none";
            }
        };
        scrollBtn.addEventListener("click", function() {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.getElementById('maiavbar');
            function toggleNavbarFixed() {
                if (window.scrollY >= 200) {
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
