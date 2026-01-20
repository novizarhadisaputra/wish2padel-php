<?php
session_start();
require '../config.php';

// Pastikan user login sebagai center
if (!isset($_SESSION['center_id'])) {
    header("Location: ../login/login.php");
    exit();
}

$conn = getDBConnection();
$center_id = $_SESSION['center_id'];
$username = $_SESSION['username'] ?? null;

// Set timezone Riyadh
date_default_timezone_set("Asia/Riyadh");
$now = date("Y-m-d H:i:s");

// Ambil data club beserta relasi
$club = $conn->query("SELECT * FROM centers WHERE id=$center_id")->fetch_assoc();

// Data related
$pistas = $conn->query("SELECT * FROM pistas WHERE center_id=$center_id");
$schedules = $conn->query("SELECT * FROM schedules WHERE center_id=$center_id");
$photos = $conn->query("SELECT * FROM photos WHERE center_id=$center_id");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $type = $_POST['delete_type'] ?? '';
    $id   = intval($_POST['delete_id'] ?? 0);

    if (!$id || !$type) {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'error' => 'Invalid params']);
        exit;
    }

    switch ($type) {
        case 'pista':
            $stmt = $conn->prepare("DELETE FROM pistas WHERE id = ? AND center_id = ?");
            break;
        case 'schedule':
            $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ? AND center_id = ?");
            break;
        case 'photo':
            $stmt = $conn->prepare("DELETE FROM photos WHERE id = ? AND center_id = ?");
            break;
        default:
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'error' => 'Invalid type']);
            exit;
    }

    $stmt->bind_param("ii", $id, $center_id);
    $ok = $stmt->execute();
    $stmt->close();

    header("Content-Type: application/json");
    echo json_encode(['success' => $ok]);
    exit;
}


// ===================== HANDLE DELETE (AJAX) =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_type'], $_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $delete_type = $_POST['delete_type'];

    $success = false;
    if ($delete_type === 'pista') {
        $stmt = $conn->prepare("DELETE FROM pistas WHERE id=? AND center_id=?");
        $stmt->bind_param("ii", $delete_id, $center_id);
    } elseif ($delete_type === 'schedule') {
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id=? AND center_id=?");
        $stmt->bind_param("ii", $delete_id, $center_id);
    } elseif ($delete_type === 'photo') {
        $stmt = $conn->prepare("DELETE FROM photos WHERE id=? AND center_id=?");
        $stmt->bind_param("ii", $delete_id, $center_id);
    }

    if (isset($stmt)) {
        $success = $stmt->execute();
        $stmt->close();
    }

    header("Content-Type: application/json");
    echo json_encode(["success" => $success]);
    exit;
}


// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $_POST['name'] ?? '';
    $street      = $_POST['street'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $city        = $_POST['city'] ?? '';
    $zone        = $_POST['zone'] ?? '';
    $phone       = $_POST['phone'] ?? '';
    $email       = $_POST['email'] ?? '';
    $website     = $_POST['website'] ?? '';
    $description = $_POST['description'] ?? '';

    // Logo (replace jika upload baru)
    $logo_url = $club['logo_url'];
    if (!empty($_FILES['logo']['name'])) {
        $logo_name = time() . '_' . basename($_FILES['logo']['name']);
        $logo_path = '../uploads/club/' . $logo_name;
        move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
        $logo_url = $logo_name;
    }

    // Update centers
    $stmt = $conn->prepare("UPDATE centers 
        SET name=?, street=?, postal_code=?, city=?, zone=?, phone=?, email=?, website=?, description=?, logo_url=?, updated_at=? 
        WHERE id=?");
    $stmt->bind_param("sssssssssssi", $name, $street, $postal_code, $city, $zone, $phone, $email, $website, $description, $logo_url, $now, $center_id);
    $stmt->execute();
    $stmt->close();

    if (!empty($_POST['pista_name'])) {
    foreach ($_POST['pista_name'] as $i => $pname) {
        $qty = $_POST['pista_quantity'][$i] ?? 0;
        $pista_id = $_POST['pista_id'][$i] ?? null;

        if ($pname) {
            if ($pista_id) {
                // Update existing pista
                $stmt = $conn->prepare("UPDATE pistas SET name=?, quantity=? WHERE id=? AND center_id=?");
                $stmt->bind_param("siisi", $pname, $qty, $now, $pista_id, $center_id);
            } else {
                // Insert new pista
                $stmt = $conn->prepare("INSERT INTO pistas (center_id, name, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $center_id, $pname, $qty);
            }
            $stmt->execute();
            $stmt->close();
        }
    }
}

// ===================== SCHEDULES =====================
if (!empty($_POST['schedule_day'])) {
    foreach ($_POST['schedule_day'] as $i => $day) {
        // Ambil tanggal open dan close
        $open = $_POST['open_time'][$i] ?? null;
        $close = $_POST['close_time'][$i] ?? null;
        $schedule_id = $_POST['schedule_id'][$i] ?? null;

        // Pastikan format date valid (YYYY-MM-DD)
        $open = $open ? date('Y-m-d', strtotime($open)) : null;
        $close = $close ? date('Y-m-d', strtotime($close)) : null;

        if ($day) {
            if ($schedule_id) {
                // Update schedule yang sudah ada
                $stmt = $conn->prepare("
                    UPDATE schedules 
                    SET day=?, open_time=?, close_time=?, updated_at=NOW() 
                    WHERE id=? AND center_id=?
                ");
                $stmt->bind_param("sssii", $day, $open, $close, $schedule_id, $center_id);
            } else {
                // Insert schedule baru
                $stmt = $conn->prepare("
                    INSERT INTO schedules (center_id, day, open_time, close_time) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("isss", $center_id, $day, $open, $close);
            }
            $stmt->execute();
            $stmt->close();
        }
    }
}


// ===================== PHOTOS =====================
if (!empty($_FILES['photos']['name'][0])) {
    foreach ($_FILES['photos']['name'] as $i => $photo_name) {
        if ($_FILES['photos']['error'][$i] === 0) {
            $new_name = time() . '_' . $photo_name;
            $tmp_name = $_FILES['photos']['tmp_name'][$i];
            move_uploaded_file($tmp_name, '../uploads/club/' . $new_name);

            $photo_id = $_POST['photo_id'][$i] ?? null;
            $caption = $_POST['photo_caption'][$i] ?? '';
            $type = $_POST['photo_type'][$i] ?? ''; // misal "gallery" atau "logo"

            if ($photo_id) {
                // Update photo yang sudah ada
                $stmt = $conn->prepare("
                    UPDATE photos 
                    SET url=?, caption=?, type=?, updated_at=? 
                    WHERE id=? AND center_id=?
                ");
                $stmt->bind_param("sssii", $new_name, $caption, $type, $photo_id, $center_id);
            } else {
                // Insert photo baru
                $stmt = $conn->prepare("
                    INSERT INTO photos (center_id, url, caption, type) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("isss", $center_id, $new_name, $caption, $type);
            }

            $stmt->execute();
            $stmt->close();
        }
    }
}


    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Update - Wish2Padel</title>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/stylee.css?v=12" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>
<body>

<?php require 'navbar.php' ?>

<section class="container mt-5 mb-5 py-5 bg-white rounded shadow">
<h2>Edit Club</h2>

<?php if ($club): ?>
<form method="post" enctype="multipart/form-data">
    <!-- Basic Info -->
    <input type="hidden" name="id" value="<?= $club['id'] ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label>Club Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($club['name']) ?>" required>
        </div>
        <div class="col-md-6">
            <label>Logo</label>
            <input type="file" name="logo" class="form-control">
            <?php if ($club['logo_url']): ?>
                <img src="../uploads/club/<?= $club['logo_url'] ?>" alt="logo" style="height:50px;" class="mt-2">
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label>Street</label>
            <input type="text" name="street" class="form-control" value="<?= htmlspecialchars($club['street']) ?>">
        </div>
        <div class="col-md-4">
            <label>Postal Code</label>
            <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($club['postal_code']) ?>">
        </div>
        <div class="col-md-4">
            <label>City</label>
            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($club['city']) ?>">
        </div>
        <div class="col-md-4">
            <label>Zone</label>
            <select name="zone" class="form-select">
                <?php $zones = ["North Zone","South Zone","East Zone","West Zone","Central Zone"];
                foreach($zones as $z){ $sel=($club['zone']==$z)?"selected":""; echo "<option value='$z' $sel>$z</option>"; } ?>
            </select>
        </div>
        <div class="col-md-4">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($club['phone']) ?>">
        </div>
        <div class="col-md-4">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($club['email']) ?>">
        </div>
        <div class="col-md-6">
            <label>Website</label>
            <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($club['website']) ?>">
        </div>
        <div class="col-12">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($club['description']) ?></textarea>
        </div>
    </div>

    <!-- Pistas -->
    <div class="mt-4">
        <h5>Fields</h5>
        <div id="pistas-wrapper">
            <?php foreach($pistas as $p): ?>
            <div class="row g-2 mb-2 pista-item">
                <div class="col-md-6"><input type="text" name="pista_name[]" class="form-control" value="<?= htmlspecialchars($p['name']) ?>"></div>
                <div class="col-md-4"><input type="number" name="pista_quantity[]" class="form-control" value="<?= $p['quantity'] ?>"></div>
                <div class="col-md-2"><button type="button" class="btn btn-danger remove-pista" data-id="<?= $p['id'] ?>" data-type="pista">Remove</button></div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-pista" class="btn btn-sm btn-primary">Add Field</button>
    </div>

    <!-- Schedules -->
    <div class="mt-4" style="display:none">
        <h5>Schedules</h5>
        <div id="schedules-wrapper">
            <?php foreach($schedules as $s): ?>
            <div class="row g-2 mb-2 schedule-item">
                <div class="col-md-3">
                    <select name="schedule_day[]" class="form-select">
                        <?php $days=["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
                        foreach($days as $d){ $sel=($s['day']==$d)?"selected":""; echo "<option value='$d' $sel>$d</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-3"><input type="time" name="open_time[]" class="form-control" value="<?= $s['open_time'] ?>"></div>
                <div class="col-md-3"><input type="time" name="close_time[]" class="form-control" value="<?= $s['close_time'] ?>"></div>
                <div class="col-md-3">
                    <button type="button" 
                            class="btn btn-danger remove-schedule" 
                            data-id="<?= $s['id'] ?>" 
                            data-type="schedule">
                        Remove
                    </button>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-schedule" class="btn btn-sm btn-primary">Add Schedule</button>
    </div>

    <!-- Photos -->
    <div class="mt-4">
        <h5>Photos</h5>
        <div class="row g-2 mb-2">
            <?php foreach($photos as $ph): ?>
            <div class="col-md-3"><img src="../uploads/club/<?= $ph['url'] ?>" class="img-fluid rounded"></div>
            <?php endforeach; ?>
        </div>
        <div id="photos-wrapper">
            <div class="mb-2 photo-item"><input type="file" name="photos[]" class="form-control">
            <button type="button" 
                    class="btn btn-danger btn-sm remove-photo mt-1" 
                    data-id="<?= $ph['id'] ?>" 
                    data-type="photo">
                Remove
            </button>

        </div>
        <button type="button" id="add-photo" class="btn btn-sm btn-primary">Add Photo</button>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-success">Update Club</button>
        <a href="dashboard.php" class="btn btn-secondary">Ok</a>
        <p class="text-black">
            If you have deleted something, please click <strong>Ok</strong> to confirm. 
            Do <strong>not</strong> click <strong>Update</strong> or the deletion will be canceled.
        </p>
    </div>

</form>
<?php else: ?>
<div class="alert alert-warning">Data club tidak ditemukan.</div>
<?php endif; ?>
</section>

<?php require '../src/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.body.addEventListener("click", function(e) {
        if (e.target.classList.contains("remove-pista") ||
            e.target.classList.contains("remove-schedule") ||
            e.target.classList.contains("remove-photo")) {

            if (!confirm("Are you sure you want to delete this item?")) return;

            const btn = e.target;
            const id = btn.dataset.id;
            const type = btn.dataset.type;

            fetch("", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `action=delete&delete_type=${encodeURIComponent(type)}&delete_id=${encodeURIComponent(id)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const wrapper = btn.closest(".pista-item, .schedule-item, .col-md-3");
                    if (wrapper) wrapper.remove();
                } else {
                    alert("Delete failed!");
                }
            });
        }
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {

    // ===== PISTAS (Fields) =====
    const pistasWrapper = document.getElementById('pistas-wrapper');
    document.getElementById('add-pista').addEventListener('click', () => {
        const div = document.createElement('div');
        div.classList.add('row','g-2','mb-2','pista-item');
        div.innerHTML = `
            <div class="col-md-6"><input type="text" name="pista_name[]" class="form-control" placeholder="Field Name"></div>
            <div class="col-md-4"><input type="number" name="pista_quantity[]" class="form-control" placeholder="Quantity"></div>
            <div class="col-md-2"><button type="button" class="btn btn-danger remove-pista">Remove</button></div>
        `;
        pistasWrapper.appendChild(div);
    });

    pistasWrapper.addEventListener('click', (e) => {
        if(e.target.classList.contains('remove-pista')){
            e.target.closest('.pista-item').remove();
        }
    });

    // ===== SCHEDULES =====
    const schedulesWrapper = document.getElementById('schedules-wrapper');
    document.getElementById('add-schedule').addEventListener('click', () => {
        const div = document.createElement('div');
        div.classList.add('row','g-2','mb-2','schedule-item');
        const daysOptions = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]
            .map(d => `<option value="${d}">${d}</option>`).join('');
        div.innerHTML = `
            <div class="col-md-3">
                <select name="schedule_day[]" class="form-select">${daysOptions}</select>
            </div>
            <div class="col-md-3"><input type="time" name="open_time[]" class="form-control"></div>
            <div class="col-md-3"><input type="time" name="close_time[]" class="form-control"></div>
            <div class="col-md-3"><button type="button" class="btn btn-danger remove-schedule">Remove</button></div>
        `;
        schedulesWrapper.appendChild(div);
    });

    schedulesWrapper.addEventListener('click', (e) => {
        if(e.target.classList.contains('remove-schedule')){
            e.target.closest('.schedule-item').remove();
        }
    });

    // ===== PHOTOS =====
    const photosWrapper = document.getElementById('photos-wrapper');
    document.getElementById('add-photo').addEventListener('click', () => {
        const div = document.createElement('div');
        div.classList.add('mb-2','photo-item');
        div.innerHTML = `
            <input type="file" name="photos[]" class="form-control">
            <button type="button" class="btn btn-danger btn-sm remove-photo mt-1">Remove</button>
        `;
        photosWrapper.appendChild(div);
    });

    photosWrapper.addEventListener('click', (e) => {
        if(e.target.classList.contains('remove-photo')){
            e.target.closest('.photo-item').remove();
        }
    });

});
</script>

</body>
</html>
