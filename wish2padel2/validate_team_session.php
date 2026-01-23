<?php
// validate_team_session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['team_id'])) return;

$team_id = (int)$_SESSION['team_id'];

$stmt = $conn->prepare("
    SELECT 
        COALESCE(TRIM(tcd.division), '') AS division,
        SUM(
            CASE 
                WHEN pt.status IS NOT NULL 
                     AND LOWER(TRIM(pt.status)) REGEXP 'paid|success|settle|complete|capture'
                THEN 1 ELSE 0 
            END
        ) AS payment_count
    FROM team_info ti
    LEFT JOIN team_contact_details tcd ON tcd.team_id = ti.id
    LEFT JOIN payment_transactions pt ON pt.team_id = ti.id
    WHERE ti.id = ?
    GROUP BY ti.id
    LIMIT 1
");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$hasPayment = (int)($result['payment_count'] ?? 0);
$division   = trim($result['division'] ?? '');

if ($hasPayment == 0 && $division == '') {
    $stmt2 = $conn->prepare("SELECT created_at FROM team_info WHERE id = ?");
    $stmt2->bind_param("i", $team_id);
    $stmt2->execute();
    $created_at = strtotime($stmt2->get_result()->fetch_assoc()['created_at'] ?? '1970-01-01');
    $stmt2->close();

    // Debug log (opsional)
    // error_log("🕒 Created: $created_at, Now: " . time());

    if (time() - $created_at > 180) {
        session_unset();
        session_destroy();

        // hapus cookie PHPSESSID
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // redirect optional
        header("Location: login/login");
        exit;
    }
}
