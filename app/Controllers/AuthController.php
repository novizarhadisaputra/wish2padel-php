<?php

namespace App\Controllers;

use App\Core\SimplePaymentSystem;

class AuthController
{
    public function logout()
    {
        session_destroy();
        redirect('/login');
    }

    public function login()
    {
        if (isset($_SESSION['user_id']) || isset($_SESSION['team_id'])) {
            redirect('/');
        }
        view('auth.login');
    }

    public function postLogin()
    {
        // Login logic migrated from login_process.php
        $conn = getDBConnection(); // Defined in config.php

        $identifier = trim($_POST['login_identifier'] ?? '');
        $password   = $_POST['login_password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $_SESSION['error_message'] = "Please fill in all fields.";
            redirect('/login');
        }

        $login_success = false;

        // 1. Check Users (Admin)
        $stmt = $conn->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                
                redirect($row['role'] === 'admin' ? '/admin/dashboard' : '/');
                return;
            } else {
                $_SESSION['error_message'] = "Incorrect password.";
            }
        }
        $stmt->close();

        // 2. Check Team Account (Simplified for now - full logic needs porting)
         $stmt = $conn->prepare("SELECT team_id, username, password_hash FROM team_account WHERE username = ? LIMIT 1");
         $stmt->bind_param("s", $identifier);
         $stmt->execute();
         $res = $stmt->get_result();
         if ($row = $res->fetch_assoc()) {
             if (password_verify($password, $row['password_hash'])) {
                 session_regenerate_id(true);
                 $_SESSION['team_id'] = $row['team_id'];
                 $_SESSION['username'] = $row['username'];
                 redirect('/dashboard'); // Assuming dashboard route
                 return;
             }
         }
        
        $_SESSION['error_message'] = "Login failed or User not found.";
        redirect('/login');
    }
}
