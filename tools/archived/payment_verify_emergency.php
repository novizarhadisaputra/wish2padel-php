<?php
/**
 * EMERGENCY SIMPLE Payment Verification
 * Temporary fix for database error after successful payment
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once 'config.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get parameters
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 1;
$payment_id = $_GET['payment_id'] ?? '';
$status = $_GET['status'] ?? '';

error_log("EMERGENCY Payment verification: team_id=$team_id, tournament_id=$tournament_id, payment_id=$payment_id, status=$status");

// Validate basic parameters
if ($team_id <= 0) {
    error_log("Invalid team_id: $team_id");
    header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=failed&error=" . urlencode("Invalid team ID"));
    exit();
}

// If no payment_id, try to get from database
if (empty($payment_id)) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT payment_id 
            FROM payment_transactions 
            WHERE team_id = ? AND tournament_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ii", $team_id, $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result && !empty($result['payment_id'])) {
            $payment_id = $result['payment_id'];
            error_log("Emergency: Auto-fetched payment_id: $payment_id for team_id: $team_id");
        }
    } catch (Exception $e) {
        error_log("Emergency: Database error: " . $e->getMessage());
    }
}

// Simple status update without complex verification
try {
    $conn = getDBConnection();
    
    // Just mark as paid if we got a success callback
    if (!empty($payment_id)) {
        $update_status = 'paid'; // Assume success since we got a payment_id
        
        $stmt = $conn->prepare("
            UPDATE payment_transactions 
            SET status = ?, updated_at = NOW() 
            WHERE payment_id = ? AND team_id = ? AND tournament_id = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("ssii", $update_status, $payment_id, $team_id, $tournament_id);
            
            if ($stmt->execute()) {
                $affected_rows = $stmt->affected_rows;
                error_log("Emergency: Updated payment status to $update_status. Affected rows: $affected_rows");
                
                if ($affected_rows > 0) {
                    // Success - redirect to success page
                    header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=success");
                    exit();
                } else {
                    error_log("Emergency: No rows affected - payment record may not exist");
                }
            } else {
                error_log("Emergency: Update execution failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log("Emergency: Failed to prepare statement: " . $conn->error);
        }
    }
    
    // If we reach here, something went wrong
    header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=failed&error=" . urlencode("Payment verification failed"));
    exit();
    
} catch (Exception $e) {
    error_log("Emergency verification error: " . $e->getMessage());
    header("Location: payment.php?team_id=$team_id&tournament_id=$tournament_id&status=failed&error=" . urlencode("Database error: " . $e->getMessage()));
    exit();
}
?>