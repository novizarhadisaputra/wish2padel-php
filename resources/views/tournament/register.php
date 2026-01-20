
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
    <?php view('partials.navbar'); ?>

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
                                <br><a href="<?= asset('tournament-register') ?>?tournament_id=<?= $tournament_id ?>" class="btn payment-btn-secondary mt-2">Go Back to Registration</a>
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

                                <form id="payment-form" method="POST" action="<?= asset('payment/verify') ?>">
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
                                            <a href="<?= asset('tournament-register') ?>?tournament_id=<?= $tournament_id ?>" class="btn payment-btn-secondary w-100">
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
                    callback_url: window.location.protocol + '//' + window.location.host + '<?= asset('payment/verify') ?>?tournament_id=<?= $tournament_id ?>',
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
                            window.location.href = '<?= asset('payment/verify') ?>?payment_id=' + data.id + '&status=paid&tournament_id=<?= $tournament_id ?>';
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
                            window.location.href = '<?= asset('payment/verify') ?>?payment_id=' + data.id + '&status=' + data.status + '&tournament_id=<?= $tournament_id ?>';
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
                    callback_url: '<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] ?><?= asset('payment/verify') ?>?tournament_id=<?= $tournament_id ?>',
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
                    <!-- Step 1 -->
                    <?php
// Ambil semua team_name dari database untuk dicek di JS nanti
$teamNames = [];
$res = $conn->query("SELECT team_name FROM team_info");
while ($row = $res->fetch_assoc()) {
    $teamNames[] = strtolower(trim($row['team_name']));
}
?>

<!-- SECTION STEP 1 -->
<section id="step1" class="active p-5">
    <h4>Team Captain Information</h4>
    <div class="mb-3">
        <label for="team_name" class="form-label">Team Name</label>
        <input type="text" class="form-control" id="team_name" name="team_name"
            placeholder="Team Name" autocomplete="organization" required>
    </div>
    <div class="mb-3">
        <label for="captain_name" class="form-label">Captain Name</label>
        <input type="text" class="form-control" id="captain_name" name="captain_name"
            placeholder="Captain Full Name" autocomplete="name" required>
    </div>
    <div class="mb-3">
        <label for="captain_phone" class="form-label">Captain Phone</label>
        <input type="tel" class="form-control" id="captain_phone" name="captain_phone"
            placeholder="966 5XXXXXXXX" autocomplete="tel" required>
    </div>
    <div class="mb-3">
        <label for="captain_email" class="form-label">Captain Email</label>
        <input type="email" class="form-control" id="captain_email" name="captain_email"
            placeholder="captain@email.com" autocomplete="email" required>
    </div>

    <button type="button" class="btn btn-next" style="background-color:#f3e6b6;" onclick="validateTeamName()">Next</button>
</section>

<script>
function normalizeName(str) {
    return str.trim().toLowerCase().replace(/\s+/g, ' ');
}

const existingTeamNames = <?= json_encode(array_map(fn($n) => strtolower(trim(preg_replace('/\s+/', ' ', $n))), $teamNames)) ?>;

function validateTeamName() {
    const teamInput = document.getElementById('team_name');
    const emailInput = document.getElementById('captain_email');
    const teamName = normalizeName(teamInput.value);
    const email = emailInput.value.trim();

    // Hapus error lama
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    teamInput.classList.remove('is-invalid', 'is-valid');
    emailInput.classList.remove('is-invalid', 'is-valid');

    // Validasi nama tim duplikat
    if (existingTeamNames.includes(teamName)) {
        showError(teamInput, 'This team name is already taken. Please choose another.');
        teamInput.focus();
        return;
    }

    // Validasi format email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError(emailInput, 'Please enter a valid email address.');
        emailInput.focus();
        return;
    }

    // Jika lolos semua
    teamInput.classList.add('is-valid');
    emailInput.classList.add('is-valid');
    nextStep(1);
}

function showError(input, message) {
    input.classList.add('is-invalid');
    const error = document.createElement('div');
    error.className = 'invalid-feedback';
    error.innerText = message;
    input.parentNode.appendChild(error);
}
</script>




                    <!-- Step 2 -->
