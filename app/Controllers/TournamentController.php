<?php

namespace App\Controllers;

class TournamentController
{
    public function show()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        
        $tournament_id = $_GET['id'] ?? null;
        $selected_division = isset($_GET['division']) ? intval($_GET['division']) : null;
        
        if (!$tournament_id) {
            // Can be handled better (e.g. 404 or redirect), but echoing error for now to match original behavior
            echo "<div class='alert alert-danger'>Tournament ID tidak ditemukan.</div>";
            return;
        }
        
        // 1. Get Tournament Info
        $stmt = $conn->prepare("SELECT name FROM tournaments WHERE id = ?");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        $tournament = $stmt->get_result()->fetch_assoc();
        
        // 2. Get Divisions
        $divisions_res = $conn->query("SELECT id, division_name FROM divisions ORDER BY id ASC");
        $divisions = [];
        while($row = $divisions_res->fetch_assoc()) {
            $divisions[] = $row;
        }
        
        // 3. Auto-select division if needed
        if ($selected_division === null) {
            // Original code had a loop checking for matches in divisions but eventually just picked the first one 
            // if logic wasn't fully guarding it. 
            // Simplified from original:
            if (!empty($divisions)) {
                $selected_division = $divisions[0]['id'];
            }
        }
        
        // 4. Fetch Schedule / Matches (Journeys)
        $sql = "
            SELECT m.id, m.scheduled_date, m.status, m.journey,
                t1.id AS team1_id, t1.team_name AS team1, t1.logo AS team1_logo, c1.division AS team1_division, t1.level AS team1_level,
                t2.id AS team2_id, t2.team_name AS team2, t2.logo AS team2_logo, c2.division AS team2_division, t2.level AS team2_level,
                mr1.pairs_won AS score1,
                mr2.pairs_won AS score2
            FROM matches m
            JOIN team_info t1 ON m.team1_id = t1.id
            JOIN team_info t2 ON m.team2_id = t2.id
            JOIN team_contact_details c1 ON c1.team_id = t1.id
            JOIN team_contact_details c2 ON c2.team_id = t2.id
            LEFT JOIN match_results mr1 ON mr1.match_id = m.id AND mr1.team_id = t1.id AND mr1.status='accept'
            LEFT JOIN match_results mr2 ON mr2.match_id = m.id AND mr2.team_id = t2.id AND mr2.status='accept'
            WHERE m.tournament_id = ?
        ";
        
        if ($selected_division !== null && $selected_division !== 0) {
            $sql .= " AND (c1.division = ? AND c2.division = ?)";
        }
        
        $sql .= " ORDER BY m.journey ASC, m.scheduled_date ASC";
        
        $stmt = $conn->prepare($sql);
        
        if ($selected_division !== null && $selected_division !== 0) {
            $stmt->bind_param("iii", $tournament_id, $selected_division, $selected_division);
        } else {
            $stmt->bind_param("i", $tournament_id);
        }
        
        $stmt->execute();
        $res = $stmt->get_result();
        
        $journeys = [];
        while($row = $res->fetch_assoc()) {
            $journeys[$row['journey']][] = $row;
        }
        $total_journey = count($journeys);

        // 5. Build Leaderboard
        $leaderboard = $this->buildLeaderboard($conn, $tournament_id, $selected_division);

        // 6. Check for Champion Logic
        $championTeamId   = $leaderboard[0]['team_id']   ?? null;
        $championTeamName = $leaderboard[0]['team_name'] ?? 'TBD';
        
        $championLogo = "../uploads/logo/default.png";
        if ($championTeamId) {
            $stmtLogo = $conn->prepare("SELECT logo FROM team_info WHERE id = ? LIMIT 1");
            $stmtLogo->bind_param("i", $championTeamId);
            $stmtLogo->execute();
            $logoRow = $stmtLogo->get_result()->fetch_assoc();
            if(!empty($logoRow['logo'])) {
                $championLogo = "../uploads/logo/".$logoRow['logo'];
            }
        }

