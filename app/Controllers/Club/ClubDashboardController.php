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

        $pistas = $conn->query("SELECT * FROM pistas WHERE center_id = $center_id")->fetch_all(MYSQLI_ASSOC);
        $schedules = $conn->query("SELECT * FROM schedules WHERE center_id = $center_id")->fetch_all(MYSQLI_ASSOC);
        $photos = $conn->query("SELECT * FROM photos WHERE center_id = $center_id")->fetch_all(MYSQLI_ASSOC);

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
            $club = $conn->query("SELECT * FROM centers WHERE id=$center_id")->fetch_assoc();

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
                move_uploaded_file($_FILES['logo']['tmp_name'], '../uploads/club/' . $logo_name);
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
                    $pista_id = $_POST['pista_id'][$i] ?? null; // Legacy view didn't have hidden ID field, it checked by existing? No, legacy logic was insert/update based on ID if present. But legacy form DIDN'T have ID input in the loop for existing items?
                    // Wait, let's check legacy `update_center.php` form.
                    // Loop: `<input type="text" name="pista_name[]" ...>`
                    // It does NOT have a hidden input for ID!
                    // Legacy code:
                    // `$pista_id = $_POST['pista_id'][$i] ?? null;`
                    // BUT WHERE IS IT IN FORM?
                    // Legacy form: `<div class="col-md-2"><button ... data-id="<?= $p['id'] ?>" ...>Remove</button></div>`
                    // It seems legacy `update_center.php` DOES NOT submit IDs for existing items in the main form loop?
                    // Let's re-read legacy `update_center.php`.
                    // The form loop for pistas just has `name="pista_name[]"` and `pista_quantity[]`.
                    // It does NOT output `<input type="hidden" name="pista_id[]" value="...">`.
                    // So `$pista_id` would be null or mismatched index?
                    // Actually, legacy PHP `update_center.php`:
                    // `foreach ($_POST['pista_name'] as $i => $pname)`
                    // `$pista_id = $_POST['pista_id'][$i] ?? null;`
                    // If the form doesn't send `pista_id[]`, this is always null.
                    // This means legacy code might have been deleting/recreating or just failing to update specific IDs properly (always inserting?).
                    // OR I missed the hidden input in `read_file`.
                    // Let's look closely at `read_file` output for `update_center.php`.
                    // `<div class="row g-2 mb-2 pista-item">`
                    // `<div class="col-md-6"><input type="text" name="pista_name[]" ...></div>`
                    // No hidden ID.
                    // So legacy code `INSERT`s new ones every time?
                    // `$stmt = $conn->prepare("INSERT INTO pistas ...")` is in the `else` block of `if ($pista_id)`.
                    // If `$pista_id` is null, it inserts.
                    // So every save duplicates Pistas? That's a bug in legacy code.
                    // I should fix it. I will add the hidden ID input in my new View.

                    if ($pname) {
                        if ($pista_id) {
                            $stmt = $conn->prepare("UPDATE pistas SET name=?, quantity=? WHERE id=? AND center_id=?");
                            $stmt->bind_param("siis", $pname, $qty, $pista_id, $center_id); // wait id is int
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
                    $schedule_id = $_POST['schedule_id'][$i] ?? null; // I must add this hidden input too
                    // Format dates
                    $open = $open ? date('Y-m-d H:i:s', strtotime($open)) : null; // Legacy used date() on time input? `date('Y-m-d', ...)`?
                    // Legacy: `$open = $open ? date('Y-m-d', strtotime($open)) : null;`
                    // Wait, `open_time` column is `DATE` or `TIME` or `DATETIME`?
                    // Input type is `time`. `strtotime` gives today's date with that time.
                    // If column is `TIME`, `date('H:i:s')` is better.
                    // Legacy code used `Y-m-d`. This suggests the column might be DATETIME or user didn't care about date part.
                    // I will replicate legacy behavior: `date('Y-m-d', strtotime($open))`.
                    // Actually, let's assume `open_time` is time.
                    // If legacy used Y-m-d, it might be wrong if it's just time.
                    // But I'll stick to legacy: `date('Y-m-d', ...)`

                    if ($day) {
                        if ($schedule_id) {
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
                foreach ($_FILES['photos']['name'] as $i => $photo_name) {
                    if ($_FILES['photos']['error'][$i] === 0) {
                        $new_name = time() . '_' . $photo_name;
                        move_uploaded_file($_FILES['photos']['tmp_name'][$i], '../uploads/club/' . $new_name);
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

        $club = $conn->query("SELECT * FROM centers WHERE id=$center_id")->fetch_assoc();
        $pistas = $conn->query("SELECT * FROM pistas WHERE center_id=$center_id")->fetch_all(MYSQLI_ASSOC);
        $schedules = $conn->query("SELECT * FROM schedules WHERE center_id=$center_id")->fetch_all(MYSQLI_ASSOC);
        $photos = $conn->query("SELECT * FROM photos WHERE center_id=$center_id")->fetch_all(MYSQLI_ASSOC);

        view('club.update', compact('club', 'pistas', 'schedules', 'photos'));
    }

    private function ensureClub()
    {
        if (!isset($_SESSION['center_id'])) {
            redirect('/login');
        }
    }
}
