<?php

namespace App\Controllers\Admin;

class DashboardController
{
    public function index()
    {
        // Session check (Auth Middleware ideally, but manual for now)
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            redirect('/login');
        }

        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? 'User';
        $role = $_SESSION['role'] ?? 'Admin';
        
        date_default_timezone_set("Asia/Riyadh");
        $server_time = date('c');
        $server_datetime = date('l, d F Y H:i:s'); 

        // Dashboard Stats
        $stats = [
            'leagues' => 0, 'zones' => 0, 'news' => 0, 'matches' => 0,
            'sponsors' => 0, 'teams' => 0, 'clubs' => 0, 'players' => 0,
            'completed_matches' => 0
        ];
        $next_match = null;
        $transactions = [];

        if ($conn) {
            $stats = [
                'leagues' => $conn->query("SELECT COUNT(*) AS total FROM league")->fetch_assoc()['total'] ?? 0,
                'zones' => $conn->query("SELECT COUNT(DISTINCT name) AS total FROM tournaments")->fetch_assoc()['total'] ?? 0,
                'news' => $conn->query("SELECT COUNT(*) AS total FROM blog_news")->fetch_assoc()['total'] ?? 0,
                'matches' => $conn->query("SELECT COUNT(*) AS total FROM matches")->fetch_assoc()['total'] ?? 0,
                'sponsors' => $conn->query("SELECT COUNT(*) AS total FROM sponsors")->fetch_assoc()['total'] ?? 0,
                'teams' => $conn->query("SELECT COUNT(DISTINCT team_name) AS total FROM team_info")->fetch_assoc()['total'] ?? 0,
                'clubs' => $conn->query("SELECT COUNT(*) AS total FROM centers")->fetch_assoc()['total'] ?? 0,
                'players' => $conn->query("SELECT COUNT(DISTINCT player_name) AS total FROM team_members_info")->fetch_assoc()['total'] ?? 0, 
                'completed_matches' => $conn->query("SELECT COUNT(DISTINCT match_id) AS total FROM match_results WHERE status='accept'")->fetch_assoc()['total'] ?? 0
            ];
            
            // Next Match
            $now = date('Y-m-d H:i:s');
            $next_match_res = $conn->query("
                SELECT m.id, m.tournament_id, m.journey, m.team1_id, m.team2_id, m.scheduled_date,
                       t.name AS tournament_name,
                       team1.team_name AS team1_name,
                       team2.team_name AS team2_name
                FROM matches m
                LEFT JOIN tournaments t ON m.tournament_id = t.id
                LEFT JOIN team_info team1 ON m.team1_id = team1.id
                LEFT JOIN team_info team2 ON m.team2_id = team2.id
                WHERE m.scheduled_date >= '$now'
                ORDER BY m.scheduled_date ASC
                LIMIT 1
            ");
            if ($next_match_res) $next_match = $next_match_res->fetch_assoc();

            // Recent Transactions
            $transactions_res = $conn->query("
            SELECT pt.id, pt.team_id, pt.tournament_id, pt.status,
                   tm.team_name, tr.name AS tournament_name
            FROM payment_transactions pt
            LEFT JOIN team_info tm ON pt.team_id = tm.id
            LEFT JOIN tournaments tr ON pt.tournament_id = tr.id
            WHERE pt.id IN (
                SELECT MAX(pt2.id)
                FROM payment_transactions pt2
                WHERE pt2.status = 'paid'
                GROUP BY pt2.team_id, DATE(pt2.created_at)
            )
            ORDER BY pt.id DESC
            LIMIT 1;
            ");
            if ($transactions_res) {
                while($row = $transactions_res->fetch_assoc()) $transactions[] = $row;
            }
        }
        
        // Payment Settings
        $amountHalalah = getDynamicPaymentAmount();
        $amountSAR = $amountHalalah / 100;

        view('admin.dashboard', compact(
            'username', 'role', 'server_time', 'server_datetime', 
            'stats', 'next_match', 'transactions', 'amountSAR', 'amountHalalah'
        ));
    }
}
