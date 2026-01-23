<?php
session_start();
require_once '../config.php';

// Pastikan user sudah login sebagai center
if (!isset($_SESSION['center_id'])) {
    header("Location: ../login/login.php");
    exit();
}

$conn = getDBConnection();
$center_id = $_SESSION['center_id'];
$username = $_SESSION['username'] ?? null;

// Ambil data center
$stmt = $conn->prepare("SELECT * FROM centers WHERE id = ?");
$stmt->bind_param("i", $center_id);
$stmt->execute();
$center_result = $stmt->get_result();
$center = $center_result ? $center_result->fetch_assoc() : null;
$stmt->close();

if (!$center) {
    die("Center not found.");
}

// Ambil data field (pistas)
$stmt = $conn->prepare("SELECT * FROM pistas WHERE center_id = ?");
$stmt->bind_param("i", $center_id);
$stmt->execute();
$res = $stmt->get_result();
$pistas = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Ambil data schedule
$stmt = $conn->prepare("SELECT * FROM schedules WHERE center_id = ?");
$stmt->bind_param("i", $center_id);
$stmt->execute();
$res = $stmt->get_result();
$schedules = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Ambil data photos
$stmt = $conn->prepare("SELECT * FROM photos WHERE center_id = ?");
$stmt->bind_param("i", $center_id);
$stmt->execute();
$res = $stmt->get_result();
$photos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Dashboard - Wish2Padel</title>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/stylee.css?=v12">
</head>
<body style="background-color:#303030;">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<?= require 'navbar.php' ?>

<section class="container bg-white mt-5 mb-5 p-5 shadow-lg border rounded">

    <!-- DATA DIRI -->
    <div class="row mb-5 align-items-center">
        <div class="col-md-4 text-center">
            <img src="../uploads/club/<?= htmlspecialchars($center['logo_url'] ?? 'default.png') ?>" 
                 alt="<?= htmlspecialchars($center['name'] ?? 'No Name') ?>" 
                 class="img-fluid rounded shadow" style="max-height:200px; object-fit:contain;">
        </div>
        <div class="col-md-8">
            <h1 class="fw-bold"><?= htmlspecialchars($center['name'] ?? 'No Name') ?></h1>
            <p class="text-muted">
                <?= htmlspecialchars($center['street'] ?? 'Not available') ?>, 
                <?= htmlspecialchars($center['city'] ?? 'Not available') ?>, 
                <?= htmlspecialchars($center['postal_code'] ?? 'Not available') ?>
            </p>
            <p>
                <i class="bi bi-telephone"></i> <?= htmlspecialchars($center['phone'] ?? '-') ?> | 
                <i class="bi bi-envelope"></i> <?= htmlspecialchars($center['email'] ?? '-') ?>
            </p>
            <p>
                <i class="bi bi-globe"></i> 
                <?php if (!empty($center['website'])): ?>
                    <a href="<?= htmlspecialchars($center['website']) ?>" target="_blank"><?= htmlspecialchars($center['website']) ?></a>
                <?php else: ?>
                    Not available
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- FIELDS -->
    <div class="mb-5">
        <h2 class="fw-bold mb-4">Fields</h2>
        <div class="row g-4">
            <?php if (!empty($pistas)): ?>
                <?php foreach ($pistas as $p): ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100 text-center p-3">
                            <h5 class="fw-bold"><?= htmlspecialchars($p['name'] ?? 'No Name') ?></h5>
                            <p class="text-muted">Amount: <?= htmlspecialchars($p['quantity'] ?? '0') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No fields available.</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- DESKRIPSI -->
    <div class="mb-5">
        <h2 class="fw-bold mb-3">About Club</h2>
        <p class="lead"><?= htmlspecialchars($center['description'] ?? 'No description available.') ?></p>
    </div>

    <div class="mb-5" style="display:none">
        <h2 class="fw-bold mb-4">Schedule</h2>
        <?php if (!empty($schedules)): ?>
            <div class="table-responsive">
                <table class="table table-striped shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Day</th>
                            <th>Open</th>
                            <th>Close</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['day'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($s['open_time'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($s['close_time'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No schedule available.</p>
        <?php endif; ?>
    </div>


    <!-- PHOTOS -->
    <div class="mb-5">
        <h2 class="fw-bold mb-4">Photos</h2>
        <div class="row g-4">
            <?php if (!empty($photos)): ?>
                <?php foreach ($photos as $ph): ?>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm">
                            <img src="../uploads/club/<?= htmlspecialchars($ph['url'] ?? 'default.png') ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($ph['caption'] ?? '') ?>" 
                                 style="object-fit:cover; height:200px;">
                            <?php if (!empty($ph['caption'])): ?>
                                <div class="card-body text-center">
                                    <small class="text-muted"><?= htmlspecialchars($ph['caption']) ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No photos available.</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- BUTTON PERBARUI -->
    <div class="text-end">
        <a href="update_center.php" class="btn btn-gold">Update Data</a>
    </div>
</section>

<?php require '../src/footer2.php'; ?>

<!-- Scroll to Top Button -->
<button id="scrollTopBtn" title="Go to top">↑</button>

<script>
AOS.init();

// Scroll to Top Button
const scrollBtn = document.getElementById("scrollTopBtn");
window.onscroll = () => {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        scrollBtn.style.display = "block";
    } else {
        scrollBtn.style.display = "none";
    }
};
scrollBtn.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
});

// Navbar Fixed on Scroll
document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('maiavbar');
    const hero = document.getElementById('club'); 
    if (!navbar) return;

    function toggleNavbarFixed() {
        if (!hero) return;
        const scrollPos = window.scrollY;
        const heroHeight = hero.offsetHeight;

        if (scrollPos >= heroHeight) {
            navbar.classList.add('navbar-fixed');
            document.body.style.paddingTop = navbar.offsetHeight + 'px';
        } else {
            navbar.classList.remove('navbar-fixed');
            document.body.style.paddingTop = '0';
        }
    }
    window.addEventListener('scroll', toggleNavbarFixed);
    toggleNavbarFixed();
});
</script>
</body>
</html>
