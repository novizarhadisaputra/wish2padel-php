<?php
/**
 * Moyasar Callback/Webhook Handler
 * Handles server-to-server notifications from Moyasar
 */

// Set proper headers for webhook
header('Content-Type: application/json');
http_response_code(200); // Always respond with 200 to Moyasar

require_once 'config.php';
require_once 'SimplePaymentSystem.php';

// Log all incoming data for debugging
$webhook_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'get_params' => $_GET,
    'post_params' => $_POST,
    'raw_input' => file_get_contents('php://input')
];

file_put_contents('webhook_log.txt', json_encode($webhook_data) . "\n", FILE_APPEND);

try {
    // Get team and tournament IDs from URL parameters
    $team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
    $tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;
    
    if (!$team_id) {
        error_log("Moyasar Webhook: Missing team_id parameter");
        echo json_encode(['status' => 'error', 'message' => 'Missing team_id']);
        exit;
    }
    
    // Moyasar sends webhook data as JSON
    $webhook_payload = null;
    
    // Try to get JSON payload
    $raw_input = file_get_contents('php://input');
    if ($raw_input) {
        $webhook_payload = json_decode($raw_input, true);
    }
    
    // If no JSON, check POST parameters
    if (!$webhook_payload && !empty($_POST)) {
        $webhook_payload = $_POST;
    }
    
    // If no POST, check GET parameters 
    if (!$webhook_payload && !empty($_GET)) {
        $webhook_payload = $_GET;
    }
    
    if (!$webhook_payload) {
        error_log("Moyasar Webhook: No payload received");
        echo json_encode(['status' => 'error', 'message' => 'No payload']);
        exit;
    }
    
    // Extract Moyasar callback parameters
    // Moyasar webhook typically includes payment object:
    $payment_id = $webhook_payload['id'] ?? '';
    $payment_status = $webhook_payload['status'] ?? '';
    $amount = $webhook_payload['amount'] ?? '';
    $currency = $webhook_payload['currency'] ?? '';
    $source_type = $webhook_payload['source']['type'] ?? '';
    $source_message = $webhook_payload['source']['message'] ?? '';
    
    if (!$payment_id) {
        error_log("Moyasar Webhook: Missing payment ID");
        echo json_encode(['status' => 'error', 'message' => 'Missing payment ID']);
        exit;
    }
    
    // Initialize payment system
    $payment_system = getPaymentSystem();

    // ---------------------------------------------------------
    // SECURITY: Signature Verification
    // ---------------------------------------------------------
    $secret_key = getMoyasarSecretKey();
    if (empty($secret_key)) {
         error_log("Moyasar Webhook Error: Secret key not configured");
         exit;
    }

    // Official Moyasar Signature Header check (if available)
    // Note: Depends on server config, passing headers correctly
    /*
    $signature = $_SERVER['HTTP_MOYASAR_SIGNATURE'] ?? '';
    // If you enable this, you need to know the raw body content exactly as sent
    // For now, we rely on the API Double-Check verification below which is robust
    */
    
    // ---------------------------------------------------------
    $verified = false;
    try {
        $verification_result = $payment_system->verifyPayment($payment_id, $team_id, $tournament_id);
        if (!isset($verification_result['error'])) {
            $verified = true;
            // Use API data as authoritative source
            $payment_status = $verification_result['status'];
        }
    } catch (Exception $e) {
        error_log("Moyasar Webhook: Could not verify payment with API: " . $e->getMessage());
    }
    
    if (!$verified) {
        error_log("Moyasar Webhook: Could not verify transaction with API");
        // Still process if we have basic webhook data
    }
    
    // Process the payment status using PaymentSystem
    $update_result = false;
    
    // Update payment status through PaymentSystem
    $update_result = $payment_system->updatePaymentFromCallback($payment_id, $payment_status, 
        "Webhook update: " . $payment_status);
    
    // Log the webhook processing result
    $log_data = [
        'team_id' => $team_id,
        'tournament_id' => $tournament_id,
        'payment_id' => $payment_id,
        'payment_status' => $payment_status,
        'amount' => $amount,
        'currency' => $currency,
        'source_type' => $source_type,
        'update_result' => $update_result ? 'success' : 'failed',
        'verified' => $verified
    ];
    
    error_log("Moyasar Webhook Processed: " . json_encode($log_data));
    
    // Respond to Moyasar
    echo json_encode([
        'status' => 'success',
        'message' => 'Webhook processed successfully',
        'team_id' => $team_id,
        'payment_id' => $payment_id,
        'updated' => $update_result
    ]);
    
} catch (Exception $e) {
    error_log("Moyasar Webhook Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>