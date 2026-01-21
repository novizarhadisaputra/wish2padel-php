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

    public function leaderboard()
    {
        $conn = getDBConnection();
        $tournament_id = 1;

        // Ambil nama tournament
        $tour_stmt = $conn->prepare("SELECT name, start_date, end_date FROM tournaments WHERE id = ?");
        $tour_stmt->bind_param("i", $tournament_id);
        $tour_stmt->execute();
        $tour_result = $tour_stmt->get_result()->fetch_assoc();

        if (!$tour_result) {
            // Handle error or default
             $tournament_name = "Tournament Not Found";
             $tournament_period = "-";
        } else {
            $tournament_name = $tour_result['name'];
            $tournament_period = date("F Y", strtotime($tour_result['start_date'])) . " – " . date("F Y", strtotime($tour_result['end_date']));
        }

        $sql = "
            SELECT
                t.id AS team_id,
                t.team_name,
                COUNT(mr.id) AS P,
                SUM(CASE WHEN mr.winner_team_id = t.id THEN 1 ELSE 0 END) AS W,
                SUM(CASE WHEN mr.winner_team_id != t.id AND mr.winner_team_id IS NOT NULL THEN 1 ELSE 0 END) AS L,
                (COALESCE(SUM(CASE WHEN mr.winner_team_id = t.id THEN 2 ELSE 0 END), 0)
                 - COALESCE((SELECT SUM(points) FROM team_penalties tp WHERE tp.team_id = t.id AND tp.tournament_id = ?), 0))
                AS points
            FROM team_info t
            LEFT JOIN matches m ON t.id = m.team1_id OR t.id = m.team2_id
            LEFT JOIN match_results mr ON m.id = mr.match_id
            WHERE m.tournament_id = ?
            GROUP BY t.id, t.team_name
            ORDER BY points DESC, W DESC, t.team_name ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $tournament_id, $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $leaderboard = [];
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            $row['rank'] = $rank++;
            $leaderboard[] = $row;
        }

        view('leaderboard', compact('leaderboard', 'tournament_name', 'tournament_period'));
    }

    public function ranking()
    {
        $conn = getDBConnection();
        // Ambil filter dari query string
        $gender_filter = $_GET['gender'] ?? 'Pria';
        $search_name = $_GET['search_name'] ?? '';

        // Ambil data semua match per pemain dengan sets_played
        $sql = "
            SELECT pp.id AS player_id, pp.player_name, tm.gender, t.team_name,
                   ps.match_id, ps.team_score, ps.is_winner,
                   (SELECT COUNT(*) FROM pair_scores ps2 WHERE ps2.match_id = ps.match_id AND ps2.pair_id = ps.pair_id) AS sets_played
            FROM pair_players pp
            INNER JOIN team_members_info tm ON pp.id = tm.id
            INNER JOIN team_pairs tp ON pp.pair_id = tp.id
            INNER JOIN pair_scores ps ON tp.id = ps.pair_id
            INNER JOIN team_info t ON tp.team_id = t.id
            WHERE tm.gender = ?
        ";

        $params = [$gender_filter];
        $types = "s";

        if (!empty($search_name)) {
            $sql .= " AND pp.player_name LIKE ?";
            $params[] = "%$search_name%";
            $types .= "s";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        // Hitung point per match dan total per pemain
        $leaderboard = [];
        foreach ($rows as $row) {
            $pid = $row['player_id'];
            $point_per_player = $row['team_score'] / 2 / max($row['sets_played'],1); // point proporsional
            if (!isset($leaderboard[$pid])) {
                $leaderboard[$pid] = [
                    'player_name' => $row['player_name'],
                    'team_name' => $row['team_name'],
                    'point_match_total' => 0,
                    'match_won' => 0,
                    'match_lost' => 0,
                    'total_matches' => 0
                ];
            }
            $leaderboard[$pid]['point_match_total'] += $point_per_player;
            $leaderboard[$pid]['match_won'] += $row['is_winner'];
            $leaderboard[$pid]['match_lost'] += (1 - $row['is_winner']);
            $leaderboard[$pid]['total_matches'] += 1;
        }

        // Urutkan berdasarkan point_match_total
        usort($leaderboard, function($a,$b){
            return $b['point_match_total'] <=> $a['point_match_total'];
        });

        view('ranking', compact('leaderboard', 'gender_filter', 'search_name'));
    }
}
