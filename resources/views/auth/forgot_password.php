<?php
// Retrieve potential error/success messages from Session
$error = $_SESSION['error_message'] ?? null;
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password - Wish2Padel</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
    <style>
        body {
            background: url('<?= asset('assets/image/mainpage.jpg') ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .forgot-card {
            background: rgba(30, 30, 30, 0.95);
            border: 1px solid #d4af37;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        .forgot-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #d4af37, #f9f9f9, #d4af37);
        }
        .logo-img {
            display: block;
            margin: 0 auto 30px;
            max-height: 100px;
            width: auto;
        }
        .form-label {
            color: #d4af37;
            font-weight: 500;
        }
        .form-control {
            background: #2c2c2c;
            border: 1px solid #444;
            color: #fff;
            padding: 12px 15px;
            border-radius: 8px;
        }
        .form-control:focus {
            background: #333;
            border-color: #d4af37;
            box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.2);
            color: #fff;
        }
        .input-group-text {
            background: #2c2c2c;
            border: 1px solid #444;
            border-right: none;
            color: #888;
        }
        .form-control-with-icon {
            border-left: none;
        }
        .btn-gold-block {
            background: #d4af37;
            color: #000;
            font-weight: 700;
            width: 100%;
            padding: 12px;
            border-radius: 25px;
            border: none;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }
        .btn-gold-block:hover {
            background: #f9f9f9;
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
        }
        .alert-custom {
            border-radius: 10px;
            font-size: 0.9rem;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #aaa;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        .login-link a:hover {
            color: #d4af37;
        }
    </style>
</head>
<body>

<div class="forgot-card">
    <a href="<?= asset('/') ?>">
        <img src="<?= getSiteLogo() ?>" alt="Wish2Padel" class="logo-img">
    </a>
    
    <h4 class="text-center text-white mb-4">Reset Password</h4>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-custom d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-custom d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div><?= htmlspecialchars($success) ?></div>
        </div>
    <?php endif; ?>

    <form action="<?= asset('forgot-password') ?>" method="POST">
        <!-- Username -->
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                <input type="text" class="form-control form-control-with-icon" id="username" name="username" placeholder="Enter your username" required>
            </div>
        </div>

        <!-- Captain Name -->
        <div class="mb-3">
            <label for="captain_team" class="form-label">Team Captain Name</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-badge-fill"></i></span>
                <input type="text" class="form-control form-control-with-icon" id="captain_team" name="captain_team" placeholder="Verification" required>
            </div>
        </div>

        <!-- Captain Email -->
        <div class="mb-3">
            <label for="captain_email" class="form-label">Team Captain Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                <input type="email" class="form-control form-control-with-icon" id="captain_email" name="captain_email" placeholder="Verification" required>
            </div>
        </div>

        <!-- New Password -->
        <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control form-control-with-icon" id="new_password" name="new_password" placeholder="Set new password" required>
            </div>
        </div>

        <button type="submit" class="btn-gold-block">Reset Password</button>
    </form>

    <div class="login-link">
        <a href="<?= asset('login') ?>">
            <i class="bi bi-arrow-left me-1"></i> Back to Login
        </a>
    </div>
</div>

</body>
</html>
