<?php
require 'config/config.php';

$conn = getDBConnection();

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "CREATE TABLE IF NOT EXISTS team_penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    tournament_id INT NOT NULL,
    points_deduction INT DEFAULT 0,
    fine_amount DECIMAL(10, 2) DEFAULT 0.00,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES team_info(id) ON DELETE CASCADE,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'team_penalties' created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
