<?php
session_start();
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

    $login_success = false;

    // ===========================================
    // 1️⃣ LOGIN ADMIN / USER
    // ===========================================
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
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role']; // ✅ role admin atau user

            $login_success = true;

            if ($row['role'] === 'admin') {
                header("Location: ../admin/dashboard");
            } else {
                header("Location: ../index");
            }
            exit();
        } else {
            $_SESSION['error_message'] = "Incorrect password.";
        }
    }
    $stmt->close();

    // ===========================================
    // 2️⃣ LOGIN CAPTAIN / TEAM ACCOUNT
    // ===========================================
    if (!$login_success) {
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
                $team_id = (int)$row['team_id'];

                // ---- Ambil tournament_id
                $t_stmt = $conn->prepare("SELECT tournament_id FROM team_info WHERE id = ?");
                $t_stmt->bind_param("i", $team_id);
                $t_stmt->execute();
                $tournament_id = $t_stmt->get_result()->fetch_assoc()['tournament_id'] ?? 1;
                $t_stmt->close();

                // ---- Ambil division
                $div_stmt = $conn->prepare("SELECT division FROM team_contact_details WHERE team_id = ? LIMIT 1");
                $div_stmt->bind_param("i", $team_id);
                $div_stmt->execute();
                $division_data = $div_stmt->get_result()->fetch_assoc();
                $division = $division_data['division'] ?? null;
                $div_stmt->close();

                // ---- Cek pembayaran
                $paymentSystem = new SimplePaymentSystem();
                $is_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);

                // ❌ Belum bayar → gagal login
                if (!$is_paid) {
                    $_SESSION['error_message'] = "Please complete your payment before logging in.";
                    header("Location: login");
                    exit();
                }

                // ✅ Sudah bayar → boleh login
                session_regenerate_id(true);
                $_SESSION['team_id']  = $team_id;
                $_SESSION['username'] = $row['username'];
                $_SESSION['division'] = $division;
                $_SESSION['role']     = 'captain'; // ✅ penting! agar navbar bisa tampil

                $login_success = true;
                header("Location: ../dashboard");
                exit();
            } else {
                $_SESSION['error_message'] = "Incorrect password.";
            }
        } else {
            $_SESSION['error_message'] = "Username not found.";
        }
        $stmt->close();
    }

    // ===========================================
    // 3️⃣ LOGIN CLUB / CENTER
    // ===========================================
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
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['center_id']   = $row['id'];
                $_SESSION['username']    = $row['username'];
                $_SESSION['center_name'] = $row['name'];
                $_SESSION['role']        = 'club'; // ✅ tambahkan role club

                $login_success = true;
                header("Location: ../club/dashboard");
                exit();
            } else {
                $_SESSION['error_message'] = "Incorrect password.";
            }
        }
        $stmt->close();
    }

    // ===========================================
    // 4️⃣ GAGAL LOGIN SEMUA
    // ===========================================
    if (!$login_success) {
        $_SESSION['error_message'] = $_SESSION['error_message'] ?? "Username or email not found.";
        header("Location: login");
        exit();
    }
}

header("Location: login.php");
exit();
