<?php

namespace App\Controllers;

use App\Core\SimplePaymentSystem;
use Exception;

class PaymentController
{
    public function show()
    {
        $conn = getDBConnection();
        $team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
        $tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;

        $error = '';
        $success = '';
        $payment_data = null;
        $team = null;
        $tournament = null;
        $url_status = $_GET['status'] ?? null;
        $url_error = $_GET['error'] ?? null;

        if ($team_id <= 0 || $tournament_id <= 0) {
            $error = "Missing required parameters. Please access this page through proper registration flow.";
        } else {
            try {
                $paymentSystem = new SimplePaymentSystem();

                // Get team info
                $stmt = $conn->prepare("SELECT team_name, captain_name, captain_email, captain_phone FROM team_info WHERE id = ? AND tournament_id = ?");
                $stmt->bind_param("ii", $team_id, $tournament_id);
                $stmt->execute();
                $team = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$team) {
                    throw new Exception("Team not found for this tournament. Please complete registration first.");
                }

                // Check if team account exists
                $stmt = $conn->prepare("SELECT username FROM team_account WHERE team_id = ?");
                $stmt->bind_param("i", $team_id);
                $stmt->execute();
                $account_result = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $has_account = !empty($account_result);

                // Get tournament info
                $stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ?");
                $stmt->bind_param("i", $tournament_id);
                $stmt->execute();
                $tournament = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$tournament) {
                    throw new Exception("Tournament not found");
                }

                // Check current payment status
                $is_team_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);

                if ($is_team_paid) {
                    $payment_info = $paymentSystem->getTeamPaymentInfo($team_id, $tournament_id);
                    if ($has_account) {
                        $success = "Your team has already completed payment for this tournament and has an active account.";
                    } else {
                        $success = "Your team has already completed payment for this tournament.";
                    }
                    // getDynamicPaymentAmount() is defined in config.php loaded via SimplePaymentSystem or index.php
                    $amount = function_exists('getDynamicPaymentAmount') ? getDynamicPaymentAmount() : 0;
                    $currency = function_exists('getDynamicPaymentCurrency') ? getDynamicPaymentCurrency() : 'SAR';

                    $payment_data = [
                        'status' => 'already_paid',
                        'payment_id' => $payment_info['payment_id'] ?? 'N/A',
                        'amount' => $amount,
                        'currency' => $currency,
                        'paid_date' => $payment_info['created_at'] ?? null,
                        'team_name' => $team['team_name'] ?? 'Unknown Team',
                        'has_account' => $has_account
                    ];
                } else {
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
                        $payment_info = null;

                        $amount = function_exists('getDynamicPaymentAmount') ? getDynamicPaymentAmount() : 0;
                        $currency = function_exists('getDynamicPaymentCurrency') ? getDynamicPaymentCurrency() : 'SAR';

                        $payment_data = [
                            'status' => 'already_paid',
                            'payment_id' => $payment_info['payment_id'] ?? 'N/A',
                            'amount' => $amount,
                            'currency' => $currency,
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
            }
        }

        $amount = function_exists('getDynamicPaymentAmount') ? getDynamicPaymentAmount() : 0;

