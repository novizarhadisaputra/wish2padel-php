<?php

namespace App\Controllers;

class PlayerController
{
    public function register()
    {
        $conn = getDBConnection();
        date_default_timezone_set('Asia/Riyadh');

        $centers = $conn->query("SELECT id, name FROM centers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $success_msg = '';
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $full_name = trim($_POST['full_name']);
            $phone     = trim($_POST['phone']);
            $email     = trim($_POST['email']);
            $gender    = $_POST['gender'] ?? '';
            $address   = trim($_POST['address']);
            $center_id = $_POST['center_id'] ?? '';

            if (empty($full_name)) $errors[] = "Full Name is required.";
            if (empty($phone)) $errors[] = "Phone Number is required.";
            if (empty($email)) $errors[] = "Email is required.";
            if (empty($gender)) $errors[] = "Gender is required.";
            if (empty($address)) $errors[] = "Address is required.";
            if (empty($center_id)) $errors[] = "Club selection is required.";

            if (empty($errors)) {
                $created_at = date('Y-m-d H:i:s');

                $stmt = $conn->prepare("
                    INSERT INTO individuals (full_name, phone, email, gender, address, center_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssssis", $full_name, $phone, $email, $gender, $address, $center_id, $created_at);

                if ($stmt->execute()) {
                    $success_msg = 'Your registration was successful. The club will contact you if they form a team.';
                    // Clear POST data to avoid repopulating form
                    $_POST = [];
                } else {
                    $errors[] = 'Database error: ' . $conn->error;
                }
            }
        }

        view('player.register', compact('centers', 'success_msg', 'errors'));
    }
}
