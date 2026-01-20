<?php
// Test the current structure of verifyPayment function
require 'config.php';

// Simple test to check if payment_transactions table can be updated
try {
    $conn = getDBConnection();
    
    // Test UPDATE query
    $test_payment_id = "test_payment_123";
    $test_team_id = 1;
    $test_tournament_id = 1;
    $test_status = "paid";
    
    $stmt = $conn->prepare("
        UPDATE payment_transactions 
        SET status = ?, updated_at = NOW() 
        WHERE payment_id = ? AND team_id = ? AND tournament_id = ?
    ");
    
    if (!$stmt) {
        echo "PREPARE ERROR: " . $conn->error . "\n";
        exit;
    }
    
    $stmt->bind_param("ssii", $test_status, $test_payment_id, $test_team_id, $test_tournament_id);
    
    if (!$stmt->execute()) {
        echo "EXECUTE ERROR: " . $stmt->error . "\n";
    } else {
        echo "UPDATE query executed successfully\n";
        echo "Affected rows: " . $stmt->affected_rows . "\n";
    }
    
    $stmt->close();
    
    // Check if the status enum accepts our values
    $result = $conn->query("SHOW COLUMNS FROM payment_transactions LIKE 'status'");
    if ($result) {
        $column = $result->fetch_assoc();
        echo "Status column definition: " . $column['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>