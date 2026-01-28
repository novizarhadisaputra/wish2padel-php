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
                if (!$conn) throw new Exception("Database connection unavailable.");
                
                $paymentSystem = new SimplePaymentSystem();
                $stmt = $conn->prepare("SELECT team_name, captain_name, captain_email, captain_phone FROM team_info WHERE id = ? AND tournament_id = ?");
                if ($stmt) {
                    $stmt->bind_param("ii", $team_id, $tournament_id);
                    $stmt->execute();
                    $team = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }

                if (!$team) throw new Exception("Team not found for this tournament.");

                $stmt = $conn->prepare("SELECT username FROM team_account WHERE team_id = ?");
                $has_account = false;
                if ($stmt) {
                    $stmt->bind_param("i", $team_id);
                    $stmt->execute();
                    $has_account = !empty($stmt->get_result()->fetch_assoc());
                    $stmt->close();
                }

                $stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $tournament_id);
                    $stmt->execute();
                    $tournament = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }

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
                $error = "Payment error: " . $e->getMessage();
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
        $team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
        $tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;

        if ($team_id <= 0 || $tournament_id <= 0) {
            $error = "Missing parameters.";
        } else {
            try {
                if (!$payment_id) throw new Exception("No payment ID provided.");

                // Verify with Moyasar
                $verification = $paymentSystem->verifyPaymentWithMoyasar($payment_id);
                
                if ($verification['status'] === 'success') {
                    $payment_status = $verification['payment_status'];
                    $payment_data = $verification['payment_data'];
                    
                    // Security Check: Validate Metadata matches GET parameters
                    // This prevents users from using a valid payment_id from another team to "verify" their own
                    $meta_team_id = $payment_data['metadata']['team_id'] ?? null;
                    $meta_tour_id = $payment_data['metadata']['tournament_id'] ?? null;

                    if ($meta_team_id && (int)$meta_team_id !== (int)$team_id) {
                         error_log("Payment Validation Warning: Team ID mismatch. GET: $team_id, META: $meta_team_id");
                         $team_id = (int)$meta_team_id;
                    }
                    
                    if ($meta_tour_id && (int)$meta_tour_id !== (int)$tournament_id) {
                         $tournament_id = (int)$meta_tour_id;
                    }

                    // Map Status
                    $dbStatus = 'pending';
                    if ($payment_status === 'paid' || $payment_status === 'captured') $dbStatus = 'paid';
                    elseif ($payment_status === 'failed' || $payment_status === 'canceled' || $payment_status === 'cancelled') $dbStatus = 'failed';
                    elseif ($payment_status === 'refunded') $dbStatus = 'refunded';
                    
                    // Upsert Transaction
                    if ($paymentSystem->createPaymentTransaction($team_id, $tournament_id, $payment_id, $dbStatus, $payment_data)) {
                        if ($dbStatus === 'paid') {
                            $_SESSION['team_id'] = $team_id;
                            $success = "Payment successful!";
                        } else {
                            $error = "Payment status: " . $payment_status;
                        }
                    } else {
                        $error = "Failed to save payment record.";
                    }
                } else {
                    $error = "Verification failed: " . ($verification['message'] ?? 'Unknown error');
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }

        $redirect_url = asset("payment?team_id=$team_id&tournament_id=$tournament_id");
        if ($success) $redirect_url .= "&status=success";
        elseif ($error) $redirect_url .= "&status=failed&error=" . urlencode($error);
        redirect($redirect_url);
    }

    public function webhook()
    {
        header('Content-Type: application/json');
        
        $input = file_get_contents('php://input');
        // Log raw webhook
        error_log("Moyasar Webhook Raw: " . $input);
        
        $payload = json_decode($input, true);
        if (!$payload) $payload = $_POST; // Fallback
        
        $payment_id = $payload['id'] ?? null;

        if (!$payment_id) {
            http_response_code(400); 
            echo json_encode(['status'=>'error', 'message'=>'Missing ID']);
            exit;
        }

        try {
            $paymentSystem = new SimplePaymentSystem();
            
            // 1. Verify with Moyasar (Always source of truth)
            $verification = $paymentSystem->verifyPaymentWithMoyasar($payment_id);
            if ($verification['status'] !== 'success') {
                http_response_code(400);
                echo json_encode(['status'=>'error', 'message'=>'Could not verify payment with Moyasar']);
                exit;
            }
            
            $data = $verification['payment_data'];
            $payment_status = $verification['payment_status']; // paid, failed, etc.
            
            // Map Status
            $dbStatus = 'pending';
            if ($payment_status === 'paid' || $payment_status === 'captured') $dbStatus = 'paid';
            elseif ($payment_status === 'failed' || $payment_status === 'canceled' || $payment_status === 'cancelled') $dbStatus = 'failed';
            elseif ($payment_status === 'refunded') $dbStatus = 'refunded';

            // 2. Extract metadata
            $meta = $data['metadata'] ?? [];
            $team_id = $meta['team_id'] ?? null;
            $t_id = $meta['tournament_id'] ?? null;
            
            // 3. Upsert Transaction
            // If team_id/tournament_id are present, use them.
            // If not present in metadata (e.g. older payments?), try to find mostly matching record?
            // For now, require metadata.
            
            if ($team_id && $t_id) {
                 $result = $paymentSystem->createPaymentTransaction((int)$team_id, (int)$t_id, $payment_id, $dbStatus, $data);
                 echo json_encode(['status'=>'success', 'updated'=>$result, 'db_status'=>$dbStatus]);
            } else {
                 // Try to Find existing record by payment_id to get team_id
                 $conn = getDBConnection();
                 $stmt = $conn->prepare("SELECT team_id, tournament_id FROM payment_transactions WHERE payment_id = ?");
                 $stmt->bind_param("s", $payment_id);
                 $stmt->execute();
                 $res = $stmt->get_result()->fetch_assoc();
                 $stmt->close();
                 
                 if ($res) {
                     $result = $paymentSystem->createPaymentTransaction((int)$res['team_id'], (int)$res['tournament_id'], $payment_id, $dbStatus, $data);
                     echo json_encode(['status'=>'success', 'updated'=>$result, 'note'=>'Used local team_id']);
                 } else {
                     error_log("Webhook Error: Metadata missing and local record not found for Payment ID $payment_id");
                     http_response_code(404);
                     echo json_encode(['status'=>'ignored', 'message'=>'Metadata missing and not found locally']);
                 }
            }
            
        } catch (Exception $e) {
            error_log("Webhook Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
        }
    }


}
