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
// Session Security Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Only set secure cookie if HTTPS is detected
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

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
                error_log("Database Connection failed: " . $conn->connect_error);
                $conn = null;
                return null;
            }
            $conn->set_charset("utf8mb4");
        } catch (\Throwable $e) {
            error_log("Database Exception: " . $e->getMessage());
            $conn = null;
            return null;
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
