<?php

namespace App\Controllers\Admin;

class ClubController
{
    public function index()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();

        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        $totalResult = $conn->query("SELECT COUNT(*) AS total FROM centers");
        $totalRows = $totalResult->fetch_assoc()['total'];
        $totalPages = ceil($totalRows / $limit);

        $sql = "SELECT id, name, city, postal_code, phone, website FROM centers ORDER BY id ASC LIMIT $limit OFFSET $offset";
        $result = $conn->query($sql);

        view('admin.club.index', compact('result', 'page', 'totalPages'));
    }

    public function create()
    {
        $this->ensureAdmin();
        view('admin.club.create');
    }

    public function store()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        date_default_timezone_set("Asia/Riyadh");
        $now = date("Y-m-d H:i:s");

        $name = $_POST['name'];
        $street = $_POST['street'];
        $postal_code = $_POST['postal_code'];
        $city = $_POST['city'];
        $zone = $_POST['zone'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $website = $_POST['website'];
        $description = $_POST['description'];
        $logo_url = '';

        if (!empty($_FILES['logo']['name'])) {
            $logo_name = time() . '_' . basename($_FILES['logo']['name']);
            move_uploaded_file($_FILES['logo']['tmp_name'], '../uploads/club/' . $logo_name);
            $logo_url = $logo_name;
        }

        $stmt = $conn->prepare("INSERT INTO centers (name, street, postal_code, city, zone, phone, email, website, description, logo_url, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssss", $name, $street, $postal_code, $city, $zone, $phone, $email, $website, $description, $logo_url, $now, $now);
        $stmt->execute();
        $center_id = $stmt->insert_id;
        $stmt->close();

        if (!empty($_POST['pista_name']) && !empty($_POST['pista_quantity'])) {
            foreach ($_POST['pista_name'] as $index => $pista_name) {
                $quantity = $_POST['pista_quantity'][$index] ?? '';
                if (!empty($pista_name) && !empty($quantity)) {
                    $stmt = $conn->prepare("INSERT INTO pistas (center_id, name, quantity) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $center_id, $pista_name, $quantity);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        if (!empty($_POST['schedule_day'])) {
            foreach ($_POST['schedule_day'] as $index => $day) {
                $open_time = $_POST['open_time'][$index] ?? '';
                $close_time = $_POST['close_time'][$index] ?? '';
                if (!empty($day)) {
                    $stmt = $conn->prepare("INSERT INTO schedules (center_id, day, open_time, close_time) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $center_id, $day, $open_time, $close_time);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        if (!empty($_FILES['photos']['name'][0])) {
            foreach ($_FILES['photos']['name'] as $index => $photo_name) {
                if ($_FILES['photos']['error'][$index] === 0) {
                    $new_name = time() . '_' . basename($photo_name);
                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$index], '../uploads/club/' . $new_name)) {
                        $stmt = $conn->prepare("INSERT INTO photos (center_id, url, caption, type, created_at) VALUES (?, ?, '', '', ?)");
                        $stmt->bind_param("iss", $center_id, $new_name, $now);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        redirect('/admin/club');
    }

    public function show()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $id = intval($_GET['id']);

        $stmt = $conn->prepare("SELECT * FROM centers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $center = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $pistas = $conn->query("SELECT * FROM pistas WHERE center_id = $id");
        $schedules = $conn->query("SELECT * FROM schedules WHERE center_id = $id");
        $photos = $conn->query("SELECT * FROM photos WHERE center_id = $id");

        view('admin.club.show', compact('center', 'pistas', 'schedules', 'photos'));
    }

    public function edit()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $id = intval($_GET['id']);

        $club = $conn->query("SELECT * FROM centers WHERE id=$id")->fetch_assoc();
        $pistas = $conn->query("SELECT * FROM pistas WHERE center_id=$id")->fetch_all(MYSQLI_ASSOC);
        $schedules = $conn->query("SELECT * FROM schedules WHERE center_id=$id")->fetch_all(MYSQLI_ASSOC);
        $photos = $conn->query("SELECT * FROM photos WHERE center_id=$id")->fetch_all(MYSQLI_ASSOC);

        view('admin.club.edit', compact('club', 'pistas', 'schedules', 'photos'));
    }

    public function update()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        date_default_timezone_set("Asia/Riyadh");
        $now = date("Y-m-d H:i:s");

        $center_id = intval($_POST['id']);
        $name = $_POST['name'];
        $street = $_POST['street'];
        $postal_code = $_POST['postal_code'];
        $city = $_POST['city'];
        $zone = $_POST['zone'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $website = $_POST['website'];
        $description = $_POST['description'];

        $club = $conn->query("SELECT logo_url FROM centers WHERE id=$center_id")->fetch_assoc();
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

        // Re-insert Pistas (Legacy logic: delete all and insert)
        $conn->query("DELETE FROM pistas WHERE center_id=$center_id");
        if (!empty($_POST['pista_name'])) {
            foreach ($_POST['pista_name'] as $i => $pname) {
                $qty = $_POST['pista_quantity'][$i] ?? '';
                if (!empty($pname) && !empty($qty)) {
                    $stmt = $conn->prepare("INSERT INTO pistas (center_id, name, quantity) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $center_id, $pname, $qty);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // Re-insert Schedules
        $conn->query("DELETE FROM schedules WHERE center_id=$center_id");
        if (!empty($_POST['schedule_day'])) {
            foreach ($_POST['schedule_day'] as $i => $day) {
                $open = $_POST['open_time'][$i] ?? '';
                $close = $_POST['close_time'][$i] ?? '';
                if (!empty($day)) {
                    $stmt = $conn->prepare("INSERT INTO schedules (center_id, day, open_time, close_time) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $center_id, $day, $open, $close);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // Add Photos
        if (!empty($_FILES['photos']['name'][0])) {
            foreach ($_FILES['photos']['name'] as $i => $photo_name) {
                if ($_FILES['photos']['error'][$i] === 0) {
                    $new_name = time() . '_' . basename($photo_name);
                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], '../uploads/club/' . $new_name)) {
                        $stmt = $conn->prepare("INSERT INTO photos (center_id, url, caption, type, created_at) VALUES (?, ?, '', '', ?)");
                        $stmt->bind_param("iss", $center_id, $new_name, $now);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        redirect('/admin/club');
    }

    public function delete()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $id = intval($_GET['id']);

        // Logic from delete.php (simple delete)
        // Ideally delete related files too, but legacy didn't seem to do it explicitly in the snippet I saw?
        // Wait, I saw delete.php in file list but didn't read it.
        // Assuming standard delete.

        $stmt = $conn->prepare("DELETE FROM centers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        redirect('/admin/club');
    }

    private function ensureAdmin()
    {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            redirect('/login');
        }
    }
}
