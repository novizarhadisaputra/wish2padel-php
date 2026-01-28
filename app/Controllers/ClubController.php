<?php

namespace App\Controllers;

class ClubController
{
    public function index()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        
        $centers = null;
        $cities = null;

        if ($conn) {
            $query = "
            SELECT c.*, COALESCE(SUM(p.quantity),0) AS total_pistas
            FROM centers c
            LEFT JOIN pistas p ON c.id = p.center_id
            GROUP BY c.id
            ";
            $centers = $conn->query($query);
            $cities = $conn->query("SELECT DISTINCT city FROM centers ORDER BY city ASC");
        }
        
        view('club.index', compact('centers', 'cities'));
    }

    public function show()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        $center_id = $_GET['id'] ?? null;
        
        if (!$center_id) {
            echo "<div class='alert alert-danger'>Center ID tidak ditemukan</div>";
            return;
        }
        
        $center = null;
        $pistas = null;
        $schedules = null;
        $photos = null;

        if ($conn && $center_id) {
            $stmt = $conn->prepare("SELECT * FROM centers WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $center_id);
                $stmt->execute();
                $center = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
            
            $pistas = $conn->query("SELECT * FROM pistas WHERE center_id = " . intval($center_id));
            $schedules = $conn->query("SELECT * FROM schedules WHERE center_id = " . intval($center_id));
            $photos = $conn->query("SELECT * FROM photos WHERE center_id = " . intval($center_id));
        }
        
        view('club.show', compact('center', 'pistas', 'schedules', 'photos'));
    }

    public function register()
    {
        $conn = getDBConnection();
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name     = trim($_POST['name']);
            $username = trim($_POST['username']);
            $email    = trim($_POST['email']);
            $phone    = trim($_POST['phone']);
            $password = $_POST['password'];
            $city     = trim($_POST['city'] ?? '');
            $created_at = date('Y-m-d H:i:s');
        
            if (empty($name)) $errors[] = "Name is required.";
            if (empty($username)) $errors[] = "Username is required.";
            if (empty($email)) $errors[] = "Email is required.";
            if (empty($phone)) $errors[] = "Phone is required.";
            if (empty($password)) $errors[] = "Password is required.";
            if (empty($city)) $errors[] = "City is required.";
        
            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
                if ($conn) {
                    $stmt = $conn->prepare("
                        INSERT INTO centers
                        (name, username, password, street, postal_code, city, zone, phone, email, website, description, logo_url, created_at, updated_at)
                        VALUES (?, ?, ?, NULL, NULL, ?, NULL, ?, ?, NULL, NULL, NULL, ?, NULL)
                    ");
                    
                    if ($stmt) {
                        $stmt->bind_param("sssssss", $name, $username, $hashedPassword, $city, $phone, $email, $created_at);
                
                        if ($stmt->execute()) {
                            $stmt->close();
                            header("Location: " . asset('login'));
                            exit;
                        } else {
                            $errors[] = "Database error: " . $conn->error;
                            $stmt->close();
                        }
                    } else {
                        $errors[] = "Failed to prepare database statement.";
                    }
                } else {
                    $errors[] = "Database connection unavailable.";
                }
            }
        }

        view('club.register', compact('errors'));
    }
}