        view('payment.show', compact('error', 'success', 'payment_data', 'team', 'tournament', 'url_status', 'url_error', 'team_id', 'tournament_id', 'amount'));
    }

    public function verify()
    {
        $conn = getDBConnection();
        $paymentSystem = new SimplePaymentSystem();

        $error = '';
        $success = '';
        $payment_status = 'pending';

        $payment_id = $_GET['payment_id'] ?? $_GET['id'] ?? null;
        $status = $_GET['status'] ?? null;
        $message = $_GET['message'] ?? '';
        $team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
        $tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;

        if ($team_id <= 0 || $tournament_id <= 0) {
            $error = "Missing required parameters. Team ID and Tournament ID are required.";
        } else {
            try {
                // Verify team exists
                $stmt = $conn->prepare("SELECT team_name, captain_name, captain_email FROM team_info WHERE id = ? AND tournament_id = ?");
                $stmt->bind_param("ii", $team_id, $tournament_id);
                $stmt->execute();
                $team = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$team) {
                    throw new Exception("Team not found for this tournament.");
                }

                $is_already_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);

                if ($is_already_paid) {
                    $success = "This team has already completed payment for this tournament.";
                } elseif ($payment_id) {
                    $verification_result = $paymentSystem->verifyPaymentWithMoyasar($payment_id);

                    if ($verification_result['status'] === 'success') {
                        $payment_status = $verification_result['payment_status'];
                        $payment_details = $verification_result['payment_data'];
                        $verified_payment_id = $payment_details['id'] ?? $payment_id;

                        if ($payment_status === 'paid' || $payment_status === 'captured') {
                            $result = $this->updateTeamPaymentRecord($conn, $team_id, $tournament_id, $verified_payment_id, $payment_details);

                            if ($result) {
                                $_SESSION['team_id'] = $team_id;
                                $success = "Payment successful! Your team registration is now complete.";
                            } else {
                                $refund_result = $paymentSystem->refundPayment(
                                    $verified_payment_id,
                                    null,
                                    "Automatic refund: Database update failed after successful payment"
                                );
                                $error = "Payment was successful but a system error occurred. Refund ID: " . ($refund_result['refund_id'] ?? 'Processing');
                            }
                        } else if ($payment_status === 'pending' || $payment_status === 'initiated') {
                            $payment_status = 'pending';
                            $error = "Payment is still being processed. Please check back in a few minutes.";
                        } else {
                            $error = "Payment verification failed. Status: " . $payment_status;
                        }
                    } else {
                        // Fallback logic
                        if ($status === 'paid' && $message === 'APPROVED') {
                             $payment_details = [
                                'id' => $payment_id,
                                'status' => 'paid',
                                'amount' => 10000, // Hardcoded fallback or constant
                                'currency' => 'SAR', // Hardcoded fallback
                                'source' => ['type' => 'unknown'],
                                'callback_verified' => true,
                                'api_verification_failed' => true
                            ];
                            // Try to get amount from config/constant via system if possible
                            // Assuming MOYASAR_AMOUNT global constant exists from require 'config.php' in legacy
                            if (defined('MOYASAR_AMOUNT')) $payment_details['amount'] = MOYASAR_AMOUNT;
                            if (defined('MOYASAR_CURRENCY')) $payment_details['currency'] = MOYASAR_CURRENCY;

                            $result = $this->updateTeamPaymentRecord($conn, $team_id, $tournament_id, $payment_id, $payment_details);

                            if ($result) {
                                $_SESSION['team_id'] = $team_id;
                                $success = "Payment successful! Your team registration is now complete.";
                            } else {
                                $refund_result = $paymentSystem->refundPayment($payment_id, null, "Automatic refund: Database update failed");
                                $error = "Payment successful but system error occurred. Refund ID: " . ($refund_result['refund_id'] ?? 'Processing');
                            }
                        } else {
                            $error = $verification_result['message'] ?? 'Failed to verify payment with Moyasar';
                        }
                    }
                } else {
                    $error = "No payment ID provided in callback.";
                }

            } catch (Exception $e) {
                $error = "System error: " . $e->getMessage();
            }
        }

        $redirect_url = asset("payment?team_id=$team_id&tournament_id=$tournament_id");

        if ($success) {
            $redirect_url .= "&status=success";
        } elseif ($error) {
            $redirect_url .= "&status=failed&error=" . urlencode($error);
        }

        header("Location: $redirect_url");
        exit;
    }

    private function getPaymentMethod($payment_details) {
        if (!$payment_details || !isset($payment_details['source'])) {
            return 'moyasar';
        }
        $source_type = $payment_details['source']['type'] ?? 'unknown';
        switch ($source_type) {
            case 'applepay': return 'apple_pay';
            case 'creditcard':
            case 'credit_card': return 'credit_card';
            case 'sadad': return 'sadad';
            case 'stc_pay': return 'stc_pay';
            default: return 'moyasar';
        }
    }

    private function updateTeamPaymentRecord($conn, $team_id, $tournament_id, $payment_id, $payment_details = null) {
        try {
            $stmt = $conn->prepare("SELECT id FROM payment_transactions WHERE team_id = ? AND tournament_id = ? AND payment_id = ?");
            $stmt->bind_param("iis", $team_id, $tournament_id, $payment_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $stmt = $conn->prepare("UPDATE payment_transactions SET
                    status = 'paid',
                    payment_data = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE team_id = ? AND tournament_id = ? AND payment_id = ?");
                $payment_data_json = $payment_details ? json_encode($payment_details) : null;
                $stmt->bind_param("siis", $payment_data_json, $team_id, $tournament_id, $payment_id);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            } else {
                $payment_data_json = $payment_details ? json_encode($payment_details) : null;
                // Use constants if available, else try to get from details
                $amount = ($payment_details && isset($payment_details['amount'])) ? $payment_details['amount'] / 100 : 0;
                $currency = ($payment_details && isset($payment_details['currency'])) ? $payment_details['currency'] : 'SAR';

                // Fallback to global constants if 0
                if ($amount == 0 && defined('MOYASAR_AMOUNT')) $amount = MOYASAR_AMOUNT / 100;
                if ($currency == 'SAR' && defined('MOYASAR_CURRENCY')) $currency = MOYASAR_CURRENCY;

                $payment_method = $this->getPaymentMethod($payment_details);

                $stmt = $conn->prepare("INSERT INTO payment_transactions
                    (team_id, tournament_id, payment_id, amount, currency, status, payment_method, payment_data, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 'paid', ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $stmt->bind_param("iisdsss", $team_id, $tournament_id, $payment_id, $amount, $currency, $payment_method, $payment_data_json);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        } catch (Exception $e) {
            return false;
        }
    }
}
