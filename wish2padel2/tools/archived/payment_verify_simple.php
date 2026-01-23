<?php
/**
 * Payment Verification Handler for Simple Payment System
 * Handles Moyasar payment callbacks and updates database status
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once 'config.php';
require_once 'SimplePaymentSystem.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log all incoming data
error_log("Payment verification called with GET: " . print_r($_GET, true));
error_log("Payment verification called with POST: " . print_r($_POST, true));

// Get parameters
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;
$payment_id = $_GET['payment_id'] ?? $_POST['id'] ?? '';
$status = $_GET['status'] ?? $_POST['status'] ?? '';

// Validate basic parameters
if ($team_id <= 0) {
    error_log("Invalid team_id: $team_id");
    header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=failed&error=" . urlencode("Invalid team ID"));
    exit();
}

// If payment_id is missing, try to fetch from database
if (empty($payment_id)) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT payment_id 
            FROM payment_transactions 
            WHERE team_id = ? AND tournament_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ii", $team_id, $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result && !empty($result['payment_id'])) {
            $payment_id = $result['payment_id'];
            error_log("Auto-fetched payment_id: $payment_id for team_id: $team_id");
        } else {
            error_log("No payment found for team_id: $team_id");
            header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=failed&error=" . urlencode("No payment record found"));
            exit();
        }
    } catch (Exception $e) {
        error_log("Database error when fetching payment_id: " . $e->getMessage());
        header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=failed&error=" . urlencode("Database error"));
        exit();
    }
}

// Final validation
if (empty($payment_id)) {
    error_log("Payment ID still empty after fetch attempt");
    header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=failed&error=" . urlencode("Payment ID not found"));
    exit();
}

try {
    // Initialize payment system
    $paymentSystem = new SimplePaymentSystem();
    
    // Verify payment with Moyasar
    $verification_result = $paymentSystem->verifyPayment($payment_id, $team_id, $tournament_id);
    
    if ($verification_result['status'] === 'success') {
        error_log("Payment verification successful for team_id=$team_id, payment_id=$payment_id");
        
        // Redirect to success page
        header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=success");
        exit();
        
    } else {
        error_log("Payment verification failed: " . $verification_result['message']);
        
        // Redirect to failed page
        header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=failed&error=" . urlencode($verification_result['message']));
        exit();
    }
    
} catch (Exception $e) {
    error_log("Payment verification error: " . $e->getMessage());
    
    // Redirect to error page
    header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=failed&error=" . urlencode("Verification system error"));
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - Padel League</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .verification-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .spinner {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-container">
            <h2 class="mb-4">Verifikasi Pembayaran</h2>
            
            <?php if ($is_success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h4>Pembayaran Berhasil!</h4>
                    <p><?= htmlspecialchars($status_message) ?></p>
                </div>
            <?php elseif (strpos($status_message, 'diproses') !== false): ?>
                <div class="alert alert-info">
                    <div class="spinner">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <h4>Memverifikasi Pembayaran</h4>
                    <p><?= htmlspecialchars($status_message) ?></p>
                    <small class="text-muted">Halaman ini akan refresh otomatis dalam 10 detik.</small>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h4>Status Pembayaran</h4>
                    <p><?= htmlspecialchars($status_message) ?></p>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="<?= htmlspecialchars($redirect_url) ?>" class="btn btn-primary">
                    <?= $is_success ? 'Kembali ke Dashboard' : 'Lanjutkan' ?>
                </a>
            </div>
            
            <?php if (isset($payment_details) && $payment_details): ?>
                <div class="mt-4">
                    <small class="text-muted">
                        Payment ID: <?= htmlspecialchars($payment_details['moyasar_payment_id'] ?? 'N/A') ?><br>
                        Status: <?= htmlspecialchars($payment_details['status'] ?? 'N/A') ?><br>
                        Waktu: <?= htmlspecialchars($payment_details['updated_at'] ?? $payment_details['created_at'] ?? 'N/A') ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>