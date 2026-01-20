<?php
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|woff|woff2|ttf|eot)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is.
} else {
    // Determine which index to load.
    // If request is for /public/..., we might need to handle it.
    // But our index.php in root delegates to public/index.php.
    include __DIR__ . '/index.php';
}
?>
