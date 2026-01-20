.<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config.php';
$conn = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">.
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password - Wish2Padel</title>
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

.login-container:hover {
  box-shadow: 0 0 30px #FFC107;
}

.logo {
  display: block;
  margin: 0 auto 25px auto;
  width: 140px;
  color: black;
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

.register-link {
  display: block;
  margin-top: 22px;
  text-align: center;
  color: #00796B;
  text-decoration: none;
  font-weight: 600;
  letter-spacing: 0.05em;
}

.register-link:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .login-container {
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
    padding: 10px;
  }
}

@media (max-width: 480px) {
  .login-container {
    padding: 25px 20px;
    margin: 3vh auto 30px;
  }
  .form-label {
    font-size: 0.9rem;
  }
  .btn-submit {
    font-size: 1rem;
    padding: 8px;
  }
}
  </style>
</head>
<body>
<?php

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $captain_team = trim($_POST['captain_team']);
    $captain_email = trim($_POST['captain_email']);
    $new_password = trim($_POST['new_password']);

    if (!$username || !$captain_team || !$captain_email || !$new_password) {
        $message = "All fields are required.";
    } else {
        // Ambil user dari team_account
        $stmt = $conn->prepare("SELECT id FROM team_account WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $account = $result->fetch_assoc();
            $account_id = $account['id'];

            // Cek di team_info apakah captain_name dan captain_email sesuai
            $stmt2 = $conn->prepare("SELECT id FROM team_info WHERE id = ? AND captain_name = ? AND captain_email = ?");
            $stmt2->bind_param('iss', $account_id, $captain_team, $captain_email);
            $stmt2->execute();
            $result2 = $stmt2->get_result();

            if ($result2->num_rows === 1) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE team_account SET password_hash = ? WHERE id = ?");
                $updateStmt->bind_param('si', $hashed_password, $account_id);
                if ($updateStmt->execute()) {
                    $_SESSION['success_message'] = "Password updated successfully. Please login.";
                    header('Location: login');
                    exit;
                } else {
                    $message = "Failed to update password. Try again.";
                }
            } else {
                $message = "Invalid combination of username, team captain, or email.";
            }
        } else {
            $message = "Username not found.";
        }
    }
}

?>

<div class="login-container shadow-sm" role="main" aria-label="Forgot password form">
    <div class="logo">
      <img src="https://www.wish2padel.com/assets/image/w2p.png" 
           alt="Wish2Padel Logo - Arab Federation Beginner Padel League" 
           height="150">
    </div>


    <?php if($message): ?>
        <div class="error-msg" tabindex="0"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form id="forgotForm" method="POST" novalidate>
        <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <i class="bi bi-person-fill input-icon"></i>
            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
        </div>

        <div class="form-group">
            <label for="captain_team" class="form-label">Team Captain Name</label>
            <i class="bi bi-person-badge-fill input-icon"></i>
            <input type="text" class="form-control" id="captain_team" name="captain_team" placeholder="Enter captain name" required>
        </div>

        <div class="form-group">
            <label for="captain_email" class="form-label">Team Captain Email</label>
            <i class="bi bi-envelope-fill input-icon"></i>
            <input type="email" class="form-control" id="captain_email" name="captain_email" placeholder="Enter captain email" required>
        </div>

        <div class="form-group">
            <label for="new_password" class="form-label">New Password</label>
            <i class="bi bi-lock-fill input-icon"></i>
            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password" required>
        </div>

        <button type="submit" class="btn btn-submit" aria-label="Reset Password">Reset Password</button>
    </form>

    <script>
        const form = document.getElementById('forgotForm');
        form.addEventListener('submit', function(e) {
            const username = form.username.value.trim();
            const captain = form.captain_team.value.trim();
            const email = form.captain_email.value.trim();
            const password = form.new_password.value.trim();
            if(!username || !captain || !email || !password) {
                e.preventDefault();
                alert('All fields are required.');
            }
        });
    </script>
    <script>
(async function autoTranslatePage() {
  const lang = localStorage.getItem("lang") || "en";
  if (lang !== "ar") return;

  // Set RTL layout
  document.documentElement.setAttribute("dir", "rtl");
  document.body.style.textAlign = "right";
  // ✅ Lock logo area agar tidak kena RTL & Translasi
const brand = document.querySelector(".navbar-brand");
if (brand) {
  brand.setAttribute("data-no-translate", "true");
  brand.style.direction = "ltr";
  brand.style.textAlign = "left";
}


  // Fixed translation for specific words (agar tidak ngawur)
  const customMap = {
    "League": "دوري",
    "LEAGUE": "دوري",
    "league": "دوري",
    "Leagues": "الدوريات",
    "Regist Team": "تسجيل الفريق",
    "Sponsors": "الرعاة",
    "Media": "وسائل الإعلام",
    "News": "الأخبار",
    "Club": "النادي"
  };

  // Ambil semua text node secara agresif tapi tetap aman
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
  const nodes = [];

  while (walker.nextNode()) {
    const node = walker.currentNode;
    const text = node.nodeValue.trim();

    if (!text) continue; // Skip kosong
    if (/^[\d\s\W]+$/.test(text)) continue; // Skip angka/simbol

    const parentTag = node.parentNode?.nodeName.toLowerCase();

    // ❌ Jangan translate teks dalam logo/icon
    if (["img", "svg", "script", "style"].includes(parentTag)) continue;

    nodes.push(node);
  }

  for (const node of nodes) {
    let original = node.nodeValue.trim();

    // Skip jika sudah ada huruf Arab (tidak re-translate)
    if (/[\u0600-\u06FF]/.test(original)) continue;

    if (customMap[original]) {
      node.nodeValue = customMap[original];
      continue;
    }

    try {
      const res = await fetch("/proxy.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "text=" + encodeURIComponent(original)
      });

      const data = await res.json();
      if (data?.translatedText) {
        node.nodeValue = data.translatedText;
      }
    } catch (e) {
      console.warn("Translate failed for:", original);
    }
  }
})();
</script>
</div>

</body>
</html>
