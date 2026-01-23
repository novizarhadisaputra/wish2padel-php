<?php
/**
 * Test Refund Functionality
 * This script tests the refund feature with a mock payment
 */

require_once 'config.php';
require_once 'SimplePaymentSystem.php';

echo "🧪 Testing Refund Functionality\n";
echo "================================\n\n";

try {
    $paymentSystem = new SimplePaymentSystem();
    
    // Test 1: Check refund capability for a non-existent payment
    echo "Test 1: Check refund capability for non-existent payment\n";
    $test_payment_id = "pay_test_123456789";
    $refund_check = $paymentSystem->canRefundPayment($test_payment_id);
    echo "Can refund: " . ($refund_check['can_refund'] ? 'YES' : 'NO') . "\n";
    echo "Reason: " . $refund_check['reason'] . "\n\n";
    
    // Test 2: Attempt refund on non-existent payment (should fail gracefully)
    echo "Test 2: Attempt refund on non-existent payment\n";
    $refund_result = $paymentSystem->refundPayment($test_payment_id, null, "Test refund");
    echo "Refund Status: " . $refund_result['status'] . "\n";
    echo "Message: " . $refund_result['message'] . "\n\n";
    
    // Test 3: Show existing payment records in database
    echo "Test 3: Show existing payment records\n";
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT payment_id, team_id, amount, status, created_at FROM payment_transactions ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "Recent payments in database:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- Payment ID: {$row['payment_id']}, Team: {$row['team_id']}, Amount: {$row['amount']}, Status: {$row['status']}, Date: {$row['created_at']}\n";
        }
    } else {
        echo "No payment records found in database.\n";
    }
    $stmt->close();
    
    echo "\n✅ Refund functionality test completed!\n";
    echo "\nRefund Features Available:\n";
    echo "- paymentSystem->refundPayment(\$payment_id, \$amount, \$reason)\n";
    echo "- paymentSystem->canRefundPayment(\$payment_id)\n";
    echo "- Automatic refund on database errors\n";
    echo "- Full refund tracking in payment_transactions table\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
}
?>