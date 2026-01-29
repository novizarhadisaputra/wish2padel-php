<?php

namespace App\Controllers;

use App\Core\SimplePaymentSystem;

class AuthController
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? getDBConnection();
    }

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
        $conn = $this->db;
        if (!$conn) {
            $_SESSION['error_message'] = "Database connection unavailable.";
            redirect('/login');
        }

        $identifier = trim($_POST['login_identifier'] ?? '');
        $password   = $_POST['login_password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $_SESSION['error_message'] = "Please fill in all fields.";
            redirect('/login');
        }

        $login_success = false;

        // 1. Check Users (Admin)
        $stmt = $conn->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($row = $res->fetch_assoc()) {
                if (password_verify($password, $row['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    $stmt->close();
                    redirect($row['role'] === 'admin' ? '/admin/dashboard' : '/');
                    return;
                } else {
                    $_SESSION['error_message'] = "Incorrect password.";
                }
            }
            $stmt->close();
        }

        // 2. Check Team Account (Simplified for now - full logic needs porting)
         $stmt = $conn->prepare("SELECT team_id, username, password_hash FROM team_account WHERE username = ? LIMIT 1");
         if ($stmt) {
             $stmt->bind_param("s", $identifier);
             $stmt->execute();
             $res = $stmt->get_result();
             if ($row = $res->fetch_assoc()) {
                 if (password_verify($password, $row['password_hash'])) {
                     session_regenerate_id(true);
                     $_SESSION['team_id'] = $row['team_id'];
                     $_SESSION['username'] = $row['username'];
                     $stmt->close();
                     redirect('/dashboard'); // Assuming dashboard route
                     return;
                 } else {
                     $_SESSION['error_message'] = "Incorrect password.";
                 }
             }
             $stmt->close();
         }
        
        if (!isset($_SESSION['error_message'])) {
            $_SESSION['error_message'] = "Login failed or User not found.";
        }
        redirect('/login');
    }

    public function forgotPassword()
    {
        view('auth.forgot_password');
    }

    public function postForgotPassword()
    {
        $conn = $this->db;
        $username = trim($_POST['username'] ?? '');
        $captain_team = trim($_POST['captain_team'] ?? '');
        $captain_email = trim($_POST['captain_email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';

        // Validation
        if (empty($username)) {
            $_SESSION['error_message'] = "Username is required.";
            redirect('/forgot-password');
        }

        // --- ADMIN FLOW (Token Based) ---
        // If only username/email provided and no captain details, assume Admin check
        if (empty($captain_team) && empty($captain_email)) {
             // For now, simpler error if they don't provide everything, 
             // but strictly speaking legacy flow requires ALL fields.
             // We will stick to legacy flow strictly for now as per user request.
             $_SESSION['error_message'] = "All fields are required for Team recovery. For Admin recovery, please contact support.";
             redirect('/forgot-password');
        }
        
        if (empty($new_password)) {
             $_SESSION['error_message'] = "New password is required.";
             redirect('/forgot-password');
        }

        // --- TEAM FLOW (Legacy logic) ---
        // 1. Check Team Account
        $stmt = $conn->prepare("SELECT id, team_id FROM team_account WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($row = $res->fetch_assoc()) {
                $account_id = $row['id'];
                $team_id = $row['team_id']; // This is the actual link to team_info usually

                // Legacy code used 'id' = 'id'. We will try to be smart.
                // We will check against team_info using the team_id FK if possible, or just ID if legacy was ID-based.
                // Let's assume legacy was correct and checks ID. But we also have team_id.
                // Best bet: Check `team_info` where `id` = $team_id (Foreign Key logic)
                
                $stmt2 = $conn->prepare("SELECT id FROM team_info WHERE id = ? AND captain_name = ? AND captain_email = ?");
                // Use $team_id here because normally team_account.team_id -> team_info.id
                $stmt2->bind_param("iss", $team_id, $captain_team, $captain_email); 
                $stmt2->execute();
                $res2 = $stmt2->get_result();

                if ($res2->num_rows === 1) {
                    // Verified! Update Password.
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE team_account SET password_hash = ? WHERE id = ?");
                    $upd->bind_param("si", $hashed, $account_id);
                    if ($upd->execute()) {
                        $_SESSION['success_message'] = "Password updated successfully. Please login.";
                        redirect('/login');
                        return;
                    } else {
                        $_SESSION['error_message'] = "Failed to update password.";
                    }
                } else {
                    $_SESSION['error_message'] = "Invalid Captain Name or Email for this Team.";
                }
            } else {
                $_SESSION['error_message'] = "Username not found.";
            }
        }
        redirect('/forgot-password');
    }
}
