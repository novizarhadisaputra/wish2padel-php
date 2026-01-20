<?php
/**
 * Payment Verification for Existing Teams
 * This handles payment verification for teams that already exist in the database
 */

session_start();
require 'config.php';
require_once 'SimplePaymentSystem.php';

// Initialize variables
$error = '';
$success = '';
$team_id = null;
$payment_status = 'pending';
$payment_id = null;

// Get parameters from Moyasar callback
$payment_id = $_GET['payment_id'] ?? $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;
$message = $_GET['message'] ?? '';
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;

// Debug log
error_log("Payment verification for existing team - Payment ID: $payment_id, Status: $status, Team: $team_id, Tournament: $tournament_id");

// Validate required parameters
if ($team_id <= 0 || $tournament_id <= 0) {
    $error = "Missing required parameters. Team ID and Tournament ID are required.";
} else {
    try {
        $conn = getDBConnection();
        $paymentSystem = new SimplePaymentSystem();
        
        // Verify team exists
        $stmt = $conn->prepare("SELECT team_name, captain_name, captain_email FROM team_info WHERE id = ? AND tournament_id = ?");
        $stmt->bind_param("ii", $team_id, $tournament_id);
        $stmt->execute();
        $team = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$team) {
            throw new Exception("Team not found for this tournament.");
        }
        
        // Check if team has already paid
        $is_already_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);
        
        if ($is_already_paid) {
            $success = "This team has already completed payment for this tournament.";
            error_log("Team $team_id already paid for tournament $tournament_id");
        } elseif ($payment_id) {
            error_log("Verifying payment ID: $payment_id for existing team $team_id");
            
            // Verify payment with Moyasar API
            $verification_result = $paymentSystem->verifyPaymentWithMoyasar($payment_id);
            
            if ($verification_result['status'] === 'success') {
                $payment_status = $verification_result['payment_status'];
                $payment_details = $verification_result['payment_data'];
                
                error_log("Payment verification successful via API - ID: $payment_id, Status: $payment_status");
                
                // Ensure we're using the correct payment ID
                $verified_payment_id = $payment_details['id'] ?? $payment_id;
                
                // If payment is successful, update payment record
                if ($payment_status === 'paid' || $payment_status === 'captured') {
                    $result = updateTeamPaymentRecord($conn, $team_id, $tournament_id, $verified_payment_id, $payment_details);
                    
                    if ($result) {
                        $_SESSION['team_id'] = $team_id;
                        $success = "Payment successful! Your team registration is now complete.";
                        error_log("Payment record updated - Team ID: $team_id, Payment ID: $verified_payment_id");
                    } else {
                        // Payment was successful but database update failed - trigger automatic refund
                        error_log("CRITICAL: Payment successful but database update failed for Payment ID: $verified_payment_id");
                        
                        $refund_result = $paymentSystem->refundPayment(
                            $verified_payment_id, 
                            null, // Full refund
                            "Automatic refund: Database update failed after successful payment"
                        );
                        
                        if ($refund_result['status'] === 'success') {
                            error_log("Automatic refund successful for Payment ID: $verified_payment_id, Refund ID: " . ($refund_result['refund_id'] ?? 'N/A'));
                            $error = "Payment was successful but a system error occurred. Your payment has been automatically refunded. Refund ID: " . ($refund_result['refund_id'] ?? 'Processing') . ". Please try again or contact support.";
                        } else {
                            error_log("Automatic refund FAILED for Payment ID: $verified_payment_id. Error: " . $refund_result['message']);
                            $error = "Payment successful but system error occurred. URGENT: Please contact support immediately with Payment ID: $verified_payment_id - refund failed.";
                        }
                    }
                } else if ($payment_status === 'pending' || $payment_status === 'initiated') {
                    $payment_status = 'pending';
                    error_log("Payment still pending - ID: $payment_id, Status: $payment_status");
                    $error = "Payment is still being processed. Please check back in a few minutes.";
                } else {
                    $error = "Payment verification failed. Status: " . $payment_status . " (Payment ID: $payment_id)";
                    error_log("Payment failed - ID: $payment_id, Status: $payment_status");
                }
            } else {
                // API verification failed, check callback status as fallback
                error_log("API verification failed: " . ($verification_result['message'] ?? 'Unknown error'));
                error_log("Checking callback status as fallback: $status");
                
                if ($status === 'paid' && $message === 'APPROVED') {
                    error_log("FALLBACK: Trusting callback status (paid/APPROVED) for existing team");
                    
                    // Create minimal payment details (default to credit card if source type unknown)
                    $payment_details = [
                        'id' => $payment_id,
                        'status' => 'paid',
                        'amount' => MOYASAR_AMOUNT,
                        'currency' => MOYASAR_CURRENCY,
                        'source' => ['type' => 'unknown'], // Will be determined by Moyasar API response
                        'callback_verified' => true,
                        'api_verification_failed' => true
                    ];
                    
                    $result = updateTeamPaymentRecord($conn, $team_id, $tournament_id, $payment_id, $payment_details);
                    
                    if ($result) {
                        $_SESSION['team_id'] = $team_id;
                        $success = "Payment successful! Your team registration is now complete.";
                        error_log("Payment record updated via callback - Team ID: $team_id, Payment ID: $payment_id");
                    } else {
                        // Payment was successful but database update failed - trigger automatic refund
                        error_log("CRITICAL: Payment successful (callback) but database update failed for Payment ID: $payment_id");
                        
                        $refund_result = $paymentSystem->refundPayment(
                            $payment_id, 
                            null, // Full refund
                            "Automatic refund: Database update failed after successful payment (callback verified)"
                        );
                        
                        if ($refund_result['status'] === 'success') {
                            error_log("Automatic refund successful for Payment ID: $payment_id, Refund ID: " . ($refund_result['refund_id'] ?? 'N/A'));
                            $error = "Payment was successful but a system error occurred. Your payment has been automatically refunded. Refund ID: " . ($refund_result['refund_id'] ?? 'Processing') . ". Please try again or contact support.";
                        } else {
                            error_log("Automatic refund FAILED for Payment ID: $payment_id. Error: " . $refund_result['message']);
                            $error = "Payment successful but system error occurred. URGENT: Please contact support immediately with Payment ID: $payment_id - refund failed.";
                        }
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
        error_log("Payment verification error for existing team: " . $e->getMessage());
    }
}

/**
 * Determine payment method from payment details
 */
function getPaymentMethod($payment_details) {
    if (!$payment_details || !isset($payment_details['source'])) {
        return 'moyasar'; // Default fallback
    }
    
    $source_type = $payment_details['source']['type'] ?? 'unknown';
    
    switch ($source_type) {
        case 'applepay':
            return 'apple_pay';
        case 'creditcard':
        case 'credit_card':
            return 'credit_card';
        case 'sadad':
            return 'sadad';
        case 'stc_pay':
            return 'stc_pay';
        default:
            return 'moyasar';
    }
}

/**
 * Update payment record for existing team
 */
function updateTeamPaymentRecord($conn, $team_id, $tournament_id, $payment_id, $payment_details = null) {
    try {
        // Check if payment record already exists
        $stmt = $conn->prepare("SELECT id FROM payment_transactions WHERE team_id = ? AND tournament_id = ? AND payment_id = ?");
        $stmt->bind_param("iis", $team_id, $tournament_id, $payment_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE payment_transactions SET 
                status = 'paid', 
                payment_data = ?, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE team_id = ? AND tournament_id = ? AND payment_id = ?");
            $payment_data_json = $payment_details ? json_encode($payment_details) : null;
            $stmt->bind_param("siis", $payment_data_json, $team_id, $tournament_id, $payment_id);
            $result = $stmt->execute();
            $stmt->close();
            
            error_log("Updated existing payment record for team $team_id, payment $payment_id");
            return $result;
        } else {
            // Insert new payment record
            $payment_data_json = $payment_details ? json_encode($payment_details) : null;
            $amount = ($payment_details && isset($payment_details['amount'])) ? $payment_details['amount'] / 100 : MOYASAR_AMOUNT / 100;
            $currency = ($payment_details && isset($payment_details['currency'])) ? $payment_details['currency'] : MOYASAR_CURRENCY;
            $payment_method = getPaymentMethod($payment_details);
            
            $stmt = $conn->prepare("INSERT INTO payment_transactions 
                (team_id, tournament_id, payment_id, amount, currency, status, payment_method, payment_data, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 'paid', ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->bind_param("iisdsss", $team_id, $tournament_id, $payment_id, $amount, $currency, $payment_method, $payment_data_json);
            $result = $stmt->execute();
            $stmt->close();
            
            error_log("Created new payment record for team $team_id, payment $payment_id");
            return $result;
        }
    } catch (Exception $e) {
        error_log("Error updating payment record: " . $e->getMessage());
        return false;
    }
}

// Determine redirect URL based on result
$redirect_url = "payment.php?team_id=$team_id&tournament_id=$tournament_id";

if ($success) {
    $redirect_url .= "&status=success";
} elseif ($error) {
    $redirect_url .= "&status=failed&error=" . urlencode($error);
}

// Redirect back to payment page with status
header("Location: $redirect_url");
exit;
?>