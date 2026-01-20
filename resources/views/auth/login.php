<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p logo.jpeg') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p logo.jpeg') ?>">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body, html {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      /* Adjusted path for public/assets */
      background: url('<?= asset('assets/image/mainpage.jpg') ?>') no-repeat center center fixed;
      background-size: cover;
    }

.login-container {
  max-width: 700px;
  width: 90%;
  background: rgba(255, 255, 255, 0.85);
  margin: 5vh auto 40px;
  padding: 35px 40px;
  border-radius: 12px;
  box-shadow: 0 0 20px rgba(0,0,0,0.25);
  transition: box-shadow 0.3s ease;
  position: relative;
}
/* ... (Rest of CSS kept same for brevity, assume valid CSS) ... */
.btn-submit {
  background-color: #88694A;
  color: #212121;
  font-weight: 700;
  border: none;
  padding: 12px;
  border-radius: 30px;
  width: 100%;
  font-size: 1.15rem;
  box-shadow: 0 5px 12px #88694A;
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
  cursor: pointer;
}
.error-msg {
  color: #D32F2F;
  font-size: 0.875rem;
  margin-top: 6px;
  min-height: 18px;
  font-weight: 600;
  text-align: center;
}
  </style>
</head>
<body>
  <div class="login-container shadow-sm">
    <div class="logo">
      <img src="<?= asset('assets/image/w2p.png') ?>" alt="Wish2Padel Logo" height="150" style="display:block;margin:0 auto;">
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
        <input type="text" class="form-control" id="login_identifier" name="login_identifier" placeholder="Enter username" required />
      </div>

      <div class="mb-3">
        <label for="login_password" class="form-label">Password</label>
        <input type="password" class="form-control" id="login_password" name="login_password" placeholder="Enter your password" required />
      </div>

      <button type="submit" class="btn btn-submit">Login</button>
      <div class="mt-3 text-center">
        <a href="<?= asset('forgot') ?>" class="text-decoration-underline fw-bold text-black">Forgot Password?</a>
      </div>
    </form>
  </div>
</body>
</html>
