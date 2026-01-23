<?php
declare(strict_types=1);

namespace App\Core;

use Exception;

/**
 * Simple and Clean Payment System for Moyasar
 * Author: Padel League System
 * Version: 3.0 - Simplified for better reliability
 */

// Ensure config is loaded for dynamic payment functions
if (!function_exists('getDynamicPaymentAmount')) {
    require_once __DIR__ . '/../../config/config.php';
}

class SimplePaymentSystem
{
    private $conn;
    private $secret_key;
    private $publishable_key;

    public function __construct()
    {
        $this->conn = getDBConnection();
        $this->secret_key = getMoyasarSecretKey();
        $this->publishable_key = getMoyasarPublishableKey();

        // Validate credentials are present and properly formatted
        $this->validateCredentials();
    }

    /**
     * Validate Moyasar credentials
     */
    private function validateCredentials(): void
    {
        if (empty($this->secret_key)) {
            throw new Exception("Moyasar secret key not configured in config.php");
        }

        if (empty($this->publishable_key)) {
            throw new Exception("Moyasar publishable key not configured in config.php");
        }

        // Check key format
        if (!preg_match('/^sk_(test|live)_/', $this->secret_key)) {
            throw new Exception("Invalid Moyasar secret key format. Must start with sk_test_ or sk_live_");
        }

        if (!preg_match('/^pk_(test|live)_/', $this->publishable_key)) {
            throw new Exception("Invalid Moyasar publishable key format. Must start with pk_test_ or pk_live_");
        }

        // Check minimum length
        if (strlen($this->secret_key) < 20) {
            throw new Exception("Moyasar secret key too short. Please check your configuration");
        }

        if (strlen($this->publishable_key) < 20) {
            throw new Exception("Moyasar publishable key too short. Please check your configuration");
        }
    }

