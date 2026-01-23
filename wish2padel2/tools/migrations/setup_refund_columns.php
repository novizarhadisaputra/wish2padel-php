<?php
/**
 * Add refund columns to payment_transactions table
 */

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Check if refund columns already exist
    $result = $conn->query("SHOW COLUMNS FROM payment_transactions LIKE 'refund_id'");
    
    if ($result->num_rows == 0) {
        echo "Adding refund columns to payment_transactions table...\n";
        
        $sql = "ALTER TABLE payment_transactions 
                ADD COLUMN refund_id VARCHAR(100) NULL AFTER payment_data,
                ADD COLUMN refund_amount DECIMAL(10,2) NULL AFTER refund_id,
                ADD COLUMN refund_reason TEXT NULL AFTER refund_amount,
                ADD COLUMN refund_data JSON NULL AFTER refund_reason";
        
        if ($conn->query($sql)) {
            echo "✅ Refund columns added successfully!\n";
        } else {
            echo "❌ Error adding refund columns: " . $conn->error . "\n";
        }
    } else {
        echo "ℹ️ Refund columns already exist in payment_transactions table.\n";
    }
    
    // Show current table structure
    echo "\nCurrent payment_transactions table structure:\n";
    $result = $conn->query("DESCRIBE payment_transactions");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']} " . ($row['Null'] == 'YES' ? '(NULL)' : '(NOT NULL)') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>