        // 7. Check if all journeys done (for champion display)
        $journeyCheck = $conn->prepare("
            SELECT COUNT(*) AS not_done
            FROM matches m
            JOIN team_contact_details t1 ON t1.team_id = m.team1_id
            JOIN team_contact_details t2 ON t2.team_id = m.team2_id
            WHERE m.tournament_id = ?
              AND t1.division = ?
              AND t2.division = ?
              AND (m.notes IS NULL OR TRIM(m.notes) = '')
              AND (m.status IS NULL OR LOWER(m.status) <> 'completed')
        ");
        $journeyCheck->bind_param("iii", $tournament_id, $selected_division, $selected_division);
        $journeyCheck->execute();
        $notDone = $journeyCheck->get_result()->fetch_assoc()['not_done'] ?? 0;
        $allJourneysDone = ($notDone == 0);

        view('tournament.show', compact(
            'tournament', 'tournament_id', 'divisions', 'selected_division',
            'journeys', 'total_journey', 'leaderboard',
            'championTeamName', 'championLogo', 'allJourneysDone', 'conn'
        ));
    }

    private function buildLeaderboard($conn, $tournament_id, $selected_division = null) {
        // Ambil tim per divisi (kalau ada filter), hanya yang ikut tournament ini
        if (!empty($selected_division)) {
            $sqlTeams = "
                SELECT DISTINCT ti.id AS team_id, ti.team_name
                FROM team_info ti
                JOIN team_contact_details tcd ON tcd.team_id = ti.id AND tcd.division = ?
                WHERE EXISTS (
                    SELECT 1 FROM matches m
                    WHERE (m.team1_id = ti.id OR m.team2_id = ti.id)
                      AND m.tournament_id = ?
                )
                ORDER BY ti.team_name ASC
            ";
            $stmtTeams = $conn->prepare($sqlTeams);
            $stmtTeams->bind_param("ii", $selected_division, $tournament_id);
        } else {
            $sqlTeams = "
                SELECT DISTINCT ti.id AS team_id, ti.team_name
                FROM team_info ti
                WHERE EXISTS (
                    SELECT 1 FROM matches m
                    WHERE (m.team1_id = ti.id OR m.team2_id = ti.id)
                      AND m.tournament_id = ?
                )
                ORDER BY ti.team_name ASC
            ";
            $stmtTeams = $conn->prepare($sqlTeams);
            $stmtTeams->bind_param("i", $tournament_id);
        }
        $stmtTeams->execute();
        $resTeams = $stmtTeams->get_result();
    
        $leaderboard = [];
    
        // Kueri bantu
        $sqlMatches = "
            SELECT DISTINCT m.id AS match_id
            FROM matches m
            WHERE (m.team1_id = ? OR m.team2_id = ?)
              AND m.tournament_id = ?
              AND m.status = 'completed'
              AND m.notes IS NULL
        ";
        $stmtM = $conn->prepare($sqlMatches);
    
        $sqlPairs = "SELECT id FROM team_pairs WHERE match_id = ? AND team_id = ?";
        $stmtP = $conn->prepare($sqlPairs);
    
        $sqlSets  = "SELECT is_winner FROM pair_scores WHERE pair_id = ? AND match_id = ?";
        $stmtS = $conn->prepare($sqlSets);
    
        while ($t = $resTeams->fetch_assoc()) {
            $team_id = (int)$t['team_id'];
    
            $matches_played = 0;
            $matches_won    = 0;
            $matches_lost   = 0;
            $pairs_won_total = 0;
            $pairs_lost_total = 0;
            $sets_won_total  = 0;
            $sets_lost_total  = 0;
            $points          = 0;
    
            // Ambil semua match completed REGULAR (notes IS NULL)
            $stmtM->bind_param("iii", $team_id, $team_id, $tournament_id);
            $stmtM->execute();
            $resM = $stmtM->get_result();
    
            while ($m = $resM->fetch_assoc()) {
                $match_id = (int)$m['match_id'];
    
                $pairs_won  = 0;
                $pairs_lost = 0;
                $sets_won   = 0;
                $sets_lost  = 0;
                $has_result = false;
    
                // Pairs dari tim ini pada match tsb
                $stmtP->bind_param("ii", $match_id, $team_id);
                $stmtP->execute();
                $resP = $stmtP->get_result();
    
                while ($p = $resP->fetch_assoc()) {
                    $pair_id = (int)$p['id'];
                    $pw = 0; $pl = 0;
    
                    $stmtS->bind_param("ii", $pair_id, $match_id);
                    $stmtS->execute();
                    $resS = $stmtS->get_result();
    
                    while ($s = $resS->fetch_assoc()) {
                        $has_result = true;
                        if ((int)$s['is_winner'] === 1) { $pw++; $sets_won++; }
                        else                           { $pl++; $sets_lost++; }
                    }
    
                    if ($pw > $pl)      $pairs_won++;
                    elseif ($pw < $pl)  $pairs_lost++;
                }
    
                if ($has_result) {
                    $matches_played++;
                    // Poin match (3-0 => 3 pts, 2-1 => 2 pts, 1-2 => 1 pt, 0-3 => 0 pt)
                    if     ($pairs_won == 3) { $matches_won++;  $points += 3; }
                    elseif ($pairs_won == 2 && $pairs_lost == 1) { $matches_won++;  $points += 2; }
                    elseif ($pairs_won == 1 && $pairs_lost == 2) { $matches_lost++; $points += 1; }
                    elseif ($pairs_won == 0 && $pairs_lost == 3) { $matches_lost++; $points += 0; }
    
                    $pairs_won_total  += $pairs_won;
                    $pairs_lost_total += $pairs_lost;
                    $sets_won_total   += $sets_won;
                    $sets_lost_total  += $sets_lost;
                }
            }
    
            $leaderboard[] = [
                'team_id'        => $team_id,
                'team_name'      => $t['team_name'],
                'matches_played' => $matches_played,
                'matches_won'    => $matches_won,
                'matches_lost'   => $matches_lost,
                'pairs_won'      => $pairs_won_total,
                'pairs_lost'     => $pairs_lost_total,
                'sets_won'       => $sets_won_total,
                'sets_lost'      => $sets_lost_total,
                'points'         => $points,
            ];
        }
    
        // Head-to-head tie-break + set/pair diff
        $__H2H_SQL = "
            SELECT t.team_id, SUM(t.pair_won) AS pairs_won
            FROM (
                SELECT tp.team_id, tp.id AS pair_id,
                       CASE WHEN SUM(ps.is_winner) > COUNT(*)/2 THEN 1 ELSE 0 END AS pair_won
                FROM matches m
                JOIN team_pairs tp ON tp.match_id = m.id
                JOIN pair_scores ps ON ps.match_id = m.id AND ps.pair_id = tp.id
                WHERE m.tournament_id = ?
                  AND m.status = 'completed'
                  AND m.notes IS NULL
                  AND ( (m.team1_id = ? AND m.team2_id = ?) OR (m.team1_id = ? AND m.team2_id = ?) )
                  AND tp.team_id IN (?, ?)
                GROUP BY tp.team_id, tp.id
                HAVING COUNT(*) > 0
            ) AS t
            GROUP BY t.team_id
        ";
        $__H2H_STMT  = $conn->prepare($__H2H_SQL);
        $__H2H_CACHE = [];
    
        usort($leaderboard, function($a, $b) use ($tournament_id, $__H2H_STMT, &$__H2H_CACHE) {
            // 1) Points
            if ($b['points'] !== $a['points']) return $b['points'] - $a['points'];
    
            // 2) H2H by pairs won
            $A = (int)$a['team_id']; $B = (int)$b['team_id'];
            $k = $A.'-'.$B;
            if (!isset($__H2H_CACHE[$k])) {
                $__H2H_STMT->bind_param("iiiiiii", $tournament_id, $A, $B, $B, $A, $A, $B);
                $__H2H_STMT->execute();
                $res = $__H2H_STMT->get_result();
                $pw = [$A=>0, $B=>0];
                while ($row = $res->fetch_assoc()) $pw[(int)$row['team_id']] = (int)$row['pairs_won'];
                $diff = $pw[$A] - $pw[$B];
                $__H2H_CACHE[$A.'-'.$B] = $diff;
                $__H2H_CACHE[$B.'-'.$A] = -$diff;
            }
            $h2h = $__H2H_CACHE[$k];
            if ($h2h !== 0) return ($h2h > 0) ? -1 : 1;
    
            // 3) Set diff
            $sdA = $a['sets_won'] - $a['sets_lost'];
            $sdB = $b['sets_won'] - $b['sets_lost'];
            if ($sdB !== $sdA) return $sdB - $sdA;
    
            // 4) Pair diff
            $pdA = $a['pairs_won'] - $a['pairs_lost'];
            $pdB = $b['pairs_won'] - $b['pairs_lost'];
            if ($pdB !== $pdA) return $pdB - $pdA;
    
            // 5) Set won
            if ($b['sets_won'] !== $a['sets_won']) return $b['sets_won'] - $a['sets_won'];
    
            // 6) Pair won
            if ($b['pairs_won'] !== $a['pairs_won']) return $b['pairs_won'] - $a['pairs_won'];
    
            // 7) Name
            return strcasecmp($a['team_name'], $b['team_name']);
        });
    
        return $leaderboard;
    }
}
