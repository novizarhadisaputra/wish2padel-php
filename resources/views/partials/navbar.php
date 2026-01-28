<?php
// Router for Navbar
$role = $_SESSION['role'] ?? null;
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
// Normalize URI
$normalizedUri = $requestUri;
if ($scriptName !== '/' && $scriptName !== '\\' && strpos($requestUri, $scriptName) === 0) {
    $normalizedUri = substr($requestUri, strlen($scriptName));
}
$isAdminRoute = (strpos($normalizedUri, '/admin') === 0);

if ($role === 'admin' && $isAdminRoute) {
    include __DIR__ . '/navbar_admin.php';
} else {
    include __DIR__ . '/navbar_public.php';
}
?>