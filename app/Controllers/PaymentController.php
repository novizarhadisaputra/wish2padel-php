<?php

namespace App\Controllers;

use App\Core\SimplePaymentSystem;
use Exception;

class PaymentController
{
    // ... (Existing methods show, verify) ...
    // I will include them to keep file complete.

    public function show()
    {
        $conn = getDBConnection();
        $team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
        $tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;

        $error = ''; $success = ''; $payment_data = null; $team = null; $tournament = null;
        $url_status = $_GET['status'] ?? null; $url_error = $_GET['error'] ?? null;

        if ($team_id <= 0 || $tournament_id <= 0) {
            $error = "Missing required parameters.";
        } else {
            try {
                $paymentSystem = new SimplePaymentSystem();
                $stmt = $conn->prepare("SELECT team_name, captain_name, captain_email, captain_phone FROM team_info WHERE id = ? AND tournament_id = ?");
                $stmt->bind_param("ii", $team_id, $tournament_id);
                $stmt->execute();
                $team = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$team) throw new Exception("Team not found for this tournament.");

                $stmt = $conn->prepare("SELECT username FROM team_account WHERE team_id = ?");
                $stmt->bind_param("i", $team_id);
                $stmt->execute();
                $has_account = !empty($stmt->get_result()->fetch_assoc());
                $stmt->close();

                $stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ?");
                $stmt->bind_param("i", $tournament_id);
                $stmt->execute();
                $tournament = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$tournament) throw new Exception("Tournament not found");

                $is_team_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);
                $amount = function_exists('getDynamicPaymentAmount') ? getDynamicPaymentAmount() : 0;
                $currency = function_exists('getDynamicPaymentCurrency') ? getDynamicPaymentCurrency() : 'SAR';