<section id="step2" class="p-5">
  <h4>Team Members</h4>
  <p style="font-size:14px; color:gray;">
    Please add between <strong>8</strong> and <strong>12</strong> team members.<br>
    <em>Note:</em> The team captain is already counted as one of the players,
    so you don’t need to add or write the captain’s name again here.
  </p>

  <div id="membersContainer" class="scroll-box">
    <?php for ($i = 1; $i <= 6; $i++): ?>
      <div class="mb-3">
        <label for="player_<?= $i ?>" class="form-label">Player <?= $i ?> Name</label>
        <input type="text" name="player_name[]" id="player_<?= $i ?>" class="form-control player-input"
          placeholder="Full Name" autocomplete="name" required>
        <div class="invalid-feedback">Player name must not be the same as another player or the captain.</div>
      </div>
    <?php endfor; ?>
  </div>

  <button type="button" class="btn" style="background-color:black; color:white" onclick="addMember()">Add Member</button>
  <button type="button" class="btn" style="background-color:grey; color:white" onclick="prevStep(2)">Back</button>
  <button type="button" class="btn btn-next" style="background-color:#f3e6b6;" onclick="validateMembers()">Next</button>

  <script>
    function normalizeName(str) {
      return str.trim().toLowerCase().replace(/\s+/g, ' ');
    }

    function validateMembers() {
      const inputs = document.querySelectorAll(".player-input");
      const values = [];
      let hasError = false;

      // Ambil nama captain dari Step 1
      const captainName = normalizeName(document.getElementById("captain_name").value);

      inputs.forEach(input => {
        const val = normalizeName(input.value);
        if (val) {
          input.classList.remove("is-invalid");

          // Cek apakah sama dengan nama captain
          if (val === captainName) {
            input.classList.add("is-invalid");
            input.nextElementSibling.innerText = "This player name matches the captain. Please remove it.";
            hasError = true;
          }
          // Cek duplikat antar pemain
          else if (values.includes(val)) {
            input.classList.add("is-invalid");
            input.nextElementSibling.innerText = "This player name is duplicated.";
            hasError = true;
          } else {
            values.push(val);
          }
        }
      });

      if (!hasError) nextStep(2);
    }

    function addMember() {
      const container = document.getElementById("membersContainer");
      const count = container.querySelectorAll(".player-input").length + 1;
      if (count > 12) return alert("Maximum 12 players allowed.");

      const div = document.createElement("div");
      div.className = "mb-3";
      div.innerHTML = `
        <label for="player_${count}" class="form-label">Player ${count} Name</label>
        <input type="text" name="player_name[]" id="player_${count}" class="form-control player-input"
          placeholder="Full Name" autocomplete="name" required>
        <div class="invalid-feedback">Player name must not be the same as another player or the captain.</div>
      `;
      container.appendChild(div);
    }
  </script>
