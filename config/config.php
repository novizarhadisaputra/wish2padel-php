<?php
// Helper to load .env file
if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
        return true;
    }
}

// Load .env from root
loadEnv(__DIR__ . '/../.env');

// Session Security Settings
if (isset($_ENV['SESSION_SECURE'])) {
    ini_set('session.cookie_secure', $_ENV['SESSION_SECURE'] === 'true' ? '1' : '0');
}
if (isset($_ENV['SESSION_HTTPONLY'])) {
    ini_set('session.cookie_httponly', $_ENV['SESSION_HTTPONLY'] === 'true' ? '1' : '0');
}
if (isset($_ENV['SESSION_SAMESITE'])) {
    ini_set('session.cookie_samesite', $_ENV['SESSION_SAMESITE']);
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'wish2padel');

if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        static $conn;
        if ($conn) return $conn;

        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                error_log("Connection failed: " . $conn->connect_error);
                // Return mock only on absolute failure if needed, or throw
                throw new Exception("Database connection failed");
            }
            $conn->set_charset("utf8mb4");
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            // Last resort fallback to avoid complete crash during refactor
            return new class {
                public $error = 'Database Connection Failed';
                public $insert_id = 0;
                public function query($sql) { return false; }
                public function prepare($sql) { return false; }
                public function close() {}
            };
        }

        return $conn;
    }
}

if (!function_exists('getDynamicPaymentAmount')) {
    function getDynamicPaymentAmount() { 
        return ($_ENV['MOYASAR_AMOUNT'] ?? 100) * 100; 
    }
}

if (!function_exists('getDynamicPaymentCurrency')) {
    function getDynamicPaymentCurrency() { 
        return $_ENV['MOYASAR_CURRENCY'] ?? 'SAR'; 
    }
}

if (!function_exists('getMoyasarPublishableKey')) {
    function getMoyasarPublishableKey() { 
        return $_ENV['MOYASAR_PUBLISHABLE_KEY'] ?? ''; 
    }
}

if (!function_exists('getMoyasarSecretKey')) {
    function getMoyasarSecretKey() { 
        return $_ENV['MOYASAR_SECRET_KEY'] ?? ''; 
    }
}

if (!function_exists('getFormattedPaymentAmount')) {
    function getFormattedPaymentAmount() {
        return (getDynamicPaymentAmount() / 100) . ' ' . getDynamicPaymentCurrency();
    }
}
