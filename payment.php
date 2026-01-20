<?php

/**
 * Simple Payment Page with Original Design
 * Version: 3.0 - Simplified backend, original frontend
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Debug GET parameters first
error_log("GET parameters: " . print_r($_GET, true));

// Include required files
require_once 'config.php';
require_once 'SimplePaymentSystem.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get and validate parameters
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;

// Debug parsed parameters
error_log("Parsed team_id: $team_id, tournament_id: $tournament_id");

// Initialize variables
$error = '';
$success = '';
$payment_data = null;
$team = null;
$tournament = null;

// Validate required parameters FIRST
if ($team_id <= 0 || $tournament_id <= 0) {
    $error = "Missing required parameters. Please access this page through proper registration flow.";
} else {
    try {
        // Use SimplePaymentSystem for proper payment handling
        $paymentSystem = new SimplePaymentSystem();
        $conn = getDBConnection();

        // Get team info
        $stmt = getDBConnection()->prepare("SELECT team_name, captain_name, captain_email, captain_phone FROM team_info WHERE id = ? AND tournament_id = ?");
        $stmt->bind_param("ii", $team_id, $tournament_id);
        $stmt->execute();
        $team = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$team) {
            // Team not found for this tournament
            throw new Exception("Team not found for this tournament. Please complete registration first.");
        }

        // Check if team account exists
        $stmt = getDBConnection()->prepare("SELECT username FROM team_account WHERE team_id = ?");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $account_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $has_account = !empty($account_result);
        error_log("Team $team_id has account: " . ($has_account ? 'YES' : 'NO'));

        // Get tournament info
        $stmt = getDBConnection()->prepare("SELECT * FROM tournaments WHERE id = ?");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        $tournament = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$tournament) {
            throw new Exception("Tournament not found");
        }

        // Check current payment status using SimplePaymentSystem
        $is_team_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);

        if ($is_team_paid) {
            // Team has already paid, show success message with payment details
            $payment_info = $paymentSystem->getTeamPaymentInfo($team_id, $tournament_id);
            if ($has_account) {
                $success = "Your team has already completed payment for this tournament and has an active account.";
            } else {
                $success = "Your team has already completed payment for this tournament.";
            }
            $payment_data = [
                'status' => 'already_paid',
                'payment_id' => $payment_info['payment_id'] ?? 'N/A',
                'amount' => getDynamicPaymentAmount(),
                'currency' => getDynamicPaymentCurrency(),
                'paid_date' => $payment_info['created_at'] ?? null,
                'team_name' => $team['team_name'] ?? 'Unknown Team',
                'has_account' => $has_account
            ];
        } else {
            // Team hasn't paid yet
            if ($has_account) {
                // Team has account but hasn't paid - they need to pay to complete registration
                error_log("Team $team_id has account but hasn't paid - requires payment");
            } else {
                // Team doesn't have account and hasn't paid - normal flow
                error_log("Team $team_id doesn't have account and hasn't paid - normal registration flow");
            }

            // Prepare payment form (NO database insertion until actual payment)
            $payment_result = $paymentSystem->preparePaymentForm($team_id, $tournament_id);

            if ($payment_result['status'] === 'success') {
                $payment_data = $payment_result;
                $payment_data['has_account'] = $has_account;

                if ($has_account) {
                    $payment_data['message'] = "Complete your payment to activate your team account for this tournament.";
                } else {
                    $payment_data['message'] = "Complete your payment to register for this tournament.";
                }
            } elseif ($payment_result['status'] === 'already_paid') {
                $success = $payment_result['message'];
                // Get payment info for display
                $payment_info = null; // Emergency: Skip payment info for now
                $payment_data = [
                    'status' => 'already_paid',
                    'payment_id' => $payment_info['payment_id'] ?? 'N/A',
                    'amount' => getDynamicPaymentAmount(),
                    'currency' => getDynamicPaymentCurrency(),
                    'paid_date' => $payment_info['created_at'] ?? null,
                    'team_name' => $team['team_name'] ?? 'Unknown Team',
                    'has_account' => $has_account
                ];
            } else {
                $error = $payment_result['message'] ?? 'Failed to create payment';
            }
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Payment page error: " . $e->getMessage());
    }
}

// Debug final state
error_log("Final state - payment_data: " . ($payment_data ? 'YES' : 'NO') . ", team_id: $team_id, tournament_id: $tournament_id, error: '$error', success: '$success'");
if ($payment_data) {
    error_log("Payment data contents: " . print_r($payment_data, true));
}

// Check URL parameters for status display
$url_status = $_GET['status'] ?? null;
$url_error = $_GET['error'] ?? null;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Payment - Wish2Padel</title>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Moyasar Form CSS -->
    <link rel="stylesheet" href="https://cdn.moyasar.com/mpf/1.14.0/moyasar.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/stylee.css?v=12">

    <style>
        /* Payment Page - Gold/Black/White Theme (Streamlined) */
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;

        }

        .payment-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .payment-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(243, 230, 182, 0.3);
            border: 2px solid #F3E6B6;
            overflow: hidden;
        }

        .payment-header {
            background: linear-gradient(135deg, #F3E6B6 0%, #d4c088 100%);
            color: #1a1a1a;
            padding: 2rem;
            text-align: center;
            border-bottom: 3px solid #1a1a1a;
        }

        .payment-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
            color: #1a1a1a;
        }

        .tournament-name {
            font-size: 1rem;
            margin: 0.5rem 0 0 0;
            color: #2d2d2d;
            opacity: 0.8;
        }

        .payment-body {
            padding: 2rem;
        }

        .team-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid #F3E6B6;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .team-info-title {
            color: #1a1a1a;
            font-weight: bold;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            border-bottom: 2px solid #F3E6B6;
            padding-bottom: 0.5rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #2d2d2d;
        }

        .info-value {
            color: #1a1a1a;
            font-weight: 500;
        }

        .amount-display {
            background: linear-gradient(135deg, #F3E6B6 0%, #d4c088 100%);
            color: #1a1a1a;
            text-align: center;
            padding: 1.5rem;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1.5rem 0;
            border-radius: 15px;
            border: 2px solid #1a1a1a;
        }

        .payment-form-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            border: 2px solid #F3E6B6;
        }

        .payment-form-title {
            color: #1a1a1a;
            font-weight: bold;
            margin-bottom: 1rem;
            text-align: center;
        }

        .mysr-form {
            margin: 1rem 0;
        }

        .security-badge {
            background: #e8f5e8;
            color: #1a7a1a;
            padding: 0.75rem;
            border-radius: 10px;
            text-align: center;
            font-size: 0.85rem;
            margin-top: 1rem;
            border: 1px solid #c8e6c9;
        }

        .alert {
            border-radius: 15px;
            border: none;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ffebee, #fde8e8);
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .alert-success {
            background: linear-gradient(135deg, #e8f5e8, #f1f8e9);
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .alert-info {
            background: linear-gradient(135deg, #e3f2fd, #e8f4fd);
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff8e1, #fffacd);
            color: #f57c00;
            border-left: 4px solid #ff9800;
        }

        .btn {
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            border: none;
            color: #F3E6B6;
            box-shadow: 0 4px 15px rgba(26, 26, 26, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2d2d2d, #1a1a1a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 26, 26, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #2e7d32, #388e3c);
            border: none;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #388e3c, #2e7d32);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.4);
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem 0;
            }

            .payment-header {
                padding: 1.5rem;
            }

            .payment-body {
                padding: 1.5rem;
            }

            .team-info-card {
                padding: 1rem;
            }

            .amount-display {
                font-size: 2rem;
                padding: 1rem;
            }

            .payment-form-container {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>


    <div class="payment-container p-md-5">
        <div class="payment-card">
            <!-- Header -->
            <div class="payment-header">
                <h1 class="payment-title">
                    <i class="bi bi-credit-card me-2"></i>Complete Payment
                </h1>
                <p class="tournament-name"><?= htmlspecialchars($tournament['name'] ?? 'Tournament Registration') ?></p>
            </div>

            <!-- Body -->
            <div class="payment-body">
                <?php
                // Display URL status alerts
                if ($url_status === 'failed' || $url_error): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Payment Failed</h5>
                            <p class="mb-2">
                                <?php if ($url_error): ?>
                                    <?= htmlspecialchars(urldecode($url_error)) ?>
                                <?php else: ?>
                                    Your payment was not completed successfully. Please try again.
                                <?php endif; ?>
                            </p>
                            <small class="text-muted">Don't worry, no charges have been made to your card.</small>
                        </div>
                    </div>
                <?php elseif ($url_status === 'cancelled' || $url_status === 'canceled'): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-x-circle me-3 fs-4 text-warning"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Payment Cancelled</h5>
                            <p class="mb-2">You cancelled the payment process. No charges were made.</p>
                            <small class="text-muted">You can try again whenever you're ready.</small>
                        </div>
                    </div>
                <?php elseif ($url_status === 'timeout'): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-clock me-3 fs-4 text-warning"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Payment Timeout</h5>
                            <p class="mb-2">The payment process timed out. Please try again.</p>
                            <small class="text-muted">No charges were made to your card.</small>
                        </div>
                    </div>
                <?php elseif ($url_status === 'success'): ?>
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Payment Successful!</h5>
                            <p class="mb-2">Your payment has been processed successfully. Your team is now registered for the tournament.</p>
                            <small class="text-muted">You will receive a confirmation email shortly.</small>
                        </div>
                    </div>

                <?php elseif ($url_status === 'new_account'): ?>
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-person-check-fill me-3 fs-4 text-success"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Account Created Successfully!</h5>
                            <p class="mb-2">Welcome! Your team account has been created and you are now logged in.</p>
                            <small class="text-muted">Complete the payment below to activate your tournament registration.</small>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <!-- Error State -->
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-x-circle me-3 fs-4"></i>
                        <div>
                            <strong>Payment Error</strong><br>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <a href="tournament.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Tournament
                        </a>
                    </div>

                <?php elseif ($success): ?>
                    <!-- Success State - Already Paid -->
                    <?php if (isset($payment_data['status']) && $payment_data['status'] === 'already_paid'): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading mb-1">Payment Already Completed!</h5>
                                <p class="mb-2"><?= htmlspecialchars($success) ?></p>

                                <!-- Payment Details -->
                                <div class="payment-details mt-3 p-3 bg-light rounded">
                                    <h6 class="mb-2"><i class="bi bi-receipt me-2"></i>Payment Details</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <strong>Team:</strong> <?= htmlspecialchars($payment_data['team_name']) ?><br>
                                                <strong>Tournament:</strong> <?= htmlspecialchars($tournament['name'] ?? 'Tournament') ?><br>
                                                <!-- <strong>Payment ID:</strong> <?= htmlspecialchars($payment_data['payment_id']) ?> -->
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <strong>Amount:</strong> <?= number_format($payment_data['amount'] / 100, 0) ?> <?= htmlspecialchars($payment_data['currency']) ?><br>
                                                <?php if ($payment_data['paid_date']): ?>
                                                    <strong>Paid on:</strong> <?= date('M j, Y g:i A', strtotime($payment_data['paid_date'])) ?><br>
                                                <?php endif; ?>
                                                <strong>Status:</strong> <span class="badge bg-success">PAID</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <a href="myteam.php" class="btn btn-success btn-lg me-2">
                                <i class="bi bi-house me-2 mb-2"></i>Go to My Team
                            </a>
                            <a href="tournament.php" class="btn btn-outline-primary mt-4">
                                <i class="bi bi-trophy me-2 mt-2"></i>View Tournaments
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Other Success Messages -->
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle me-3 fs-4"></i>
                            <div>
                                <strong>Success!</strong><br>
                                <?= htmlspecialchars($success) ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <a href="dashboard.php" class="btn btn-gold">
                                <i class="bi bi-house me-2 mb-2"></i>Go to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>

                <?php elseif ($payment_data && $team && $tournament): ?>
                    <!-- Payment Form -->

                    <!-- Team Information -->
                    <div class="row">
                        <div class="team-info-card">
                            <h5 class="team-info-title">
                                <i class="bi bi-people-fill me-2"></i>Registration Details
                            </h5>

                            <div class="info-row">
                                <span class="info-label">Team Name</span>
                                <span class="info-value"><?= htmlspecialchars($team['team_name'] ?? 'Unknown Team') ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Team Captain</span>
                                <span class="info-value"><?= htmlspecialchars($team['captain_name'] ?? 'Unknown Captain') ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Tournament</span>
                                <span class="info-value"><?= htmlspecialchars($tournament['name'] ?? 'Unknown Tournament') ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Registration Fee</span>
                                <span class="info-value"><?= getDynamicPaymentAmount() ?> SAR</span>
                            </div>
                        </div>
                        <!-- Payment Form -->
                        <div class="payment-form-container">
                            <h5 class="payment-form-title">
                                <i class="bi bi-credit-card-fill me-2"></i>Secure Payment
                                <small class="d-block text-muted mt-1" style="font-size: 0.85rem; font-weight: normal;">
                                    <i class="bi bi-credit-card me-1"></i>Credit/Debit Card
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-apple me-1"></i>Apple Pay
                                </small>
                            </h5>

                            <!-- Moyasar Payment Form -->
                            <div class="mysr-form"></div>

                            <!-- Security Info -->
                            <div class="security-badge">
                                <i class="bi bi-shield-check me-2"></i>
                                256-bit SSL encryption • PCI DSS compliant • Apple Pay & Card payments secured by Moyasar
                            </div>
                        </div>

                    </div>

                    <!-- Amount Display -->
                    <!-- <div class="amount-display">
                    <?=getDynamicPaymentAmount() ?>.00 SAR
                </div> -->

                    <!-- Payment Messages -->
                    <div id="payment-messages"></div>

                <?php else: ?>
                    <!-- Fallback error state -->
                    <div class="alert alert-warning" role="alert">
                        <h6 class="alert-heading mb-1">
                            <i class="bi bi-exclamation-triangle me-2"></i>Unable to load payment form
                        </h6>
                        <p class="mb-2">Please check your connection and try again.</p>
                        <div class="text-center">
                            <a href="regis.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Registration
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Moyasar Form JS -->
    <script src="https://cdn.moyasar.com/mpf/1.14.0/moyasar.js"></script>

    <?php
    // Debug kondisi untuk JavaScript - FINAL CHECK
    $get_team_id = (int)($_GET['team_id'] ?? 0);
    $get_tournament_id = (int)($_GET['tournament_id'] ?? 1);

    error_log("FINAL CHECK before JS condition: GET team_id=$get_team_id, GET tournament_id=$get_tournament_id, payment_data=" . ($payment_data ? 'YES' : 'NO'));

    $js_condition = ($payment_data && $get_team_id > 0 && $get_tournament_id > 0);
    error_log("JavaScript condition check: payment_data=" . ($payment_data ? 'true' : 'false') . ", GET team_id=$get_team_id, GET tournament_id=$get_tournament_id, result=" . ($js_condition ? 'PASS' : 'FAIL'));
    ?>

    <?php if ($js_condition): ?>


        <script>
            // // Force immediate console log - SHOULD APPEAR NOW
            // // console.log('🔥 SCRIPT LOADED - payment.php - CONDITIONS MET');
            // // console.log('🔥 Raw GET values: team_id=<?= $_GET['team_id'] ?? 'NOT_SET' ?>, tournament_id=<?= $_GET['tournament_id'] ?? 'NOT_SET' ?>');
            // // console.log('🔥 PHP values: team_id=<?= $team_id ?>, tournament_id=<?= $tournament_id ?>');
            // // console.log('🔥 Checking conditions: payment_data=', <?= json_encode($payment_data ? true : false) ?>);

            // // Debug payment data
            // // console.log('💾 Payment Data:', <?= json_encode($payment_data) ?>);
            // // console.log('👥 Team Data:', <?= json_encode($team) ?>);
            // // console.log('🏆 Tournament Data:', <?= json_encode($tournament) ?>);

            // Payment configuration - USE $_GET directly to avoid scope issues
            const PaymentConfig = {
                teamId: <?= json_encode((int)($_GET['team_id'] ?? 0)) ?>,
                tournamentId: <?= json_encode((int)($_GET['tournament_id'] ?? 1)) ?>,
                amount: <?= json_encode($payment_data['amount']) ?>,
                currency: <?= json_encode($payment_data['currency']) ?>,
                publishableKey: <?= json_encode($payment_data['publishable_key']) ?>,
                teamName: <?= json_encode($team['team_name'] ?? 'Unknown Team') ?>,
                callbackUrl: <?= json_encode('payment_verify_existing.php?team_id=' . ($_GET['team_id'] ?? 0) . '&tournament_id=' . ($_GET['tournament_id'] ?? 1)) ?>
            };

            // // console.log('🚀 Payment Config:', PaymentConfig);

            // Initialize payment form when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Moyasar === 'undefined') {
                    showMessage('error', 'Payment system not available. Please refresh the page.');
                    return;
                }

                // Deteksi Apple device
                const isAppleDevice = /iPhone|iPad|Macintosh/.test(navigator.userAgent) &&
                    (/Safari/.test(navigator.userAgent) || /CriOS/.test(navigator.userAgent));

                // Default: hanya credit card
                let methods = ['creditcard'];

                // Jika Apple device → tambahkan Apple Pay
                if (isAppleDevice && window.ApplePaySession && ApplePaySession.canMakePayments()) {
                    methods.push('applepay');
                }

                Moyasar.init({
                    element: '.mysr-form',
                    amount: PaymentConfig.amount,
                    currency: PaymentConfig.currency,
                    description: PaymentConfig.teamName + ' - Tournament Registration',
                    publishable_api_key: PaymentConfig.publishableKey,
                    callback_url: window.location.origin + '/' + PaymentConfig.callbackUrl,
                    methods: methods,
                    apple_pay: {
                        label: 'Padel League Registration',
                        country: 'SA',
                        validate_merchant_url: 'https://api.moyasar.com/v1/applepay/initiate'
                    },
                    on_completed: function(payment) {
                        showMessage('success', 'Payment successful! Redirecting...');
                        setTimeout(function() {
                            window.location.href = PaymentConfig.callbackUrl + '&payment_id=' + payment.id;
                        }, 2000);
                    },
                    on_failed: function(error) {
                        console.error('❌ Payment failed:', error);
                        showMessage('error', 'Payment failed: ' + (error.message || 'Unknown error'));
                    }
                });
            });


            // Show message function
            function showMessage(type, message) {
                const messagesDiv = document.getElementById('payment-messages');
                if (!messagesDiv) return;

                const alertClass = type === 'success' ? 'alert-success' :
                    type === 'error' ? 'alert-danger' :
                    type === 'info' ? 'alert-info' : 'alert-warning';
                const icon = type === 'success' ? 'check-circle' :
                    type === 'error' ? 'x-circle' :
                    type === 'info' ? 'info-circle' : 'exclamation-triangle';

                messagesDiv.innerHTML = `
                <div class="alert ${alertClass} d-flex align-items-center" role="alert">
                    <i class="bi bi-${icon} me-2 fs-5"></i>
                    <div>
                        <strong>${type === 'success' ? 'Success' : type === 'error' ? 'Error' : type === 'info' ? 'Info' : 'Warning'}</strong><br>
                        ${message}
                    </div>
                </div>
            `;

                // console.log(`📢 Message [${type}]: ${message}`);
            }
        </script>
    <?php else: ?>
        <!-- Condition NOT MET - Debug Section -->
        <!-- <div style="background: #ffebee; padding: 1rem; margin: 1rem; border: 1px solid #f44336;">
        <h6>DEBUG: JavaScript Condition NOT MET</h6>
        <p><strong>Payment Data:</strong> <?= $payment_data ? 'Available' : 'NOT AVAILABLE' ?></p>
        <p><strong>Team ID:</strong> <?= $team_id ?: 'EMPTY/NULL' ?></p>
        <p><strong>Tournament ID:</strong> <?= $tournament_id ?: 'EMPTY/NULL' ?></p>
        <p><strong>Error:</strong> <?= $error ?: 'No error' ?></p>
        <p><strong>Success:</strong> <?= $success ?: 'No success message' ?></p>
    </div> -->

        <script>
            // console.log('❌ JavaScript condition NOT met');
            // console.log('❌ payment_data:', <?= json_encode($payment_data) ?>);
            // console.log('❌ team_id:', <?= json_encode($team_id) ?>);
            // console.log('❌ tournament_id:', <?= json_encode($tournament_id) ?>);
            // console.log('❌ error:', <?= json_encode($error) ?>);
            // console.log('❌ success:', <?= json_encode($success) ?>);
        </script>
    <?php endif; ?>
</body>

</html>