</section>


                    <!-- Step 3 -->
                    <section id="step3" class="p-5">
                        <h4>Contact Information</h4>
                        <div class="mb-3">
                            <label for="contact_phone" class="form-label">Contact Phone</label>
                            <input type="tel" required name="contact_phone" id="contact_phone"
                                class="form-control" autocomplete="tel"
                                placeholder="966 5XXXXXXXX">
                        </div>
                        <div class="mb-3">
                            <label for="contact_email" class="form-label">Contact Email</label>
                            <input type="email" required name="contact_email" id="contact_email"
                                class="form-control" autocomplete="email"
                                placeholder="example@email.com">
                        </div>

                        <h4>Additional Details</h4>
                        <div class="mb-3">
                            <label for="club" class="form-label">Club</label>
                            <select name="club" id="club" class="form-select" title="Select your club" required>
                                <option value="">-- Select Club --</option>
                                <?php foreach ($centers as $center): ?>
                                    <option value="<?= htmlspecialchars($center['name']) ?>"><?= htmlspecialchars($center['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input required type="text" name="city" id="city" class="form-control"
                                autocomplete="address-level2" placeholder="Enter your city">
                        </div>
                        <h4>Padel Experience</h4>

                        <div class="mb-3">
                            <label for="experience" class="form-label">How much time your team member played padel</label>
                            <select name="experience" id="experience" class="form-select" required>
                                <option value="">-- Select Duration --</option>
                                <option value="1 month">1 month</option>
                                <option value="2 months">2 months</option>
                                <option value="3 months">3 months</option>
                                <option value="6 months">6 months</option>
                                <option value="1 year">1 year</option>
                                <option value="2 years plus">+2 years</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="competed" class="form-label">Have you ever competed?</label>
                            <select name="competed" id="competed" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="regional" class="form-label">Do you compete in regional tournaments?</label>
                            <select name="regional" id="regional" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                        </div>


                        <div class="mb-3">
                            <label for="level" class="form-label">Level</label>
                            <select name="level" id="level" class="form-select" title="Select your skill level" required>
                                <option value="" disabled selected>-- Select Level --</option>

                                <option value="Advanced B+">Advanced: B+ (4.5-4)</option>
                                <option value="Advanced B">Advanced: B (4-4.5)</option>
                                <option value="Advanced B-">Advanced: B- (3.5-4)</option>
                                <option value="U.Intermediate C+">U.Intermediate: C+ (3-3.5)</option>
                                <option value="Intermediate C">Intermediate: C (2.5-3)</option>
                                <option value="L. Intermediate C-">L. Intermediate: C- (2-2.5)</option>
                                <option value="U. Beginner D+">U. Beginner: D+ (1.5-2)</option>
                                <option value="Beginner D">Beginner: D (1-1.5)</option>
                                <option value="L. Beginner D-">L. Beginner: D- (&lt;1)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"
                                placeholder="Any additional details..."></textarea>
                        </div>
                        <button type="button" class="btn" style="background-color:grey; color:white" onclick="prevStep(3)">Back</button>
                        <button type="button" class="btn btn-next" style="background-color:#f3e6b6;" onclick="nextStep(3)">Next</button>
                    </section>

                   <!-- Step 4 -->
<section id="step4" class="p-5 mb-5 mt-5">
    <h4>Create Account Access</h4>
    <p style="font-size:14px; color:gray;">
        This account access will be used by the team captain to manage and organize their team.
    </p>

    <!-- USERNAME INPUT -->
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" id="username" class="form-control"
            placeholder="Choose a username" autocomplete="username" required>
        <div id="usernameError" class="text-danger mt-1" style="display:none;">
            This username is already in use. Please choose a different one.
        </div>
    </div>

    <!-- PASSWORD INPUT + CHECKLIST -->
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control"
            placeholder="Create a strong password" autocomplete="new-password" required maxlength="20">

        <div id="passwordStrengthText" class="mt-1" style="font-size: 13px; color: gray;">Enter a password...</div>
        <div class="progress mt-1" style="height: 5px;">
            <div id="passwordStrengthBar" class="progress-bar bg-danger" style="width: 0%;"></div>
        </div>

        <ul id="passwordCriteria" class="mt-2" style="font-size: 13px; list-style: none; padding-left: 0;">
            <li id="criteria-length" class="text-danger">❌ Minimum 8 characters (Max 20)</li>
            <li id="criteria-uppercase" class="text-danger">❌ At least 1 uppercase letter</li>
            <li id="criteria-number" class="text-danger">❌ At least 1 number</li>
            <li id="criteria-symbol" class="text-danger">❌ At least 1 special character (!@#$%^&*)</li>
        </ul>
    </div>

    <button type="button" class="btn" style="background-color:grey; color:white" onclick="prevStep(4)">Back</button>
    <button type="button" class="btn btn-next" style="background-color:#f3e6b6;" onclick="validateUsernameBeforeNext()">Next</button>
</section>

<script>
// Existing usernames passed from PHP
const existingUsernames = <?= json_encode($existingUsernames ?? []) ?>;

// PASSWORD STRENGTH + CHECKLIST
document.getElementById('password').addEventListener('input', function () {
    const password = this.value;
    const strengthText = document.getElementById('passwordStrengthText');
    const strengthBar = document.getElementById('passwordStrengthBar');

    let strength = 0;
    const lengthOK = password.length >= 8 && password.length <= 20;
    const upperOK  = /[A-Z]/.test(password);
    const numberOK = /\d/.test(password);
    const symbolOK = /[\W_]/.test(password);

    updateCriteria('criteria-length', lengthOK, "Minimum 8 characters (Max 20)");
    updateCriteria('criteria-uppercase', upperOK, "At least 1 uppercase letter");
    updateCriteria('criteria-number', numberOK, "At least 1 number");
    updateCriteria('criteria-symbol', symbolOK, "At least 1 special character (!@#$%^&*)");

    if (lengthOK) strength += 25;
    if (upperOK) strength += 25;
    if (numberOK) strength += 25;
    if (symbolOK) strength += 25;

    strengthBar.style.width = strength + "%";
    if (strength <= 25) {
        strengthBar.className = "progress-bar bg-danger";
        strengthText.textContent = "Very weak — easy to guess";
        strengthText.style.color = "red";
    } else if (strength <= 50) {
        strengthBar.className = "progress-bar bg-warning";
        strengthText.textContent = "Weak — could be stronger";
        strengthText.style.color = "orange";
    } else if (strength <= 75) {
        strengthBar.className = "progress-bar bg-info";
        strengthText.textContent = "Good — but can improve";
        strengthText.style.color = "blue";
    } else {
        strengthBar.className = "progress-bar bg-success";
        strengthText.textContent = "Strong password ✔️";
        strengthText.style.color = "green";
    }
});

function updateCriteria(id, condition, text) {
    const el = document.getElementById(id);
    if (condition) {
        el.classList.remove("text-danger");
        el.classList.add("text-success");
        el.textContent = "✅ " + text;
    } else {
        el.classList.add("text-danger");
        el.classList.remove("text-success");
        el.textContent = "❌ " + text;
    }
}

// FINAL VALIDATION BEFORE NEXT
function validateUsernameBeforeNext() {
    const usernameInput = document.getElementById('username');
    const username = usernameInput.value.trim().toLowerCase();
    const userError = document.getElementById('usernameError');

    const password = document.getElementById('password').value;
    const lengthOK = password.length >= 8 && password.length <= 20;
    const upperOK  = /[A-Z]/.test(password);
    const numberOK = /\d/.test(password);
    const symbolOK = /[\W_]/.test(password);

    if (existingUsernames.includes(username)) {
        userError.style.display = "block";
        usernameInput.focus();
        return;
    } else {
        userError.style.display = "none";
    }

    if (!(lengthOK && upperOK && numberOK && symbolOK)) {
        alert("Password does not meet all security requirements. Please review the criteria.");
        return;
    }

    nextStep(4);
}
</script>



                    <script>
                        function validateUsernameBeforeNext() {
                            const username = document.getElementById("username").value.trim();
                            const errorDiv = document.getElementById("usernameError");
                            const input = document.getElementById("username");
                            const nextBtn = document.querySelector('#step4 .btn-next');

                            if (!username) {
                                errorDiv.textContent = "Please enter a username.";
                                errorDiv.style.display = "block";
                                input.classList.add("is-invalid");
                                input.focus();
                                return;
                            }

                            // Show checking indicator
                            nextBtn.disabled = true;
                            nextBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Checking...';

                            // Check with server
                            fetch("?check_username=" + encodeURIComponent(username))
                                .then(res => {
                                    if (!res.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return res.json();
                                })
                                .then(data => {
                                    if (data.exists) {
                                        errorDiv.textContent = "Username already exists. Please choose a different one.";
                                        errorDiv.style.display = "block";
                                        input.classList.add("is-invalid");
                                        input.focus();

                                        // Show alert for immediate attention
                                        alert("Username '" + username + "' is already taken. Please choose a different username.");
                                    } else {
                                        errorDiv.style.display = "none";
                                        input.classList.remove("is-invalid");
                                        nextStep(4); // Continue to next step if valid
                                    }
                                })
                                .catch(err => {
                                    console.error('Username check error:', err);
                                    errorDiv.textContent = "Error checking username availability. Please try again.";
                                    errorDiv.style.display = "block";
                                    input.classList.add("is-invalid");
                                    alert("Network error while checking username. Please check your connection and try again.");
                                })
                                .finally(() => {
                                    // Reset button
                                    nextBtn.disabled = false;
                                    nextBtn.innerHTML = 'Next';
                                });
                        }
                    </script>

                    <!-- Step 5 - Modified to show immediate payment -->
                    <section id="step5" class="p-5">
                        <h4>Complete Registration</h4>
                        <p class="text-muted" style="font-size:14px;">
                            Click <strong>Complete Registration & Pay</strong> to proceed to the secure payment form.
                        </p>

                        <div class="alert alert-success">
                            <h6><i class="bi bi-credit-card me-2"></i>Payment Details</h6>
                            <p class="mb-1">Tournament: <?= htmlspecialchars($tournament['name'] ?? 'Tournament Registration') ?></p>
                            <p class="mb-0">Amount: <strong><?= getFormattedPaymentAmount() ?></strong>
                                <?php if (($_ENV['MOYASAR_TEST_MODE'] ?? 'false') === 'true'): ?>
                                    <br><small class="text-muted">(Test Mode - Use test card: 4111111111111111)</small>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            You will be directed to secure payment form. Your team data will be saved only after successful payment.
                        </div>

                        <button type="button" class="btn" style="background-color:grey; color:white" onclick="prevStep(5)">Back</button>
                        <button type="button" class="btn" style="background-color:#28a745; color:white;" onclick="submitForm()">
                            <i class="bi bi-credit-card me-2"></i>Complete Registration & Pay
                        </button>
                    </section>
                </form>
            </div>
        <?php endif; ?>
        </div>

        <?php view('partials.footer'); ?>

        <!-- Scroll to Top Button -->
        <button id="scrollTopBtn" title="Go to top">↑</button>

        <script>
            document.addEventListener("keydown", function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    const activeStep = document.querySelector("section.active");
                    const nextBtn = activeStep.querySelector(".btn-next");
                    if (nextBtn) nextBtn.click();
                }
            });

            let memberCount = 6;

            function nextStep(step) {
                const currentStep = document.querySelector(`#step${step}`);
                const inputs = currentStep.querySelectorAll("input[required], select[required], textarea[required]");

                let valid = true;
                inputs.forEach(input => {
                    // Check if the input is visible and focusable
                    if (input.offsetParent !== null && input.hasAttribute("required") && !input.value.trim()) {
                        valid = false;
                        input.classList.add("is-invalid");

                        // Ensure the input can be focused
                        if (input.offsetParent === null) {
                            console.warn(`Input ${input.name} is not focusable`);
                        }
                    } else {
                        input.classList.remove("is-invalid");
                    }
                });

                if (!valid) {
                    // Focus on the first invalid field that is visible
                    const firstInvalid = currentStep.querySelector("input.is-invalid, select.is-invalid, textarea.is-invalid");
                    if (firstInvalid && firstInvalid.offsetParent !== null) {
                        firstInvalid.focus();
                    }

                    alert("Please fill in all required fields before proceeding.");
                    return;
                }

                // Hide current step and show next step
                currentStep.classList.remove('active');
                const next = document.querySelector(`#step${step+1}`);
                if (next) {
                    next.classList.add('active');
                }
            }

            function addMember() {
                if (memberCount < 12) {
                    memberCount++;
                    const div = document.createElement('div');
                    div.className = 'mb-3';
                    div.innerHTML = `<label for="player_${memberCount}" class="form-label">Player ${memberCount} Name</label>
                         <input type="text" name="player_name[]" id="player_${memberCount}" class="form-control" autocomplete="name">`;
                    document.getElementById('membersContainer').appendChild(div);
                }
            }

            function prevStep(step) {
                document.querySelector(`#step${step}`).classList.remove('active');
                const prev = document.querySelector(`#step${step-1}`);
                prev.classList.add('active');
            }

            function validateAndSubmit() {
                // Validate all steps before submission
                let allValid = true;
                let firstInvalidStep = null;

                // Check all steps from 1 to 4
                for (let stepNum = 1; stepNum <= 4; stepNum++) {
                    const step = document.querySelector(`#step${stepNum}`);
                    const inputs = step.querySelectorAll("input[required], select[required], textarea[required]");
                    let stepValid = true;

                    inputs.forEach(input => {
                        // Only validate if input has a value to trim (not for selects)
                        const isEmpty = input.type === 'select-one' ? !input.value : !input.value.trim();

                        if (isEmpty) {
                            stepValid = false;
                            allValid = false;
                            input.classList.add("is-invalid");

                            // Mark first invalid step
                            if (!firstInvalidStep) {
                                firstInvalidStep = stepNum;
                            }
                        } else {
                            input.classList.remove("is-invalid");
                        }
                    });
                }

                if (!allValid) {
                    // Navigate to the first step with invalid fields
                    if (firstInvalidStep) {
                        // Hide current step
                        document.querySelector('section.active').classList.remove('active');
                        // Show the step with invalid fields
                        document.querySelector(`#step${firstInvalidStep}`).classList.add('active');
                    }

                    alert(`Please fill in all required fields before proceeding to payment. Check step ${firstInvalidStep}.`);
                    return false;
                }

                // All validations passed, allow form submission
                return true;
            }

            function submitForm() {
                // First validate all steps
                if (validateAndSubmit()) {
                    // Show loading indicator
                    const submitBtn = document.querySelector('button[onclick="submitForm()"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creating Account...';

                    // Add form submission error handling
                    const form = document.getElementById('regForm');

                    // Create a promise to handle form submission
                    const formData = new FormData(form);

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (response.redirected) {
                                // Success - redirect to payment
                                window.location.href = response.url;
                            } else {
                                return response.text();
                            }
                        })
                        .then(html => {
                            if (html) {
                                // Error occurred, reload page to show error message
                                document.body.innerHTML = html;

                                // Scroll to top to show error
                                window.scrollTo(0, 0);
                            }
                        })
                        .catch(error => {
                            console.error('Form submission error:', error);
                            alert('Network error occurred. Please check your connection and try again.');

                            // Reset button
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        });
                }
                // If validation fails, validateAndSubmit will handle showing errors
            }

            // Scroll to top functionality
            const scrollBtn = document.getElementById("scrollTopBtn");

            window.onscroll = function() {
                if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                    scrollBtn.style.display = "block";
                } else {
                    scrollBtn.style.display = "none";
                }
            };

            scrollBtn.addEventListener("click", function() {
                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });
            });

            // Debug function to check form state
            function debugFormState() {
                console.log("=== Form Debug Information ===");

                for (let stepNum = 1; stepNum <= 5; stepNum++) {
                    const step = document.querySelector(`#step${stepNum}`);
                    const isActive = step.classList.contains('active');
                    const inputs = step.querySelectorAll("input, select, textarea");

                    console.log(`Step ${stepNum}: ${isActive ? 'ACTIVE' : 'hidden'}`);

                    inputs.forEach(input => {
                        const isRequired = input.hasAttribute('required');
                        const isVisible = input.offsetParent !== null;
                        const hasValue = input.value && input.value.trim() !== '';
                        const isInvalid = input.classList.contains('is-invalid');

                        if (isRequired || isInvalid) {
                            console.log(`  - ${input.name || input.id}: required=${isRequired}, visible=${isVisible}, hasValue=${hasValue}, invalid=${isInvalid}, value="${input.value}"`);
                        }
                    });
                }

                // Also check form state
                const form = document.getElementById('regForm');
                console.log("Form novalidate:", form.hasAttribute('novalidate'));
            }

            // Add global error handler for form validation
            document.addEventListener('invalid', function(e) {
                e.preventDefault();
                console.warn('Form validation error:', e.target.name, 'is not focusable or invalid');
                debugFormState();
            }, true);
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>