    /**
     * Create payment and save to database in one simple transaction
     */
    public function createPayment(int $team_id, int $tournament_id = 1): array
    {
        try {
            // 1. Check if already paid (only 'paid' status blocks new payment)
            if ($this->isTeamPaid($team_id, $tournament_id)) {
                return [
                    'status' => 'already_paid',
                    'message' => 'Team has already completed payment for this tournament'
                ];
            }

            // 2. Get team information
            $team = $this->getTeamInfo($team_id, $tournament_id);
            if (!$team) {
                throw new Exception("Team not found");
            }

            // 3. Try to prepare payment with Moyasar (NO DATABASE INSERTION YET)
            try {
                $moyasar_payment = $this->createMoyasarPayment($team, $team_id, $tournament_id);

                // 4. DO NOT save to database - only return form data
                // Database insertion will happen in payment verification

                // 5. Return success response
                return [
                    'status' => 'success',
                    'payment_id' => $moyasar_payment['id'],
                    'payment_url' => $moyasar_payment['url'] ?? '',
                    'publishable_key' => $this->publishable_key,
                    'amount' => getDynamicPaymentAmount(),
                    'currency' => getDynamicPaymentCurrency(),
                    'description' => 'Padel Tournament Registration - ' . $team['team_name']
                ];
            } catch (Exception $moyasar_error) {
                // Check if it's an authentication error
                if (strpos($moyasar_error->getMessage(), 'authentication_error') !== false) {
                    // Return fallback payment form data without API call
                    error_log("Moyasar API authentication failed, using fallback: " . $moyasar_error->getMessage());

                    return [
                        'status' => 'success',
                        'payment_id' => 'fallback_' . $team_id . '_' . $tournament_id . '_' . time(),
                        'payment_url' => '',
                        'publishable_key' => $this->publishable_key,
                        'amount' => getDynamicPaymentAmount(),
                        'currency' => getDynamicPaymentCurrency(),
                        'description' => 'Padel Tournament Registration - ' . $team['team_name'],
                        'fallback_mode' => true,
                        'api_error' => 'Authentication failed - please check your API keys'
                    ];
                }

                // If not auth error, re-throw
                throw $moyasar_error;
            }
        } catch (Exception $e) {
            error_log("Payment creation failed: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare payment form data without inserting into database
     * This method sets up the payment form but doesn't create a transaction record
     */
    public function preparePaymentForm(int $team_id, int $tournament_id = 1): array
    {
        try {
            // 1. Check if already paid
            if ($this->isTeamPaid($team_id, $tournament_id)) {
                return [
                    'status' => 'already_paid',
                    'message' => 'Team has already completed payment for this tournament'
                ];
            }

            // 2. Get team information
            $team = $this->getTeamInfo($team_id, $tournament_id);
            if (!$team) {
                throw new Exception("Team not found");
            }

            // 3. Return form data without creating Moyasar payment or database entry
            return [
                'status' => 'success',
                'payment_id' => 'form_' . $team_id . '_' . $tournament_id . '_' . time(),
                'payment_url' => '',
                'publishable_key' => $this->publishable_key,
                'amount' => getDynamicPaymentAmount(),
                'currency' => getDynamicPaymentCurrency(),
                'description' => 'Padel Tournament Registration - ' . $team['team_name'],
                'form_mode' => true
            ];
        } catch (Exception $e) {
            error_log("Payment form preparation error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to prepare payment form: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create payment transaction record when payment is actually made
     * This should be called from payment verification after successful payment
     */
    public function createPaymentTransaction(int $team_id, int $tournament_id, string $payment_id, array $payment_data = []): bool
    {
        try {
            $now = date("Y-m-d H:i:s");
            $amount = getDynamicPaymentAmount();
            $currency = getDynamicPaymentCurrency();

            $stmt = $this->conn->prepare("
                INSERT INTO payment_transactions 
                (team_id, tournament_id, payment_id, amount, currency, status, 
                 payment_method, payment_data, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'paid', 'moyasar', ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    status = 'paid',
                    payment_data = VALUES(payment_data),
                    updated_at = VALUES(updated_at)
            ");

            $payment_data_json = json_encode($payment_data);

            $stmt->bind_param(
                "iisdssss",
                $team_id,
                $tournament_id,
                $payment_id,
                $amount,
                $currency,
                $payment_data_json,
                $now,
                $now
            );

            $result = $stmt->execute();
            $stmt->close();

            if ($result) {
                error_log("Payment transaction created successfully: team_id=$team_id, tournament_id=$tournament_id, payment_id=$payment_id");
                return true;
            } else {
                error_log("Failed to create payment transaction: " . $this->conn->error);
                return false;
            }
        } catch (Exception $e) {
            error_log("Payment transaction creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if team has already paid using payment_transactions table
     */
    public function isTeamPaid(int $team_id, int $tournament_id = 1): bool
    {
        $stmt = $this->conn->prepare("
            SELECT status, payment_id, amount, created_at 
            FROM payment_transactions 
            WHERE team_id = ? AND tournament_id = ? 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->bind_param("ii", $team_id, $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result) {
            return false;
        }

        // Only consider team paid if status is specifically 'paid'
        // Allow retry for pending, failed, or any other status
        $is_paid = ($result['status'] === 'paid' && !empty($result['payment_id']));

        // Log for debugging
        error_log("Payment check for team $team_id, tournament $tournament_id: " .
            ($is_paid ? 'PAID' : 'NOT PAID') .
            " (Status: {$result['status']}, Payment ID: {$result['payment_id']})");

        return $is_paid;
    }

    /**
     * Get team payment information from payment_transactions table
     */
    public function getTeamPaymentInfo(int $team_id, int $tournament_id = 1): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT payment_id, status, amount, currency, payment_method, created_at, updated_at
            FROM payment_transactions 
            WHERE team_id = ? AND tournament_id = ? 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->bind_param("ii", $team_id, $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ?: null;
    }

    /**
     * Check if team exists and get team information
     */
    private function getTeamInfo(int $team_id, int $tournament_id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT team_name, captain_name, captain_email, captain_phone
            FROM team_info 
            WHERE id = ? AND tournament_id = ?
        ");
        $stmt->bind_param("ii", $team_id, $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ?: null;
    }

    /**
     * Create payment with Moyasar API
     */
    private function createMoyasarPayment(array $team, int $team_id, int $tournament_id): array
    {
        $callback_url = $this->getCallbackUrl($team_id, $tournament_id);

        // Ensure amount is valid for SAR (must be multiple of 10)
        $amount = getDynamicPaymentAmount();
        if ($amount % 10 !== 0) {
            $amount = round($amount / 10) * 10;
        }

        // Create invoice for hosted checkout (recommended for redirect/hosted flows)
        $payload = [
            'amount' => $amount,
            'currency' => getDynamicPaymentCurrency(),
            'description' => 'Padel Tournament Registration - ' . $team['team_name'],
            'callback_url' => $callback_url,
            'metadata' => [
                'team_id' => (string)$team_id,
                'tournament_id' => (string)$tournament_id,
                'team_name' => $team['team_name'],
                'captain_name' => $team['captain_name']
            ]
        ];
        // Use /invoices for hosted checkout (no card/source required here)
        $response = $this->callMoyasarAPI('POST', '/invoices', $payload);

        if ($response['status'] !== 201 || !isset($response['body']['id'])) {
            throw new Exception("Moyasar API error: " . json_encode($response['body']));
        }

        return $response['body'];
    }

    /**
     * Save payment data to database
     */
    private function savePaymentToDatabase(int $team_id, int $tournament_id, array $moyasar_payment): void
    {
        $now = date("Y-m-d H:i:s");
        $expires_at = date("Y-m-d H:i:s", time() + 3600); // 1 hour

        $stmt = $this->conn->prepare("
            INSERT INTO payment_transactions 
            (team_id, tournament_id, payment_id, amount, currency, status, 
             payment_method, payment_data, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'pending', 'moyasar', ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                payment_data = VALUES(payment_data),
                updated_at = VALUES(updated_at)
        ");

        $payment_page_url = $moyasar_payment['url'] ?? "payment.php?team_id={$team_id}&tournament_id={$tournament_id}";
        $amount = getDynamicPaymentAmount();
        $currency = getDynamicPaymentCurrency();

        // Use Moyasar payment ID as our payment reference for consistency
        $payment_reference = $moyasar_payment['id'];

        $amount_sar = ($moyasar_payment['amount'] ?? getDynamicPaymentAmount()) / 100;
        $payment_data_json = json_encode($moyasar_payment);

        $stmt->bind_param(
            "iisdssss",
            $team_id,
            $tournament_id,
            $moyasar_payment['id'],
            $amount_sar,
            $currency,
            $payment_data_json,
            $now,
            $now
        );

        if (!$stmt->execute()) {
            throw new Exception("Database save failed: " . $stmt->error);
        }
        $stmt->close();

        error_log("Payment saved to database: team_id={$team_id}, payment_id={$moyasar_payment['id']} (consistent IDs)");
    }

    /**
     * Update payment status (called from webhook/callback)
     */
    public function updatePaymentStatus(string $payment_id, string $status): bool
    {
        try {
            $now = date("Y-m-d H:i:s");

            $stmt = $this->conn->prepare("
                UPDATE payment_transactions 
                SET status = ?, updated_at = ?
                WHERE payment_id = ?
            ");

            $stmt->bind_param("sss", $status, $now, $payment_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                error_log("Payment status updated: payment_id={$payment_id}, status={$status}");
            }

            return $success;
        } catch (Exception $e) {
            error_log("Failed to update payment status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment details from payment_transactions
     */
    public function getPaymentDetails(int $team_id, int $tournament_id = 1): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM payment_transactions 
            WHERE team_id = ? AND tournament_id = ? 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->bind_param("ii", $team_id, $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result) {
            return ['status' => 'not_found'];
        }

        return $result;
    }

    /**
     * Get detailed payment status for a team (compatible with navbar)
     * @param int $team_id
     * @param int $tournament_id
     * @return array
     */
    public function getTeamPaymentDetails(int $team_id, int $tournament_id = 1): array
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM payment_transactions
                WHERE team_id = ? AND tournament_id = ?
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->bind_param("ii", $team_id, $tournament_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment = $result->fetch_assoc();
            $stmt->close();

            if (!$payment) {
                return [
                    'exists' => false,
                    'status' => 'not_found',
                    'is_paid' => false,
                    'message' => 'No payment record found'
                ];
            }

            $paid_statuses = ['paid', 'captured', 'approved'];
            $status = trim($payment['status']);

            // Handle empty status - treat as pending if has payment_id, otherwise as initiated
            if (empty($status)) {
                $status = !empty($payment['payment_id']) ? 'pending' : 'initiated';
            }

            $is_paid = in_array($status, $paid_statuses);
            $has_payment_id = !empty($payment['payment_id']);

            return [
                'exists' => true,
                'status' => $status,
                'is_paid' => $is_paid,
                'has_payment_id' => $has_payment_id,
                'payment_id' => $payment['payment_id'],
                'payment_method' => $payment['payment_method'] ?? 'credit_card',
                'amount' => $payment['amount'],
                'currency' => $payment['currency'],
                'error_message' => $payment['error_message'] ?? null,
                'created_at' => $payment['created_at'],
                'updated_at' => $payment['updated_at']
            ];
        } catch (Exception $e) {
            error_log("Error getting team payment details: " . $e->getMessage());
            return [
                'exists' => false,
                'status' => 'error',
                'is_paid' => false,
                'message' => 'Error checking payment status'
            ];
        }
    }

    /**
     * Verify payment status with Moyasar and update database
     */
    public function verifyPayment(string $moyasar_payment_id, int $team_id, int $tournament_id): array
    {
        try {
            // Try both endpoints - payments and invoices
            error_log("Verifying payment ID: $moyasar_payment_id");

            // First try /invoices/ endpoint (hosted checkout flows)
            $response = $this->callMoyasarAPI('GET', '/invoices/' . $moyasar_payment_id);

            if ($response['status'] !== 200) {
                // If invoices endpoint returns record_not_found, try payments as a fallback.
                $body = $response['body'] ?? null;
                $isNotFound = false;
                if (is_array($body) && isset($body['type']) && $body['type'] === 'record_not_found') {
                    $isNotFound = true;
                }

                if ($isNotFound) {
                    error_log("Invoices endpoint returned record_not_found, falling back to /payments/ for ID: $moyasar_payment_id");
                    $response = $this->callMoyasarAPI('GET', '/payments/' . $moyasar_payment_id);
                    if ($response['status'] !== 200) {
                        error_log("Payments endpoint also failed with status: " . $response['status']);
                        error_log("Response: " . print_r($response['body'], true));
                        throw new Exception("Failed to get payment details from both Moyasar endpoints. Status: " . $response['status']);
                    }
                } else {
                    // Some other error from /invoices (auth, network, etc.) — surface it
                    error_log("Invoices endpoint failed with status: " . $response['status']);
                    error_log("Response: " . print_r($response['body'], true));
                    throw new Exception("Invoices endpoint error: " . json_encode($response['body']));
                }
            }

            $payment_data = $response['body'];
            error_log("Moyasar payment data: " . print_r($payment_data, true));

            // Check payment status
            $moyasar_status = $payment_data['status'] ?? 'unknown';
            $database_status = '';

            // Map Moyasar status to database ENUM values
            switch ($moyasar_status) {
                case 'paid':
                case 'captured':
                    $database_status = 'paid';
                    break;
                case 'failed':
                case 'canceled':
                case 'cancelled':
                    $database_status = 'failed';
                    break;
                case 'pending':
                case 'authorized':
                    $database_status = 'pending';
                    break;
                case 'initiated':
                    // Check if there are actual payments
                    if (!empty($payment_data['payments'])) {
                        // Get the latest payment status
                        $latest_payment = end($payment_data['payments']);
                        $payment_status = $latest_payment['status'] ?? 'pending';

                        switch ($payment_status) {
                            case 'paid':
                            case 'captured':
                                $database_status = 'paid';
                                break;
                            case 'failed':
                            case 'canceled':
                            case 'cancelled':
                                $database_status = 'failed';
                                break;
                            default:
                                $database_status = 'pending';
                        }
                    } else {
                        $database_status = 'pending'; // Invoice created but not paid yet
                    }
                    break;
                default:
                    // For unknown statuses, default to pending
                    error_log("Unknown Moyasar status: $moyasar_status, defaulting to pending");
                    $database_status = 'pending';
            }

            // Validate that database_status is a valid ENUM value
            $valid_statuses = ['pending', 'paid', 'failed', 'cancelled'];
            if (!in_array($database_status, $valid_statuses)) {
                error_log("Invalid database status: $database_status, defaulting to pending");
                $database_status = 'pending';
            }

            // Update database status
            error_log("Updating payment status: payment_id=$moyasar_payment_id, team_id=$team_id, tournament_id=$tournament_id, status=$database_status");

            $stmt = $this->conn->prepare("
                UPDATE payment_transactions 
                SET status = ?, updated_at = NOW() 
                WHERE payment_id = ? AND team_id = ? AND tournament_id = ?
            ");

            if (!$stmt) {
                throw new Exception("Failed to prepare UPDATE statement: " . $this->conn->error);
            }

            $stmt->bind_param("ssii", $database_status, $moyasar_payment_id, $team_id, $tournament_id);

            if (!$stmt->execute()) {
                $error_msg = "Failed to update payment status in database: " . $stmt->error;
                $stmt->close();
                throw new Exception($error_msg);
            }

            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            if ($affected_rows === 0) {
                // Try to find the record to understand why it wasn't updated
                $check_stmt = $this->conn->prepare("
                    SELECT id, status, payment_id, team_id, tournament_id 
                    FROM payment_transactions 
                    WHERE payment_id = ? OR (team_id = ? AND tournament_id = ?)
                    ORDER BY created_at DESC LIMIT 3
                ");
                $check_stmt->bind_param("sii", $moyasar_payment_id, $team_id, $tournament_id);
                $check_stmt->execute();
                $existing_records = $check_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $check_stmt->close();

                error_log("No rows affected. Existing records: " . print_r($existing_records, true));
                throw new Exception("No payment record found to update. Payment ID: $moyasar_payment_id, Team: $team_id, Tournament: $tournament_id");
            }

            error_log("Payment status updated: team_id=$team_id, payment_id=$moyasar_payment_id, status=$database_status (using payment_transactions table)");

            return [
                'status' => 'success',
                'database_status' => $database_status,
                'moyasar_status' => $moyasar_status,
                'message' => "Payment status updated to: $database_status"
            ];
        } catch (Exception $e) {
            error_log("Payment verification failed: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create temporary payment for registration form (without saving team data yet)
     * Uses direct Moyasar Payments API
     */
    public function createTemporaryPayment(array $registration_data): array
    {
        try {
            // Create payment request to Moyasar
            $payment_request = [
                'amount' => getDynamicPaymentAmount(),
                'currency' => getDynamicPaymentCurrency(),
                'description' => 'Tournament Registration - ' . $registration_data['team_name'],
                'callback_url' => $this->getTemporaryCallbackUrl($registration_data),
                'source' => [
                    'type' => 'creditcard'
                ],
                'metadata' => [
                    'team_name' => $registration_data['team_name'],
                    'captain_name' => $registration_data['captain_name'],
                    'captain_email' => $registration_data['captain_email'],
                    'tournament_id' => $registration_data['tournament_id']
                ]
            ];

            // Call Moyasar API to create payment
            $response = $this->callMoyasarAPI('POST', '/payments', $payment_request);

            if ($response['status'] === 201 || $response['status'] === 200) {
                $payment_data = $response['body'];

                return [
                    'status' => 'success',
                    'payment_id' => $payment_data['id'],
                    'publishable_key' => $this->publishable_key,
                    'amount' => getDynamicPaymentAmount(),
                    'currency' => getDynamicPaymentCurrency(),
                    'description' => 'Tournament Registration - ' . $registration_data['team_name'],
                    'moyasar_payment' => $payment_data
                ];
            } else {
                throw new Exception('Failed to create payment with Moyasar: HTTP ' . $response['status']);
            }
        } catch (Exception $e) {
            error_log("Temporary payment creation failed: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get callback URL for temporary payment
     */
    private function getTemporaryCallbackUrl(array $registration_data): string
    {
        if (function_exists('asset')) {
            return asset("payment/verify?tournament_id={$registration_data['tournament_id']}");
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

        // Ensure consistent port for development
        if (strpos($host, 'localhost') !== false && strpos($host, ':') === false) {
            $host = 'localhost:8000';
        }

        return "{$protocol}://{$host}/payment/verify?tournament_id={$registration_data['tournament_id']}";
    }

    /**
     * Verify payment with Moyasar API
     */
    public function verifyPaymentWithMoyasar(string $payment_id): array
    {
        try {
            $response = $this->callMoyasarAPI('GET', "/payments/{$payment_id}");

            if ($response['status'] === 200) {
                $payment_data = $response['body'];

                return [
                    'status' => 'success',
                    'payment_status' => $payment_data['status'] ?? 'unknown',
                    'payment_data' => $payment_data
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Failed to verify payment with Moyasar: HTTP ' . $response['status']
                ];
            }
        } catch (Exception $e) {
            error_log("Payment verification failed: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Make HTTP request to Moyasar API
     */
    private function callMoyasarAPI(string $method, string $path, array $data = null): array
    {
        $url = ($_ENV['MOYASAR_API_URL'] ?? 'https://api.moyasar.com/v1') . $path;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_USERPWD, $this->secret_key . ":");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new Exception("Invalid JSON response from Moyasar API");
        }

        return ['status' => $httpCode, 'body' => $decoded];
    }

    /**
     * Refund a payment using Moyasar API
     * @param string $payment_id Payment ID to refund
     * @param float|null $amount Amount to refund (null for full refund)
     * @param string $reason Reason for refund
     * @return array Refund result
     */
    public function refundPayment(string $payment_id, ?float $amount = null, string $reason = 'Automatic refund due to system error'): array
    {
        try {
            error_log("Initiating refund for payment: $payment_id, amount: " . ($amount ?? 'full') . ", reason: $reason");

            $data = [
                'description' => $reason
            ];

            // Add amount if partial refund
            if ($amount !== null) {
                $data['amount'] = (int)($amount * 100); // Convert to cents
            }

            $response = $this->callMoyasarAPI('POST', "/payments/{$payment_id}/refund", $data);

            if ($response['status'] === 201 || $response['status'] === 200) {
                // Refund successful
                $refund_data = $response['body'];

                error_log("Refund successful: " . json_encode($refund_data));

                // Update payment record in database
                $this->updateRefundRecord($payment_id, $refund_data);

                return [
                    'status' => 'success',
                    'refund_id' => $refund_data['id'] ?? null,
                    'refund_amount' => ($refund_data['amount'] ?? 0) / 100,
                    'refund_data' => $refund_data,
                    'message' => 'Payment refunded successfully'
                ];
            } else {
                // Refund failed
                $error_message = $response['body']['message'] ?? 'Unknown refund error';
                error_log("Refund failed: HTTP {$response['status']}, Error: $error_message");

                return [
                    'status' => 'error',
                    'message' => "Refund failed: $error_message",
                    'http_code' => $response['status'],
                    'response' => $response['body']
                ];
            }
        } catch (Exception $e) {
            error_log("Refund exception for payment $payment_id: " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => 'Refund system error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update refund record in database
     */
    private function updateRefundRecord(string $payment_id, array $refund_data): void
    {
        try {
            // Update payment_transactions table with refund info
            $stmt = $this->conn->prepare("UPDATE payment_transactions SET 
                status = 'refunded', 
                refund_id = ?, 
                refund_amount = ?, 
                refund_reason = ?, 
                refund_data = ?, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE payment_id = ?");

            $refund_id = $refund_data['id'] ?? null;
            $refund_amount = ($refund_data['amount'] ?? 0) / 100;
            $refund_reason = $refund_data['description'] ?? 'Automatic refund';
            $refund_data_json = json_encode($refund_data);

            $stmt->bind_param("sdsss", $refund_id, $refund_amount, $refund_reason, $refund_data_json, $payment_id);
            $stmt->execute();
            $stmt->close();

            error_log("Updated refund record in database for payment: $payment_id");
        } catch (Exception $e) {
            error_log("Failed to update refund record: " . $e->getMessage());
        }
    }

    /**
     * Check if payment can be refunded
     */
    public function canRefundPayment(string $payment_id): array
    {
        try {
            // Get payment details from Moyasar
            $verification = $this->verifyPaymentWithMoyasar($payment_id);

            if ($verification['status'] !== 'success') {
                return [
                    'can_refund' => false,
                    'reason' => 'Cannot verify payment status'
                ];
            }

            $payment_data = $verification['payment_data'];
            $payment_status = $payment_data['status'] ?? '';

            // Check if payment is in refundable state
            if ($payment_status === 'paid' || $payment_status === 'captured') {
                // Check if already refunded
                $refunded_amount = $payment_data['refunded'] ?? 0;
                $total_amount = $payment_data['amount'] ?? 0;

                if ($refunded_amount >= $total_amount) {
                    return [
                        'can_refund' => false,
                        'reason' => 'Payment already fully refunded'
                    ];
                }

                return [
                    'can_refund' => true,
                    'remaining_amount' => ($total_amount - $refunded_amount) / 100
                ];
            }

            return [
                'can_refund' => false,
                'reason' => "Payment status '$payment_status' is not refundable"
            ];
        } catch (Exception $e) {
            return [
                'can_refund' => false,
                'reason' => 'Error checking refund status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update payment from callback (wrapper for updatePaymentStatus)
     */
    public function updatePaymentFromCallback($payment_id, $status, $message = '')
    {
        return $this->updatePaymentStatus($payment_id, $status);
    }

    /**
     * Get callback URL for payment
     */
    private function getCallbackUrl(int $team_id, int $tournament_id): string
    {
        if (function_exists('asset')) {
            return asset("payment/verify?team_id={$team_id}&tournament_id={$tournament_id}");
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

        // Ensure consistent port for development
        if (strpos($host, 'localhost') !== false && strpos($host, ':') === false) {
            $host = 'localhost:8000';
        }

        return "{$protocol}://{$host}/payment/verify?team_id={$team_id}&tournament_id={$tournament_id}";
    }
}
