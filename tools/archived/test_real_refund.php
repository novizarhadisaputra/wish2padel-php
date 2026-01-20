<?php
/**
 * Test Refund with Real Payment ID
 */

require_once 'config.php';
require_once 'SimplePaymentSystem.php';

if ($argc < 2) {
    echo "Usage: php test_real_refund.php <payment_id>\n";
    echo "Example: php test_real_refund.php 7372aad1-a3c7-488f-8f5b-e15ac1c93234\n";
    exit(1);
}

$payment_id = $argv[1];

echo "🧪 Testing Refund with Real Payment ID: $payment_id\n";
echo "================================================\n\n";

try {
    $paymentSystem = new SimplePaymentSystem();
    
    // Test 1: Check if payment can be refunded
    echo "Test 1: Checking refund capability\n";
    $refund_check = $paymentSystem->canRefundPayment($payment_id);
    echo "Can refund: " . ($refund_check['can_refund'] ? 'YES' : 'NO') . "\n";
    echo "Reason: " . $refund_check['reason'] . "\n";
    
    if (isset($refund_check['remaining_amount'])) {
        echo "Remaining refundable amount: " . $refund_check['remaining_amount'] . " SAR\n";
    }
    
    if ($refund_check['can_refund']) {
        echo "\n⚠️  WARNING: This will attempt a REAL refund!\n";
        echo "Type 'YES' to proceed with refund test: ";
        $handle = fopen("php://stdin", "r");
        $confirm = trim(fgets($handle));
        fclose($handle);
        
        if ($confirm === 'YES') {
            echo "\nTest 2: Attempting refund\n";
            $refund_result = $paymentSystem->refundPayment($payment_id, null, "Test refund from system");
            
            echo "Refund Status: " . $refund_result['status'] . "\n";
            echo "Message: " . $refund_result['message'] . "\n";
            
            if ($refund_result['status'] === 'success') {
                echo "Refund ID: " . ($refund_result['refund_id'] ?? 'N/A') . "\n";
                echo "Refund Amount: " . ($refund_result['refund_amount'] ?? 'N/A') . " SAR\n";
            }
        } else {
            echo "\nRefund test cancelled.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>