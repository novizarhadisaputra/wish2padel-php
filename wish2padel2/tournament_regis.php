<?php
session_start();
require 'config.php';
require_once 'SimplePaymentSystem.php';

$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
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

// Check if user is logged in and get team ID
$team_id = null;
if ($username) {
    $stmt = $conn->prepare("SELECT team_id FROM team_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result) {
        $team_id = $result['team_id'];
    }
    $stmt->close();
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

if ($tournament_id) {
    // Get tournament data
    $stmt = $conn->prepare("SELECT id, name, description FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();

    // Get centers for the tournament zone
    if ($tournament) {
        $zoneName = $tournament['name'];
        $stmt = $conn->prepare("SELECT id, name FROM centers WHERE zone = ?");
        $stmt->bind_param("s", $zoneName);
        $stmt->execute();
        $centers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// If showing payment, prepare data for form (no API call needed)
if ($show_payment) {
    try {
        $temp_data = $_SESSION['temp_registration_data'];

        // Just prepare the form data, no API call yet
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $team_name = trim($_POST['team_name']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($team_name) || empty($username) || empty($password)) {
            throw new Exception("Team name, username, and password are required.");
        }

        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM team_account WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception("Username already exists. Please choose a different username.");
        }
        $stmt->close();

        // Check if team name already exists
        $stmt = $conn->prepare("SELECT id FROM team_info WHERE team_name = ?");
        $stmt->bind_param("s", $team_name);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception("Team name already exists. Please choose a different team name.");
        }
        $stmt->close();

        // Start transaction to create account without payment
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
$stmt = $conn->prepare("
    INSERT INTO team_members_info (team_id, player_name, role)
    VALUES (?, ?, ?)
");

// Gunakan bind_param sekali
$stmt->bind_param("iss", $new_team_id, $player_name, $role);

// 1️⃣ Insert Captain
$player_name = trim($_POST['captain_name']);
$role = 'captain';
$stmt->execute();

// 2️⃣ Insert pemain lain, lewati Player 1 (Captain)
$role = 'player';
foreach ($_POST['player_name'] as $index => $pname) {
    if ($index === 0) continue; // skip Player 1
    $pname = trim($pname);
    if (!empty($pname)) {
        $player_name = $pname;
        $stmt->execute();
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

        // Auto-login: Regenerate session ID for security
        session_regenerate_id(true);

        // Set session data like login_process.php
        $_SESSION['username'] = $username;
        $_SESSION['team_id'] = $new_team_id;
        $_SESSION['payment_status'] = 'unpaid';
        $_SESSION['payment_paid'] = false;

        // Redirect to payment for this tournament with success message
        $success = "Complete payment to activate your tournament registration.";
        header("Location: payment.php?team_id={$new_team_id}&tournament_id={$tournament_id}&status=new_account");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(TRUE); // Reset autocommit

        // Enhanced error logging with more context
        $error_context = [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'tournament_id' => $tournament_id,
            'team_name' => $_POST['team_name'] ?? 'N/A',
            'username' => $_POST['username'] ?? 'N/A',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        error_log("Registration error - Full context: " . json_encode($error_context));

        // User-friendly error message
        $error = "Account creation failed: " . $e->getMessage() . ". Please check your information and try again. If the problem persists, contact support.";

        // Add JavaScript alert for immediate feedback
        echo "<script>";
        echo "document.addEventListener('DOMContentLoaded', function() {";
        echo "    alert('Registration Error: " . addslashes($e->getMessage()) . "');";
        echo "    console.error('Registration failed:', " . json_encode($error_context) . ");";
        echo "});";
        echo "</script>";
    }
}
if (isset($_GET['check_username'])) {
    $username = trim($_GET['check_username']);
    $stmt = $conn->prepare("SELECT id FROM team_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(["exists" => $result->fetch_assoc() ? true : false]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>League Registration - Padel League</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/stylee.css?=v12">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">
        <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p logo.jpeg">

    <!-- Payment Form Custom Styling -->
    <style>
        /* Payment Form Gold/Black/White Theme */
        .payment-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .payment-form-box {
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
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
            color: #1a1a1a;
        }

        .tournament-name {
            font-size: 1.2rem;
            margin: 0.5rem 0 0 0;
            color: #2d2d2d;
        }

        .payment-body {
            padding: 2rem;
            background: #ffffff;
        }

        .payment-details-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid #F3E6B6;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 8px rgba(243, 230, 182, 0.2);
        }

        .payment-card-title {
            color: #1a1a1a;
            font-weight: bold;
            margin-bottom: 1rem;
            border-bottom: 2px solid #F3E6B6;
            padding-bottom: 0.5rem;
        }

        .payment-info-grid {
            display: grid;
            gap: 0.8rem;
        }

        .payment-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .payment-label {
            font-weight: 600;
            color: #2d2d2d;
        }

        .payment-value {
            color: #1a1a1a;
            font-weight: 500;
        }

        .payment-amount {
            color: #F3E6B6;
            background: #1a1a1a;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .payment-test-mode {
            background: #fff3cd;
            border: 1px solid #F3E6B6;
            border-radius: 8px;
            padding: 0.8rem;
            margin-top: 1rem;
            color: #856404;
            font-size: 0.9rem;
        }

        .payment-form-section {
            background: #ffffff;
            border: 2px solid #F3E6B6;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .payment-form-title {
            color: #1a1a1a;
            font-weight: bold;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .payment-form-title i {
            color: #F3E6B6;
            margin-right: 0.5rem;
        }

        .payment-form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .payment-form-control:focus {
            border-color: #F3E6B6;
            box-shadow: 0 0 0 0.2rem rgba(243, 230, 182, 0.25);
            outline: none;
        }

        .payment-form-label {
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .payment-amount-display {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #F3E6B6;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            margin: 1.5rem 0;
            border: 2px solid #F3E6B6;
        }

        .payment-amount-display h4 {
            margin: 0;
            font-weight: bold;
        }

        .payment-btn-primary {
            background: linear-gradient(135deg, #F3E6B6 0%, #d4c088 100%);
            border: 2px solid #1a1a1a;
            color: #1a1a1a;
            font-weight: bold;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .payment-btn-primary:hover {
            background: linear-gradient(135deg, #d4c088 0%, #F3E6B6 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(243, 230, 182, 0.4);
            color: #1a1a1a;
            border-color: #1a1a1a;
        }

        .payment-btn-secondary {
            background: transparent;
            border: 2px solid #6c757d;
            color: #6c757d;
            font-weight: bold;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .payment-btn-secondary:hover {
            background: #6c757d;
            color: white;
        }

        .payment-alert-warning {
            background: #fff3cd;
            border: 2px solid #F3E6B6;
            color: #856404;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-alert-danger {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Card formatting */
        .card-number-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .payment-container {
                padding: 1rem 0;
            }

            .payment-header {
                padding: 1.5rem;
            }

            .payment-title {
                font-size: 1.5rem;
            }

            .payment-body {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php require 'src/navbar.php' ?>

    <?php if ($show_payment): ?>
        <!-- Payment Step with Gold/Black/White Theme -->
        <div class="payment-container">
            <div class="container-fluid">
                <div class="form-box payment-form-box">
                    <!-- Payment Header -->
                    <div class="payment-header">
                        <h2 class="payment-title">Complete Payment</h2>
                        <h4 class="tournament-name"><?= htmlspecialchars($tournament['name'] ?? 'Tournament Registration') ?></h4>
                    </div>

                    <!-- Payment Body -->
                    <div class="payment-body">
                        <?php if ($error): ?>
                            <div class="payment-alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                                <br><a href="tournament_regis.php?tournament_id=<?= $tournament_id ?>" class="btn payment-btn-secondary mt-2">Go Back to Registration</a>
                            </div>
                        <?php elseif ($payment_data): ?>
                            <!-- Payment Details Card -->
                            <div class="payment-details-card">
                                <h6 class="payment-card-title"><i class="bi bi-info-circle me-2"></i>Payment Details</h6>
                                <div class="payment-info-grid">
                                    <div class="payment-info-item">
                                        <span class="payment-label">Team:</span>
                                        <span class="payment-value"><?= htmlspecialchars($_SESSION['temp_registration_data']['team_name']) ?></span>
                                    </div>
                                    <div class="payment-info-item">
                                        <span class="payment-label">Captain:</span>
                                        <span class="payment-value"><?= htmlspecialchars($_SESSION['temp_registration_data']['captain_name']) ?></span>
                                    </div>
                                    <div class="payment-info-item">
                                        <span class="payment-label">Amount:</span>
                                        <span class="payment-amount"><?= getDynamicPaymentAmount() / 100 ?> <?= getDynamicPaymentCurrency() ?></span>
                                    </div>
                                </div>
                                <?php if (($_ENV['MOYASAR_TEST_MODE'] ?? 'false') === 'true'): ?>
                                    <div class="payment-test-mode">
                                        <i class="bi bi-info-circle me-2"></i>Test Mode - Use card: 4111111111111111, any future date, any CVV
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Payment Form Section -->
                            <div class="payment-form-section">
                                <h5 class="payment-form-title">
                                    <i class="bi bi-credit-card"></i>Payment Information
                                </h5>

                                <form id="payment-form" method="POST" action="payment_verify_integrated.php">
                                    <input type="hidden" name="tournament_id" value="<?= $tournament_id ?>">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="cardholder_name" class="payment-form-label">Cardholder Name</label>
                                                <input type="text" class="form-control payment-form-control" id="cardholder_name" name="cardholder_name" required
                                                    value="<?= htmlspecialchars($_SESSION['temp_registration_data']['captain_name']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="card_number" class="payment-form-label">Card Number</label>
                                                <input type="text" class="form-control payment-form-control card-number-input" id="card_number" name="card_number" required
                                                    placeholder="1234 5678 9012 3456" maxlength="19">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="expiry_month" class="payment-form-label">Month</label>
                                                <select class="form-select payment-form-control" id="expiry_month" name="expiry_month" required>
                                                    <option value="">MM</option>
                                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                                        <option value="<?= sprintf('%02d', $i) ?>"><?= sprintf('%02d', $i) ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="expiry_year" class="payment-form-label">Year</label>
                                                <select class="form-select payment-form-control" id="expiry_year" name="expiry_year" required>
                                                    <option value="">YYYY</option>
                                                    <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                                        <option value="<?= $i ?>"><?= $i ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="cvc" class="payment-form-label">CVC</label>
                                                <input type="text" class="form-control payment-form-control" id="cvc" name="cvc" required
                                                    placeholder="123" maxlength="4">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="payment-amount-display">
                                        <h4>Total Amount: <?= getDynamicPaymentAmount() / 100 ?> <?= getDynamicPaymentCurrency() ?></h4>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <a href="tournament_regis.php?tournament_id=<?= $tournament_id ?>" class="btn payment-btn-secondary w-100">
                                                <i class="bi bi-arrow-left me-2"></i>Back to Registration
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <button type="submit" class="btn payment-btn-primary" id="pay-button">
                                                <i class="bi bi-credit-card me-2"></i>Pay Now & Complete Registration
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Format card number input
            document.getElementById('card_number').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                if (formattedValue.length > 19) {
                    formattedValue = formattedValue.substring(0, 19);
                }
                e.target.value = formattedValue;
            });

            // Handle form submission
            document.getElementById('payment-form').addEventListener('submit', function(e) {
                e.preventDefault();

                const payButton = document.getElementById('pay-button');
                payButton.disabled = true;
                payButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing Payment...';

                // Get form data
                const formData = new FormData(this);

                // Validate form
                const cardNumber = formData.get('card_number').replace(/\s/g, '');
                if (cardNumber.length < 13) {
                    alert('Please enter a valid card number');
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="bi bi-credit-card me-2"></i>Pay Now & Complete Registration';
                    return;
                }

                // Create payment request to Moyasar
                const paymentData = {
                    amount: <?= getDynamicPaymentAmount() ?>,
                    currency: '<?= getDynamicPaymentCurrency() ?>',
                    description: 'Tournament Registration - <?= addslashes($_SESSION['temp_registration_data']['team_name']) ?>',
                    source: {
                        type: 'creditcard',
                        name: formData.get('cardholder_name'),
                        number: cardNumber,
                        month: formData.get('expiry_month'),
                        year: formData.get('expiry_year'),
                        cvc: formData.get('cvc')
                    },
                    callback_url: window.location.protocol + '//' + window.location.host + '/payment_verify_integrated.php?tournament_id=<?= $tournament_id ?>',
                    metadata: {
                        team_name: '<?= addslashes($_SESSION['temp_registration_data']['team_name']) ?>',
                        tournament_id: '<?= $tournament_id ?>'
                    }
                };

                // Submit to Moyasar using fetch with proper authentication
                fetch('https://api.moyasar.com/v1/payments', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Basic ' + btoa('<?= getMoyasarPublishableKey() ?>:')
                        },
                        body: JSON.stringify(paymentData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Payment response:', data);
                        console.log('Payment ID from Moyasar:', data.id);
                        console.log('Payment status:', data.status);

                        // Log payment ID details for debugging
                        if (data.id) {
                            console.log('Payment ID type:', typeof data.id);
                            console.log('Payment ID length:', data.id.length);
                            console.log('Payment ID format check:', /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(data.id) ? 'Valid UUID' : 'Not UUID format');
                        }

                        if (data.status === 'paid' || data.status === 'captured') {
                            // Payment successful, redirect to verification
                            console.log('Redirecting to verification with Payment ID:', data.id);
                            window.location.href = 'payment_verify_integrated.php?payment_id=' + data.id + '&status=paid&tournament_id=<?= $tournament_id ?>';
                        } else if (data.status === 'failed') {
                            alert('Payment failed: ' + (data.message || 'Payment was declined'));
                            payButton.disabled = false;
                            payButton.innerHTML = '<i class="bi bi-credit-card me-2"></i>Pay Now & Complete Registration';
                        } else if (data.status === 'initiated' && data.source && data.source.transaction_url) {
                            // 3DS authentication required
                            console.log('3DS authentication required, redirecting to:', data.source.transaction_url);
                            window.location.href = data.source.transaction_url;
                        } else {
                            // Payment pending or other status
                            console.log('Payment pending/other status, redirecting to verification with Payment ID:', data.id);
                            window.location.href = 'payment_verify_integrated.php?payment_id=' + data.id + '&status=' + data.status + '&tournament_id=<?= $tournament_id ?>';
                        }
                    })
                    .catch(error => {
                        console.error('Payment error:', error);
                        alert('Payment processing error: ' + error.message + '. Please check your card details and try again.');
                        payButton.disabled = false;
                        payButton.innerHTML = '<i class="bi bi-credit-card me-2"></i>Pay Now & Complete Registration';
                    });
            });
        </script>
        </div>
        </div>
        </div>

        <!-- Include Moyasar Script -->
        <script src="https://cdn.moyasar.com/mpf/1.14.0/moyasar.js"></script>
        <script>
            <?php if ($payment_data): ?>
                Moyasar.init({
                    element: '.mysr-form',
                    amount: <?= getDynamicPaymentAmount() ?>,
                    currency: '<?= getDynamicPaymentCurrency() ?>',
                    description: 'Tournament Registration - <?= addslashes($_SESSION['temp_registration_data']['team_name']) ?>',
                    publishable_api_key: '<?= getMoyasarPublishableKey() ?>',
                    callback_url: '<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] ?>/payment_verify_integrated.php?tournament_id=<?= $tournament_id ?>',
                    methods: ['creditcard']
                });
            <?php endif; ?>
        </script>

    <?php else: ?>
        <!-- Registration Form (existing code) -->

        <style>
            .form-box {
                max-width: 900px;
                margin: auto;
                margin-top: 20px;
                padding: 20px;
                border-radius: 12px;
                background: #fff;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .scroll-box {
                max-height: 350px;
                overflow-y: auto;
                padding-right: 8px;
            }

            section {
                display: none;
                opacity: 0;
                transition: opacity 0.5s ease;
            }

            section.active {
                display: block;
                opacity: 1;
            }

            /* Ensure invalid form controls are visible */
            .is-invalid {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            }

            .is-invalid:focus {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            }

            /* Ensure all form steps are properly hidden/shown */
            section:not(.active) {
                display: none !important;
            }

            section.active {
                display: block !important;
                opacity: 1 !important;
            }
        </style>

        <div class="container-fluid mt-5">
            <div class="form-box">
                <!-- Error Alert -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Registration Failed</h5>
                        <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Success Alert -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Success!</h5>
                        <p class="mb-0"><?= htmlspecialchars($success) ?></p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

<form method="POST" id="regForm" novalidate onkeydown="return event.key != 'Enter';">

  <!-- ===== Progress Bar ===== -->
  <div class="mb-4">
    <div class="progress" style="height:6px; background:#333;">
      <div id="progressBar" class="progress-bar" style="width:20%; background:#f3e6b6;"></div>
    </div>
    <p class="text-center text-muted small mt-2">Step <span id="currentStep">1</span> of 5</p>
  </div>

  <!-- ======================== STEP 1 ======================== -->
  <?php
  // Ambil semua team_name untuk validasi duplikat
  $teamNames = [];
  $res = $conn->query("SELECT team_name FROM team_info");
  while ($row = $res->fetch_assoc()) {
      $teamNames[] = strtolower(trim($row['team_name']));
  }
  ?>

  <section id="step1" class="active p-5">
    <h4>Team Captain Information</h4>
    <div class="mb-3">
      <label class="form-label">Team Name</label>
      <input type="text" class="form-control" id="team_name" name="team_name" placeholder="Team Name" autocomplete="organization" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Captain Name</label>
      <input type="text" class="form-control" id="captain_name" name="captain_name" placeholder="Captain Full Name" autocomplete="name" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Captain Phone</label>
      <input type="tel" class="form-control" id="captain_phone" name="captain_phone" placeholder="966 5XXXXXXXX" autocomplete="tel" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Captain Email</label>
      <input type="email" class="form-control" id="captain_email" name="captain_email" placeholder="captain@email.com" autocomplete="email" required>
    </div>
    <button type="button" class="btn btn-next" style="background-color:#f3e6b6;" onclick="validateTeamName()">Next</button>
  </section>

<!-- ======================== STEP 2 ======================== -->
<section id="step2" class="p-5">
  <h4>Team Members</h4>
  <p class="small text-muted">
    Captain is automatically added as Player 1.<br>
    Please add between <strong>8</strong> and <strong>12</strong> total players.<br>
    You can start registration with at least <strong>5 players</strong> and complete your team later.
  </p>

  <div id="membersContainer">
    <!-- Player 1: Captain -->
    <div class="mb-3 position-relative">
      <label class="form-label">Player 1 (Captain)</label>
      <input type="text" id="player_1" name="player_name[]" class="form-control player-input"
             placeholder="Captain Name" readonly>
      <small class="text-danger error-msg" style="display:none;"></small>
    </div>

    <!-- Player 2–5 (required) -->
    <?php for ($i = 2; $i <= 5; $i++): ?>
      <div class="mb-3 position-relative">
        <label class="form-label">Player <?= $i ?> Name</label>
        <input type="text" name="player_name[]" id="player_<?= $i ?>"
               class="form-control player-input" placeholder="Full Name" required>
        <small class="text-danger error-msg" style="display:none;"></small>
      </div>
    <?php endfor; ?>
  </div>

  <!-- Add Player Button -->
  <div class="text-end mb-3">
    <button type="button" class="btn btn-sm btn-outline-dark" id="addPlayerBtn">
      <i class="bi bi-person-plus me-1"></i>Add Player
    </button>
  </div>

  <!-- Notice -->
  <div class="alert alert-warning small" style="border-radius:10px;">
    ⚠️ Each team must have between 8 and 12 players in total.<br>
    You may register now with 5 players and add the rest later.
  </div>

  <button type="button" class="btn" style="background:black;color:white"
          onclick="prevStep(2)">Back</button>
  <button type="button" class="btn btn-next" id="nextStep2Btn"
          style="background-color:#f3e6b6;" onclick="validateMembers()">Next</button>
</section>

<script>
// === Autofill Captain Name and Validate === //
document.getElementById('captain_name').addEventListener('input', () => {
  const cap = document.getElementById('captain_name').value.trim();
  document.getElementById('player_1').value = cap;
  validateAllNames(); // trigger validation realtime
});

document.addEventListener('DOMContentLoaded', () => {
  const p1 = document.getElementById('player_1');
  p1.style.backgroundColor = 'white';
  p1.style.cursor = 'not-allowed';
});

// === Add Player (max 12) === //
let currentPlayers = 5;
document.getElementById('addPlayerBtn').addEventListener('click', () => {
  if (currentPlayers >= 12) return alert('Maximum 12 players allowed.');
  currentPlayers++;
  const div = document.createElement('div');
  div.className = 'mb-3 position-relative';
  div.innerHTML = `
    <label class="form-label">Player ${currentPlayers} Name</label>
    <input type="text" name="player_name[]" id="player_${currentPlayers}"
           class="form-control player-input" placeholder="Full Name" required>
    <small class="text-danger error-msg" style="display:none;"></small>
  `;
  document.getElementById('membersContainer').appendChild(div);
  attachDuplicateCheck(div.querySelector('input'));
});

// === Validation All Names (local + DB) === //
async function validateAllNames() {
  const inputs = document.querySelectorAll('.player-input');
  const counts = {};
  const duplicates = [];

  // normalize names
  inputs.forEach(i => {
    const val = i.value.trim().toLowerCase().replace(/\s+/g, ' ');
    if (val) counts[val] = (counts[val] || 0) + 1;
  });

  // find local duplicates
  for (const [n, total] of Object.entries(counts)) {
    if (total > 1) duplicates.push(n);
  }

  // reset errors
  inputs.forEach(clearError);

  // mark local duplicates
  inputs.forEach(i => {
    const val = i.value.trim().toLowerCase().replace(/\s+/g, ' ');
    if (duplicates.includes(val) && val !== '') {
      showDuplicateError(i, "This name already exists in your team.");
    }
  });

  // check database for unique names
  const uniqueNames = Object.keys(counts);
  for (const n of uniqueNames) {
    try {
      const url = window.location.pathname + "?check_member_name=" + encodeURIComponent(n);
      const res = await fetch(url);
      const data = await res.json();
      if (data.exists) {
        inputs.forEach(i => {
          if (i.value.trim().toLowerCase().replace(/\s+/g, ' ') === n) {
            showDuplicateError(i, "This player name already exists in another team.");
          }
        });
      }
    } catch (e) {
      console.error("DB check failed:", e);
    }
  }

  updateNextButton();
}

// === Helper Functions === //
function showDuplicateError(input, msg) {
  input.classList.add('is-invalid');
  const err = input.parentElement.querySelector('.error-msg');
  err.textContent = msg;
  err.style.display = 'block';
}

function clearError(input) {
  input.classList.remove('is-invalid');
  const err = input.parentElement.querySelector('.error-msg');
  err.textContent = '';
  err.style.display = 'none';
}

function attachDuplicateCheck(input) {
  input.addEventListener('input', validateAllNames);
  input.addEventListener('keyup', validateAllNames);
}
document.querySelectorAll('.player-input').forEach(inp => attachDuplicateCheck(inp));

// === Enable/Disable Next Button === //
function updateNextButton() {
  const btn = document.getElementById('nextStep2Btn');
  const hasInvalid = document.querySelectorAll('.player-input.is-invalid').length > 0;
  btn.disabled = hasInvalid;
  btn.style.opacity = hasInvalid ? '0.5' : '1';
  btn.style.cursor = hasInvalid ? 'not-allowed' : 'pointer';
}

// === Final Validation Before Next === //
function validateMembers() {
  const inputs = document.querySelectorAll('.player-input');
  let filled = 0, invalid = 0;

  inputs.forEach(i => {
    if (i.id !== 'player_1' && i.value.trim()) filled++;
    if (i.classList.contains('is-invalid')) invalid++;
  });

  if (invalid > 0) {
    alert('Please fix duplicate player names before continuing.');
    return;
  }
  if (filled < 5) {
    alert('Please enter at least 5 players before continuing.');
    return;
  }
  nextStep(2);
}
</script>

<?php
// === Database Name Check Endpoint === //
if (isset($_GET['check_member_name'])) {
  $name = strtolower(trim($_GET['check_member_name']));
  $stmt = $conn->prepare("SELECT COUNT(*) FROM team_members_info WHERE LOWER(player_name)=?");
  $stmt->bind_param("s", $name);
  $stmt->execute();
  $stmt->bind_result($count);
  $stmt->fetch();
  $stmt->close();
  header('Content-Type: application/json');
  echo json_encode(['exists' => $count > 0]);
  exit;
}
?>




  <!-- ======================== STEP 3 ======================== -->
<section id="step3" class="p-5">
  <h4>Contact Information</h4>

  <div class="mb-3">
    <label class="form-label">Contact Phone</label>
    <input type="tel" name="contact_phone" id="contact_phone"
           class="form-control" placeholder="966 5XXXXXXXX" required>
    <small class="text-danger error-msg" style="display:none;">This field is required.</small>
  </div>

  <div class="mb-3">
    <label class="form-label">Contact Email</label>
    <input type="email" name="contact_email" id="contact_email"
           class="form-control" placeholder="example@email.com" required>
    <small class="text-danger error-msg" style="display:none;">This field is required.</small>
  </div>

  <h4>Additional Details</h4>

  <div class="mb-3">
    <label class="form-label">Club</label>
    <select name="club" id="club" class="form-select" required>
      <option value="">-- Select Club --</option>
      <?php foreach ($centers as $center): ?>
        <option value="<?= htmlspecialchars($center['name']) ?>">
          <?= htmlspecialchars($center['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="text-danger error-msg" style="display:none;">Please select a club.</small>
  </div>

  <div class="mb-3">
    <label class="form-label">City</label>
    <input type="text" name="city" id="city"
           class="form-control" placeholder="Enter your city" required>
    <small class="text-danger error-msg" style="display:none;">This field is required.</small>
  </div>

  <h4>Padel Experience</h4>

  <div class="mb-3">
    <label class="form-label">How long your team played padel</label>
    <select name="experience" id="experience" class="form-select" required>
      <option value="">-- Select Duration --</option>
      <option>1 month</option>
      <option>2 months</option>
      <option>3 months</option>
      <option>6 months</option>
      <option>1 year</option>
      <option>2 years plus</option>
    </select>
    <small class="text-danger error-msg" style="display:none;">Please select a duration.</small>
  </div>

  <div class="mb-3">
    <label class="form-label">Have you ever competed?</label>
    <select name="competed" id="competed" class="form-select" required>
      <option value="">-- Select --</option>
      <option>yes</option>
      <option>no</option>
    </select>
    <small class="text-danger error-msg" style="display:none;">Please choose an option.</small>
  </div>

  <div class="mb-3">
    <label class="form-label">Do you compete in regional tournaments?</label>
    <select name="regional" id="regional" class="form-select" required>
      <option value="">-- Select --</option>
      <option>yes</option>
      <option>no</option>
    </select>
    <small class="text-danger error-msg" style="display:none;">Please choose an option.</small>
  </div>

  <div class="mb-3">
  <label class="form-label">Level</label>
  <select name="level" id="level" class="form-select" required>
    <option value="">-- Select Level --</option>

    <!-- 🎾 Advanced -->
    <option value="Advanced B+">Advanced: B+ (4.5–5)</option>
    <option value="Advanced B">Advanced: B (4–4.5)</option>
    <option value="Advanced B-">Advanced: B− (3.5–4)</option>

    <!-- 🎾 Intermediate -->
    <option value="Intermediate C+">Intermediate: C+ (3.0–3.5)</option>
    <option value="Intermediate C">Intermediate: C (2.5–3.0)</option>
    <option value="Intermediate C-">Intermediate: C− (2.0–2.5)</option>

    <!-- 🎾 Beginner -->
    <option value="Beginner D+">Beginner: D+ (1.5–2.0)</option>
    <option value="Beginner D">Beginner: D (1.0–1.5)</option>
    <option value="Beginner D-">Beginner: D− (&lt;1)</option>
  </select>
  <small class="text-danger error-msg" style="display:none;">Please select a level.</small>
</div>


  <div class="mb-3">
    <label class="form-label">Additional Notes</label>
    <textarea name="notes" id="notes" class="form-control"
              rows="3" placeholder="Any additional details..." ></textarea>
    <small class="text-danger error-msg" style="display:none;">This field is required.</small>
  </div>

  <button type="button" class="btn" style="background:grey;color:white"
          onclick="prevStep(3)">Back</button>
  <button type="button" class="btn btn-next"
          style="background:#f3e6b6;" onclick="validateStep3()">Next</button>
</section>

<script>
function validateStep3() {
  const step3 = document.querySelector('#step3');
  const requiredFields = step3.querySelectorAll('[required]');
  let allValid = true;

  requiredFields.forEach(field => {
    const msg = field.parentElement.querySelector('.error-msg');
    field.classList.remove('is-invalid');
    if (msg) { msg.style.display = 'none'; msg.textContent = ''; }

    if (!field.value.trim()) {
      allValid = false;
      field.classList.add('is-invalid');
      if (msg) {
        msg.textContent = 'This field is required.';
        msg.style.display = 'block';
      }
    } else if (field.type === 'email') {
      const emailRegex = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
      if (!emailRegex.test(field.value.trim())) {
        allValid = false;
        field.classList.add('is-invalid');
        if (msg) {
          msg.textContent = 'Please enter a valid email address.';
          msg.style.display = 'block';
        }
      }
    }
  });

  if (!allValid) {
    window.scrollTo({ top: step3.offsetTop - 50, behavior: 'smooth' });
    return; // stop here
  }

  nextStep(3);
}
</script>


  <!-- ======================== STEP 4 ======================== -->
  <section id="step4" class="p-5">
    <h4>Create Account Access</h4>
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" id="username" class="form-control" placeholder="Choose a username" required>
      <div id="usernameError" class="text-danger small mt-1" style="display:none;"></div>
    </div>
    <div class="mb-3">
  <label class="form-label">Password</label>
  <div class="d-flex align-items-center position-relative">
    <input type="password" name="password" id="password"
      class="form-control pe-5" placeholder="Create a strong password" required maxlength="20">
    <i class="bi bi-eye-slash position-absolute end-0 me-3" id="togglePass" style="cursor:pointer;"></i>
  </div>

  <div id="passwordStrengthText" class="small mt-1 text-muted">Enter a password...</div>
  <div class="progress mt-1" style="height:5px;">
    <div id="passwordStrengthBar" class="progress-bar bg-danger" style="width:0%;"></div>
  </div>
  <ul id="passwordCriteria" class="small list-unstyled mt-2">
    <li id="criteria-length" class="text-danger">❌ Minimum 8 characters (Max 20)</li>
    <li id="criteria-uppercase" class="text-danger">❌ At least 1 uppercase letter</li>
    <li id="criteria-number" class="text-danger">❌ At least 1 number</li>
    <li id="criteria-symbol" class="text-danger">❌ At least 1 special character</li>
  </ul>
</div>

<div class="mb-3">
  <label class="form-label">Re-enter Password</label>
  <div class="d-flex align-items-center position-relative">
    <input type="password" id="repassword" class="form-control pe-5" placeholder="Re-enter password" required>
    <i class="bi bi-eye-slash position-absolute end-0 me-3" id="toggleRePass" style="cursor:pointer;"></i>
  </div>
  <div id="repassError" class="text-danger small mt-1" style="display:none;">Passwords do not match.</div>
</div>

    <button type="button" class="btn" style="background:grey;color:white" onclick="prevStep(4)">Back</button>
    <button type="button" class="btn btn-next" style="background:#f3e6b6;" onclick="validateUsernameBeforeNext()">Next</button>
  </section>

  <!-- ======================== STEP 5 ======================== -->
  <section id="step5" class="p-5">
    <h4>Complete Registration</h4>
    <div class="alert alert-success">
      <h6><i class="bi bi-credit-card me-2"></i>Payment Details</h6>
      <p class="mb-1">Tournament: <?= htmlspecialchars($tournament['name']??'Tournament Registration') ?></p>
      <p class="mb-0">Amount: <strong><?= getFormattedPaymentAmount() ?></strong></p>
    </div>
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>Your data will be saved after successful payment.
    </div>
    <button type="button" class="btn" style="background:grey;color:white" onclick="prevStep(5)">Back</button>
    <button type="button" class="btn" style="background:#28a745;color:white" onclick="submitForm()">
      <i class="bi bi-credit-card me-2"></i>Complete Registration & Pay
    </button>
  </section>
</form>

<!-- ======================== CSS IMPROVEMENT ======================== -->
<style>
section{display:none;}
section.active{display:block;}
.progress{transition:width .4s;}
.form-control.is-invalid{border-color:#dc3545;}
.form-control.is-valid{border-color:#198754;}
</style>

<!-- ======================== SCRIPT ======================== -->
<script>
const existingTeamNames = <?= json_encode(array_map(fn($n)=>strtolower(trim($n)),$teamNames)) ?>;
let currentStep=1;
const totalSteps=5;

// === PROGRESS BAR UPDATE ===
function updateProgress(){
  const percent=(currentStep/totalSteps)*100;
  document.getElementById("progressBar").style.width=percent+"%";
  document.getElementById("currentStep").textContent=currentStep;
}

// === SIMPLE UTILITIES ===
function normalizeName(str){return str.trim().toLowerCase().replace(/\s+/g,' ');}
function showError(input,msg){
  input.classList.add("is-invalid");
  const err=document.createElement("div");
  err.className="invalid-feedback";
  err.innerText=msg;
  input.parentNode.appendChild(err);
}

// === STEP 1 VALIDATION (TEAM INFO) ===
function validateTeamName(){
  const t=document.getElementById('team_name');
  const c=document.getElementById('captain_name');
  const e=document.getElementById('captain_email');
  document.querySelectorAll('.invalid-feedback').forEach(el=>el.remove());
  [t,c,e].forEach(i=>i.classList.remove('is-invalid','is-valid'));
  const team=normalizeName(t.value);
  const email=e.value.trim();
  if(existingTeamNames.includes(team)){showError(t,"This team name is already taken.");t.focus();return;}
  if(!team){showError(t,"Please enter a team name.");return;}
  if(!c.value.trim()){showError(c,"Please enter a captain name.");return;}
  const emailRegex=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if(!emailRegex.test(email)){showError(e,"Please enter a valid email.");return;}
  [t,c,e].forEach(i=>i.classList.add('is-valid'));
  nextStep(1);
}

// === STEP 2 VALIDATION (MEMBERS) ===
function validateMembers(){
  const inputs=document.querySelectorAll(".player-input");
  const vals=[];
  let err=false;
  inputs.forEach((i,idx)=>{
    i.classList.remove("is-invalid");
    if(idx<5 && !i.value.trim()){
      i.classList.add("is-invalid");
      err=true;
    }
  });
  if(!err)nextStep(2);
}

// === STEP CONTROL ===
function nextStep(step){
  document.querySelector(`#step${step}`).classList.remove('active');
  currentStep=step+1;
  document.querySelector(`#step${currentStep}`).classList.add('active');
  updateProgress();
}
function prevStep(step){
  document.querySelector(`#step${step}`).classList.remove('active');
  currentStep=step-1;
  document.querySelector(`#step${currentStep}`).classList.add('active');
  updateProgress();
}

// === PASSWORD VALIDATION ===
const p=document.getElementById('password');
p.addEventListener('input',()=>{
  const pass=p.value;
  const l=pass.length>=8&&pass.length<=20;
  const u=/[A-Z]/.test(pass);
  const n=/\d/.test(pass);
  const s=/[\W_]/.test(pass);
  updateCriteria('criteria-length',l,"Minimum 8 characters (Max 20)");
  updateCriteria('criteria-uppercase',u,"At least 1 uppercase letter");
  updateCriteria('criteria-number',n,"At least 1 number");
  updateCriteria('criteria-symbol',s,"At least 1 special character");
  const str=[l,u,n,s].filter(Boolean).length*25;
  const bar=document.getElementById('passwordStrengthBar');
  const txt=document.getElementById('passwordStrengthText');
  bar.style.width=str+"%";
  if(str<=25){bar.className="progress-bar bg-danger";txt.textContent="Very weak";}
  else if(str<=50){bar.className="progress-bar bg-warning";txt.textContent="Weak";}
  else if(str<=75){bar.className="progress-bar bg-info";txt.textContent="Good";}
  else{bar.className="progress-bar bg-success";txt.textContent="Strong ✔️";}
});
function updateCriteria(id,ok,text){
  const e=document.getElementById(id);
  e.textContent=(ok?"✅ ":"❌ ")+text;
  e.className=ok?"text-success":"text-danger";
}

// === TOGGLE PASSWORD VISIBILITY ===
document.getElementById("togglePass").onclick=()=>toggle('password','togglePass');
document.getElementById("toggleRePass").onclick=()=>toggle('repassword','toggleRePass');
function toggle(id,icon){
  const input=document.getElementById(id);
  const ico=document.getElementById(icon);
  if(input.type==="password"){input.type="text";ico.classList.replace('bi-eye-slash','bi-eye');}
  else{input.type="password";ico.classList.replace('bi-eye','bi-eye-slash');}
}

// === STEP4 VALIDATION ===
function validateUsernameBeforeNext(){
  const u=document.getElementById('username');
  const user=u.value.trim().toLowerCase();
  const eDiv=document.getElementById('usernameError');
  const p1=document.getElementById('password').value;
  const p2=document.getElementById('repassword').value;
  eDiv.style.display='none';
  u.classList.remove('is-invalid');
  if(!user){eDiv.textContent="Please enter a username.";eDiv.style.display='block';u.focus();return;}
  fetch("?check_username="+encodeURIComponent(user))
  .then(r=>r.json())
  .then(d=>{
    if(d.exists){eDiv.textContent="Username already exists.";eDiv.style.display='block';u.classList.add('is-invalid');return;}
    if(p1!==p2){document.getElementById('repassError').style.display='block';return;}
    document.getElementById('repassError').style.display='none';
    nextStep(4);
  }).catch(()=>{alert('Error checking username.');});
}

// === FINAL SUBMIT ===
function validateAndSubmit(){
  for(let i=1;i<=4;i++){
    const step=document.querySelector(`#step${i}`);
    const req=step.querySelectorAll("[required]");
    for(const inp of req){if(!inp.value.trim()){alert(`Please fill all required fields in step ${i}.`);return false;}}
  }
  return true;
}
function submitForm(){
  if(!validateAndSubmit())return;
  const btn=document.querySelector('button[onclick="submitForm()"]');
  const original=btn.innerHTML;
  btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split me-2"></i>Creating...';
  const form=document.getElementById('regForm');
  fetch(window.location.href,{method:'POST',body:new FormData(form)})
  .then(r=>{if(r.redirected)window.location=r.url;else return r.text();})
  .then(h=>{if(h){document.body.innerHTML=h;window.scrollTo(0,0);}})
  .catch(()=>{alert('Network error.');btn.disabled=false;btn.innerHTML=original;});
}
updateProgress();
</script>



            </div>
        <?php endif; ?>
        </div>

        <?php require 'src/footer.php' ?>

        <!-- Scroll to Top Button -->
        <button id="scrollTopBtn" title="Go to top">↑</button>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>