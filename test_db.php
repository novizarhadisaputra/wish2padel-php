<?php
require_once __DIR__ . '/config/config.php';

$conn = getDBConnection();

if ($conn && $conn instanceof mysqli) {
    if ($conn->connect_error) {
        echo "Connection Error: " . $conn->connect_error . "\n";
    } else {
        echo "Connection Successful!\n";
        $res = $conn->query("SELECT 1");
        if ($res) {
            echo "Query Successful!\n";
        } else {
            echo "Query Failed: " . $conn->error . "\n";
        }
    }
} else {
    echo "Connection failed (getDBConnection returned null)\n";
}
