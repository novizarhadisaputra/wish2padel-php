<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=13') ?>">
</head>
<body class="login-page">
  <div class="login-container shadow-sm">
    <div class="logo">
      <img src="<?= getSiteLogo() ?>" alt="Wish2Padel Logo" height="150" style="display:block;margin:0 auto;">
    </div>

    <?php
    if (isset($_SESSION['error_message'])) {
        echo '<div class="error-msg" tabindex="0">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <form id="loginForm" method="POST" action="<?= asset('login') ?>" novalidate>
      <div class="mb-3">
        <label for="login_identifier" class="form-label">Username</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
            <input type="text" class="form-control" id="login_identifier" name="login_identifier" placeholder="Enter username" required />
        </div>
      </div>

      <div class="mb-3">
        <label for="login_password" class="form-label">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" class="form-control" id="login_password" name="login_password" placeholder="Enter your password" required />
        </div>
      </div>

      <button type="submit" class="btn btn-submit">Login</button>
      <div class="mt-3 text-center">
        <a href="<?= asset('forgot') ?>" class="text-decoration-underline fw-bold text-black">Forgot Password?</a>
      </div>
    </form>
  </div>
</body>
</html>
