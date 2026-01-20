<?php
require 'config.php';

try {
    $conn = getDBConnection();
    
    // Check payment_transactions table structure
    echo "=== PAYMENT_TRANSACTIONS TABLE STRUCTURE ===\n";
    $result = $conn->query('DESCRIBE payment_transactions');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . ' - ' . $row['Key'] . ' - ' . $row['Default'] . "\n";
        }
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }
    
    echo "\n=== RECENT PAYMENT_TRANSACTIONS ===\n";
    $result = $conn->query('SELECT * FROM payment_transactions ORDER BY created_at DESC LIMIT 5');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . ", Team: " . $row['team_id'] . ", Payment: " . $row['payment_id'] . ", Status: " . $row['status'] . ", Created: " . $row['created_at'] . "\n";
        }
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }
    
    echo "\n=== TEAM_PAYMENT_LINKS TABLE (if exists) ===\n";
    $result = $conn->query('DESCRIBE team_payment_links');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . ' - ' . $row['Key'] . ' - ' . $row['Default'] . "\n";
        }
    } else {
        echo "Table team_payment_links does not exist or ERROR: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>