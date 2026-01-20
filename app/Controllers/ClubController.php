<?php

namespace App\Controllers;

class ClubController
{
    public function index()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        
        $query = "
        SELECT c.*, COALESCE(SUM(p.quantity),0) AS total_pistas
        FROM centers c
        LEFT JOIN pistas p ON c.id = p.center_id
        GROUP BY c.id
        ";
        $centers = $conn->query($query);
        
        $cities = $conn->query("SELECT DISTINCT city FROM centers ORDER BY city ASC");
        
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
        
        $stmt = $conn->prepare("SELECT * FROM centers WHERE id = ?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $center = $stmt->get_result()->fetch_assoc();
        
        $pistas = $conn->query("SELECT * FROM pistas WHERE center_id = $center_id");
        $schedules = $conn->query("SELECT * FROM schedules WHERE center_id = $center_id");
        $photos = $conn->query("SELECT * FROM photos WHERE center_id = $center_id");
        
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
            $created_at = date('Y-m-d H:i:s');
        
            if (empty($name)) $errors[] = "Name is required.";
            if (empty($username)) $errors[] = "Username is required.";
            if (empty($email)) $errors[] = "Email is required.";
            if (empty($phone)) $errors[] = "Phone is required.";
            if (empty($password)) $errors[] = "Password is required.";
        
            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
                $stmt = $conn->prepare("
                    INSERT INTO centers
                    (name, username, password, street, postal_code, city, zone, phone, email, website, description, logo_url, created_at, updated_at)
                    VALUES (?, ?, ?, NULL, NULL, NULL, NULL, ?, ?, NULL, NULL, NULL, ?, NULL)
                ");
                
                $stmt->bind_param("ssssss", $name, $username, $hashedPassword, $phone, $email, $created_at);
        
                if ($stmt->execute()) {
                    // Redirect to login (assuming we have a route or using legacy for now if not migrated)
                    // The legacy code redirected to login/login.php. 
                    // We should verify if we have a login route. Yes, /login.
                    header("Location: " . asset('login'));
                    exit;
                } else {
                    $errors[] = "Database error: " . $conn->error;
                }
            }
        }

        view('club.register', compact('errors'));
    }
}
