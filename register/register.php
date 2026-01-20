<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register - Wish2Padel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body, html {
  height: 100%;
  margin: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: url('../assets/image/mainpage.jpg') no-repeat center center fixed;
  background-size: cover;
  color: #212121;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.register-container {
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

.register-container:hover {
  box-shadow: 0 0 30px #FFC107;
}

.logo {
  display: block;
  margin: 0 auto 25px auto;
  width: 140px;
  color: #00796B;
  font-weight: 700;
  font-size: 2rem;
  text-align: center;
  user-select: none;
  letter-spacing: 2px;
  text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
}

.form-group {
  position: relative;
  margin-bottom: 28px;
}

.form-label {
  font-weight: 600;
  margin-bottom: 6px;
  color: #004D40;
  display: block;
  font-size: 1rem;
}

.form-control {
  border: none;
  border-bottom: 2.5px solid #ccc;
  border-radius: 0;
  padding-left: 40px;
  padding-top: 10px;
  padding-bottom: 10px;
  font-size: 1rem;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
  background: transparent;
  width: 100%;
  box-sizing: border-box;
}

.form-control::placeholder {
  color: #999;
  font-style: italic;
}

.form-control:focus {
  border-color: #FFC107;
  box-shadow: 0 2px 8px rgba(255, 193, 7, 0.5);
  outline: none;
  background: transparent;
}

.input-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #757575;
  font-size: 1.2rem;
  transition: color 0.3s ease;
  pointer-events: none;
}

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

.btn-submit:hover {
  background-color: #696969;
  box-shadow: 0 7px 18px #696969;
}

.btn-submit:active {
  background-color: #b78100;
  box-shadow: none;
}

.error-msg {
  color: #D32F2F;
  font-size: 0.875rem;
  margin-top: 6px;
  min-height: 18px;
  font-weight: 600;
  text-align: center;
}

.login-link {
  display: block;
  margin-top: 22px;
  text-align: center;
  color: #00796B;
  text-decoration: none;
  font-weight: 600;
  letter-spacing: 0.05em;
}

.login-link:hover {
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
  .register-container {
    padding: 30px 25px;
  }
  .logo {
    font-size: 1.75rem;
    width: auto;
    margin-bottom: 20px;
  }
  .form-label {
    font-size: 0.95rem;
  }
  .btn-submit {
    font-size: 1.1rem;
    padding: 12px;
  }
}

@media (max-width: 480px) {
  .register-container {
    padding: 25px 20px;
    margin: 3vh auto 30px;
  }
  .form-label {
    font-size: 0.9rem;
  }
  .btn-submit {
    font-size: 1rem;
    padding: 10px;
  }
}

  </style>
</head>
<body>
  <div class="register-container shadow-sm" role="main" aria-label="User registration form">
    <div class="logo">
      <img src="https://www.wish2padel.com/assets/image/w2p.png" 
           alt="Wish2Padel Logo - Arab Federation Beginner Padel League" 
           height="150">
    </div>

    <?php
   
    if (isset($_SESSION['error_messages'])) {
        echo '<div class="alert alert-danger" role="alert" tabindex="0">';
        foreach ($_SESSION['error_messages'] as $error) {
            echo htmlspecialchars($error) . '<br>';
        }
        echo '</div>';
        unset($_SESSION['error_messages']);
    }
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success" role="alert" tabindex="0">'
            .htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    ?>

    <form id="registerForm" method="POST" action="register_process.php" novalidate>
      <div class="form-group">
        <label for="username" class="form-label">Username</label>
        <i class="bi bi-person-fill input-icon"></i>
        <input
          type="text"
          class="form-control"
          id="username"
          name="username"
          placeholder="Choose a username"
          required
          aria-describedby="usernameError"
          aria-required="true"
          minlength="3"
        />
        <div class="error-msg" id="usernameError" aria-live="polite"></div>
      </div>

      <div class="form-group">
        <label for="email" class="form-label">Email address</label>
        <i class="bi bi-envelope-fill input-icon"></i>
        <input
          type="email"
          class="form-control"
          id="email"
          name="email"
          placeholder="Your active email"
          required
          aria-describedby="emailError"
          aria-required="true"
        />
        <div class="error-msg" id="emailError" aria-live="polite"></div>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <i class="bi bi-lock-fill input-icon"></i>
        <input
          type="password"
          class="form-control"
          id="password"
          name="password"
          placeholder="At least 6 characters"
          required
          minlength="6"
          aria-describedby="passwordError"
          aria-required="true"
        />
        <div class="error-msg" id="passwordError" aria-live="polite"></div>
      </div>

      <div class="form-group">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <i class="bi bi-lock-fill input-icon"></i>
        <input
          type="password"
          class="form-control"
          id="confirm_password"
          name="confirm_password"
          placeholder="Repeat your password"
          required
          minlength="6"
          aria-describedby="confirmPasswordError"
          aria-required="true"
        />
        <div class="error-msg" id="confirmPasswordError" aria-live="polite"></div>
      </div>

      <button type="submit" class="btn btn-submit" aria-label="Register">Register</button>
      <a href="login.php" class="login-link">Already have an account? Login here</a>
    </form>
  </div>

  <script>
    const form = document.getElementById('registerForm');
    const errors = {
      username: document.getElementById('usernameError'),
      email: document.getElementById('emailError'),
      password: document.getElementById('passwordError'),
      confirm_password: document.getElementById('confirmPasswordError'),
    };

    form.addEventListener('submit', function(e) {
      let valid = true;
      Object.values(errors).forEach(el => el.textContent = '');

      const username = form.username.value.trim();
      if (username.length < 3) {
        errors.username.textContent = 'Username must be at least 3 characters.';
        valid = false;
      }

      const email = form.email.value.trim();
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(email)) {
        errors.email.textContent = 'Please enter a valid email.';
        valid = false;
      }

      const password = form.password.value;
      if (password.length < 6) {
        errors.password.textContent = 'Password must be at least 6 characters.';
        valid = false;
      }

      const confirmPassword = form.confirm_password.value;
      if (password !== confirmPassword) {
        errors.confirm_password.textContent = 'Passwords do not match.';
        valid = false;
      }

      if (!valid) {
        e.preventDefault();
      }
    });
  </script>
</body>
</html>
