<?php

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wish2padel');

if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        // Mock connection for environment where DB might not be available or credentials missing
        // In a real scenario, this would be: return new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        static $mockConn;
        if ($mockConn) return $mockConn;

        $mockConn = new class {
            public $error = '';
            public $insert_id = 1;

            public function query($sql) {
                // Return a mock result set
                return new class {
                    public $num_rows = 0;
                    public function fetch_assoc() { return null; }
                    public function fetch_all($mode = 0) { return []; }
                };
            }
            public function prepare($sql) {
                return new class {
                    public function bind_param(...$args) {}
                    public function execute() { return true; }
                    public function get_result() {
                        return new class {
                            public $num_rows = 0;
                            public function fetch_assoc() { return null; }
                            public function fetch_all($mode = 0) { return []; }
                        };
                    }
                    public function close() {}
                };
            }
            public function autocommit($bool) {}
            public function commit() {}
            public function rollback() {}
            public function close() {}
        };

        return $mockConn;
    }
}

if (!function_exists('getDynamicPaymentAmount')) {
    function getDynamicPaymentAmount() { return 10000; } // 100.00
}

if (!function_exists('getDynamicPaymentCurrency')) {
    function getDynamicPaymentCurrency() { return 'SAR'; }
}

if (!function_exists('getMoyasarPublishableKey')) {
    function getMoyasarPublishableKey() { return 'pk_test_1234567890'; }
}

if (!function_exists('getMoyasarSecretKey')) {
    function getMoyasarSecretKey() { return 'sk_test_1234567890'; }
}

if (!function_exists('getFormattedPaymentAmount')) {
    function getFormattedPaymentAmount() {
        return (getDynamicPaymentAmount() / 100) . ' ' . getDynamicPaymentCurrency();
    }
}
