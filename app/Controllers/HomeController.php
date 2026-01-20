<?php

namespace App\Controllers;

class HomeController
{
    public function index()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        
        $conn->query("SET time_zone = '+03:00'");
        $result = $conn->query("
            SELECT t.*, l.date, l.name AS league_name
            FROM tournaments t
            JOIN league l ON t.id_league = l.id
            WHERE YEAR(l.date) = YEAR(CURDATE())
            ORDER BY l.date DESC, t.id DESC
        ");

        view('home', compact('result', 'username'));
    }
}
