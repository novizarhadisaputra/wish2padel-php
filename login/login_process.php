<?php
require_once '../config.php';
require_once '../SimplePaymentSystem.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();

    $identifier = trim($_POST['login_identifier'] ?? '');
    $password   = $_POST['login_password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $_SESSION['error_message'] = "Please fill in all fields.";
        header("Location: login");
        exit();
    }

    $login_success = false; // flag untuk menentukan apakah login berhasil

    // ===== 1️⃣ Cek di tabel users (admin) =====
    $stmt = $conn->prepare("
        SELECT id, username, email, password_hash, role
        FROM users
        WHERE username = ? OR email = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            $login_success = true;
            header("Location: " . ($row['role'] === 'admin' ? "../admin/dashboard" : "../index"));
            exit();
        } else {
            $_SESSION['error_message'] = "Incorrect password.";
            $login_success = false;
        }
    }
    $stmt->close();

    if (!$login_success) {

    // Cek apakah akun tim ada
    $stmt = $conn->prepare("
        SELECT team_id, username, password_hash
        FROM team_account
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $team_id = $row['team_id'];

            session_regenerate_id(true);
            $_SESSION['team_id'] = $team_id;
            $_SESSION['username'] = $row['username'];

            // ----- CEK MATCH BERDASARKAN WAKTU -----
            date_default_timezone_set('Asia/Riyadh');
            $now = new DateTime();
            $today = $now->format('Y-m-d');
            
            // Ambil match terdekat yg >= hari ini (bukan match lampau)
            $matchStmt = $conn->prepare("
                SELECT id, scheduled_date
                FROM matches
                WHERE (team1_id = ? OR team2_id = ?)
                  AND DATE(scheduled_date) >= ?
                ORDER BY scheduled_date ASC
                LIMIT 1
            ");
            $matchStmt->bind_param("iis", $team_id, $team_id, $today);
            $matchStmt->execute();
            $matchResult = $matchStmt->get_result();


            // Ambil SEMUA match aktif (bukan cuma 1)
$matchStmt = $conn->prepare("
    SELECT id, scheduled_date
    FROM matches
    WHERE (team1_id = ? OR team2_id = ?)
      AND DATE(scheduled_date) >= ?
    ORDER BY scheduled_date ASC
");
$matchStmt->bind_param("iis", $team_id, $team_id, $today);
$matchStmt->execute();
$matchResult = $matchStmt->get_result();

$foundAction = false;

while ($match = $matchResult->fetch_assoc()) {
    $match_id = $match['id'];
    $matchTime = new DateTime($match['scheduled_date']);

    $startWindow  = (clone $matchTime)->modify('-20 minutes');
    $midWindow    = (clone $matchTime)->modify('+40 minutes');
    $resultWindow = (clone $matchTime)->modify('+90 minutes');

    // --- CEK WAKTU LINEUP ---
    if ($now >= $startWindow && $now <= $midWindow) {
        $pairCheck = $conn->prepare("
            SELECT COUNT(*) AS total_players 
            FROM pair_players
            WHERE pair_id IN (
                SELECT id FROM team_pairs WHERE match_id = ? AND team_id = ?
            )
        ");
        $pairCheck->bind_param("ii", $match_id, $team_id);
        $pairCheck->execute();
        $pairCount = $pairCheck->get_result()->fetch_assoc()['total_players'];
        $pairCheck->close();

        if ($pairCount == 0) {
            $_SESSION['current_match_id'] = $match_id;
            header("Location: ../auth/auth_scheduled");
            $foundAction = true;
            break; // stop di match aktif pertama yang belum diisi
        }
    }

    // --- CEK WAKTU RESULT ---
    if ($now >= $resultWindow) {
        $resStmt = $conn->prepare("
            SELECT status
            FROM match_results
            WHERE match_id = ? AND team_id = ?
        ");
        $resStmt->bind_param("ii", $match_id, $team_id);
        $resStmt->execute();
        $res = $resStmt->get_result()->fetch_assoc();
        $resStmt->close();

        if (!$res || strtolower($res['status']) !== 'accept') {
            $_SESSION['current_match_id'] = $match_id;
            header("Location: ../auth/auth_result");
            $foundAction = true;
            break; // stop di match aktif pertama yang butuh result
        }
    }
}

$matchStmt->close();

// Kalau tidak ada match aktif → lanjut ke dashboard / cek pembayaran
if (!$foundAction) {

    // ----- CEK PEMBAYARAN -----
    $t_stmt = $conn->prepare("SELECT tournament_id FROM team_info WHERE id = ?");
    $t_stmt->bind_param("i", $team_id);
    $t_stmt->execute();
    $tournament_id = $t_stmt->get_result()->fetch_assoc()['tournament_id'] ?? 1;
    $t_stmt->close();
    
    $paymentSystem = new SimplePaymentSystem();
    $is_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);

    if ($is_paid) {
        header("Location: ../dashboard");
    } else {
        header("Location: ../regis");
    }
    exit();
}


            $matchStmt->close();

            // ----- CEK PEMBAYARAN -----
            $t_stmt = $conn->prepare("SELECT tournament_id FROM team_info WHERE id = ?");
            $t_stmt->bind_param("i", $team_id);
            $t_stmt->execute();
            $tournament_id = $t_stmt->get_result()->fetch_assoc()['tournament_id'] ?? 1;
            $t_stmt->close();
            
            // Cek pembayaran sesuai turnamen-nya
            $paymentSystem = new SimplePaymentSystem();
            $is_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);
            $payment_info = $paymentSystem->getTeamPaymentInfo($team_id, $tournament_id);

            $_SESSION['payment_status'] = $payment_info['status'] ?? 'unknown';
            $_SESSION['payment_paid'] = $is_paid ?? false;

            $login_success = true;

            if ($is_paid) {
                header("Location: ../dashboard");
            } else {
                header("Location: ../regis");
            }
            exit();

        } else {
            $_SESSION['error_message'] = "Incorrect password.";
        }
    } else {
        $_SESSION['error_message'] = "Username not found.";
    }

    $stmt->close();
}




    // ===== 3️⃣ Cek di tabel centers =====
    if (!$login_success) {
        $stmt = $conn->prepare("
            SELECT id, username, name, password
            FROM centers
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) { // gunakan password_verify karena hashed
                session_regenerate_id(true);
                $_SESSION['center_id']   = $row['id'];
                $_SESSION['username']    = $row['username'];
                $_SESSION['center_name'] = $row['name'];

                $login_success = true;
                header("Location: ../club/dashboard");
                exit();
            } else {
                $_SESSION['error_message'] = "Incorrect password.";
            }
        }
        $stmt->close();
    }

    // ===== 4️⃣ Kalau tidak ketemu di semua tabel =====
    if (!$login_success) {
        $_SESSION['error_message'] = $_SESSION['error_message'] ?? "Username or email not found.";
        header("Location: login");
        exit();
    }
}

header("Location: login.php");
exit();
