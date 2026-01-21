<?php

namespace App\Controllers\Club;

class ClubDashboardController
{
    public function index()
    {
        $this->ensureClub();
        $conn = getDBConnection();
        $center_id = $_SESSION['center_id'];

        $stmt = $conn->prepare("SELECT * FROM centers WHERE id = ?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $center = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$center) die("Center not found.");

        // Prepared statements for other queries
        $stmt = $conn->prepare("SELECT * FROM pistas WHERE center_id = ?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $pistas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM schedules WHERE center_id = ?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM photos WHERE center_id = ?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        view('club.dashboard', compact('center', 'pistas', 'schedules', 'photos'));
    }

    public function teams()
    {
        $this->ensureClub();
        $conn = getDBConnection();
        $center_id = $_SESSION['center_id'];

        $stmt = $conn->prepare("SELECT * FROM centers WHERE id=?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $center = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$center) die("Center not found.");

        // Remove Individual Logic
        if (isset($_GET['remove_individual'])) {
            $id = intval($_GET['remove_individual']);
            $stmt = $conn->prepare("DELETE FROM individuals WHERE id=? AND center_id=?");
            $stmt->bind_param("ii", $id, $center_id);
            $stmt->execute();
            $stmt->close();
            redirect('/club/team');
        }

        // Teams
        $stmt = $conn->prepare("SELECT ti.*, tcd.contact_phone, tcd.contact_email, tcd.city, tcd.level FROM team_info ti JOIN team_contact_details tcd ON ti.id = tcd.team_id WHERE tcd.club = ?");
        $stmt->bind_param("s", $center['name']);
        $stmt->execute();
        $teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Team Members
        $team_members = [];
        if (!empty($teams)) {
            $team_ids = array_column($teams, 'id');
            $in  = str_repeat('?,', count($team_ids) - 1) . '?';
            $types = str_repeat('i', count($team_ids));
            $stmt = $conn->prepare("SELECT * FROM team_members_info WHERE team_id IN ($in)");
            $stmt->bind_param($types, ...$team_ids);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $team_members[$row['team_id']][] = $row;
            }
            $stmt->close();
        }

        // Individuals
        $stmt = $conn->prepare("SELECT * FROM individuals WHERE center_id=?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $individuals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        view('club.team', compact('center', 'teams', 'team_members', 'individuals'));
    }

    public function update()
    {
        $this->ensureClub();
        $conn = getDBConnection();
        $center_id = $_SESSION['center_id'];
        date_default_timezone_set("Asia/Riyadh");
        $now = date("Y-m-d H:i:s");

        // AJAX Delete
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
            $type = $_POST['delete_type'] ?? '';
            $id   = intval($_POST['delete_id'] ?? 0);
            if ($id && $type) {
                if ($type === 'pista') $stmt = $conn->prepare("DELETE FROM pistas WHERE id = ? AND center_id = ?");
                elseif ($type === 'schedule') $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ? AND center_id = ?");
                elseif ($type === 'photo') $stmt = $conn->prepare("DELETE FROM photos WHERE id = ? AND center_id = ?");

                if (isset($stmt)) {
                    $stmt->bind_param("ii", $id, $center_id);
                    $ok = $stmt->execute();
                    $stmt->close();
                    header("Content-Type: application/json");
                    echo json_encode(['success' => $ok]);
                    exit;
                }
            }
            header("Content-Type: application/json");
            echo json_encode(['success' => false]);
            exit;
        }

        // Handle Update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $conn->prepare("SELECT * FROM centers WHERE id=?");
            $stmt->bind_param("i", $center_id);
            $stmt->execute();
            $club = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $name = $_POST['name'] ?? '';
            $street = $_POST['street'] ?? '';
            $postal_code = $_POST['postal_code'] ?? '';
            $city = $_POST['city'] ?? '';
            $zone = $_POST['zone'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $website = $_POST['website'] ?? '';
            $description = $_POST['description'] ?? '';

            $logo_url = $club['logo_url'];
            if (!empty($_FILES['logo']['name'])) {
                $logo_name = time() . '_' . basename($_FILES['logo']['name']);
                $targetDir = __DIR__ . '/../../../public/uploads/club/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                move_uploaded_file($_FILES['logo']['tmp_name'], $targetDir . $logo_name);
                $logo_url = $logo_name;
            }

            $stmt = $conn->prepare("UPDATE centers SET name=?, street=?, postal_code=?, city=?, zone=?, phone=?, email=?, website=?, description=?, logo_url=?, updated_at=? WHERE id=?");
            $stmt->bind_param("sssssssssssi", $name, $street, $postal_code, $city, $zone, $phone, $email, $website, $description, $logo_url, $now, $center_id);
            $stmt->execute();
            $stmt->close();

            // Pistas
            if (!empty($_POST['pista_name'])) {
                foreach ($_POST['pista_name'] as $i => $pname) {
                    $qty = $_POST['pista_quantity'][$i] ?? 0;
                    $pista_id = isset($_POST['pista_id'][$i]) ? intval($_POST['pista_id'][$i]) : 0;

                    if ($pname) {
                        if ($pista_id > 0) {
                            $stmt = $conn->prepare("UPDATE pistas SET name=?, quantity=? WHERE id=? AND center_id=?");
                            $stmt->bind_param("siii", $pname, $qty, $pista_id, $center_id);
                        } else {
                            $stmt = $conn->prepare("INSERT INTO pistas (center_id, name, quantity) VALUES (?, ?, ?)");
                            $stmt->bind_param("isi", $center_id, $pname, $qty);
                        }
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            // Schedules
            if (!empty($_POST['schedule_day'])) {
                foreach ($_POST['schedule_day'] as $i => $day) {
                    $open = $_POST['open_time'][$i] ?? null;
                    $close = $_POST['close_time'][$i] ?? null;
                    $schedule_id = isset($_POST['schedule_id'][$i]) ? intval($_POST['schedule_id'][$i]) : 0;

                    // Format dates (keeping legacy behavior)
                    $open = $open ? date('Y-m-d H:i:s', strtotime($open)) : null;

                    if ($day) {
                        if ($schedule_id > 0) {
                            $stmt = $conn->prepare("UPDATE schedules SET day=?, open_time=?, close_time=?, updated_at=NOW() WHERE id=? AND center_id=?");
                            $stmt->bind_param("sssii", $day, $open, $close, $schedule_id, $center_id);
                        } else {
                            $stmt = $conn->prepare("INSERT INTO schedules (center_id, day, open_time, close_time) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("isss", $center_id, $day, $open, $close);
                        }
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            // Photos
            if (!empty($_FILES['photos']['name'][0])) {
                $targetDir = __DIR__ . '/../../../public/uploads/club/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                foreach ($_FILES['photos']['name'] as $i => $photo_name) {
                    if ($_FILES['photos']['error'][$i] === 0) {
                        $new_name = time() . '_' . $photo_name;
                        move_uploaded_file($_FILES['photos']['tmp_name'][$i], $targetDir . $new_name);
                        $caption = $_POST['photo_caption'][$i] ?? '';
                        $type = $_POST['photo_type'][$i] ?? '';
                        $stmt = $conn->prepare("INSERT INTO photos (center_id, url, caption, type) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isss", $center_id, $new_name, $caption, $type);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            redirect('/club/dashboard');
        }

        $stmt = $conn->prepare("SELECT * FROM centers WHERE id=?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $club = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM pistas WHERE center_id=?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $pistas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM schedules WHERE center_id=?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM photos WHERE center_id=?");
        $stmt->bind_param("i", $center_id);
        $stmt->execute();
        $photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        view('club.update', compact('club', 'pistas', 'schedules', 'photos'));
    }

    private function ensureClub()
    {
        if (!isset($_SESSION['center_id'])) {
            redirect('/login');
        }
    }
}
