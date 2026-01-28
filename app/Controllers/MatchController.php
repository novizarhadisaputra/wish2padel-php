<?php

namespace App\Controllers;

class MatchController
{
    public function show()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        $match_id = $_GET['id'] ?? null;

        if (!$match_id) {
            echo "<div class='alert alert-danger m-4'>Match ID not found.</div>";
            return;
        }

        $match = null;
        $pairs = [];

        if ($conn && $match_id) {
            // Ambil data match + logo tim
            $sql = "
            SELECT m.id AS match_id, m.scheduled_date,
                t1.id AS team1_id, t1.team_name AS team1, t1.logo AS logo1,
                t2.id AS team2_id, t2.team_name AS team2, t2.logo AS logo2,
                tcd1.club AS club1, tcd1.division AS division1,
                tour.name AS tournament_name
            FROM matches m
            JOIN team_info t1 ON m.team1_id = t1.id
            JOIN team_info t2 ON m.team2_id = t2.id
            JOIN team_contact_details tcd1 ON tcd1.team_id = t1.id
            JOIN tournaments tour ON tour.id = m.tournament_id
            WHERE m.id = ?
            ";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $match_id);
                $stmt->execute();
                $match = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }

            // Ambil semua pair
            $sql_pairs = "
            SELECT tp.id AS pair_id, tp.pair_number, tp.team_id
            FROM team_pairs tp
            WHERE tp.match_id = ?
            ORDER BY tp.pair_number ASC
            ";
            $stmt_pairs = $conn->prepare($sql_pairs);
            if ($stmt_pairs) {
                $stmt_pairs->bind_param("i", $match_id);
                $stmt_pairs->execute();
                $pairs_res = $stmt_pairs->get_result();
                if ($pairs_res) {
                    while($pair = $pairs_res->fetch_assoc()) {
                        $pair_id = $pair['pair_id'];

                        // Pemain
                        $stmt_players = $conn->prepare("SELECT player_name FROM pair_players WHERE pair_id=? ORDER BY id ASC");
                        if ($stmt_players) {
                            $stmt_players->bind_param("i", $pair_id);
                            $stmt_players->execute();
                            $players_res = $stmt_players->get_result();
                            $players = [];
                            if ($players_res) {
                                while($p = $players_res->fetch_assoc()) $players[] = $p['player_name'];
                            }
                            $pair['players'] = $players;
                            $stmt_players->close();
                        }

                        // Skor per set
                        $stmt_scores = $conn->prepare("SELECT set_number, team_score FROM pair_scores WHERE match_id=? AND pair_id=? ORDER BY set_number ASC");
                        if ($stmt_scores) {
                            $stmt_scores->bind_param("ii", $match_id, $pair_id);
                            $stmt_scores->execute();
                            $scores_res = $stmt_scores->get_result();
                            $scores = [];
                            if ($scores_res) {
                                while($s = $scores_res->fetch_assoc()) $scores[$s['set_number']] = $s['team_score'];
                            }
                            $pair['scores'] = $scores;
                            $stmt_scores->close();
                        }

                        $pairs[] = $pair;
                    }
                }
                $stmt_pairs->close();
            }
        }

        // Kelompokkan berdasarkan pair_number
        $pairs_by_number = [];
        foreach($pairs as $p) {
            $pairs_by_number[$p['pair_number']][$p['team_id']] = $p;
        }

        view('match.show', compact('match', 'pairs_by_number', 'conn'));
    }
}
