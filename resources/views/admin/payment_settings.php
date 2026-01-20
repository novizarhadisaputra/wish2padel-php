<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p.png') ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Payment Settings - Admin Panel</title>
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body style="background-color: #303030">
    <?php view('partials.navbar'); ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Page Header -->
                <div class="card mb-4" style="background: linear-gradient(135deg, #F3E6B6 0%, #d4c088 100%); border: none;">
                    <div class="card-body text-center">
                        <h2 class="card-title mb-2" style="color: #1a1a1a; font-weight: bold;">
                            <i class="bi bi-credit-card"></i> Payment Settings
                        </h2>
                        <p class="card-text" style="color: #2d2d2d;">Control tournament registration fees and payment configuration. <strong>Settings here take priority over .env values.</strong></p>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$table_exists): ?>
                    <!-- Create Table Form -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning">
                            <h5 class="card-title mb-0"><i class="bi bi-exclamation-triangle"></i> Setup Required</h5>
                        </div>
                        <div class="card-body">
                            <p>The payment settings table doesn't exist yet. Click the button below to create it with default values.</p>
                            <form method="POST" action="<?= asset('admin/payment_settings') ?>">
                                <button type="submit" name="create_table" class="btn btn-warning">
                                    <i class="bi bi-database-add"></i> Create Payment Settings Table
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Payment Settings Form -->
                    <div class="card">
                        <div class="card-header" style="background-color: #1a1a1a; color: #F3E6B6;">
                            <h5 class="card-title mb-0"><i class="bi bi-gear"></i> Payment Configuration</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="paymentSettingsForm" action="<?= asset('admin/payment_settings') ?>">
                                <div class="row">
                                    <!-- Payment Amount -->
                                    <div class="col-md-6 mb-3">
                                        <label for="payment_amount" class="form-label fw-bold">
                                            <i class="bi bi-currency-dollar"></i> Registration Fee (SAR)
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">SAR</span>
                                            <input type="number" class="form-control" id="payment_amount" name="payment_amount"
                                                value="<?= htmlspecialchars($payment_amount) ?>"
                                                min="0.01" step="0.01" required placeholder="e.g., 100.00">
                                            <span class="input-group-text">.00</span>
                                        </div>
                                        <div class="form-text">
                                            <strong>Enter amount in Saudi Riyals.</strong> System will automatically convert to halala for Moyasar payment gateway.<br>
                                            <em>Example: Enter 100.00 for 100 SAR registration fee</em><br>
                                            Current amount: <?= number_format($payment_amount, 2) ?> SAR (will be sent as <?= number_format($payment_amount * 100) ?> halala to Moyasar)
                                        </div>
                                    </div>

                                    <!-- Payment Currency -->
                                    <div class="col-md-6 mb-3">
                                        <label for="payment_currency" class="form-label fw-bold">
                                            <i class="bi bi-globe"></i> Currency
                                        </label>
                                        <select class="form-select" id="payment_currency" name="payment_currency" required>
                                            <option value="SAR" <?= $payment_currency === 'SAR' ? 'selected' : '' ?>>SAR (Saudi Riyal)</option>
                                            <option value="USD" <?= $payment_currency === 'USD' ? 'selected' : '' ?>>USD (US Dollar)</option>
                                            <option value="EUR" <?= $payment_currency === 'EUR' ? 'selected' : '' ?>>EUR (Euro)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Payment Enabled -->
                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="payment_enabled" name="payment_enabled"
                                            <?= $payment_enabled ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="payment_enabled">
                                            <i class="bi bi-toggle-on"></i> Enable Payment System
                                        </label>
                                    </div>
                                    <div class="form-text">When disabled, teams can register without payment</div>
                                </div>

                                <!-- Preview -->
                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="bi bi-eye"></i> Preview</h6>
                                        <p class="card-text mb-1">
                                            <strong>Registration Fee:</strong>
                                            <span id="preview_amount"><?= $payment_currency ?> <?= number_format($payment_amount, 2) ?></span>
                                            <small class="text-muted">(<?= number_format($payment_amount * 100) ?> halala for Moyasar)</small>
                                        </p>
                                        <p class="card-text mb-1">
                                            <strong>Status:</strong>
                                            <span class="badge <?= $payment_enabled ? 'bg-success' : 'bg-secondary' ?>" id="preview_status">
                                                <?= $payment_enabled ? 'Enabled' : 'Disabled' ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_settings" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Update Payment
                                    </button>
                                    <a href="<?= asset('admin/dashboard') ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Current Settings Display -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="card-title mb-0"><i class="bi bi-info-circle"></i> Current Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Setting</th>
                                            <th>Value</th>
                                            <th>Description</th>
                                            <th>Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($settings as $setting): ?>
                                            <tr>
                                                <td><code><?= htmlspecialchars($setting['setting_name']) ?></code></td>
                                                <td><strong><?= htmlspecialchars($setting['setting_value']) ?></strong></td>
                                                <td><?= htmlspecialchars($setting['description']) ?></td>
                                                <td><small class="text-muted">Just updated</small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php view('partials.footer'); ?>

    <script>
        // Live preview updates
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('payment_amount');
            const currencySelect = document.getElementById('payment_currency');
            const enabledCheckbox = document.getElementById('payment_enabled');

            function updatePreview() {
                const amount = parseFloat(amountInput.value) || 0;
                const currency = currencySelect.value;
                const enabled = enabledCheckbox.checked;

                // Amount is already in SAR, convert to halala for Moyasar info
                const halalas = Math.round(amount * 100);

                document.getElementById('preview_amount').innerHTML =
                    currency + ' ' + amount.toFixed(2) + ' <small class="text-muted">(' + halalas.toLocaleString() + ' halala for Moyasar)</small>';

                const statusBadge = document.getElementById('preview_status');
                statusBadge.textContent = enabled ? 'Enabled' : 'Disabled';
                statusBadge.className = 'badge ' + (enabled ? 'bg-success' : 'bg-secondary');
            }
            amountInput.addEventListener('input', updatePreview);
            currencySelect.addEventListener('change', updatePreview);
            enabledCheckbox.addEventListener('change', updatePreview);

            // Form validation
            document.getElementById('paymentSettingsForm').addEventListener('submit', function(e) {
                const amount = parseFloat(amountInput.value);
                if (amount < 0.01) {
                    e.preventDefault();
                    alert('Payment amount must be at least 0.01 SAR');
                    amountInput.focus();
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>