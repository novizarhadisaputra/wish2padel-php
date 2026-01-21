<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Payment - Wish2Padel</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
        <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Moyasar Form CSS -->
    <link rel="stylesheet" href="https://cdn.moyasar.com/mpf/1.14.0/moyasar.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">

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
                        <a href="<?= asset('tournament') ?>" class="btn btn-primary">
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
                            <a href="<?= asset('myteam') ?>" class="btn btn-success btn-lg me-2">
                                <i class="bi bi-house me-2 mb-2"></i>Go to My Team
                            </a>
                            <a href="<?= asset('tournament') ?>" class="btn btn-outline-primary mt-4">
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
                            <a href="<?= asset('dashboard') ?>" class="btn btn-gold">
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
                                <span class="info-value"><?= $amount ?> SAR</span>
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
                            <a href="<?= asset('regis') ?>" class="btn btn-primary">
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
    $js_condition = ($payment_data && $team_id > 0 && $tournament_id > 0);
    ?>

    <?php if ($js_condition): ?>


        <script>
            // Payment configuration
            const PaymentConfig = {
                teamId: <?= json_encode($team_id) ?>,
                tournamentId: <?= json_encode($tournament_id) ?>,
                amount: <?= json_encode($payment_data['amount']) ?>,
                currency: <?= json_encode($payment_data['currency']) ?>,
                publishableKey: <?= json_encode($payment_data['publishable_key']) ?>,
                teamName: <?= json_encode($team['team_name'] ?? 'Unknown Team') ?>,
                callbackUrl: <?= json_encode(asset('payment/verify?team_id=' . $team_id . '&tournament_id=' . $tournament_id)) ?>
            };

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
                    callback_url: PaymentConfig.callbackUrl, // Use fully qualified URL from PHP
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
            }
        </script>
    <?php endif; ?>
</body>

</html>
