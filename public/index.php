<?php
// Display errors for debugging during refactor (REMOVE IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Configuration (DB, Env, Session)
require_once __DIR__ . '/../config/config.php';

// Initialize Session (after config load so settings apply)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/Helpers/view.php';

// Autoloader (Simple manual autoloader for now, or Composer later)
spl_autoload_register(function ($class) {
    // Prefix mapping
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    // Does the class use the prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Load Routes
require_once __DIR__ . '/../routes/web.php';

// Dispatch Router
use App\Core\Router;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Remove subfolder from URI if running in subdirectory (e.g. /wish2padel/public)
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$script_name = str_replace('\\', '/', $script_name); // Ensure forward slashes

if (strpos($uri, $script_name) === 0 && $script_name !== '/') {
    $uri = substr($uri, strlen($script_name));
} else {
    // If we are rewriting from root, SCRIPT_NAME might contain /public but URI won't
    // Try checking parent directory (e.g. /wish2padel instead of /wish2padel/public)
    $parent_script_name = dirname($script_name);
    if ($parent_script_name !== '/' && strpos($uri, $parent_script_name) === 0) {
         $uri = substr($uri, strlen($parent_script_name));
    }
}

Router::dispatch($uri);
