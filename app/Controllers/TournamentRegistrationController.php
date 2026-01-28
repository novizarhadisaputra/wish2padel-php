<?php

namespace App\Controllers;

use App\Core\SimplePaymentSystem;
use Exception;

class TournamentRegistrationController
{
    public function index()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        $tournament_id = isset($_GET['tournament_id']) ? (int) $_GET['tournament_id'] : null;
        $current_step = $_GET['step'] ?? 'registration';

        date_default_timezone_set("Asia/Riyadh");
        $now = date("Y-m-d H:i:s");

        $tournament = null;
        $centers = [];
        $payment_data = null;
        $error = '';
        $success = '';
        $team_already_paid = false;
        $team_payment_info = null;

        // AJAX Check Username
        if (isset($_GET['check_username'])) {
            $usernameCheck = trim($_GET['check_username']);
            $exists = false;
            if ($conn) {
                $stmt = $conn->prepare("SELECT id FROM team_account WHERE username = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $usernameCheck);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $exists = $result && $result->fetch_assoc() ? true : false;
                    $stmt->close();
                }
            }
            echo json_encode(["exists" => $exists]);
            exit;
        }

        // AJAX Check Member Name (from Legacy)
        if (isset($_GET['check_member_name'])) {
            $name = strtolower(trim($_GET['check_member_name']));
            $exists = false;
            if ($conn) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM team_members_info WHERE LOWER(player_name)=?");
                if ($stmt) {
                    $stmt->bind_param("s", $name);
                    $stmt->execute();
                    $count = 0;
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $exists = $count > 0;
                    $stmt->close();
                }
            }
            header('Content-Type: application/json');
            echo json_encode(['exists' => $exists]);
            exit;
        }

        // Check if user is logged in and get team ID
        $team_id = null;
        if ($username && $conn) {
            $stmt = $conn->prepare("SELECT team_id FROM team_account WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                if ($result) {
                    $team_id = $result['team_id'];
                }
                $stmt->close();
            }
        }

        // Check if team has already paid for this tournament
        if ($team_id && $tournament_id) {
            $paymentSystem = new SimplePaymentSystem();
            $team_already_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);

            if ($team_already_paid) {
                $team_payment_info = $paymentSystem->getTeamPaymentInfo($team_id, $tournament_id);
            }
        }

        // Check if we're on payment step
        $show_payment = ($current_step === 'payment' && !empty($_SESSION['temp_registration_data']) && !$team_already_paid);

        if ($tournament_id && $conn) {
            // Get tournament data
            $stmt = $conn->prepare("SELECT id, name, description FROM tournaments WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $tournament_id);
                $stmt->execute();
                $tournament = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }

            // Get centers for the tournament zone
            if ($tournament) {
                $zoneName = $tournament['name'];
                $stmt = $conn->prepare("SELECT id, name FROM centers WHERE zone = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $zoneName);
                    $stmt->execute();
                    $centers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                }
            }
        }

        // If showing payment, prepare data for form
        if ($show_payment) {
            try {
                $temp_data = $_SESSION['temp_registration_data'];

                $payment_data = [
                    'status' => 'success',
                    'team_name' => $temp_data['team_name'],
                    'captain_name' => $temp_data['captain_name'],
                    'amount' => getDynamicPaymentAmount(),
                    'currency' => getDynamicPaymentCurrency()
                ];
            } catch (Exception $e) {
                $error = "Payment initialization error: " . $e->getMessage();
                error_log("Payment initialization error: " . $e->getMessage());
            }
        }

        // Pass existing usernames for JS check
        // Original code didn't query this explicitly for the view, but the JS logic referenced existingTeamNames
        // Wait, the view has:
        // $res = $conn->query("SELECT team_name FROM team_info");
        // So we need to pass this or let the view query it (Controller should do it).

        $teamNames = [];
        $existingUsernames = [];

        if ($conn) {
            $res = $conn->query("SELECT team_name FROM team_info");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $teamNames[] = strtolower(trim($row['team_name']));
                }
            }

            $resU = $conn->query("SELECT username FROM team_account");
            if ($resU) {
                while($row = $resU->fetch_assoc()) {
                     $existingUsernames[] = strtolower(trim($row['username']));
                }
            }
        }

        view('tournament.register', compact(
            'tournament', 'centers', 'payment_data', 'error', 'success',
            'team_already_paid', 'team_payment_info', 'show_payment', 'team_id',
            'tournament_id', 'teamNames', 'existingUsernames', 'conn'
        ));
    }

    public function store()
    {
        $conn = getDBConnection();
        $tournament_id = isset($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : (isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : null);

        date_default_timezone_set("Asia/Riyadh");
        $now = date("Y-m-d H:i:s");

        try {
            // Validate input
            $team_name = trim($_POST['team_name']);
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if (empty($team_name) || empty($username) || empty($password)) {
                throw new Exception("Team name, username, and password are required.");
            }

            if ($conn) {
                // Check if username already exists
                $stmt = $conn->prepare("SELECT id FROM team_account WHERE username = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    if ($stmt->get_result()->fetch_assoc()) {
                        throw new Exception("Username already exists. Please choose a different username.");
                    }
                    $stmt->close();
                }

                // Check if team name already exists
                $stmt = $conn->prepare("SELECT id FROM team_info WHERE team_name = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $team_name);
                    $stmt->execute();
                    if ($stmt->get_result()->fetch_assoc()) {
                        throw new Exception("Team name already exists. Please choose a different team name.");
                    }
                    $stmt->close();
                }
            } else {
                throw new Exception("Database connection unavailable.");
            }

            // Start transaction
            $conn->autocommit(FALSE);

            // Step 1 - team_info
            $stmt = $conn->prepare("INSERT INTO team_info
                (team_name, captain_name, captain_phone, captain_email, tournament_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssisss",
                $team_name,
                $_POST['captain_name'],
                $_POST['captain_phone'],
                $_POST['captain_email'],
                $tournament_id,
                $now
            );
            $stmt->execute();
            $new_team_id = $conn->insert_id;
            $stmt->close();


            // Step 2 - team_members_info
            $stmt = $conn->prepare("INSERT INTO team_members_info
                (team_id, player_name, role)
                VALUES (?, ?, ?)");

            // Insert Captain
            $captain_role = 'captain';
            $stmt->bind_param("iss", $new_team_id, $_POST['captain_name'], $captain_role);
            $stmt->execute();

            // Insert players
            if (isset($_POST['player_name']) && is_array($_POST['player_name'])) {
                foreach ($_POST['player_name'] as $pname) {
                    $trimmed_pname = trim($pname);
                    if (!empty($trimmed_pname)) {
                        $role = 'player';
                        $stmt->bind_param("iss", $new_team_id, $trimmed_pname, $role);
                        $stmt->execute();
                    }
                }
            }

            $stmt->close();


            // Step 3 - team_contact_details
            $stmt = $conn->prepare("INSERT INTO team_contact_details
                (team_id, contact_phone, contact_email, club, city, level, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "issssss",
                $new_team_id,
                $_POST['contact_phone'],
                $_POST['contact_email'],
                $_POST['club'],
                $_POST['city'],
                $_POST['level'],
                $_POST['notes']
            );
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO team_experience
                (team_id, experience, competed, regional)
                VALUES (?, ?, ?, ?)");
            $stmt->bind_param(
                "isss",
                $new_team_id,
                $_POST['experience'],
                $_POST['competed'],
                $_POST['regional']
            );
            $stmt->execute();
            $stmt->close();


            // Step 4 - team_account
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO team_account
                (team_id, username, password_hash, created_at)
                VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $new_team_id, $username, $password_hash, $now);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            // Auto-login
            session_regenerate_id(true);

            $_SESSION['username'] = $username;
            $_SESSION['team_id'] = $new_team_id;
            $_SESSION['payment_status'] = 'unpaid';
            $_SESSION['payment_paid'] = false;

            // Redirect to payment
            // Using asset helper to generate correct URL
            header("Location: " . asset("payment?team_id={$new_team_id}&tournament_id={$tournament_id}&status=new_account"));
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $conn->autocommit(TRUE);

            $error_context = [
                'error_message' => $e->getMessage(),
                'tournament_id' => $tournament_id,
                'team_name' => $_POST['team_name'] ?? 'N/A',
                'username' => $_POST['username'] ?? 'N/A',
                'timestamp' => date('Y-m-d H:i:s')
            ];

            error_log("Registration error: " . json_encode($error_context));

            // Return error to view (we can re-render index with error)
            // Or simplified: output error script/HTML.
            // Better to re-render.

            // To re-render, we need to gather all data again.
            // Simplified: call index with error message?
            // The logic in index() initializes vars. We can pass error.
            // But how to pass it?
            // Hack: echo script as original code did.
            echo "<script>";
            echo "alert('Registration Error: " . addslashes($e->getMessage()) . "');";
            echo "window.history.back();"; // Simple fallback
            echo "</script>";
            exit;
        }
    }
}
