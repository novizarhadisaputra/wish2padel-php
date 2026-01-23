# 🔄 Sistem Refund Otomatis - Dokumentasi Lengkap

## 📋 Overview

Sistem refund otomatis telah diimplementasikan untuk menangani situasi ketika pembayaran berhasil tetapi terjadi error dalam pemrosesan database atau sistem. Sistem ini menggunakan Moyasar Refund API untuk melakukan refund otomatis.

## 🔧 Komponen yang Digunakan

### 1. **payment_verify_existing.php** 
- Verifier utama yang digunakan untuk existing teams
- Handles payment verification untuk team yang sudah ada di database
- Melakukan automatic refund jika ada error setelah payment sukses

### 2. **SimplePaymentSystem.php - Refund Methods**
- `refundPayment($payment_id, $amount, $reason)` - Melakukan refund
- `canRefundPayment($payment_id)` - Mengecek apakah payment bisa di-refund
- `updateRefundRecord($payment_id, $refund_data)` - Update database dengan data refund

### 3. **Database Schema - payment_transactions**
Kolom refund yang ditambahkan:
- `refund_id VARCHAR(100)` - ID refund dari Moyasar
- `refund_amount DECIMAL(10,2)` - Jumlah yang di-refund
- `refund_reason TEXT` - Alasan refund
- `refund_data JSON` - Data lengkap refund dari Moyasar API

## 🚀 Cara Kerja Sistem

### Alur Normal (Payment Sukses)
```
Payment Success → Database Update Success → Registration Complete
```

### Alur dengan Error (Automatic Refund)
```
Payment Success → Database Update Failed → Automatic Refund → Error Message with Refund ID
```

## 📝 Implementasi Detail

### 1. Automatic Refund Trigger
```php
if ($result) {
    // Success - normal flow
    $_SESSION['team_id'] = $team_id;
    $success = "Payment successful! Your team registration is now complete.";
} else {
    // Database update failed - trigger refund
    $refund_result = $paymentSystem->refundPayment(
        $verified_payment_id, 
        null, // Full refund
        "Automatic refund: Database update failed after successful payment"
    );
    
    if ($refund_result['status'] === 'success') {
        $error = "Payment was successful but a system error occurred. Your payment has been automatically refunded. Refund ID: " . ($refund_result['refund_id'] ?? 'Processing');
    } else {
        $error = "Payment successful but system error occurred. URGENT: Please contact support immediately with Payment ID: $verified_payment_id - refund failed.";
    }
}
```

### 2. Refund API Integration
```php
public function refundPayment(string $payment_id, ?float $amount = null, string $reason = 'Automatic refund due to system error'): array
{
    $data = [
        'description' => $reason
    ];
    
    // Add amount if partial refund
    if ($amount !== null) {
        $data['amount'] = (int)($amount * 100); // Convert to cents
    }
    
    $response = $this->callMoyasarAPI('POST', "/payments/{$payment_id}/refund", $data);
    
    // Handle response and update database
}
```

## 📊 Status dan Error Handling

### Refund Status
- `success` - Refund berhasil diproses
- `error` - Refund gagal, butuh manual intervention

### Error Messages untuk User
1. **Refund Berhasil**: "Payment was successful but a system error occurred. Your payment has been automatically refunded. Refund ID: [REFUND_ID]. Please try again or contact support."

2. **Refund Gagal**: "Payment successful but system error occurred. URGENT: Please contact support immediately with Payment ID: [PAYMENT_ID] - refund failed."

## 🔒 Keamanan dan Logging

### Error Logging
- Semua refund activity dicatat di error log
- Payment ID dan refund ID disimpan untuk tracking
- Automatic refund reason dicatat dengan jelas

### Database Tracking
- Status payment diupdate ke 'refunded'
- Refund data lengkap disimpan dalam JSON format
- Audit trail lengkap untuk setiap transaksi

## 🧪 Testing

### Test Scripts Available
1. `test_refund_system.php` - Basic refund functionality test
2. `test_real_refund.php <payment_id>` - Test dengan payment ID real
3. `setup_refund_columns.php` - Database migration script

### Manual Testing
```bash
# Test refund functionality
php test_refund_system.php

# Test dengan payment ID real (hati-hati - akan melakukan refund!)
php test_real_refund.php <payment_id>
```

## 📞 Integration Points

### Current Verifier
- `payment.php` menggunakan `payment_verify_existing.php` sebagai callback URL
- Callback URL: `payment_verify_existing.php?team_id={ID}&tournament_id={ID}`

### API Dependencies
- Moyasar API untuk payment verification
- Moyasar Refund API untuk automatic refunds
- Database untuk tracking dan audit

## ⚠️ Important Notes

1. **Automatic Refund** hanya terjadi ketika payment berhasil tetapi database update gagal
2. **Full Refund** dilakukan by default untuk menghindari partial refund complexity
3. **Error Messages** memberikan Refund ID kepada user untuk tracking
4. **Manual Intervention** diperlukan jika automatic refund gagal
5. **API Credentials** harus valid untuk refund functionality bekerja

## 🔄 Fallback Mechanisms

1. **API Verification Failure**: System menggunakan callback status sebagai fallback
2. **Refund API Failure**: Error message dengan payment ID untuk manual processing
3. **Database Failure**: Refund tetap dicoba dan error dicatat dengan detail lengkap

---

**Status**: ✅ Implemented and Ready
**Last Updated**: September 23, 2025
**Version**: 1.0