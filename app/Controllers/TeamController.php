<?php

namespace App\Controllers;

class TeamController
{
    public function profile()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        $team_id = $_GET['id'] ?? 1;

        // Ambil data tim sekaligus tournament_id
        $team_sql = "
            SELECT ti.id, ti.team_name, ti.captain_name, ti.logo, ti.created_at, ti.tournament_id, t.name AS tournament_name
            FROM team_info ti
            LEFT JOIN tournaments t ON ti.tournament_id = t.id
            WHERE ti.id = ?
        ";
        $stmt = $conn->prepare($team_sql);
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $team = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$team) {
            echo "<div class='alert alert-danger'>Team not found.</div>";
            return;
        }

        $tournament_name = $team['tournament_name'] ?? '-';

        // Ambil contact details
        $contact_sql = "
            SELECT 
                tcd.club, tcd.city, tcd.division, tcd.notes,
                d.division_name
            FROM team_contact_details tcd
            LEFT JOIN divisions d ON tcd.division = d.id
            WHERE tcd.team_id = ?";

        $stmt = $conn->prepare($contact_sql);
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $contact = $stmt->get_result()->fetch_assoc();

        // Members
        $sql_members = "SELECT player_name, age, profile, role, position 
                        FROM team_members_info 
                        WHERE team_id = ? 
                        ORDER BY (role = 'Captain') DESC, player_name ASC";
        $stmt = $conn->prepare($sql_members);
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $res_members = $stmt->get_result();
        $final_members = $res_members ? $res_members->fetch_all(MYSQLI_ASSOC) : [];

        // Match History
        $sql_schedule = "
            SELECT 
                m.id, m.journey, m.scheduled_date, m.status,
                t1.team_name AS team1, t1.logo AS team1_logo, m.team1_id,
                t2.team_name AS team2, t2.logo AS team2_logo, m.team2_id
            FROM matches m
            LEFT JOIN team_info t1 ON m.team1_id = t1.id
            LEFT JOIN team_info t2 ON m.team2_id = t2.id
            WHERE m.team1_id = ? OR m.team2_id = ?
            ORDER BY m.scheduled_date ASC
        ";
        $stmt = $conn->prepare($sql_schedule);
        $stmt->bind_param("ii", $team_id, $team_id);
        $stmt->execute();
        $resSchedule = $stmt->get_result();

        view('team.profile', compact('team', 'contact', 'final_members', 'resSchedule'));
    }

    public function myTeam()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        $current_page = 'my_team'; // Mapping identifier
        
        if (!isset($_SESSION['username'])) {
            header("Location: " . asset('login'));
            exit;
        }

        $team_id = $_SESSION['team_id'] ?? null;
        if (!$team_id) {
            // Ideally flash message here
             header("Location: " . asset('login'));
             exit;
        }
        
        // --- Logic from myteam.php ---
        
        // 1. Fetch Team Info
        $stmt = $conn->prepare("SELECT * FROM team_info WHERE id = ?");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $team_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // 2. Fetch Contact Details
        $stmt = $conn->prepare("SELECT * FROM team_contact_details WHERE team_id = ?");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $team_contact = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // 3. Fetch Members Info
        $stmt = $conn->prepare("SELECT * FROM team_members_info WHERE team_id = ?");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $team_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // 4. Determine Active Windows
        $now = date("Y-m-d H:i:s");
        
        $team_stmt = $conn->prepare("
            SELECT ti.*, 
                   t.id AS tournament_id, t.name AS tournament_name, 
                   t.start_date, t.end_date, t.status AS tournament_status,
                   l.date AS league_year
            FROM team_info ti 
            JOIN tournaments t ON t.id = ti.tournament_id 
            JOIN league l ON l.id = t.id_league
            WHERE ti.id = ?
        ");
        $team_stmt->bind_param("i", $team_id);
        $team_stmt->execute();
        $team = $team_stmt->get_result()->fetch_assoc();
        $team_stmt->close();
        
        $leagueYear = $team['league_year'] ?? date('Y');
        $tournament = [
            'id'         => $team['tournament_id'] ?? null,
            'status'     => $team['tournament_status'] ?? null,
            'name'       => $team['tournament_name'] ?? null,
            'start_date' => $team['start_date'] ?? null,
            'league_year'=> $leagueYear,
        ];
        
        $seasonYear = (int)$leagueYear;
        $activeWindow = null;
        $activeWindowLabel = null;
        
        $stmt = $conn->prepare("
            SELECT id, start_date, end_date
            FROM transfer_windows
            WHERE (YEAR(start_date) = ? OR YEAR(end_date) = ?)
            ORDER BY start_date ASC
            LIMIT 2
        ");
        $stmt->bind_param("ii", $seasonYear, $seasonYear);
        $stmt->execute();
        $res = $stmt->get_result();
        $windows = [];
        while ($row = $res->fetch_assoc()) { $windows[] = $row; }
        $stmt->close();
        
        foreach ($windows as $idx => $w) {
            if ($w['start_date'] <= $now && $w['end_date'] >= $now) {
                $activeWindow = $w;
                $activeWindowLabel = ($idx === 0) ? "First Transfer Window" : "Second Transfer Window";
                break;
            }
        }
        $canEditMembers = ($activeWindow !== null);

        // --- Handle POST Requests (Logic extracted) ---

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Update Member
            if (isset($_POST['update_member'])) {
                 $member_id    = (int)$_POST['member_id'];
                 $age          = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
                 $position     = $_POST['position'] ?? null;
                 $player_name  = $_POST['player_name'] ?? null;
                 $profile      = null;
         
                 if (!empty($_FILES['profile']['name'])) {
                     $targetDir = __DIR__ . "/../../public/uploads/profile/"; // Adjusted path for Controller
                     if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
             
                     $ext        = pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION);
                     $fileName   = "profile_" . time() . "_" . $member_id . "." . $ext;
                     $targetFile = $targetDir . $fileName;
             
                     if (move_uploaded_file($_FILES['profile']['tmp_name'], $targetFile)) {
                         $profile = $fileName;
                     }
                 }
         
                 $stmtOld = $conn->prepare("SELECT player_name FROM team_members_info WHERE id = ?");
                 $stmtOld->bind_param("i", $member_id);
                 $stmtOld->execute();
                 $old_name = $stmtOld->get_result()->fetch_assoc()['player_name'] ?? null;
                 $stmtOld->close();
         
                 if ($profile) {
                     $stmt = $conn->prepare("UPDATE team_members_info SET age = ?, player_name = ?, position = ?, profile = ? WHERE id = ?");
                     $stmt->bind_param("isssi", $age, $player_name, $position, $profile, $member_id);
                 } else {
                     $stmt = $conn->prepare("UPDATE team_members_info SET age = ?, player_name = ?, position = ? WHERE id = ?");
                     $stmt->bind_param("issi", $age, $player_name, $position, $member_id);
                 }
                 $stmt->execute();
                 $stmt->close();
         
                 if ($old_name !== null && $player_name !== null && $old_name !== $player_name) {
                     $stmtUpdatePair = $conn->prepare("UPDATE pair_players SET player_name = ? WHERE player_name = ?");
                     $stmtUpdatePair->bind_param("ss", $player_name, $old_name);
                     $stmtUpdatePair->execute();
                     $stmtUpdatePair->close();
                 }
                 
                 header("Location: " . asset('myteam'));
                 exit;
            }
            
            // Update Team Info
            if (isset($_POST['update_team'])) {
                 $team_name     = $_POST['team_name'];
                 $captain_name  = $_POST['captain_name'];
                 $captain_phone = $_POST['captain_phone'];
                 $captain_email = $_POST['captain_email'];
             
                 $stmt = $conn->prepare("UPDATE team_info SET team_name=?, captain_name=?, captain_phone=?, captain_email=? WHERE id=?");
                 $stmt->bind_param("ssssi", $team_name, $captain_name, $captain_phone, $captain_email, $team_id);
                 $stmt->execute();
                 $stmt->close();
             
                 if (isset($_FILES['team_logo']) && !empty($_FILES['team_logo']['tmp_name'])) {
                     $file = $_FILES['team_logo'];
                     $upload_dir = __DIR__ . '/../../public/uploads/logo/'; // Adjusted path
                     if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
             
                     $filename = basename($file['name']);
                     $target_file = $upload_dir . $filename;
             
                     if (move_uploaded_file($file['tmp_name'], $target_file)) {
                         $stmt = $conn->prepare("UPDATE team_info SET logo=? WHERE id=?");
                         $stmt->bind_param("si", $filename, $team_id);
                         $stmt->execute();
                         $stmt->close();
                     }
                 }
                 header("Location: " . asset('myteam'));
                 exit;
            }
            
            // Update Contact
            if(isset($_POST['update_contact'])) {
                 $contact_phone = $_POST['contact_phone'];
                 $contact_email = $_POST['contact_email'];
                 $club          = $_POST['club'];
                 $city          = $_POST['city'];
                 $notes         = $_POST['notes'];
             
                 $stmt = $conn->prepare("UPDATE team_contact_details SET contact_phone=?, contact_email=?, club=?, city=?, notes=? WHERE team_id=?");
                 $stmt->bind_param("sssssi", $contact_phone, $contact_email, $club, $city, $notes, $team_id);
                 $stmt->execute();
                 $stmt->close();
                 header("Location: " . asset('myteam'));
                 exit;
            }
            
            // Add Member
            if(isset($_POST['add_member']) && $canEditMembers) {
                 $name     = trim($_POST['player_name']);
                 $age      = !empty($_POST['age']) ? (int)$_POST['age'] : null;
                 $position = $_POST['position'] ?? null;
                 $role     = 'Player';
                 $profile  = null;
             
                 if ($name !== '') {
                     if (!empty($_FILES['profile']['name'])) {
                         $targetDir = __DIR__ . "/../../public/uploads/profile/";
                         if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                 
                         $ext        = pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION);
                         $fileName   = "profile_" . time() . "_" . $team_id . "." . $ext;
                         $targetFile = $targetDir . $fileName;
                 
                         if (move_uploaded_file($_FILES['profile']['tmp_name'], $targetFile)) {
                             $profile = $fileName;
                         }
                     }
             
                     if ($profile) {
                         $stmt = $conn->prepare("INSERT INTO team_members_info (team_id, player_name, age, position, role, profile, joined_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                         $stmt->bind_param("isisss", $team_id, $name, $age, $position, $role, $profile);
                     } else {
                         $stmt = $conn->prepare("INSERT INTO team_members_info (team_id, player_name, age, position, role, joined_at) VALUES (?, ?, ?, ?, ?, NOW())");
                         $stmt->bind_param("isiss", $team_id, $name, $age, $position, $role);
                     }
                     $stmt->execute();
                     $stmt->close();
                 }
                 header("Location: " . asset('myteam'));
                 exit;
            }
            
            // Delete Member
            if(isset($_POST['delete_member']) && $canEditMembers) {
                $member_id = (int)$_POST['member_id'];
                $stmt = $conn->prepare("DELETE FROM team_members_info WHERE id = ? AND team_id = ?");
                $stmt->bind_param("ii", $member_id, $team_id);
                $stmt->execute();
                $stmt->close();
                header("Location: " . asset('myteam'));
                exit;
            }

        }

        view('team.my_team', compact('team_info', 'team_contact', 'team_members', 'activeWindow', 'activeWindowLabel', 'tournament', 'canEditMembers'));
    }
    public function scheduled()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        $current_page = 'scheduled'; 
        
        $team_id = $_SESSION['team_id'] ?? null;
        if (!$team_id) {
             header("Location: " . asset('login'));
             exit;
        }
        
        $players = [];
        $res1 = $conn->query("SELECT username AS name FROM team_account WHERE team_id = $team_id");
        while($r = $res1->fetch_assoc()) $players[] = $r['name'];
        
        $res2 = $conn->query("SELECT player_name AS name FROM team_members_info WHERE team_id = $team_id");
        while($r = $res2->fetch_assoc()) $players[] = $r['name'];
        
        // Ambil semua match untuk team ini (baik scheduled maupun completed)
        $matches_res = $conn->query("
            SELECT 
                m.id, m.journey, m.status, m.scheduled_date,
                m.team1_id, m.team2_id,
                t1.team_name AS team1_name, t1.logo AS team1_logo,
                t2.team_name AS team2_name, t2.logo AS team2_logo,
                tour.name AS tournament_name
            FROM matches m
            LEFT JOIN team_info t1 ON m.team1_id = t1.id
            LEFT JOIN team_info t2 ON m.team2_id = t2.id
            LEFT JOIN tournaments tour ON m.tournament_id = tour.id
            WHERE m.team1_id = $team_id OR m.team2_id = $team_id
            ORDER BY m.scheduled_date ASC
        ");

        view('team.scheduled', compact('matches_res', 'players', 'team_id', 'conn'));
    }
}
