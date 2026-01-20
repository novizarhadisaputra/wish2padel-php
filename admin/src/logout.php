<?php
session_start();

// Bersihkan semua data session
$_SESSION = [];

// Hancurkan session
session_destroy();

// Redirect ke halaman login setelah logout
header("Location: ../../index.php");
exit();