                if ($is_team_paid) {
                    $payment_info = $paymentSystem->getTeamPaymentInfo($team_id, $tournament_id);
                    $success = $has_account ? "Your team has already completed payment and has an account." : "Your team has already completed payment.";
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
                        $payment_data['message'] = $has_account ? "Complete payment to activate account." : "Complete payment to register.";
                    } elseif ($payment_result['status'] === 'already_paid') {
                        $success = $payment_result['message'];
                        $payment_data = [
                            'status' => 'already_paid',
                            'payment_id' => 'N/A',
                            'amount' => $amount,
                            'currency' => $currency,
                            'paid_date' => null,
                            'team_name' => $team['team_name'],
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
        $error = ''; $success = '';
        $payment_id = $_GET['payment_id'] ?? $_GET['id'] ?? null;
        $status = $_GET['status'] ?? null;
        $message = $_GET['message'] ?? '';
        $team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
        $tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;

        if ($team_id <= 0 || $tournament_id <= 0) {
            $error = "Missing parameters.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT id FROM team_info WHERE id = ? AND tournament_id = ?");
                $stmt->bind_param("ii", $team_id, $tournament_id);
                $stmt->execute();
                if (!$stmt->get_result()->fetch_assoc()) throw new Exception("Team not found.");
                $stmt->close();

                if ($paymentSystem->isTeamPaid($team_id, $tournament_id)) {
                    $success = "Team already paid.";
                } elseif ($payment_id) {
                    $verification_result = $paymentSystem->verifyPaymentWithMoyasar($payment_id);
                    if ($verification_result['status'] === 'success') {
                        $payment_status = $verification_result['payment_status'];
                        $payment_details = $verification_result['payment_data'];
                        if ($payment_status === 'paid' || $payment_status === 'captured') {
                            if ($this->updateTeamPaymentRecord($conn, $team_id, $tournament_id, $payment_id, $payment_details)) {
                                $_SESSION['team_id'] = $team_id;
                                $success = "Payment successful!";
                            } else {
                                $paymentSystem->refundPayment($payment_id, null, "DB Error");
                                $error = "System error. Payment refunded.";
                            }
                        } else {
                            $error = "Payment status: " . $payment_status;
                        }
                    } else {
                        // Fallback
                        if ($status === 'paid' && $message === 'APPROVED') {
                             $fallback_details = ['id'=>$payment_id, 'status'=>'paid', 'amount'=>10000, 'currency'=>'SAR', 'source'=>['type'=>'unknown']];
                             if ($this->updateTeamPaymentRecord($conn, $team_id, $tournament_id, $payment_id, $fallback_details)) {
                                 $_SESSION['team_id'] = $team_id;
                                 $success = "Payment successful!";
                             } else {
                                 $error = "System error.";
                             }
                        } else {
                            $error = "Verification failed.";
                        }
                    }
                } else {
                    $error = "No payment ID.";
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
        $redirect_url = asset("payment?team_id=$team_id&tournament_id=$tournament_id");
        if ($success) $redirect_url .= "&status=success";
        elseif ($error) $redirect_url .= "&status=failed&error=" . urlencode($error);
        redirect($redirect_url); // Uses helper if available or header loc
    }

    public function webhook()
    {
        header('Content-Type: application/json');
        http_response_code(200);

        try {
            $payment_system = new SimplePaymentSystem();

            $input = file_get_contents('php://input');
            $payload = json_decode($input, true);
            if (!$payload) $payload = $_POST;
            if (!$payload) $payload = $_GET;

            $payment_id = $payload['id'] ?? null;
            $status = $payload['status'] ?? null;

            if (!$payment_id) {
                echo json_encode(['status'=>'error', 'message'=>'Missing ID']);
                exit;
            }

            // Verify with Moyasar API
            $verified_status = $status;
            try {
                // If payment ID is present, we try to verify/fetch details via API
                // Note: SimplePaymentSystem might not expose a direct "verify and return status" easily without team_id context,
                // but let's check `verifyPaymentWithMoyasar`.
                $res = $payment_system->verifyPaymentWithMoyasar($payment_id);
                if ($res['status'] === 'success') {
                    $verified_status = $res['payment_status'];
                }
            } catch (Exception $e) {
                // Log error
            }

            // Update DB
            // Note: `updatePaymentFromCallback` in SimplePaymentSystem handles looking up the transaction by payment_id
            // We don't need team_id/tournament_id if the transaction exists in DB.
            $result = $payment_system->updatePaymentFromCallback($payment_id, $verified_status, "Webhook Update");

            echo json_encode(['status'=>'success', 'updated'=>$result]);

        } catch (Exception $e) {
            echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
        }
    }

    private function getPaymentMethod($payment_details) {
        $type = $payment_details['source']['type'] ?? 'unknown';
        switch ($type) {
            case 'applepay': return 'apple_pay';
            case 'creditcard': case 'credit_card': return 'credit_card';
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

            $json = $payment_details ? json_encode($payment_details) : null;

            if ($existing) {
                $stmt = $conn->prepare("UPDATE payment_transactions SET status='paid', payment_data=?, updated_at=NOW() WHERE team_id=? AND tournament_id=? AND payment_id=?");
                $stmt->bind_param("siis", $json, $team_id, $tournament_id, $payment_id);
            } else {
                $amount = ($payment_details['amount'] ?? 0) / 100;
                $currency = $payment_details['currency'] ?? 'SAR';
                if ($amount == 0 && defined('MOYASAR_AMOUNT')) $amount = MOYASAR_AMOUNT / 100;
                $method = $this->getPaymentMethod($payment_details);
                $stmt = $conn->prepare("INSERT INTO payment_transactions (team_id, tournament_id, payment_id, amount, currency, status, payment_method, payment_data, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'paid', ?, ?, NOW(), NOW())");
                $stmt->bind_param("iisdsss", $team_id, $tournament_id, $payment_id, $amount, $currency, $method, $json);
            }
            $res = $stmt->execute();
            $stmt->close();
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }
}
