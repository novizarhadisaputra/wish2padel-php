<?php

namespace App\Controllers\Admin;

use Exception;
use mysqli;

use App\Services\PasswordService;

class AdminController
{
    // ... (Existing methods: division, news, sponsors, teams, matches, pair, result, tournament, playoff)
    // I will include ALL methods to ensure the file is complete.

    public function personnel()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $search = $_GET['search'] ?? '';

        $sql = "SELECT tm.id, tm.player_name, tm.role, 
                       t.captain_phone as phone, t.captain_email as email, 
                       t.team_name, l.name as league_name, tor.name as tournament_name
                FROM team_members_info tm
                JOIN team_info t ON tm.team_id = t.id
                LEFT JOIN payment_transactions pt ON t.id = pt.team_id AND pt.status = 'paid'
                LEFT JOIN tournaments tor ON pt.tournament_id = tor.id
                LEFT JOIN league l ON tor.id_league = l.id
                WHERE 1=1";
        
        $types = "";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (tm.player_name LIKE ? OR t.team_name LIKE ?)";
            $types .= "ss";
            $term = "%$search%";
            $params[] = $term; 
            $params[] = $term;
        }

        $sql .= " ORDER BY t.team_name ASC, tm.role ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
             $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        view('admin.personnel', compact('result', 'search'));
    }

    public function division()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $search = $_GET['search'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_id'], $_POST['division'])) {
            $team_id = intval($_POST['team_id']);
            $division = intval($_POST['division']);
            $stmt = $conn->prepare("UPDATE team_contact_details SET division = ? WHERE team_id = ?");
            $stmt->bind_param('ii', $division, $team_id);
            $stmt->execute();
            $stmt->close();
            redirect('/admin/division');
        }

        $orderBy = "ORDER BY CASE WHEN tcd.division IS NULL OR tcd.division = '' THEN 0 ELSE 1 END ASC, tcd.level ASC, ti.id ASC";
        $sql = "SELECT ti.id AS team_id, ti.team_name, t.name AS tournament_name, tcd.level, tcd.division, te.experience, te.competed, te.regional FROM team_info ti LEFT JOIN tournaments t ON ti.tournament_id = t.id LEFT JOIN team_contact_details tcd ON ti.id = tcd.team_id LEFT JOIN team_experience te ON ti.id = te.team_id INNER JOIN payment_transactions pt ON pt.team_id = ti.id AND pt.tournament_id = ti.tournament_id AND pt.status = 'paid'";

        if ($search) {
            $sql .= " WHERE ti.team_name LIKE ? $orderBy";
            $stmt = $conn->prepare($sql);
            $like = "%$search%";
            $stmt->bind_param('s', $like);
        } else {
            $sql .= " $orderBy";
            $stmt = $conn->prepare($sql);
        }

        $stmt->execute();
        $teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $divisions = $conn->query("SELECT id, division_name FROM divisions ORDER BY id ASC");
        view('admin.division', compact('teams', 'divisions', 'search'));
    }

    public function news()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $uploadDir = __DIR__ . '/../../../public/uploads/news/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (isset($_POST['add'])) {
            $title = $_POST['title'];
            $highlight = $_POST['highlight'];
            $description = $_POST['description'];
            $fileName = null;
            if (!empty($_FILES["image"]["name"])) {
                $fileName = time().'_'.basename($_FILES["image"]["name"]);
                move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir.$fileName);
            }
            $created_at = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO blog_news (title, highlight, description, image, created_at) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $title, $highlight, $description, $fileName, $created_at);
            $stmt->execute();
            redirect('/admin/news');
        }

        if (isset($_POST['update'])) {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $highlight = $_POST['highlight'];
            $description = $_POST['description'];
            if (!empty($_FILES["image"]["name"])) {
                $fileName = time().'_'.basename($_FILES["image"]["name"]);
                move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir.$fileName);
                $stmt = $conn->prepare("UPDATE blog_news SET title=?, highlight=?, description=?, image=? WHERE id=?");
                $stmt->bind_param("sssi", $title, $highlight, $description, $fileName, $id);
            } else {
                $stmt = $conn->prepare("UPDATE blog_news SET title=?, highlight=?, description=? WHERE id=?");
                $stmt->bind_param("sssi", $title, $highlight, $description, $id);
            }
            $stmt->execute();
            redirect('/admin/news');
        }

        if (isset($_POST['delete'])) {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM blog_news WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirect('/admin/news');
        }

        $news = $conn->query("SELECT * FROM blog_news ORDER BY created_at DESC");
        view('admin.news', compact('news'));
    }

    public function sponsors()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $uploadDir = __DIR__ . '/../../../public/uploads/sponsor/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (isset($_POST['add_sponsor'])) {
            $name = $_POST['sponsor_name'];
            $web = $_POST['website'];
            $desc = $_POST['description'];
            $status = $_POST['status'];
            $type = ($status === 'sponsor' && !empty($_POST['type'])) ? $_POST['type'] : null;
            $logo = null;
            if (!empty($_FILES['sponsor_logo']['name'])) {
                $fileName = time() . "_" . basename($_FILES["sponsor_logo"]["name"]);
                if (move_uploaded_file($_FILES["sponsor_logo"]["tmp_name"], $uploadDir . $fileName)) {
                    $logo = $fileName;
                }
            }
            $created_at = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO sponsors (sponsor_name, sponsor_logo, website, description, status, type, created_at) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssss", $name, $logo, $web, $desc, $status, $type, $created_at);
            $stmt->execute();
            redirect('/admin/sponsors');
        }

        if (isset($_POST['edit_sponsor'])) {
            $id = $_POST['sponsor_id'];
            $name = $_POST['sponsor_name'];
            $web = $_POST['website'];
            $desc = $_POST['description'];
            $status = $_POST['status'];
            $type = ($status === 'sponsor' && !empty($_POST['type'])) ? $_POST['type'] : null;
            $logo = null;
            if (!empty($_FILES['sponsor_logo']['name'])) {
                $fileName = time() . "_" . basename($_FILES["sponsor_logo"]["name"]);
                if (move_uploaded_file($_FILES["sponsor_logo"]["tmp_name"], $uploadDir . $fileName)) {
                    $logo = $fileName;
                }
            }
            if ($logo) {
                $stmt = $conn->prepare("UPDATE sponsors SET sponsor_name=?, sponsor_logo=?, website=?, description=?, status=?, type=? WHERE sponsor_id=?");
                $stmt->bind_param("ssssssi", $name, $logo, $web, $desc, $status, $type, $id);
            } else {
                $stmt = $conn->prepare("UPDATE sponsors SET sponsor_name=?, website=?, description=?, status=?, type=? WHERE sponsor_id=?");
                $stmt->bind_param("sssssi", $name, $web, $desc, $status, $type, $id);
            }
            $stmt->execute();
            redirect('/admin/sponsors');
        }

        if (isset($_POST['delete_sponsor'])) {
            $id = $_POST['sponsor_id'];
            $stmt = $conn->prepare("DELETE FROM sponsors WHERE sponsor_id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirect('/admin/sponsors');
        }

        $sponsors = $conn->query("SELECT * FROM sponsors ORDER BY sponsor_id DESC");
        view('admin.sponsors', compact('sponsors'));
    }

    public function teams()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $flash = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'update_level') {
                $team_id = (int)($_POST['team_id'] ?? 0);
                $division = trim($_POST['division'] ?? '');
                $level = trim($_POST['level'] ?? '');
                if ($team_id > 0) {
                    $stmt = $conn->prepare("UPDATE team_contact_details SET division = ?, level = ? WHERE team_id = ?");
                    $stmt->bind_param("ssi", $division, $level, $team_id);
                    if ($stmt->execute()) {
                        $flash = ["type" => "success", "msg" => "Division & Level updated successfully."];
                    } else {
                        $flash = ["type" => "danger", "msg" => "Failed to update: " . $stmt->error];
                    }
                }
            }
            if ($action === 'delete_team') {
                $team_id = (int)($_POST['team_id'] ?? 0);
                if ($team_id > 0) {
                    $conn->begin_transaction();
                    try {
                        $tables = ['payment_transactions', 'team_members_info', 'team_account', 'team_contact_details', 'team_experience'];
                        foreach($tables as $tbl) { 
                            // Tables are hardcoded array above, so safe to interpolate table name, but IDs should be bound
                            $stmtDel = $conn->prepare("DELETE FROM $tbl WHERE team_id = ?");
                            $stmtDel->bind_param("i", $team_id);
                            $stmtDel->execute();
                        }
                        $stmtInfo = $conn->prepare("DELETE FROM team_info WHERE id = ?");
                        $stmtInfo->bind_param("i", $team_id);
                        $stmtInfo->execute();
                        $conn->commit();
                        $flash = ["type" => "success", "msg" => "Team deleted successfully."];
                    } catch (\Throwable $e) {
                        $conn->rollback();
                        $flash = ["type" => "danger", "msg" => "Delete failed: " . $e->getMessage()];
                    }
                }
            }
        }

        $search = $_GET['search'] ?? '';
        $currentYear = date('Y');
        $league_id = $_GET['league_id'] ?? '';
        if ($league_id === '') {
            $resDefault = $conn->query("SELECT id FROM league WHERE date = '$currentYear' LIMIT 1");
            if ($rowDefault = $resDefault->fetch_assoc()) $league_id = $rowDefault['id'];
        }
        $tournament_id = $_GET['tournament_id'] ?? '';
        $team_id = $_GET['team_id'] ?? '';

        $where = []; $params = []; $types = '';
        $sql = "WITH ranked_payments AS (SELECT ti.id AS team_id, ti.team_name, ti.created_at, tcd.division AS division_id, d.division_name AS division_name, tcd.club, tcd.city, tcd.notes, tcd.contact_phone, tcd.contact_email, ti.captain_name, ti.captain_phone, ti.captain_email, t.id AS tournament_id, t.name AS tournament_name, l.id AS league_id, l.name AS league_name, pt.status AS payment_status, ROW_NUMBER() OVER (PARTITION BY ti.id ORDER BY CASE pt.status WHEN 'paid' THEN 1 WHEN 'pending' THEN 2 ELSE 3 END) AS rn FROM payment_transactions pt JOIN team_info ti ON pt.team_id = ti.id LEFT JOIN team_contact_details tcd ON tcd.team_id = ti.id LEFT JOIN divisions d ON tcd.division = d.id LEFT JOIN tournaments t ON pt.tournament_id = t.id LEFT JOIN league l ON t.id_league = l.id) SELECT * FROM ranked_payments";

        if ($league_id !== '') { $where[] = 'league_id = ?'; $params[] = $league_id; $types .= 'i'; }
        if ($tournament_id !== '') { $where[] = 'tournament_id = ?'; $params[] = $tournament_id; $types .= 'i'; }
        if ($team_id !== '') { $where[] = 'team_id = ?'; $params[] = $team_id; $types .= 'i'; }
        if ($search !== '') { $where[] = 'team_name LIKE ?'; $params[] = "%$search%"; $types .= 's'; }

        if (!empty($where)) { $sql .= " WHERE rn = 1 AND " . implode(' AND ', $where); } else { $sql .= " WHERE rn = 1"; }
        $sql .= " ORDER BY team_id ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $leagues = $conn->query("SELECT id, name, date FROM league ORDER BY name ASC");
        $divisions_all = $conn->query("SELECT id, division_name FROM divisions ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
        $tournamentsSql = "SELECT t.id, t.name AS tournament_name, l.name AS league_name, date AS league_year FROM tournaments t LEFT JOIN league l ON t.id_league = l.id";
        if (!empty($league_id)) {
             $tournamentsSql .= " WHERE l.id = ?";
             $stmtTour = $conn->prepare($tournamentsSql);
             $stmtTour->bind_param("i", $league_id);
             $stmtTour->execute();
             $tournaments = $stmtTour->get_result();
        } else {
             $tournamentsSql .= " ORDER BY t.name ASC";
             $tournaments = $conn->query($tournamentsSql);
        }
        $teams_list = $conn->query("SELECT id, team_name FROM team_info ORDER BY team_name ASC");

        view('admin.team', compact('result', 'leagues', 'tournaments', 'teams_list', 'search', 'league_id', 'tournament_id', 'team_id', 'flash'));
    }

    public function penalties()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();

        if (isset($_POST['store_penalty'])) {
            $team_id = (int)$_POST['team_id'];
            $tournament_id = (int)$_POST['tournament_id'];
            $points = (int)$_POST['points_deduction'];
            $fine = (float)$_POST['fine_amount'];
            $reason = $_POST['reason'];

            $stmt = $conn->prepare("INSERT INTO team_penalties (team_id, tournament_id, points_deduction, fine_amount, reason) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiids", $team_id, $tournament_id, $points, $fine, $reason);
            $stmt->execute();
            redirect('/admin/penalties');
        }

        if (isset($_POST['delete_penalty'])) {
            $id = (int)$_POST['penalty_id'];
            $stmt = $conn->prepare("DELETE FROM team_penalties WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirect('/admin/penalties');
        }

        $sql = "SELECT p.*, t.team_name, tor.name as tournament_name 
                FROM team_penalties p 
                JOIN team_info t ON p.team_id = t.id 
                JOIN tournaments tor ON p.tournament_id = tor.id 
                ORDER BY p.created_at DESC";
        
        $penalties = $conn->query($sql);
        
        // Fetch data for form
        $teams = $conn->query("SELECT id, team_name FROM team_info ORDER BY team_name ASC");
        $tournaments = $conn->query("SELECT id, name FROM tournaments ORDER BY id DESC");

        view('admin.penalties', compact('penalties', 'teams', 'tournaments'));
    }

    public function matches()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        date_default_timezone_set('Asia/Riyadh');

        $league_years_res = $conn->query("SELECT DISTINCT YEAR(date) AS year FROM league ORDER BY year DESC");
        $years = []; while ($y = $league_years_res->fetch_assoc()) { $years[] = (int)$y['year']; }
        $current_year = (int)date('Y');
        $years = array_filter($years, fn($yr) => $yr <= $current_year);
        $selected_year = isset($_GET['year']) && $_GET['year'] !== "" ? (int)$_GET['year'] : $current_year;

        if (isset($_POST['add_match']) && $selected_year) {
            try {
                $conn->autocommit(FALSE);
                $tournaments = $conn->query("SELECT tor.id, tor.name, tor.start_date, tor.id_league FROM tournaments tor INNER JOIN league l ON tor.id_league = l.id WHERE YEAR(l.date) = $selected_year");
                if (!$tournaments->num_rows) throw new Exception("No tournaments found for selected year.");

                $stmt_check_exist = $conn->prepare("SELECT id FROM matches WHERE tournament_id = ? AND team1_id = ? AND team2_id = ? LIMIT 1");
                $stmt_insert_match = $conn->prepare("INSERT INTO matches (tournament_id, team1_id, team2_id, scheduled_date, status, journey) VALUES (?, ?, ?, ?, 'scheduled', ?)");
                $stmt_insert_pair = $conn->prepare("INSERT INTO team_pairs (match_id, pair_number, team_id) VALUES (?, ?, ?)");

                while ($tournament = $tournaments->fetch_assoc()) {
                    $tournament_id = (int)$tournament['id'];
                    $start_date_ts = strtotime($tournament['start_date']);
                    $match_days = [3,4,5];
                    $match_hour = 20;

                    $divisions_res = $conn->query("SELECT DISTINCT tcd.division FROM team_contact_details tcd INNER JOIN payment_transactions tp ON tcd.team_id = tp.team_id WHERE tp.tournament_id = $tournament_id AND tp.status = 'paid' AND tcd.division IS NOT NULL ORDER BY tcd.division ASC");
                    while ($div = $divisions_res->fetch_assoc()) {
                        $division = (int)$div['division'];
                        $teams_res = $conn->query("SELECT ti.id, ti.team_name FROM team_info ti INNER JOIN team_contact_details tcd ON ti.id = tcd.team_id INNER JOIN payment_transactions tp ON ti.id = tp.team_id WHERE tcd.division = $division AND tp.tournament_id = $tournament_id AND tp.status = 'paid' ORDER BY ti.id ASC");
                        $teams = []; while ($t = $teams_res->fetch_assoc()) $teams[] = $t;
                        $N = count($teams); if ($N < 2) continue;

                        $team_ids_orig = array_map(fn($t) => $t['id'], $teams);
                        $has_odd = ($N % 2 === 1);
                        $rotating_ids = $team_ids_orig;
                        if ($has_odd) { $rotating_ids[] = null; $num_slots = $N + 1; } else { $num_slots = $N; }
                        $rounds = ($has_odd) ? $N : ($N - 1);
                        $work = $rotating_ids;
                        if (count($work) !== $num_slots) { while (count($work) < $num_slots) $work[] = null; }

                        $current_date = $start_date_ts;
                        $journey = 1;
                        $next_valid_day = function(&$ts) use ($match_days) {
                            $dow = (int)date('N', $ts);
                            while (!in_array($dow, $match_days)) { $ts = strtotime('+1 day', $ts); $dow = (int)date('N', $ts); }
                        };

                        for ($leg = 1; $leg <= 2; $leg++) {
                            for ($r = 0; $r < $rounds; $r++) {
                                $pairs = []; $len = count($work);
                                for ($i = 0; $i < $len / 2; $i++) {
                                    $a = $work[$i]; $b = $work[$len - 1 - $i];
                                    if ($a === null || $b === null) continue;
                                    if ($leg === 1) { $home = $a; $away = $b; } else { $home = $b; $away = $a; }
                                    if ($home === $away) continue;
                                    $pairs[] = [$home, $away];
                                }
                                $match_per_day = 0;
                                foreach ($pairs as $pair) {
                                    $next_valid_day($current_date);
                                    if ($match_per_day >= 1) {
                                        $current_date = strtotime('+1 day', $current_date);
                                        $match_per_day = 0;
                                        $next_valid_day($current_date);
                                    }
                                    $match_date_ts = mktime($match_hour, 0, 0, date('n', $current_date), date('j', $current_date), date('Y', $current_date));
                                    $sched_str = date('Y-m-d H:i:s', $match_date_ts);

                                    $stmt_check_exist->bind_param("iii", $tournament_id, $pair[0], $pair[1]);
                                    $stmt_check_exist->execute();
                                    $stmt_check_exist->store_result();
                                    $exists = $stmt_check_exist->num_rows > 0;
                                    $stmt_check_exist->free_result();

                                    $stmt_check_conflict = $conn->prepare("SELECT id FROM matches WHERE tournament_id = ? AND scheduled_date = ? AND (team1_id = ? OR team2_id = ? OR team1_id = ? OR team2_id = ?) LIMIT 1");
                                    $stmt_check_conflict->bind_param("isiiii", $tournament_id, $sched_str, $pair[0], $pair[0], $pair[1], $pair[1]);
                                    $stmt_check_conflict->execute();
                                    $stmt_check_conflict->store_result();
                                    $conflict = $stmt_check_conflict->num_rows > 0;
                                    $stmt_check_conflict->free_result();

                                    if ($conflict) {
                                        $current_date = strtotime('+1 day', $current_date);
                                        $next_valid_day($current_date);
                                        $match_date_ts = mktime($match_hour, 0, 0, date('n', $current_date), date('j', $current_date), date('Y', $current_date));
                                        $sched_str = date('Y-m-d H:i:s', $match_date_ts);
                                    }

                                    if (!$exists) {
                                        $stmt_insert_match->bind_param("iiisi", $tournament_id, $pair[0], $pair[1], $sched_str, $journey);
                                        $stmt_insert_match->execute();
                                        $match_id = $stmt_insert_match->insert_id;
                                        foreach ([$pair[0], $pair[1]] as $tid) {
                                            for ($k = 1; $k <= 3; $k++) {
                                                $stmt_insert_pair->bind_param("iii", $match_id, $k, $tid);
                                                $stmt_insert_pair->execute();
                                            }
                                        }
                                    }
                                    $match_per_day++;
                                }
                                $journey++;
                                $first = array_shift($work); $last = array_pop($work);
                                array_unshift($work, $last); array_unshift($work, $first);
                                $rest = array_slice($work, 1); $rest_last = array_pop($rest);
                                array_unshift($rest, $rest_last); $work = array_merge([$first], $rest);
                            }
                        }
                    }
                }
                $conn->commit();
                redirect('/admin/matches?year=' . $selected_year);
            } catch (Exception $e) {
                $conn->rollback();
                echo "<div class='alert alert-danger m-3'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        $matches_exist = false;
        $matches_list = [];
        if($selected_year){
            $matches_res = $conn->query("SELECT m.*, t1.team_name AS team1_name, t2.team_name AS team2_name, tor.name AS tournament_name, l.name AS league_name, tcd.level, tcd.division FROM matches m LEFT JOIN team_info t1 ON m.team1_id = t1.id LEFT JOIN team_info t2 ON m.team2_id = t2.id LEFT JOIN tournaments tor ON m.tournament_id = tor.id LEFT JOIN league l ON tor.id_league = l.id LEFT JOIN team_contact_details tcd ON t1.id = tcd.team_id WHERE YEAR(l.date) = $selected_year ORDER BY m.scheduled_date ASC");
            if($matches_res->num_rows > 0){
                $matches_exist = true;
                while($row = $matches_res->fetch_assoc()) $matches_list[] = $row;
            }
        }

        if(isset($_POST['update_match'])){
            $id = (int) $_POST['match_id'];
            $team1_id = (int) $_POST['team1_id'];
            $team2_id = (int) $_POST['team2_id'];
            $scheduled_date = str_replace("T"," ",$_POST['scheduled_date']).":00";
            $status = $_POST['status'];
            $scheduled_timestamp = strtotime($scheduled_date);
            $scheduled_date_riyadh = date('Y-m-d H:i:s',$scheduled_timestamp);
            $stmt = $conn->prepare("UPDATE matches SET team1_id=?, team2_id=?, scheduled_date=?, status=? WHERE id=?");
            $stmt->bind_param("iissi", $team1_id, $team2_id, $scheduled_date_riyadh, $status, $id);
            $stmt->execute();
            redirect('/admin/matches?year='.$selected_year);
        }

        if (isset($_POST['report_no_show'])) {
            $match_id = (int)$_POST['match_id'];
            $team_no_show_id = (int)$_POST['report_no_show'];
            $tournament_id = (int)$_POST['tournament_id'];

            // Identify opponent
            $stmt = $conn->prepare("SELECT team1_id, team2_id FROM matches WHERE id = ?");
            $stmt->bind_param("i", $match_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($m = $res->fetch_assoc()) {
                $team1 = $m['team1_id'];
                $team2 = $m['team2_id'];
                $opponent_id = ($team_no_show_id == $team1) ? $team2 : $team1;

                // Check penalties
                $stmtCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM team_penalties WHERE team_id = ? AND tournament_id = ?");
                $stmtCheck->bind_param("ii", $team_no_show_id, $tournament_id);
                $stmtCheck->execute();
                $cnt = $stmtCheck->get_result()->fetch_assoc()['cnt'];

                $type = ($cnt == 0) ? 'warning' : 'deduction';
                $points = ($cnt == 0) ? 0 : 1;

                $stmtIns = $conn->prepare("INSERT INTO team_penalties (team_id, tournament_id, match_id, type, points) VALUES (?, ?, ?, ?, ?)");
                $stmtIns->bind_param("iiisi", $team_no_show_id, $tournament_id, $match_id, $type, $points);
                $stmtIns->execute();

                // Update Match Status
                $stmtUpd = $conn->prepare("UPDATE matches SET status = 'completed', notes = 'No Show' WHERE id = ?");
                $stmtUpd->bind_param("i", $match_id);
                $stmtUpd->execute();

                // Record Result (Opponent Wins)
                // Remove existing results for this match to avoid duplicates
                // Remove existing results for this match to avoid duplicates
                $stmtDelRes = $conn->prepare("DELETE FROM match_results WHERE match_id = ?");
                $stmtDelRes->bind_param("i", $match_id);
                $stmtDelRes->execute();

                // Add winner result. NOTE: winner_team_id column assumed based on LeagueController usage.
                // We use ignore in case of schema mismatch, but really we rely on it working.
                $stmtRes = $conn->prepare("INSERT INTO match_results (match_id, team_id, pairs_won, pairs_lost, status, winner_team_id) VALUES (?, ?, 3, 0, 'accept', ?)");
                $stmtRes->bind_param("iii", $match_id, $opponent_id, $opponent_id);
                $stmtRes->execute();

                redirect('/admin/matches?year='.$selected_year);
            }
        }

        view('admin.match', compact('years', 'selected_year', 'matches_exist', 'matches_list'));
    }

    public function pair()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $filter_match_id = $_GET['match_id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['match_id'], $_POST['team_id'])) {
            $match_id = (int) $_POST['match_id'];
            $team_id = (int) $_POST['team_id'];
            if ($_POST['action'] === 'accept') {
                $stmt = $conn->prepare("UPDATE match_results SET status='accept', updated_at=NOW() WHERE match_id=? AND team_id=?");
                $stmt->bind_param("ii", $match_id, $team_id);
                $stmt->execute();
            } elseif ($_POST['action'] === 'reject') {
                $conn->autocommit(false);
                try {
                    $res_team = $conn->query("SELECT d.division AS division_id, m.journey, m.notes FROM team_contact_details d JOIN matches m ON m.id = $match_id WHERE d.team_id = $team_id");
                    $team_info = $res_team->fetch_assoc();
                    $division_id = (int)$team_info['division_id'];
                    $journey = (int)$team_info['journey'];
                    $notes = $team_info['notes'];
                    if ($notes === null) {
                        $pointsTable = [
                            1 => [650,604,558,511,464,418,372,372,418,464,511,558,604,650],
                            2 => [540,501,463,424,386,348,309,309,348,386,424,463,501,540],
                            3 => [450,417,386,354,321,289,257,257,289,321,354,386,417,450],
                            4 => [375,348,320,294,268,241,214,214,241,268,294,320,348,375],
                        ];
                        $pairs_res = $conn->query("SELECT id FROM team_pairs WHERE match_id=$match_id AND team_id=$team_id");
                        while ($pair = $pairs_res->fetch_assoc()) {
                            $pair_id = (int)$pair['id'];
                            $res_set = $conn->query("SELECT SUM(is_winner) AS won_sets FROM pair_scores WHERE match_id=$match_id AND pair_id=$pair_id AND team_id=$team_id")->fetch_assoc();
                            $won_sets = (int)$res_set['won_sets'];
                            if ($won_sets >= 2) { $pair_point = $pointsTable[$division_id][$journey - 1] ?? 0; }
                            elseif ($won_sets === 1) { $pair_point = 15; }
                            else { $pair_point = 10; }
                            $res_players = $conn->query("SELECT player_name FROM pair_players WHERE pair_id=$pair_id");
                            while ($p = $res_players->fetch_assoc()) {
                                $player_name = $conn->real_escape_string($p['player_name']);
                                $conn->query("UPDATE team_members_info SET point = point - $pair_point WHERE team_id=$team_id AND player_name='$player_name'");
                            }
                        }
                    }
                    $conn->query("DELETE FROM pair_scores WHERE match_id=$match_id AND team_id=$team_id");
                    $conn->query("DELETE FROM match_results WHERE match_id=$match_id AND team_id=$team_id");
                    $conn->commit();
                    redirect('/admin/pair');
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "<div class='alert alert-danger'>Error rollback: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
            redirect('/admin/pair');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scores'], $_POST['match_id'])) {
            $match_id = (int) $_POST['match_id'];
            foreach ($_POST['scores'] as $score_id => $score_value) {
                $stmt = $conn->prepare("UPDATE pair_scores SET team_score=? WHERE id=?");
                $stmt->bind_param("ii", $score_value, $score_id);
                $stmt->execute();
            }
            $pair_totals = [];
            $pairs = $conn->query("SELECT id FROM team_pairs WHERE match_id={$match_id}");
            while ($p = $pairs->fetch_assoc()) {
                $res = $conn->query("SELECT SUM(team_score) AS total FROM pair_scores WHERE pair_id={$p['id']}")->fetch_assoc();
                $pair_totals[$p['id']] = $res['total'];
            }
            $max_score = max($pair_totals);
            foreach ($pair_totals as $pair_id => $total) {
                $is_winner = ($total == $max_score) ? 1 : 0;
                $conn->query("UPDATE pair_scores SET is_winner={$is_winner} WHERE pair_id={$pair_id}");
            }
            $winner_pair_id = array_search($max_score, $pair_totals);
            $conn->query("UPDATE match_results SET winner_pair_id={$winner_pair_id}, last_updated=NOW() WHERE match_id={$match_id}");
            redirect('/admin/pair?match_id=' . $match_id);
        }

        if ($filter_match_id) {
            $stmt = $conn->prepare("SELECT m.id AS match_id, t1.team_name AS team1, t2.team_name AS team2 FROM matches m JOIN team_info t1 ON m.team1_id = t1.id JOIN team_info t2 ON m.team2_id = t2.id WHERE m.id=?");
            $stmt->bind_param("i", $filter_match_id);
            $stmt->execute();
            $matches = $stmt->get_result();
        } else {
            $matches = $conn->query("SELECT m.id AS match_id, t1.team_name AS team1, t2.team_name AS team2 FROM matches m JOIN team_info t1 ON m.team1_id = t1.id JOIN team_info t2 ON m.team2_id = t2.id ORDER BY m.id ASC");
        }

        view('admin.pair', compact('matches', 'filter_match_id'));
    }

    public function result()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        date_default_timezone_set('Asia/Riyadh');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
            $id = (int) $_POST['id'];
            $status = $_POST['status'];
            if (in_array($status, ['accept', 'reject'])) {
                $updated_at = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("UPDATE match_results SET status = ?, updated_at = ? WHERE id = ?");
                $stmt->bind_param("ssi", $status, $updated_at, $id);
                $stmt->execute();
            }
        }
        $results = $conn->query("SELECT * FROM match_results ORDER BY created_at ASC");
        view('admin.result', compact('results'));
    }

    public function tournament()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        date_default_timezone_set('Asia/Riyadh');
        $leagues = $conn->query("SELECT * FROM league ORDER BY name ASC");

        if(isset($_POST['add_league'])) {
            $name = $_POST['name'];
            $deskripsi = $_POST['deskripsi'] ?: NULL;
            $date_input = $_POST['date'];
            $date = date('Y-m-d', strtotime($date_input));
            $stmt = $conn->prepare("INSERT INTO league (name, deskripsi, date) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $deskripsi, $date);
            $stmt->execute();
        }

        if (isset($_POST['edit_league'])) {
            $id = (int) $_POST['id'];
            $name = trim($_POST['name']);
            $deskripsi = !empty($_POST['deskripsi']) ? $_POST['deskripsi'] : null;
            $year = isset($_POST['date']) && is_numeric($_POST['date']) ? (int) $_POST['date'] : null;
            $stmt = $conn->prepare("UPDATE league SET name=?, deskripsi=?, date=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $deskripsi, $year, $id);
            $stmt->execute();
        }

        if(isset($_POST['delete_league'])) {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM league WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }

        if (isset($_POST['add_division'])) {
            $id = (int) $_POST['id'];
            $division_name = trim($_POST['division_name']);
            $gender = $_POST['gender'] ?? 'Men';
            $stmt = $conn->prepare("INSERT INTO divisions (id, division_name, gender) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id, $division_name, $gender);
            $stmt->execute();
        }

        if (isset($_POST['edit_division'])) {
            $old_id = (int) $_POST['id'];
            $new_id = (int) $_POST['id_new'];
            $division_name = trim($_POST['division_name']);
            $gender = $_POST['gender'] ?? 'Men';

            // Note: INSERT ... ON DUPLICATE KEY UPDATE might overwrite other fields if using 'id'.
            // Better to use explicit UPDATE for edit if ID hasn't changed, but here ID is primary key.
            // If ID changes, we need to handle that. But 'id' is used in logic.
            // Assuming this logic attempts to allow changing ID.

            // Updated logic to support gender
            $stmt = $conn->prepare("INSERT INTO divisions (id, division_name, gender) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE division_name = VALUES(division_name), gender = VALUES(gender)");
            $stmt->bind_param("iss", $new_id, $division_name, $gender);
            $stmt->execute();

            $result = $conn->query("SELECT tcd.team_id FROM team_contact_details tcd JOIN team_info ti ON tcd.team_id = ti.id JOIN tournaments tor ON ti.tournament_id = tor.id WHERE tcd.division = $old_id AND tor.status = 'upcoming'");
            while ($row = $result->fetch_assoc()) {
                $team_id = (int)$row['team_id'];
                $conn->query("UPDATE team_contact_details SET division = $new_id WHERE team_id = $team_id");
            }
        }

        if (isset($_POST['delete_division'])) {
            $id = (int) $_POST['id'];
            $check = $conn->prepare("SELECT COUNT(*) FROM team_contact_details WHERE division = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $check->bind_result($used_count);
            $check->fetch();
            $check->close();
            if ($used_count == 0) {
                $stmt = $conn->prepare("DELETE FROM divisions WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        }

        if (isset($_POST['add_tournament'])) {
            $name = $_POST['name'];
            $start_date = date('Y-m-d', strtotime($_POST['start_date']));
            $end_date = date('Y-m-d', strtotime($_POST['end_date']));
            $id_league = (int)$_POST['id_league'];
            $status = 'upcoming';
            $stmt = $conn->prepare("INSERT INTO tournaments (name, start_date, end_date, id_league, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", $name, $start_date, $end_date, $id_league, $status);
            $stmt->execute();
            redirect('/admin/tournament');
        }

        if(isset($_POST['edit_tournament'])) {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $start_date = date('Y-m-d', strtotime($_POST['start_date']));
            $end_date = date('Y-m-d', strtotime($_POST['end_date']));
            $id_league = $_POST['id_league'];
            $stmt = $conn->prepare("UPDATE tournaments SET name=?, start_date=?, end_date=?, id_league=? WHERE id=?");
            $stmt->bind_param("sssii", $name, $start_date, $end_date, $id_league, $id);
            $stmt->execute();
        }

        if(isset($_POST['complete_tournament'])) {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE tournaments SET status='completed' WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }

        $leagues = $conn->query("SELECT * FROM league ORDER BY id DESC");
        $divisions = $conn->query("SELECT * FROM divisions ORDER BY id ASC");
        $tournaments = $conn->query("SELECT t.*, l.name AS league_name FROM tournaments t LEFT JOIN league l ON t.id_league = l.id ORDER BY t.id DESC");
        view('admin.tournament', compact('leagues', 'divisions', 'tournaments'));
    }

    public function playoff()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        date_default_timezone_set('Asia/Riyadh');

        $isRegularSeasonCompleted = function($conn, $tournament_id, $division) {
            $stmt = $conn->prepare("SELECT COUNT(*) AS pending FROM matches m JOIN team_info t1 ON t1.id = m.team1_id JOIN team_contact_details d1 ON d1.team_id = t1.id JOIN team_info t2 ON t2.id = m.team2_id JOIN team_contact_details d2 ON d2.team_id = t2.id WHERE m.tournament_id = ? AND d1.division = ? AND d2.division = ? AND m.status <> 'completed' AND m.notes IS NULL");
            $stmt->bind_param("iii", $tournament_id, $division, $division);
            $stmt->execute();
            return ((int)$stmt->get_result()->fetch_assoc()['pending'] ?? 0) === 0;
        };

        $getTop4ByPairsWon = function($conn, $tournament_id, $division) {
            $sql = "WITH pr AS (SELECT tp.team_id, m.id AS match_id, tp.id AS pair_id, SUM(ps.is_winner) AS sets_won_pair, (COUNT(*) - SUM(ps.is_winner)) AS sets_lost_pair, CASE WHEN SUM(ps.is_winner) > COUNT(*)/2 THEN 1 ELSE 0 END AS pair_won FROM matches m JOIN team_pairs tp ON tp.match_id = m.id JOIN pair_scores ps ON ps.match_id = m.id AND ps.pair_id = tp.id WHERE m.tournament_id = ? AND m.status = 'completed' AND m.notes IS NULL GROUP BY tp.team_id, m.id, tp.id HAVING COUNT(*) > 0), per_match AS (SELECT team_id, match_id, SUM(pair_won) AS pairs_won_match, SUM(sets_won_pair) AS sets_won_match, SUM(sets_lost_pair) AS sets_lost_match, CASE WHEN SUM(pair_won) = 3 THEN 3 WHEN SUM(pair_won) = 2 THEN 2 WHEN SUM(pair_won) = 1 THEN 1 ELSE 0 END AS points_match FROM pr GROUP BY team_id, match_id), agg AS (SELECT team_id, SUM(pairs_won_match) AS pairs_won, SUM(3 - pairs_won_match) AS pairs_lost, SUM(sets_won_match) AS sets_won, SUM(sets_lost_match) AS sets_lost, SUM(points_match) AS points FROM per_match GROUP BY team_id) SELECT a.team_id FROM agg a JOIN team_contact_details d ON d.team_id = a.team_id JOIN team_info ti ON ti.id = a.team_id WHERE d.division = ? ORDER BY a.points DESC, (a.sets_won - a.sets_lost) DESC, (a.pairs_won - a.pairs_lost) DESC, ti.team_name ASC LIMIT 4";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $tournament_id, $division);
            $stmt->execute();
            $res = $stmt->get_result();
            $out = []; while ($r = $res->fetch_assoc()) $out[] = (int)$r['team_id'];
            return $out;
        };

        $hasPlayoffGenerated = function($conn, $tournament_id, $division) {
            $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM matches m JOIN team_info t1 ON t1.id = m.team1_id JOIN team_contact_details d1 ON d1.team_id = t1.id JOIN team_info t2 ON t2.id = m.team2_id JOIN team_contact_details d2 ON d2.team_id = t2.id WHERE m.tournament_id = ? AND d1.division = ? AND d2.division = ? AND m.notes IS NOT NULL");
            $stmt->bind_param("iii", $tournament_id, $division, $division);
            $stmt->execute();
            return ((int)$stmt->get_result()->fetch_assoc()['cnt'] ?? 0) > 0;
        };

        $insertMatchWithPairs = function($conn, $tournament_id, $team1_id, $team2_id, $scheduled_str, $journey, $note = null) {
            $note_sql = $note ? "'" . $conn->real_escape_string($note) . "'" : "NULL";
            $team1_sql = $team1_id === null ? "NULL" : (int)$team1_id;
            $team2_sql = $team2_id === null ? "NULL" : (int)$team2_id;
            $q = "INSERT INTO matches (tournament_id, team1_id, team2_id, scheduled_date, status, journey, notes) VALUES (".(int)$tournament_id.", $team1_sql, $team2_sql, '".$conn->real_escape_string($scheduled_str)."', 'scheduled', ".(int)$journey.", $note_sql)";
            $conn->query($q);
            $match_id = (int)$conn->insert_id;
            if ($team1_id && $team2_id) {
                $stmt_pair = $conn->prepare("INSERT INTO team_pairs (match_id, pair_number, team_id) VALUES (?, ?, ?)");
                foreach ([$team1_id, $team2_id] as $tid) {
                    for ($k = 1; $k <= 3; $k++) {
                        $stmt_pair->bind_param("iii", $match_id, $k, $tid);
                        $stmt_pair->execute();
                    }
                }
            }
        };

        $league_years_res = $conn->query("SELECT DISTINCT YEAR(date) AS year FROM league ORDER BY year DESC");
        $years = []; while ($y = $league_years_res->fetch_assoc()) $years[] = (int)$y['year'];
        $current_year = (int)date('Y');
        $years = array_filter($years, fn($yr) => $yr <= $current_year);
        $selected_year = isset($_GET['year']) && $_GET['year'] !== "" ? (int)$_GET['year'] : $current_year;

        if (isset($_POST['generate_playoff'])) {
            $tournament_id = (int)$_POST['tournament_id'];
            $division      = (int)$_POST['division'];
            try {
                $conn->autocommit(FALSE);
                if ($hasPlayoffGenerated($conn, $tournament_id, $division)) throw new Exception("Playoff already exists.");
                if (!$isRegularSeasonCompleted($conn, $tournament_id, $division)) throw new Exception("Regular season not finished yet.");
                $top4 = $getTop4ByPairsWon($conn, $tournament_id, $division);
                if (count($top4) < 4) throw new Exception("Not enough teams to generate playoff.");
                [$rank1, $rank2, $rank3, $rank4] = $top4;

                $stmtLast = $conn->prepare("SELECT MAX(m.journey) AS last_journey FROM matches m JOIN team_contact_details tcd ON tcd.team_id IN (m.team1_id, m.team2_id) WHERE m.tournament_id = ? AND tcd.division = ?");
                $stmtLast->bind_param("ii", $tournament_id, $division);
                $stmtLast->execute();
                $last = (int)($stmtLast->get_result()->fetch_assoc()['last_journey'] ?? 0);
                $baseJourney = max($last, 14);

                $week = function($days) { return date('Y-m-d 20:00:00', strtotime("+$days days")); };

                $insertMatchWithPairs($conn, $tournament_id, $rank3, $rank4, $week(7), $baseJourney + 1, 'Semi Final 1');
                $insertMatchWithPairs($conn, $tournament_id, $rank4, $rank3, $week(14), $baseJourney + 2, 'Semi Final 2');
                $insertMatchWithPairs($conn, $tournament_id, null, $rank2, $week(21), $baseJourney + 3, 'Final 1');
                $insertMatchWithPairs($conn, $tournament_id, $rank2, null, $week(28), $baseJourney + 4, 'Final 2');

                $conn->commit();
                redirect('/admin/playoff?ok=1&year=' . $selected_year);
            } catch (Exception $e) {
                $conn->rollback();
                redirect('/admin/playoff?err=' . urlencode($e->getMessage()) . '&year=' . $selected_year);
            }
        }

        $tournaments = $conn->query("SELECT tor.id, tor.name, tor.start_date, tor.id_league, l.name AS league_name FROM tournaments tor INNER JOIN league l ON tor.id_league = l.id WHERE YEAR(l.date) = $selected_year ORDER BY l.name ASC, tor.name ASC");
        $rows = [];
        if ($tournaments && $tournaments->num_rows) {
            while ($t = $tournaments->fetch_assoc()) {
                $tid = (int)$t['id'];
                $div_query = $conn->prepare("SELECT DISTINCT d.division FROM team_contact_details d INNER JOIN payment_transactions tp ON tp.team_id = d.team_id WHERE tp.tournament_id = ? AND tp.status = 'paid' AND d.division IS NOT NULL ORDER BY d.division ASC");
                $div_query->bind_param("i", $tid);
                $div_query->execute();
                $divs = $div_query->get_result();
                while ($d = $divs->fetch_assoc()) {
                    $division = (int)$d['division'];
                    $rows[] = [
                        'league_name' => $t['league_name'],
                        'tournament_id' => $tid,
                        'tournament' => $t['name'],
                        'division' => $division,
                        'regular_done' => $isRegularSeasonCompleted($conn, $tid, $division),
                        'already' => $hasPlayoffGenerated($conn, $tid, $division),
                        'top4' => $getTop4ByPairsWon($conn, $tid, $division)
                    ];
                }
            }
        }
        view('admin.playoff', compact('years', 'selected_year', 'rows'));
    }

    public function documents()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $uploadDir = __DIR__ . '/../../../public/uploads/template/';
        $dbDir = "uploads/template/";

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (isset($_POST['add'])) {
            $doc_name = trim($_POST['doc_name'] ?? '');
            if (!empty($_FILES["pdf"]["name"])) {
                $fileName = time().'_'.basename($_FILES["pdf"]["name"]);
                move_uploaded_file($_FILES["pdf"]["tmp_name"], $uploadDir.$fileName);
                $filePath = $dbDir.$fileName;
                $created_at = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("INSERT INTO documents (doc_name, file_path, created_at) VALUES (?,?,?)");
                $stmt->bind_param("sss", $doc_name, $filePath, $created_at);
                $stmt->execute();
            }
            redirect('/admin/documents');
        }

        if (isset($_POST['update'])) {
            $id = (int)$_POST['id'];
            $doc_name = trim($_POST['doc_name']);
            if (!empty($_FILES["pdf"]["name"])) {
                $fileName = time().'_'.basename($_FILES["pdf"]["name"]);
                move_uploaded_file($_FILES["pdf"]["tmp_name"], $uploadDir.$fileName);
                $newFilePath = $dbDir.$fileName;
                $stmt = $conn->prepare("UPDATE documents SET doc_name=?, file_path=? WHERE id=?");
                $stmt->bind_param("ssi", $doc_name, $newFilePath, $id);
            } else {
                $stmt = $conn->prepare("UPDATE documents SET doc_name=? WHERE id=?");
                $stmt->bind_param("si", $doc_name, $id);
            }
            $stmt->execute();
            redirect('/admin/documents');
        }

        if (isset($_POST['delete'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM documents WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirect('/admin/documents');
        }

        $result = $conn->query("SELECT * FROM documents ORDER BY created_at DESC");
        view('admin.documents', compact('result'));
    }

    public function gallery()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $uploadDir = __DIR__ . '/../../../public/uploads/gallery/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (isset($_POST['add_media'])) {
            $name = trim($_POST['media_name']);
            $cover = '';
            if (!empty($_FILES['cover_image']['name'])) {
                $cover = time().'_'.basename($_FILES['cover_image']['name']);
                move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir.$cover);
            }
            $created_at = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO media (name, cover_image, created_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $cover, $created_at);
            $stmt->execute();
            redirect('/admin/gallery');
        }

        if (isset($_POST['update_media'])) {
            $id = intval($_POST['id']);
            $name = trim($_POST['media_name']);
            if (!empty($_FILES['cover_image']['name'])) {
                $cover = time().'_'.basename($_FILES['cover_image']['name']);
                move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir.$cover);
                $stmt = $conn->prepare("UPDATE media SET name=?, cover_image=? WHERE id=?");
                $stmt->bind_param("ssi", $name, $cover, $id);
            } else {
                $stmt = $conn->prepare("UPDATE media SET name=? WHERE id=?");
                $stmt->bind_param("si", $name, $id);
            }
            $stmt->execute();
            redirect('/admin/gallery');
        }

        if (isset($_POST['delete_media'])) {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM media WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirect('/admin/gallery');
        }

        if (isset($_POST['add_category'])) {
            $media_id = intval($_POST['media_id']);
            $name = trim($_POST['category_name']);
            $cover = '';
            if(!empty($_FILES['cover_image']['name'])){
                $cover = time().'_'.basename($_FILES['cover_image']['name']);
                move_uploaded_file($_FILES['cover_image']['tmp_name'],$uploadDir.$cover);
            }
            $created_at = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO category (media_id,name,cover_image,created_at) VALUES (?,?,?,?)");
            $stmt->bind_param("isss",$media_id,$name,$cover,$created_at);
            $stmt->execute();
            redirect('/admin/gallery');
        }

        if (isset($_POST['update_category'])) {
            $id = intval($_POST['id']);
            $media_id = intval($_POST['media_id']);
            $name = trim($_POST['category_name']);
            if (!empty($_FILES['cover_image']['name'])) {
                $cover = time().'_'.basename($_FILES['cover_image']['name']);
                move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir.$cover);
                $stmt = $conn->prepare("UPDATE category SET media_id=?, name=?, cover_image=? WHERE id=?");
                $stmt->bind_param("issi", $media_id, $name, $cover, $id);
            } else {
                $stmt = $conn->prepare("UPDATE category SET media_id=?, name=? WHERE id=?");
                $stmt->bind_param("isi", $media_id, $name, $id);
            }
            $stmt->execute();
            redirect('/admin/gallery');
        }

        if (isset($_POST['delete_category'])) {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM category WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirect('/admin/gallery');
        }

        if(isset($_POST['add_photo'])){
            $category_id = intval($_POST['category_id']);
            $video_url = !empty($_POST['video_url']) ? trim($_POST['video_url']) : null;

            // Handle Video File Upload
            if (!empty($_FILES['video_file']['name'])) {
                $vidName = time().'_vid_'.basename($_FILES['video_file']['name']);
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadDir.$vidName)) {
                    $video_url = 'uploads/gallery/' . $vidName;
                }
            }

            if($category_id && !empty($_FILES['image']['name'])){
                $fileName = time().'_'.basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'],$uploadDir.$fileName);
                $created_at = date('Y-m-d H:i:s');
                // Insert with video_url
                $stmt = $conn->prepare("INSERT INTO photo (category_id, file_name, video_url, created_at) VALUES (?,?,?,?)");
                $stmt->bind_param("isss", $category_id, $fileName, $video_url, $created_at);
                $stmt->execute();
            }
            redirect('/admin/gallery');
        }

        if (isset($_POST['update_photo'])) {
            $id = intval($_POST['id']);
            $category_id = intval($_POST['category_id']);
            $video_url = !empty($_POST['video_url']) ? trim($_POST['video_url']) : null;

            // Handle Video File Upload
            if (!empty($_FILES['video_file']['name'])) {
                $vidName = time().'_vid_'.basename($_FILES['video_file']['name']);
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadDir.$vidName)) {
                    $video_url = 'uploads/gallery/' . $vidName;
                }
            }

            if (!empty($_FILES['image']['name'])) {
                $fileName = time().'_'.basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir.$fileName);
                $stmt = $conn->prepare("UPDATE photo SET category_id=?, file_name=?, video_url=? WHERE id=?");
                $stmt->bind_param("issi", $category_id, $fileName, $video_url, $id);
            } else {
                $stmt = $conn->prepare("UPDATE photo SET category_id=?, video_url=? WHERE id=?");
                $stmt->bind_param("isi", $category_id, $video_url, $id);
            }
            $stmt->execute();
            redirect('/admin/gallery');
        }

        if(isset($_POST['delete_photo'])){
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM photo WHERE id=?");
            $stmt->bind_param("i",$id);
            $stmt->execute();
            redirect('/admin/gallery');
        }

        $medias = $conn->query("SELECT * FROM media ORDER BY created_at DESC");
        $categories = $conn->query("SELECT c.*, m.name AS media_name FROM category c JOIN media m ON c.media_id=m.id ORDER BY c.created_at DESC");
        $photos = $conn->query("SELECT p.*, c.name AS category_name, m.name AS media_name FROM photo p JOIN category c ON p.category_id=c.id JOIN media m ON c.media_id=m.id ORDER BY p.created_at DESC");
        view('admin.gallery', compact('medias', 'categories', 'photos'));
    }

    public function paymentSettings()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $success = ''; $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (isset($_POST['update_settings'])) {
                    $payment_amount_sar = (float) $_POST['payment_amount'];
                    $payment_amount = (int) ($payment_amount_sar * 100);
                    $payment_currency = trim($_POST['payment_currency']);
                    $payment_enabled = isset($_POST['payment_enabled']) ? 1 : 0;

                    $conn->autocommit(false);
                    $stmt = $conn->prepare("UPDATE payment_settings SET setting_value = ? WHERE setting_name = 'PAYMENT_AMOUNT'");
                    $stmt->bind_param("s", $payment_amount); $stmt->execute();
                    $stmt = $conn->prepare("UPDATE payment_settings SET setting_value = ? WHERE setting_name = 'PAYMENT_CURRENCY'");
                    $stmt->bind_param("s", $payment_currency); $stmt->execute();
                    $stmt = $conn->prepare("UPDATE payment_settings SET setting_value = ? WHERE setting_name = 'PAYMENT_ENABLED'");
                    $stmt->bind_param("s", $payment_enabled); $stmt->execute();
                    $conn->commit();
                    $success = "Payment settings updated successfully!";
                } elseif (isset($_POST['create_table'])) {
                    $sql = "CREATE TABLE IF NOT EXISTS `payment_settings` (`id` int(11) NOT NULL AUTO_INCREMENT, `setting_name` varchar(100) NOT NULL UNIQUE, `setting_value` text NOT NULL, `description` text, `created_at` datetime DEFAULT CURRENT_TIMESTAMP, `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `unique_setting_name` (`setting_name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    $conn->query($sql);
                    $defaults = [['PAYMENT_AMOUNT', '100', 'Fee in SAR'], ['PAYMENT_CURRENCY', 'SAR', 'Code'], ['PAYMENT_ENABLED', '1', '1=enabled']];
                    $stmt = $conn->prepare("INSERT INTO payment_settings (setting_name, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    foreach ($defaults as $default) { $stmt->bind_param("sss", $default[0], $default[1], $default[2]); $stmt->execute(); }
                    $success = "Payment settings table created!";
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error: " . $e->getMessage();
            }
        }

        $table_exists = $conn->query("SHOW TABLES LIKE 'payment_settings'")->num_rows > 0;
        $settings = [];
        if ($table_exists) {
            $result = $conn->query("SELECT setting_name, setting_value, description FROM payment_settings");
            while ($row = $result->fetch_assoc()) { $settings[$row['setting_name']] = $row; }
        }
        $payment_amount_halala = $settings['PAYMENT_AMOUNT']['setting_value'] ?? 100;
        $payment_amount = $payment_amount_halala / 100;
        $payment_currency = $settings['PAYMENT_CURRENCY']['setting_value'] ?? 'SAR';
        $payment_enabled = ($settings['PAYMENT_ENABLED']['setting_value'] ?? 1) == 1;

        view('admin.payment_settings', compact('success', 'error', 'table_exists', 'settings', 'payment_amount', 'payment_currency', 'payment_enabled'));
    }

    public function presentation()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $uploadDir = __DIR__ . '/../../../public/uploads/presentasion/';
        $dbDir = "uploads/presentasion/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (isset($_POST['add'])) {
            $description = $_POST['description'];
            $filePath = null;
            if (!empty($_FILES["image"]["name"])) {
                $fileName = time().'_'.basename($_FILES["image"]["name"]);
                move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir.$fileName);
                $filePath = $dbDir.$fileName;
            }
            $created_at = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO presentations (description, file_path, created_at) VALUES (?,?,?)");
            $stmt->bind_param("sss", $description, $filePath, $created_at);
            $stmt->execute();
            redirect('/admin/presentation');
        }

        if (isset($_POST['update'])) {
            $id = $_POST['id'];
            $description = $_POST['description'];
            if (!empty($_FILES["image"]["name"])) {
                $fileName = time().'_'.basename($_FILES["image"]["name"]);
                move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir.$fileName);
                $filePath = $dbDir.$fileName;
                $stmt = $conn->prepare("UPDATE presentations SET description=?, file_path=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param("ssi", $description, $filePath, $id);
            } else {
                $stmt = $conn->prepare("UPDATE presentations SET description=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param("si", $description, $id);
            }
            $stmt->execute();
            redirect('/admin/presentation');
        }

        if (isset($_POST['delete'])) {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM presentations WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirect('/admin/presentation');
        }

        $result = $conn->query("SELECT * FROM presentations ORDER BY created_at DESC");
        view('admin.presentation', compact('result'));
    }

    public function players()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $sql = "SELECT tmi.player_name, tmi.age, tmi.role, tmi.position, tmi.point, ti.team_name FROM team_members_info tmi JOIN team_info ti ON ti.id = tmi.team_id GROUP BY tmi.player_name, ti.team_name ORDER BY tmi.player_name ASC";
        $result = $conn->query($sql);
        view('admin.players', compact('result'));
    }

    public function registrations()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $search = $_GET['search'] ?? '';

        $sql = "SELECT pt.*, ti.team_name, ti.created_at AS team_created_at, t.name AS tournament_name FROM payment_transactions pt JOIN team_info ti ON pt.team_id = ti.id LEFT JOIN tournaments t ON pt.tournament_id = t.id";
        if ($search) {
            $sql .= " WHERE ti.team_name LIKE ? AND pt.status = 'paid'";
            $stmt = $conn->prepare($sql . " ORDER BY pt.created_at DESC");
            $like = "%$search%";
            $stmt->bind_param('s', $like);
        } else {
            $stmt = $conn->prepare($sql . " WHERE pt.status = 'paid' ORDER BY pt.created_at DESC");
        }
        $stmt->execute();
        $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        view('admin.registrations', compact('transactions', 'search'));
    }

    public function windows()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();

        if (isset($_POST['add'])) {
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $stmt = $conn->prepare("INSERT INTO transfer_windows (start_date, end_date) VALUES (?,?)");
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            redirect('/admin/windows');
        }

        if (isset($_POST['update'])) {
            $id = $_POST['id'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $stmt = $conn->prepare("UPDATE transfer_windows SET start_date=?, end_date=? WHERE id=?");
            $stmt->bind_param("ssi", $start_date, $end_date, $id);
            $stmt->execute();
            redirect('/admin/windows');
        }

        if (isset($_POST['delete'])) {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM transfer_windows WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirect('/admin/windows');
        }

        $result = $conn->query("SELECT * FROM transfer_windows ORDER BY start_date DESC");
        view('admin.windows', compact('result'));
    }

    public function users()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();

        // Teams
        $teams = $conn->query("SELECT ta.team_id, ta.username, ti.team_name FROM team_account ta JOIN team_info ti ON ta.team_id = ti.id ORDER BY ti.team_name ASC");

        // Users
        $users = $conn->query("SELECT id, username, email, role FROM users ORDER BY username ASC");

        view('admin.users', compact('teams', 'users'));
    }

    public function impersonate()
    {
        $this->ensureAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $type = $_POST['type'];

            if ($type === 'team') {
                $conn = getDBConnection();
                $stmt = $conn->prepare("SELECT username FROM team_account WHERE team_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    session_unset();
                    $_SESSION['team_id'] = $id;
                    $_SESSION['username'] = $row['username'];
                    redirect('/dashboard');
                }
            } elseif ($type === 'user') {
                $conn = getDBConnection();
                $stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    session_unset();
                    $_SESSION['user_id'] = $id;
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    redirect($row['role'] === 'admin' ? '/admin/dashboard' : '/');
                }
            }
        }
        redirect('/admin/users');
    }

    public function settings()
    {
        $this->ensureAdmin();
        $conn = getDBConnection();
        $success = null;
        $error = null;
        $uploadDir = __DIR__ . '/../../../public/uploads/logo/';
        $dbDir = "uploads/logo/";

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_general') {
                if (!empty($_FILES['site_logo']['name'])) {
                    $ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $fileName = 'site_logo_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $uploadDir . $fileName)) {
                            $filePath = $dbDir . $fileName;
                            $stmt = $conn->prepare("INSERT INTO payment_settings (setting_name, setting_value) VALUES ('SITE_LOGO', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                            $stmt->bind_param("s", $filePath);
                            $stmt->execute();
                            $success = "Logo updated successfully.";
                        } else {
                            $error = "Failed to move uploaded file.";
                        }
                    } else {
                        $error = "Invalid file type. Please upload an image.";
                    }
                }
            } elseif ($action === 'change_password') {
                $current = $_POST['current_password'];
                $new = $_POST['new_password'];
                $confirm = $_POST['confirm_password'];
                $userId = $_SESSION['user_id'];

                $passwordService = new PasswordService($conn);
                $result = $passwordService->changePassword($userId, $current, $new, $confirm);

                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        }

        view('admin.settings', compact('success', 'error'));
    }

    private function ensureAdmin()
    {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            redirect('/login');
        }
    }
}
