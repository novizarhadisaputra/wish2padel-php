<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../login/login.php");
    exit();
}
require '../../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
if (isset($_GET['id'])) {
    $center_id = intval($_GET['id']);
    $photo_query = $conn->prepare("SELECT url FROM photos WHERE center_id = ?");
    $photo_query->bind_param("i", $center_id);
    $photo_query->execute();
    $photo_result = $photo_query->get_result();
    while ($photo = $photo_result->fetch_assoc()) {
        $file_path = '../../uploads/club/' . $photo['url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    $photo_query->close();

    $stmt = $conn->prepare("DELETE FROM photos WHERE center_id = ?");
    $stmt->bind_param("i", $center_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM pistas WHERE center_id = ?");
    $stmt->bind_param("i", $center_id);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM schedules WHERE center_id = ?");
    $stmt->bind_param("i", $center_id);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("SELECT logo_url FROM centers WHERE id = ?");
    $stmt->bind_param("i", $center_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $center = $res->fetch_assoc();
    $stmt->close();

    if ($center && $center['logo_url']) {
        $logo_path = '../../uploads/club/' . $center['logo_url'];
        if (file_exists($logo_path)) unlink($logo_path);
    }

    $stmt = $conn->prepare("DELETE FROM centers WHERE id = ?");
    $stmt->bind_param("i", $center_id);
    $stmt->execute();
    $stmt->close();

    header("Location: club.php");
    exit;
} else {
    header("Location: club.php");
    exit;
}
?>
