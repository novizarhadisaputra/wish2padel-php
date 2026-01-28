<?php

namespace App\Controllers;

use DateTime;
use App\Core\SimplePaymentSystem;

class DashboardController
{
    public function index()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        $team_id = $_SESSION['team_id'] ?? null;
        
        if (!$team_id) {
            header("Location: " . asset('login'));
            exit();
        }

        $tournament_id = 1;
        if ($conn) {
            $t_stmt = $conn->prepare("SELECT tournament_id FROM team_info WHERE id = ?");
            if ($t_stmt) {
                $t_stmt->bind_param("i", $team_id);
                $t_stmt->execute();
                $tournament_id = $t_stmt->get_result()->fetch_assoc()['tournament_id'] ?? 1;
                $t_stmt->close();
            }
        }
        
        $paymentSystem = new SimplePaymentSystem();
        $is_paid = $paymentSystem->isTeamPaid($team_id, $tournament_id);
        
        if (!$is_paid) {
            header("Location: " . asset('regis'));
            exit();
        }
        
        date_default_timezone_set("Asia/Riyadh");
        $now = new DateTime();
        
        // Match checking logic (abbreviated for MVC, ideally moved to a service)
        if ($conn) {
            $matchResult = $conn->prepare("SELECT id, scheduled_date FROM matches WHERE (team1_id = ? OR team2_id = ?) AND status != 'completed'");
            if ($matchResult) {
                $matchResult->bind_param("ii", $team_id, $team_id);
                $matchResult->execute();
                $matchData = $matchResult->get_result();
                
                while ($matchData && $match = $matchData->fetch_assoc()) {
                    $match_id = $match['id'];
                    $matchTime = new DateTime($match['scheduled_date']);
                
                    $startWindow  = (clone $matchTime)->modify('-20 minutes');
                    $midWindow    = (clone $matchTime)->modify('+40 minutes');
                    $resultWindow = (clone $matchTime)->modify('+90 minutes');
                
                    if ($now >= $startWindow && $now <= $midWindow) {
                        // Check if pairs declared
                        $pairCheck = $conn->prepare("
                            SELECT COUNT(*) AS total_players 
                            FROM pair_players
                            WHERE pair_id IN (
                                SELECT id FROM team_pairs WHERE match_id = ? AND team_id = ?
                            )
                        ");
                        if ($pairCheck) {
                            $pairCheck->bind_param("ii", $match_id, $team_id);
                            $pairCheck->execute();
                            $pairCount = $pairCheck->get_result()->fetch_assoc()['total_players'];
                            $pairCheck->close();
                    
                            if ($pairCount == 0) {
                                $_SESSION['current_match_id'] = $match_id;
                                header("Location: auth/auth_scheduled.php"); 
                                exit();
                            }
                        }
                    }
                
                    if ($now >= $resultWindow) {
                        // Check result status
                        $resStmt = $conn->prepare("
                            SELECT status FROM match_results WHERE match_id = ? AND team_id = ?
                        ");
                        if ($resStmt) {
                            $resStmt->bind_param("ii", $match_id, $team_id);
                            $resStmt->execute();
                            $res = $resStmt->get_result()->fetch_assoc();
                            $resStmt->close();
                    
                            if (!$res || strtolower($res['status']) !== 'accept') {
                                $_SESSION['current_match_id'] = $match_id;
                                header("Location: auth/auth_result.php"); 
                                exit();
                            }
                        }
                    }
                }
            }
        }
        
        // Data gathering for view
        $team = null;
        $selected_division = null;
        $tournament = null;
        $leaderboard = [];
        $resSchedule = null;
        $divisionLabel = '-';

        if ($conn) {
            $sqlTeam = "SELECT ti.id, ti.team_name, ti.captain_name, ti.logo, ti.tournament_id FROM team_info ti WHERE ti.id = ?";
            $stmt = $conn->prepare($sqlTeam);
            if ($stmt) {
                $stmt->bind_param("i", $team_id);
                $stmt->execute();
                $team = $stmt->get_result()->fetch_assoc();
            }
            
            $sqlDetail = "SELECT division FROM team_contact_details WHERE team_id = ? LIMIT 1";
            $stmt = $conn->prepare($sqlDetail);
            if ($stmt) {
                $stmt->bind_param("i", $team_id);
                $stmt->execute();
                $selected_division = $stmt->get_result()->fetch_assoc()['division'] ?? null;
            }
            
            $sqlT = "SELECT id, name, description, start_date FROM tournaments WHERE id = ?";
            $stmt = $conn->prepare($sqlT);
            if ($stmt) {
                $stmt->bind_param("i", $tournament_id);
                $stmt->execute();
                $tournament = $stmt->get_result()->fetch_assoc();
            }

            // Leaderboard Logic
            $leaderboard = $this->getLeaderboard($conn, $tournament_id, $selected_division);
            
            // Schedule
            $sqlSchedule = "
                SELECT 
                    m.id, m.journey, m.team1_id, m.team2_id, m.scheduled_date, m.status,
                    t1.team_name AS team1, t1.logo AS team1_logo,
                    t2.team_name AS team2, t2.logo AS team2_logo
                FROM matches m
                JOIN team_info t1 ON t1.id = m.team1_id
                JOIN team_info t2 ON t2.id = m.team2_id
                WHERE m.tournament_id = ?
                  AND (m.team1_id = ? OR m.team2_id = ?)
                ORDER BY m.scheduled_date ASC
            ";
            $stmt = $conn->prepare($sqlSchedule);
            if ($stmt) {
                $stmt->bind_param("iii", $tournament_id, $team_id, $team_id);
                $stmt->execute();
                $resSchedule = $stmt->get_result();
            }

            // Division Label
            if (!empty($selected_division)) {
                $stmtDiv = $conn->prepare("SELECT division_name FROM divisions WHERE id = ?");
                if ($stmtDiv) {
                    $stmtDiv->bind_param("i", $selected_division);
                    $stmtDiv->execute();
                    $resDiv = $stmtDiv->get_result()->fetch_assoc();
                    $divisionLabel = $resDiv['division_name'] ?? '-';
                }
            }
        }
        
        view('dashboard.index', compact('team', 'tournament', 'leaderboard', 'resSchedule', 'selected_division', 'divisionLabel'));
    }

    private function getLeaderboard($conn, $tournament_id, $selected_division) {
        // ... (This logic is complex, strictly copied from dashboard.php for now to ensure consistency)
        // Ideally this should be in a Service or Model class.
        // For this task, I will replicate the logic inside the controller or pass it to view if it acts as ViewModel.
        // Since it's large, let's keep it in controller private method.
        $leaderboard = [];
        if (!$conn) return $leaderboard;

        if (!empty($selected_division)) {
            $sqlTeams = "
                SELECT ti.id AS team_id, ti.team_name, ti.logo, tcd.division
                FROM team_info ti
                JOIN team_contact_details tcd ON tcd.team_id = ti.id
                JOIN payment_transactions tt ON tt.team_id = ti.id
                WHERE tcd.division = ?
                  AND ti.tournament_id = ?
                  AND tt.status = 'paid'
                ORDER BY ti.team_name ASC
            ";
            $stmt = $conn->prepare($sqlTeams);
            $stmt->bind_param("ii", $selected_division, $tournament_id);
        } else {
            $sqlTeams = "
                SELECT ti.id AS team_id, ti.team_name, ti.logo, tcd.division
                FROM team_info ti
                JOIN team_contact_details tcd ON tcd.team_id = ti.id
                JOIN payment_transactions tt ON tt.team_id = ti.id
                WHERE ti.tournament_id = ?
                  AND tt.status = 'paid'
                ORDER BY ti.team_name ASC
            ";
            $stmt = $conn->prepare($sqlTeams);
            $stmt->bind_param("i", $tournament_id);
        }
        $stmt->execute();
        $resTeams = $stmt->get_result();
        
        $leaderboard = [];
        while ($t = $resTeams->fetch_assoc()) {
            $tid = (int) $t['team_id'];
             $sqlMatches = "
                SELECT DISTINCT m.id AS match_id
                FROM matches m
                JOIN team_pairs tp ON tp.match_id = m.id
                WHERE tp.team_id = ? 
                  AND m.tournament_id = ? 
                  AND m.status = 'completed'
                  AND (
                      m.notes IS NULL
                      OR (
                          m.notes NOT LIKE '%Semi Final%'
                          AND m.notes NOT LIKE '%Final%'
                      )
                  )
            ";
            $stmtM = $conn->prepare($sqlMatches);
            $stmtM->bind_param("ii", $tid, $tournament_id);
            $stmtM->execute();
            $resMatches = $stmtM->get_result();

            $matches_played = 0;
            $matches_won    = 0;
            $matches_lost   = 0;
            $pairs_won_total = 0;
            $pairs_lost_total = 0;
            $sets_won_total  = 0;
            $sets_lost_total = 0;
            $points = 0;

            while ($m = $resMatches->fetch_assoc()) {
                $match_id = (int) $m['match_id'];
                $sqlPairs = "SELECT id AS pair_id FROM team_pairs WHERE match_id = ? AND team_id = ?";
                $stmtP = $conn->prepare($sqlPairs);
                $stmtP->bind_param("ii", $match_id, $tid);
                $stmtP->execute();
                $resPairs = $stmtP->get_result();

                $pairs_won = 0;
                $pairs_lost = 0;
                $sets_won = 0;
                $sets_lost = 0;
                $has_result = false;

                while ($pair = $resPairs->fetch_assoc()) {
                    $pair_id = (int) $pair['pair_id'];
                    $sqlSets = "SELECT is_winner FROM pair_scores WHERE pair_id = ? AND match_id = ?";
                    $stmtS = $conn->prepare($sqlSets);
                    $stmtS->bind_param("ii", $pair_id, $match_id);
                    $stmtS->execute();
                    $resSets = $stmtS->get_result();
                    
                    $pair_sets_won = 0;
                    $pair_sets_lost = 0;

                    while ($set = $resSets->fetch_assoc()) {
                        $has_result = true;
                        if ((int)$set['is_winner'] === 1) {
                            $pair_sets_won++; $sets_won++;
                        } else {
                            $pair_sets_lost++; $sets_lost++;
                        }
                    }

                    if ($pair_sets_won > $pair_sets_lost) $pairs_won++;
                    elseif ($pair_sets_won < $pair_sets_lost) $pairs_lost++;
                }

                if ($has_result) {
                    $matches_played++;
                    if ($pairs_won == 3) { $matches_won++; $points += 3; }
                    elseif ($pairs_won == 2 && $pairs_lost == 1) { $matches_won++; $points += 2; }
                    elseif ($pairs_won == 1 && $pairs_lost == 2) { $matches_lost++; $points += 1; }
                    elseif ($pairs_won == 0 && $pairs_lost == 3) { $matches_lost++; $points += 0; }

                    $pairs_won_total += $pairs_won;
                    $pairs_lost_total += $pairs_lost;
                    $sets_won_total  += $sets_won;
                    $sets_lost_total += $sets_lost;
                }
            }

            $leaderboard[] = [
                'team_id'        => $tid,
                'team_name'      => $t['team_name'],
                'logo'           => $t['logo'] ?? '',
                'division'       => $t['division'] ?? '-',
                'matches_played' => $matches_played,
                'matches_won'    => $matches_won,
                'matches_lost'   => $matches_lost,
                'pairs_won'      => $pairs_won_total,
                'pairs_lost'     => $pairs_lost_total,
                'sets_won'       => $sets_won_total,
                'sets_lost'      => $sets_lost_total,
                'points'         => $points
            ];
        }

        // H2H Sorting Logic
         $__H2H_SQL = "
            SELECT t.team_id, SUM(t.pair_won) AS pairs_won
            FROM (
                SELECT
                    tp.team_id,
                    tp.id AS pair_id,
                    CASE WHEN SUM(ps.is_winner) > COUNT(*)/2 THEN 1 ELSE 0 END AS pair_won
                FROM matches m
                JOIN team_pairs tp ON tp.match_id = m.id
                JOIN pair_scores ps ON ps.match_id = m.id AND ps.pair_id = tp.id
                WHERE m.tournament_id = ?
                  AND m.status = 'completed'
                  AND (
                      m.notes IS NULL OR (m.notes NOT LIKE '%Semi Final%' AND m.notes NOT LIKE '%Final%')
                  )
                  AND (
                       (m.team1_id = ? AND m.team2_id = ?)
                    OR (m.team1_id = ? AND m.team2_id = ?)
                  )
                  AND tp.team_id IN (?, ?)
                GROUP BY tp.team_id, tp.id
                HAVING COUNT(*) > 0
            ) AS t
            GROUP BY t.team_id
        ";
        $__H2H_STMT = $conn->prepare($__H2H_SQL);
        $__H2H_CACHE = [];

        usort($leaderboard, function($a, $b) use ($tournament_id, $__H2H_STMT, &$__H2H_CACHE) {
             if ($b['points'] !== $a['points']) return $b['points'] - $a['points'];
             
             // H2H
             $A = (int)$a['team_id']; $B = (int)$b['team_id'];
             $k1 = $A.'-'.$B;
             if (!isset($__H2H_CACHE[$k1])) {
                 $__H2H_STMT->bind_param("iiiiiii", $tournament_id, $A, $B, $B, $A, $A, $B);
                 $__H2H_STMT->execute();
                 $res = $__H2H_STMT->get_result();
                 $pw = [$A => 0, $B => 0];
                 while ($row = $res->fetch_assoc()) $pw[(int)$row['team_id']] = (int)$row['pairs_won'];
                 $diff = $pw[$A] - $pw[$B];
                 $__H2H_CACHE[$A.'-'.$B] = $diff;
                 $__H2H_CACHE[$B.'-'.$A] = -$diff;
             }
             $h2h = $__H2H_CACHE[$k1];
             if ($h2h !== 0) return ($h2h > 0) ? -1 : 1;

             $sdA = $a['sets_won'] - $a['sets_lost'];
             $sdB = $b['sets_won'] - $b['sets_lost'];
             if ($sdB !== $sdA) return $sdB - $sdA;

             $pdA = $a['pairs_won'] - $a['pairs_lost'];
             $pdB = $b['pairs_won'] - $b['pairs_lost'];
             if ($pdB !== $pdA) return $pdB - $pdA;

             if ($b['sets_won'] !== $a['sets_won']) return $b['sets_won'] - $a['sets_won'];
             if ($b['pairs_won'] !== $a['pairs_won']) return $b['pairs_won'] - $a['pairs_won'];

             return strcasecmp($a['team_name'], $b['team_name']);
        });

        return $leaderboard;
    }
}
