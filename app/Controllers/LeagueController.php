<?php

namespace App\Controllers;

use App\Core\SimplePaymentSystem;
use DateTime;

class LeagueController
{
    public function about()
    {
        $conn = getDBConnection();
        $result = $conn->query("SELECT * FROM presentations ORDER BY id ASC");
        
        view('about-league', compact('result'));
    }

    public function registration()
    {
        $conn = getDBConnection();
        $team_id = $_SESSION['team_id'] ?? null;

        date_default_timezone_set("Asia/Riyadh");
        $today = new DateTime();
        $currentYear = (int)$today->format('Y');

        // Ambil semua liga tahun ini
        $leagueStmt = $conn->prepare("SELECT id, name, date FROM league WHERE YEAR(date) = ? ORDER BY date DESC");
        $leagueStmt->bind_param("i", $currentYear);
        $leagueStmt->execute();
        $leagueRes = $leagueStmt->get_result();
        $currentLeagues = $leagueRes->fetch_all(MYSQLI_ASSOC);
        $leagueIds = array_column($currentLeagues, 'id');

        // Ambil semua turnamen di liga tahun ini
        $tournaments = [];
        if ($leagueIds) {
            $in = implode(',', $leagueIds);
            $tournamentResult = $conn->query("
                SELECT id, name, description, start_date, end_date, id_league 
                FROM tournaments 
                WHERE id_league IN ($in)
                ORDER BY start_date DESC
            ");
            $tournaments = $tournamentResult->fetch_all(MYSQLI_ASSOC);
        }

        // Pass dependencies for view logic (or process it here, but keeping it close to original for now)
        // We need to pass the PaymentSystem class or instance if the view uses it extensively, 
        // or better, prepare the data here.
        // The view `regis.php` instantiates `SimplePaymentSystem`. 
        // We will pass the data needed to the view.
        
    
        view('registration', compact('currentLeagues', 'tournaments', 'team_id', 'today', 'currentYear', 'conn'));
    }

    public function hub()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        
        $leagueResult = $conn->query("SELECT * FROM league ORDER BY date DESC");
        $leagues = [];
        while ($row = $leagueResult->fetch_assoc()) {
            $leagues[] = $row;
        }
        
        $tournamentResult = $conn->query("
            SELECT t.*, l.name AS league_name, l.date AS league_year 
            FROM tournaments t 
            LEFT JOIN league l ON t.id_league = l.id 
            ORDER BY l.date DESC, t.start_date DESC
        ");
        $tournaments = [];
        while ($row = $tournamentResult->fetch_assoc()) {
            $tournaments[$row['league_year']][$row['id_league']][] = $row; 
        }
        
        view('league', compact('leagues', 'tournaments'));
    }
}
