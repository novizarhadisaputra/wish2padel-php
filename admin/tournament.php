<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}
require '../config.php';
$conn = getDBConnection();
$username = $_SESSION['username'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.wish2padel.com/assets/image/w2p.png">
    <link rel="apple-touch-icon" href="https://www.wish2padel.com/assets/image/w2p.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>League Zone - Wish2Padel</title>
  <link rel="stylesheet" href="../assets/css/stylee.css?v=12">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: #303030">

<?php
// Set timezone Riyadh
date_default_timezone_set('Asia/Riyadh');


// Ambil semua leagues untuk dropdown
$leagues = $conn->query("SELECT * FROM league ORDER BY name ASC");

// ------------------ HANDLE LEAGUE ------------------
// Add League
if(isset($_POST['add_league'])) {
    $name = $_POST['name'];
    $deskripsi = $_POST['deskripsi'] ?: NULL;
    $date_input = $_POST['date']; // misal input type="date"
    $date = date('Y-m-d', strtotime($date_input)); // convert ke Riyadh date
    $stmt = $conn->prepare("INSERT INTO league (name, deskripsi, date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $deskripsi, $date);
    $stmt->execute();
    $stmt->close();
}

// Edit League
if (isset($_POST['edit_league'])) {
    $id = (int) $_POST['id'];
    $name = trim($_POST['name']);
    $deskripsi = !empty($_POST['deskripsi']) ? $_POST['deskripsi'] : null;

    // Pastikan date dikirim dan valid
    $year = isset($_POST['date']) && is_numeric($_POST['date']) ? (int) $_POST['date'] : null;

    if ($year === null || $year < 1901 || $year > 2155) {
        // YEAR MySQL hanya valid antara 1901–2155
        error_log("Invalid YEAR value: " . var_export($year, true));
        die("Invalid year input.");
    }

    // Coba update — kolom YEAR bisa diisi pakai string atau integer, dua-duanya valid
    $stmt = $conn->prepare("UPDATE league SET name=?, deskripsi=?, date=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $deskripsi, $year, $id);

    if ($stmt->execute()) {
        echo "League updated successfully!";
    } else {
        echo "MySQL Error: " . $stmt->error;
    }

    $stmt->close();
}


// Delete League
if(isset($_POST['delete_league'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM league WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// ✅ ADD Division
if (isset($_POST['add_division'])) {
    $id = (int) $_POST['id'];
    $division_name = trim($_POST['division_name']);

    $stmt = $conn->prepare("INSERT INTO divisions (id, division_name) VALUES (?, ?)");
    $stmt->bind_param("is", $id, $division_name);
    $stmt->execute();
    $stmt->close();
}


if (isset($_POST['edit_division'])) {
    $old_id = (int) $_POST['id'];       // contoh: 2
    $new_id = (int) $_POST['id_new'];   // contoh: 3
    $division_name = trim($_POST['division_name']); 

    // 0️⃣ Pastikan division baru sudah ada (kalau belum, tambahin dulu)
    $stmt = $conn->prepare("
        INSERT INTO divisions (id, division_name)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE division_name = VALUES(division_name)
    ");
    $stmt->bind_param("is", $new_id, $division_name);
    $stmt->execute();
    $stmt->close();

    // 1️⃣ Ambil semua tim yang eligible (upcoming)
    $result = $conn->query("
        SELECT tcd.team_id
        FROM team_contact_details tcd
        JOIN team_info ti ON tcd.team_id = ti.id
        JOIN tournaments tor ON ti.tournament_id = tor.id
        WHERE tcd.division = $old_id AND tor.status = 'upcoming'
    ");

    $updated = 0;
    while ($row = $result->fetch_assoc()) {
        $team_id = (int)$row['team_id'];
        $conn->query("UPDATE team_contact_details SET division = $new_id WHERE team_id = $team_id");
        if ($conn->affected_rows > 0) {
            $updated++;
        }
    }
    
}






// ✅ DELETE Division — tapi periksa dulu apakah masih dipakai
if (isset($_POST['delete_division'])) {
    $id = (int) $_POST['id'];

    // Cek kalau masih ada tim yang pakai divisi ini
    $check = $conn->prepare("SELECT COUNT(*) FROM team_contact_details WHERE division = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->bind_result($used_count);
    $check->fetch();
    $check->close();

    if ($used_count > 0) {
        echo "<script>alert('Cannot delete! This division is still used by $used_count team(s).');</script>";
    } else {
        $stmt = $conn->prepare("DELETE FROM divisions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}


// ------------------ HANDLE TOURNAMENT ------------------
// Add Tournament
if (isset($_POST['add_tournament'])) {
    // Naikkan warning/error jadi exception
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Tangkep fatal error terakhir -> console
    register_shutdown_function(function() {
        $e = error_get_last();
        if ($e) echo "<script>console.error('FATAL:', ".json_encode($e, JSON_UNESCAPED_SLASHES).");</script>";
    });

    // Helpers
    function jslog($label, $data = []) {
        echo "<script>console.log(".json_encode($label).", ".json_encode($data, JSON_UNESCAPED_SLASHES).");</script>";
    }
    function jsredir($url, $extraLog = []) {
        if ($extraLog) jslog('Redirecting', $extraLog);
        echo "<script>location.href=".json_encode($url).";</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=".htmlspecialchars($url, ENT_QUOTES)."'></noscript>";
        exit;
    }

    try {
        $conn->begin_transaction();

        // =======================
        // STEP 1: INPUT
        // =======================
        $name       = $_POST['name'];
        $start_date = date('Y-m-d', strtotime($_POST['start_date']));
        $end_date   = date('Y-m-d', strtotime($_POST['end_date']));
        $id_league  = (int)$_POST['id_league'];
        $status     = 'upcoming';
        jslog('STEP 1: INPUT', compact('name','start_date','end_date','id_league','status'));

        // =======================
        // STEP 2: INSERT TOURNAMENT BARU
        // =======================
        $stmt = $conn->prepare("
            INSERT INTO tournaments (name, start_date, end_date, id_league, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssis", $name, $start_date, $end_date, $id_league, $status);
        $stmt->execute();
        $new_tournament_id = (int)$stmt->insert_id;
        $stmt->close();
        jslog('STEP 2: INSERTED new tournament', ['new_tournament_id'=>$new_tournament_id]);

        // =======================
        // STEP 3: CARI TURNAMEN LAMA (nama sama, id < baru, ambil paling baru sebelumnya)
        // =======================
        $oldTournament = $conn->prepare("
            SELECT id FROM tournaments
            WHERE name = ? AND id < ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $oldTournament->bind_param("si", $name, $new_tournament_id);
        $oldTournament->execute();
        $res = $oldTournament->get_result();

        if ($res->num_rows === 0) {
            $oldTournament->close();
            $conn->commit();
            jslog('INFO: No previous tournament found. Only inserted the new one.', [
                'name'=>$name, 'new_tournament_id'=>$new_tournament_id
            ]);
            jsredir('tournament', ['onlyInsert'=>true]);
        }
        $old_tournament_id = (int)$res->fetch_assoc()['id'];
        $oldTournament->close();
        jslog('STEP 3: FOUND old tournament', ['old_tournament_id'=>$old_tournament_id]);

        // =======================
        // STEP 4: DIV_MAX (tabel divisions hanya id, division_name)
        // =======================
        $stmtMaxDiv = $conn->prepare("SELECT COALESCE(MAX(id), 1) AS max_div FROM divisions");
        $stmtMaxDiv->execute();
        $DIV_MAX = (int)$stmtMaxDiv->get_result()->fetch_assoc()['max_div'];
        $stmtMaxDiv->close();
        if ($DIV_MAX < 1) $DIV_MAX = 1;

        $promote  = fn($div) => max(1, (int)$div - 1);
        $relegate = fn($div) => min($DIV_MAX, (int)$div + 1);
        jslog('STEP 4: DIV_MAX & helpers ready', ['DIV_MAX'=>$DIV_MAX]);

        // =======================
        // STEP 5: HITUNG LEADERBOARD REGULAR (journey <= 14) DARI TURNAMEN LAMA
        // =======================
        $leaderboard = [];
        $stmtTeamsLB = $conn->prepare("
            SELECT ti.id AS team_id, ti.team_name, ti.logo, COALESCE(tcd.division, 0) AS division
            FROM team_info ti
            LEFT JOIN team_contact_details tcd ON tcd.team_id = ti.id
            WHERE ti.tournament_id = ?
        ");
        $stmtTeamsLB->bind_param("i", $old_tournament_id);
        $stmtTeamsLB->execute();
        $resTeamsLB = $stmtTeamsLB->get_result();

        $stmtMatches = $conn->prepare("
            SELECT DISTINCT m.id AS match_id
            FROM matches m
            JOIN team_pairs tp ON tp.match_id = m.id
            WHERE tp.team_id = ?
              AND m.tournament_id = ?
              AND m.status = 'completed'
              AND m.journey <= 14
        ");
        $stmtPairs = $conn->prepare("SELECT id FROM team_pairs WHERE match_id = ? AND team_id = ?");
        $stmtSets  = $conn->prepare("SELECT is_winner FROM pair_scores WHERE pair_id = ? AND match_id = ?");

        $__teams_count = 0;
        while ($t = $resTeamsLB->fetch_assoc()) {
            $__teams_count++;
            $tid      = (int)$t['team_id'];
            $div_id   = (int)$t['division'];
            $nameTeam = $t['team_name'];
            $logo     = $t['logo'] ?? '';

            $stmtMatches->bind_param("ii", $tid, $old_tournament_id);
            $stmtMatches->execute();
            $resM = $stmtMatches->get_result();

            $matches_played = 0;
            $matches_won = $matches_lost = 0;
            $pairs_won_total = 0;
            $pairs_lost_total = 0;
            $sets_won_total  = 0;
            $sets_lost_total = 0;
            $points = 0;

            while ($m = $resM->fetch_assoc()) {
                $match_id = (int)$m['match_id'];

                $stmtPairs->bind_param("ii", $match_id, $tid);
                $stmtPairs->execute();
                $resP = $stmtPairs->get_result();

                $pairs_won = 0;
                $pairs_lost = 0;
                $sets_won = 0;
                $sets_lost = 0;
                $has_result = false;

                while ($p = $resP->fetch_assoc()) {
                    $pair_id = (int)$p['id'];
                    $stmtSets->bind_param("ii", $pair_id, $match_id);
                    $stmtSets->execute();
                    $resS = $stmtSets->get_result();

                    $w = 0; $l = 0;
                    while ($s = $resS->fetch_assoc()) {
                        $has_result = true;
                        if ((int)$s['is_winner'] === 1) { $w++; $sets_won++; }
                        else { $l++; $sets_lost++; }
                    }
                    if ($w > $l) $pairs_won++;
                    elseif ($w < $l) $pairs_lost++;
                }

                if ($has_result) {
                    $matches_played++;
                    if     ($pairs_won == 3)                   { $matches_won++; $points += 3; }
                    elseif ($pairs_won == 2 && $pairs_lost==1) { $matches_won++; $points += 2; }
                    elseif ($pairs_won == 1 && $pairs_lost==2) { $matches_lost++; $points += 1; }
                    elseif ($pairs_won == 0 && $pairs_lost==3) { $matches_lost++; }
                    $pairs_won_total += $pairs_won;
                    $pairs_lost_total += $pairs_lost;
                    $sets_won_total   += $sets_won;
                    $sets_lost_total  += $sets_lost;
                }
            }

            $leaderboard[] = [
                'team_id'        => $tid,
                'team_name'      => $nameTeam,
                'logo'           => $logo,
                'division'       => $div_id,
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
        $stmtSets->close();
        $stmtPairs->close();
        $stmtMatches->close();
        $stmtTeamsLB->close();

        jslog('STEP 5: Leaderboard collected', [
            'team_entries'=>count($leaderboard), 'teams_scanned'=>$__teams_count
        ]);

        // =======================
        // STEP 6: GROUP & SORT per DIVISI (regular season)
        // =======================
        $teamsByDiv = [];
        foreach ($leaderboard as $row) {
            $d = (int)$row['division'];
            if ($d <= 0) continue;
            $teamsByDiv[$d][] = $row;
        }
        foreach ($teamsByDiv as $div => &$bucket) {
            usort($bucket, function($a, $b) {
                if ($b['points'] !== $a['points']) return $b['points'] - $a['points'];
                $sdA = $a['sets_won'] - $a['sets_lost'];
                $sdB = $b['sets_won'] - $b['sets_lost'];
                if ($sdB !== $sdA) return $sdB - $sdA;
                $pdA = $a['pairs_won'] - $a['pairs_lost'];
                $pdB = $b['pairs_won'] - $b['pairs_lost'];
                if ($pdB !== $pdA) return $pdB - $pdA;
                if ($b['sets_won']  !== $a['sets_won'])  return $b['sets_won']  - $a['sets_won'];
                if ($b['pairs_won'] !== $a['pairs_won']) return $b['pairs_won'] - $a['pairs_won'];
                return strcasecmp($a['team_name'], $b['team_name']);
            });
        }
        unset($bucket);
        jslog('STEP 6: Grouped & sorted per division', array_map(fn($rows)=>count($rows), $teamsByDiv));

 // =======================
// STEP 7: FINAL 2-LEG — hitung pemenang final (TOP-2)
// =======================

// Helper: hitung metric 1 tim di 1 match (cuma pair milik tim & match tsb)
$calcLegMetrics = function(mysqli $conn, int $match_id, int $team_id): array {
    $pairsWon = 0; $setsWon = 0; $setsLost = 0;

    $sp = $conn->prepare("SELECT id FROM team_pairs WHERE match_id=? AND team_id=?");
    $sp->bind_param("ii", $match_id, $team_id);
    $sp->execute();
    $rp = $sp->get_result();

    $ss = $conn->prepare("SELECT is_winner FROM pair_scores WHERE match_id=? AND pair_id=?");

    while ($p = $rp->fetch_assoc()) {
        $pid = (int)$p['id'];
        $w = 0; $l = 0;

        $ss->bind_param("ii", $match_id, $pid);
        $ss->execute();
        $rs = $ss->get_result();

        while ($s = $rs->fetch_assoc()) {
            if ((int)$s['is_winner'] === 1) { $w++; $setsWon++; } else { $l++; $setsLost++; }
        }
        if ($w > $l) $pairsWon++;
    }

    $ss->close();
    $sp->close();

    return [
        'match_points' => $pairsWon,
        'pairs_won'    => $pairsWon,
        'sets_won'     => $setsWon,
        'sets_lost'    => $setsLost
    ];
};

// Ambil match final (berdasarkan NOTES, bukan journey)
$stmtFinals = $conn->prepare("
    SELECT 
        m.id, m.notes, m.team1_id, m.team2_id,
        d1.division AS div1, d2.division AS div2
    FROM matches m
    JOIN team_contact_details d1 ON d1.team_id = m.team1_id
    JOIN team_contact_details d2 ON d2.team_id = m.team2_id
    WHERE m.tournament_id = ?
      AND m.notes IN ('Final 1', 'Final 2')
      AND m.team1_id IS NOT NULL
      AND m.team2_id IS NOT NULL
      AND d1.division = d2.division
");
$stmtFinals->bind_param("i", $old_tournament_id);
$stmtFinals->execute();
$resF = $stmtFinals->get_result();

$finalByDiv = []; // div => ['leg1'=>row, 'leg2'=>row]
while ($r = $resF->fetch_assoc()) {
    $div  = (int)$r['div1'];
    $note = strtolower(trim($r['notes']));
    if (!isset($finalByDiv[$div])) $finalByDiv[$div] = [];

    // Normalisasi: hilangkan spasi ganda, dan cocokkan berbagai format final
    if (in_array($note, ['final 1', 'final leg 1'])) {
        $finalByDiv[$div]['leg1'] = $r;
    } elseif (in_array($note, ['final 2', 'final leg 2'])) {
        $finalByDiv[$div]['leg2'] = $r;
    }

    // Optional debug log biar kelihatan di console
    jslog('Final detected', [
        'division' => $div,
        'notes'    => $r['notes'],
        'match_id' => $r['id'],
        'team1_id' => $r['team1_id'],
        'team2_id' => $r['team2_id']
    ]);
}
$stmtFinals->close();

jslog('STEP 7: Finals fetched', ['divs_with_finals'=>count($finalByDiv)]);

// Hitung pemenang & finalis per divisi
$finalWinnerByDiv = [];
$finalLoserByDiv  = [];
$finalDebugLog    = [];

foreach ($finalByDiv as $div => $legs) {
    if (empty($legs['leg1']) || empty($legs['leg2'])) continue; // belum lengkap

    $leg1 = $legs['leg1'];
    $leg2 = $legs['leg2'];

    // pastikan 2 tim konsisten di dua leg
    $uniq = [];
    $uniq[(int)$leg1['team1_id']] = true;
    $uniq[(int)$leg1['team2_id']] = true;
    $uniq[(int)$leg2['team1_id']] = true;
    $uniq[(int)$leg2['team2_id']] = true;
    $ids = array_keys($uniq);
    if (count($ids) !== 2) continue;
    [$A,$B] = $ids;

    // metric tiap leg per tim
    $A_leg1 = $calcLegMetrics($conn, (int)$leg1['id'], $A);
    $A_leg2 = $calcLegMetrics($conn, (int)$leg2['id'], $A);
    $B_leg1 = $calcLegMetrics($conn, (int)$leg1['id'], $B);
    $B_leg2 = $calcLegMetrics($conn, (int)$leg2['id'], $B);

    // agregat 2 leg
    $A_pairs_total = $A_leg1['pairs_won'] + $A_leg2['pairs_won'];
    $B_pairs_total = $B_leg1['pairs_won'] + $B_leg2['pairs_won'];

    $A_setsW = $A_leg1['sets_won'] + $A_leg2['sets_won'];
    $A_setsL = $A_leg1['sets_lost'] + $A_leg2['sets_lost'];
    $B_setsW = $B_leg1['sets_won'] + $B_leg2['sets_won'];
    $B_setsL = $B_leg1['sets_lost'] + $B_leg2['sets_lost'];

    $A_set_diff = $A_setsW - $A_setsL;
    $B_set_diff = $B_setsW - $B_setsL;

    // Tentukan pemenang
    if ($A_pairs_total !== $B_pairs_total) {
        $winner = ($A_pairs_total > $B_pairs_total) ? $A : $B;
    } else {
        if ($A_leg2['pairs_won'] !== $B_leg2['pairs_won']) {
            $winner = ($A_leg2['pairs_won'] > $B_leg2['pairs_won']) ? $A : $B;
        } else {
            // Fallback: yang away di leg 2 menang
            $winner = ($leg2['team2_id'] == $A) ? $A : $B;
        }
    }

    $loser = ($winner === $A) ? $B : $A;

    $finalWinnerByDiv[$div] = $winner;
    $finalLoserByDiv[$div]  = $loser;

    // Debug log
    $finalDebugLog[$div] = [
        'leg1' => [ $A => $A_leg1, $B => $B_leg1 ],
        'leg2' => [ $A => $A_leg2, $B => $B_leg2 ],
        'aggregate' => [
            $A => ['pairs'=>$A_pairs_total, 'set_diff'=>$A_set_diff, 'setsW'=>$A_setsW, 'setsL'=>$A_setsL],
            $B => ['pairs'=>$B_pairs_total, 'set_diff'=>$B_set_diff, 'setsW'=>$B_setsW, 'setsL'=>$B_setsL],
        ],
        'winner' => $winner,
        'loser'  => $loser
    ];
}

jslog('STEP 8: Finals aggregate (detail)', $finalDebugLog);
jslog('STEP 8: Final winners per division', $finalWinnerByDiv);


// =======================
// STEP 9: PROMOSI & DEGRADASI
// =======================
// Top-1  = champion regular (#1 per divisi)
// Top-2  = **pemenang final** (kalau sama dengan champion, ambil finalis lainnya)
// Jika tidak ada final → fallback regular #2 (kalau ada)
$divisionUpdates = [];
$championsLog = [];
$top2Log = [];
$relegatedLog = [];

foreach ($teamsByDiv as $div => $rows) {
    // Champion REGULAR
    $championId   = (int)$rows[0]['team_id'];
    $championsLog[$div] = ['team_id'=>$championId, 'team_name'=>$rows[0]['team_name']];

    // Tentukan Top-2 (promosi dari FINAL)
    $finalWinner = $finalWinnerByDiv[$div] ?? 0;
    $finalLoser  = $finalLoserByDiv[$div] ?? 0;

    if ($finalWinner > 0) {
        // kalau finalWinner sama dgn champion → ambil finalis lainnya biar tetap 2 tim naik
        $top2 = ($finalWinner !== $championId) ? $finalWinner : ($finalLoser ?: 0);
    } else {
        // tidak ada final → fallback regular #2 (jika ada)
        $top2 = isset($rows[1]) ? (int)$rows[1]['team_id'] : 0;
    }

    // simpan log Top-2 (nama untuk debugging)
    if ($top2 > 0) {
        foreach ($rows as $r) if ((int)$r['team_id'] === $top2) {
            $top2Log[$div] = ['team_id'=>$top2, 'team_name'=>$r['team_name']];
            break;
        }
    } else {
        $top2Log[$div] = ['team_id'=>0, 'team_name'=>'(none)'];
    }

    // Degradasi dari REGULAR
    $n = count($rows);
    $relegatedIds = [];
    if     ($n >= 4) { $relegatedIds = [ (int)$rows[$n-1]['team_id'], (int)$rows[$n-2]['team_id'] ]; }
    elseif ($n === 3){ $relegatedIds = [ (int)$rows[2]['team_id'] ]; }
    elseif ($n === 2){ $relegatedIds = [ (int)$rows[1]['team_id'] ]; }

    $relegatedLog[$div] = [];
    foreach ($relegatedIds as $rid) {
        foreach ($rows as $r) if ((int)$r['team_id'] === $rid) {
            $relegatedLog[$div][] = ['team_id'=>$rid, 'team_name'=>$r['team_name']];
            break;
        }
    }

    // Apply perubahan divisi
    foreach ($rows as $r) {
        $tid = (int)$r['team_id'];
        $od  = (int)$r['division'];
        if ($od <= 0) { $divisionUpdates[$tid] = $od; continue; }

        if ($tid === $championId || ($top2 && $tid === $top2)) {
            $divisionUpdates[$tid] = $promote($od);    // NAiK
        } elseif (in_array($tid, $relegatedIds, true)) {
            $divisionUpdates[$tid] = $relegate($od);   // TURUN
        } else {
            $divisionUpdates[$tid] = $od;              // TETAP
        }
    }
}

jslog('PROMO: champions (regular) per division', $championsLog);
jslog('PROMO: top2 (FINAL winner / fallback)', $top2Log);
jslog('RELEGATIONS per division', $relegatedLog);
jslog('STEP 9: Division updates (promote/relegate)', $divisionUpdates);


        // =======================
        // STEP 10: CLONE DATA ke TURNAMEN BARU + sinkron division
        // =======================
        $teams = $conn->query("SELECT * FROM team_info WHERE tournament_id = {$old_tournament_id}");
        $clonedTeams = 0;
        $movedAccounts = 0;

        while ($team = $teams->fetch_assoc()) {
            $old_team_id = (int)$team['id'];

            // 10.1 team_info
            $stmt = $conn->prepare("
                INSERT INTO team_info (team_name, captain_name, captain_phone, captain_email, logo, tournament_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssi",
                $team['team_name'],
                $team['captain_name'],
                $team['captain_phone'],
                $team['captain_email'],
                $team['logo'],
                $new_tournament_id
            );
            $stmt->execute();
            $new_team_id = (int)$stmt->insert_id;
            $stmt->close();
            $clonedTeams++;

            // 10.2 contact details
            $stmtContact = $conn->prepare("
                SELECT contact_phone, contact_email, club, city, level, division, notes
                FROM team_contact_details
                WHERE team_id = ?
            ");
            $stmtContact->bind_param("i", $old_team_id);
            $stmtContact->execute();
            $contactRes = $stmtContact->get_result();

            $calcDiv = isset($divisionUpdates[$old_team_id]) ? (int)$divisionUpdates[$old_team_id] : 0;
            if ($contactRes->num_rows > 0) {
                $contact = $contactRes->fetch_assoc();
                if ($calcDiv <= 0) $calcDiv = (int)($contact['division'] ?? 0);

                $stmtInsertContact = $conn->prepare("
                    INSERT INTO team_contact_details 
                        (team_id, contact_phone, contact_email, club, city, level, division, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtInsertContact->bind_param("isssssis",
                    $new_team_id,
                    $contact['contact_phone'],
                    $contact['contact_email'],
                    $contact['club'],
                    $contact['city'],
                    $contact['level'],
                    $calcDiv,
                    $contact['notes']
                );
                $stmtInsertContact->execute();
                $stmtInsertContact->close();
            } else {
                if ($calcDiv <= 0) $calcDiv = 1; // fallback: taruh Div 1 kalau kosong
                $stmtInsertContact = $conn->prepare("
                    INSERT INTO team_contact_details 
                        (team_id, contact_phone, contact_email, club, city, level, division, notes)
                    VALUES (?, '', '', '', '', '', ?, '')
                ");
                $stmtInsertContact->bind_param("ii", $new_team_id, $calcDiv);
                $stmtInsertContact->execute();
                $stmtInsertContact->close();
            }
            $stmtContact->close();

            // 10.3 clone members
            $stmtMembers = $conn->prepare("
                SELECT player_name, age, profile, role, position, joined_at, point
                FROM team_members_info
                WHERE team_id = ?
            ");
            $stmtMembers->bind_param("i", $old_team_id);
            $stmtMembers->execute();
            $membersRes = $stmtMembers->get_result();
            $clonedMembers = 0;
            
            while ($m = $membersRes->fetch_assoc()) {
                $stmtIM = $conn->prepare("
                    INSERT INTO team_members_info
                        (team_id, player_name, age, profile, role, position, joined_at, point)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtIM->bind_param("isissssi",
                    $new_team_id,
                    $m['player_name'],
                    $m['age'],
                    $m['profile'],
                    $m['role'],
                    $m['position'],
                    $m['joined_at'],
                    $m['point'] // tambahkan ini
                );
                $stmtIM->execute();
                $stmtIM->close();
                $clonedMembers++;
            }
            $stmtMembers->close();

            // 10.4 pindahkan akun ke tim baru
            $stmtAcc = $conn->prepare("UPDATE team_account SET team_id = ? WHERE team_id = ?");
            $stmtAcc->bind_param("ii", $new_team_id, $old_team_id);
            $stmtAcc->execute();
            $movedAccounts += $stmtAcc->affected_rows;
            $stmtAcc->close();

            jslog('CLONE per team', [
                'old_team_id'=>$old_team_id,
                'new_team_id'=>$new_team_id,
                'division_new'=>$calcDiv,
                'members_cloned'=>$clonedMembers
            ]);
        }

        jslog('STEP 10: Clone summary', [
            'clonedTeams'=>$clonedTeams,
            'movedAccounts'=>$movedAccounts,
            'new_tournament_id'=>$new_tournament_id,
            'old_tournament_id'=>$old_tournament_id
        ]);

        // =======================
        // DONE
        // =======================
        $conn->commit();
        jsredir('tournament', ['done'=>true, 'new_tournament_id'=>$new_tournament_id]);

    } catch (Throwable $e) {
        // Rollback & log
        $conn->rollback();
        echo "<script>console.error('AddTournament ERROR:', ".json_encode($e->getMessage(), JSON_UNESCAPED_SLASHES).");</script>";
        echo "Terjadi error saat add tournament. Cek Console (F12).";
        exit;
    }
}



// Edit Tournament
if(isset($_POST['edit_tournament'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];

    $start_date_input = $_POST['start_date'];
    $start_date = date('Y-m-d', strtotime($start_date_input));

    $end_date_input = $_POST['end_date']; // ✅ ambil dari form edit
    $end_date = date('Y-m-d', strtotime($end_date_input));

    $id_league = $_POST['id_league'];

    $stmt = $conn->prepare("UPDATE tournaments 
                            SET name=?, start_date=?, end_date=?, id_league=?
                            WHERE id=?");
    $stmt->bind_param("sssii", $name, $start_date, $end_date, $id_league, $id);
    $stmt->execute();
    $stmt->close();
}


// Complete Tournament
if(isset($_POST['complete_tournament'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("UPDATE tournaments SET status='completed' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Ambil data league & tournament (urut descending ID)
$leagues = $conn->query("SELECT * FROM league ORDER BY id DESC");
$divisions = $conn->query("SELECT * FROM divisions ORDER BY id ASC");
$tournaments = $conn->query("
    SELECT t.*, l.name AS league_name 
    FROM tournaments t 
    LEFT JOIN league l ON t.id_league = l.id 
    ORDER BY t.id DESC
");
?>


<?php require 'src/navbar.php' ?>


<!-- ================= MANAGE LEAGUE ================= -->
<section class="p-4">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-white">
        <i class="bi bi-trophy-fill text-warning me-2"></i> Manage League
      </h2>
      <button class="btn-gold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addLeagueModal">
        <i class="bi bi-plus-circle me-1"></i> Add League
      </button>
    </div>

    <!-- Table Card -->
    <div class="card shadow border-0 rounded-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-success">
              <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Year</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
  <?php while($row = $leagues->fetch_assoc()): ?>
    <tr>
      <td class="fw-semibold text-dark"><?= htmlspecialchars($row['name']) ?></td>
      <td class="text-muted"><?= htmlspecialchars($row['deskripsi'] ?? '-') ?></td>
      <td>
        <span class="badge bg-info text-dark px-3 py-2 rounded-pill">
          <?= $row['date'] ?>
        </span>
      </td>
      <td class="text-center">
        <button class="btn btn-sm btn-outline-warning me-1 rounded-circle" 
                data-bs-toggle="modal" 
                data-bs-target="#editLeagueModal<?= $row['id'] ?>" 
                title="Edit">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger rounded-circle" 
                data-bs-toggle="modal" 
                data-bs-target="#deleteLeagueModal<?= $row['id'] ?>" 
                title="Delete">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>
  <?php endwhile; ?>
</tbody>

          </table>
          <?php 
$leagues->data_seek(0); // Reset pointer agar bisa dibaca ulang
while($row = $leagues->fetch_assoc()): 
    $leagueId = (int)$row['id'];
?>
    <!-- Edit League Modal -->
    <div class="modal fade" id="editLeagueModal<?= $leagueId ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered" style="margin-top:70px">
        <div class="modal-content shadow-lg rounded-4 border-0">
          <div class="modal-header bg-warning text-dark rounded-top-4">
            <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update League</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST">
            <div class="modal-body">
              <input type="hidden" name="id" value="<?= $leagueId ?>">

              <div class="form-floating mb-3">
                <input type="text" name="name" class="form-control" 
                       value="<?= htmlspecialchars($row['name']) ?>" 
                       placeholder="Enter league name" required>
                <label>Name</label>
              </div>

              <div class="form-floating mb-3">
                <input type="text" name="deskripsi" class="form-control" 
                       value="<?= htmlspecialchars($row['deskripsi'] ?? '') ?>" 
                       placeholder="Optional: short description">
                <label>Description</label>
              </div>

              <div class="form-floating mb-3">
                <input type="number" name="date" class="form-control" 
                       value="<?= $row['date'] ?>" 
                       placeholder="e.g., 2025" required>
                <label>Year</label>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="edit_league" class="btn-gold">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete League Modal -->
    <div class="modal fade" id="deleteLeagueModal<?= $leagueId ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4 border-0">
          <div class="modal-header bg-danger text-white rounded-top-4">
            <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST">
            <div class="modal-body">
              <p class="mb-0">Are you sure you want to delete league 
                <strong class="text-danger"><?= htmlspecialchars($row['name']) ?></strong>?
              </p>
              <input type="hidden" name="id" value="<?= $leagueId ?>">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="delete_league" class="btn btn-danger">Delete</button>
            </div>
          </form>
        </div>
      </div>
    </div>
<?php endwhile; ?>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= MANAGE ZONE ================= -->
<?php
// Ambil data league & tournament
$leagues = $conn->query("SELECT * FROM league ORDER BY id DESC");
$tournamentsQuery = $conn->query("
    SELECT t.*, l.name AS league_name 
    FROM tournaments t 
    LEFT JOIN league l ON t.id_league = l.id 
    ORDER BY t.id DESC
");

// Simpan ke array supaya bisa dipakai ulang
$tournaments = [];
while($row = $tournamentsQuery->fetch_assoc()) {
    $tournaments[] = $row;
}
?>

<!-- ================= MANAGE ZONE ================= -->
<section class="p-4">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-white">
        <i class="bi bi-geo-alt-fill text-primary me-2"></i> Manage Zone
      </h2>
      <button class="btn-gold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addTournamentModal">
        <i class="bi bi-plus-circle me-1"></i> Add Zone
      </button>
    </div>

    <!-- Table -->
    <div class="card shadow border-0 rounded-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>League</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>

            <tbody>
            <?php if (empty($tournaments)): ?>
              <tr><td colspan="6" class="text-center text-muted">No zones found.</td></tr>
            <?php else: ?>
              <?php foreach($tournaments as $row): ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                  <td class="text-muted"><?= htmlspecialchars($row['league_name'] ?? '-') ?></td>
                  <td><span class="badge bg-info text-dark px-3 py-2 rounded-pill"><?= $row['start_date'] ?></span></td>
                  <td><span class="badge bg-secondary text-dark px-3 py-2 rounded-pill"><?= $row['end_date'] ?: '-' ?></span></td>
                  <td>
                    <?php if($row['status']=='upcoming'): ?>
                      <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Upcoming</span>
                    <?php elseif($row['status']=='completed'): ?>
                      <span class="badge bg-success px-3 py-2 rounded-pill">Completed</span>
                    <?php else: ?>
                      <span class="badge bg-secondary px-3 py-2 rounded-pill"><?= ucfirst($row['status']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-warning me-1 rounded-circle" data-bs-toggle="modal" data-bs-target="#editTournamentModal<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                    <?php if($row['status']=='upcoming'): ?>
                      <button class="btn btn-sm btn-outline-success rounded-circle" data-bs-toggle="modal" data-bs-target="#completeTournamentModal<?= $row['id'] ?>"><i class="bi bi-check2-circle"></i></button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>

          <!-- ===== Render Semua Modal ===== -->
          <?php foreach($tournaments as $row): $id = (int)$row['id']; ?>
            <!-- Edit Modal -->
            <div class="modal fade" id="editTournamentModal<?= $id ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0 rounded-4">
                  <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update Zone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST">
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= $id ?>">
                      <div class="form-floating mb-3">
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
                        <label>Name</label>
                      </div>
                      <div class="form-floating mb-3">
                        <select name="id_league" class="form-select" required>
                          <option value="">-- Select League --</option>
                          <?php foreach($leagues as $league): ?>
                            <option value="<?= $league['id'] ?>" <?= $row['id_league']==$league['id'] ? 'selected' : '' ?>><?= htmlspecialchars($league['name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <label>League</label>
                      </div>
                      <div class="form-floating mb-3">
                        <input type="date" name="start_date" class="form-control" value="<?= $row['start_date'] ?>" required>
                        <label>Start Date</label>
                      </div>
                      <div class="form-floating mb-3">
                        <input type="date" name="end_date" class="form-control" value="<?= $row['end_date'] ?>" required>
                        <label>End Date</label>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" name="edit_tournament" class="btn-gold">Save Changes</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Complete Modal -->
            <div class="modal fade" id="completeTournamentModal<?= $id ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0 rounded-4">
                  <div class="modal-header bg-success text-white rounded-top-4">
                    <h5 class="modal-title"><i class="bi bi-check2-circle me-2"></i>Complete Zone</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST">
                    <div class="modal-body">
                      Are you sure you want to mark <strong class="text-success">"<?= htmlspecialchars($row['name']) ?>"</strong> as completed?
                      <input type="hidden" name="id" value="<?= $id ?>">
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" name="complete_tournament" class="btn btn-success">Complete</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

        </div>
      </div>
    </div>
  </div>
</section>


<section class="p-4">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-white">
        <i class="bi bi-grid-3x3-gap-fill text-warning me-2"></i> Manage Division
      </h2>
      <button class="btn-gold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addDivisionModal">
        <i class="bi bi-plus-circle me-1"></i> Add Division
      </button>
    </div>

    <!-- Table Card -->
    <div class="card shadow border-0 rounded-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-success">
              <tr>
                <th>Division Number</th>
                <th>Name</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
           <tbody>
  <?php 
  $rows = [];
  while($row = $divisions->fetch_assoc()):
      $rows[] = $row;
  ?>
    <tr>
      <td class="fw-semibold text-dark"><?= $row['id'] ?></td>
      <td class="text-muted"><?= htmlspecialchars($row['division_name']) ?></td>
      <td class="text-center">
        <button class="btn btn-sm btn-outline-warning me-1 rounded-circle" data-bs-toggle="modal" data-bs-target="#editDivisionModal<?= $row['id'] ?>" title="Edit">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger rounded-circle" data-bs-toggle="modal" data-bs-target="#deleteDivisionModal<?= $row['id'] ?>" title="Delete">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>
  <?php endwhile; ?>
</tbody>

          </table>
          <?php foreach($rows as $row): $id = (int)$row['id']; ?>

<!-- Edit Division Modal -->
<div class="modal fade" id="editDivisionModal<?= $id ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="margin-top:70px">
    <div class="modal-content shadow-lg rounded-4 border-0">
      <div class="modal-header bg-warning text-dark rounded-top-4">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update Division</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $id ?>">

          <div class="form-floating mb-3">
            <input type="number" name="id_new" class="form-control"
                   value="<?= $id ?>" 
                   placeholder="Division ID" required>
            <label>Division ID</label>
          </div>

          <div class="form-floating mb-3">
            <input type="text" name="division_name" class="form-control"
                   value="<?= htmlspecialchars($row['division_name']) ?>"
                   placeholder="Division Name" required>
            <label>Division Name</label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_division" class="btn-gold">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Division Modal -->
<div class="modal fade" id="deleteDivisionModal<?= $id ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-4 border-0">
      <div class="modal-header bg-danger text-white rounded-top-4">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <p class="mb-0">Are you sure you want to delete division 
            <strong class="text-danger"><?= htmlspecialchars($row['division_name']) ?></strong>?
          </p>
          <input type="hidden" name="id" value="<?= $id ?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="delete_division" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endforeach; ?>

        </div>
      </div>
    </div>
  </div>
</section>



<!-- Add League Modal -->
<div class="modal fade" id="addLeagueModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="margin-top:70px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add League</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" placeholder="Enter league name" required>
          </div>
          <div class="mb-3">
            <label>Description</label>
            <input type="text" name="deskripsi" class="form-control" placeholder="Optional: short description">
          </div>
          <div class="mb-3">
            <label>Year</label>
            <input type="number" name="date" class="form-control" placeholder="e.g., 2025" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_league" class="btn-gold">Add League</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Add Tournament Modal -->
<div class="modal fade" id="addTournamentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="margin-top:100px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Zone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Zone</label>
                        <select name="name" class="form-select" required>
                            <option value="" disabled selected>Select Zone</option>
                            <option value="North Zone">North Zone</option>
                            <option value="South Zone">South Zone</option>
                            <option value="East Zone">East Zone</option>
                            <option value="West Zone">West Zone</option>
                            <option value="Central Zone">Central Zone</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>League</label>
                        <select name="id_league" class="form-control" required>
                            <option value="">-- Select League --</option>
                            <?php foreach($leagues as $league): ?>
                            <option value="<?= $league['id'] ?>">
                            <?= htmlspecialchars($league['name']) ?> (<?= htmlspecialchars($league['date']) ?>)
                        </option>

                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <!-- ✅ Tambahan End Date -->
                    <div class="mb-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_tournament" class="btn-gold">Add Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Division Modal -->
<div class="modal fade" id="addDivisionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="margin-top:70px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Division</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          
          <div class="mb-3">
            <label>Division Rank</label>
            <input type="number" name="id" class="form-control" placeholder="Enter division rank (e.g. 1)" required>
          </div>

          <div class="mb-3">
            <label>Division Name</label>
            <input type="text" name="division_name" class="form-control" placeholder="Enter division name (e.g. Advanced B)" required>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_division" class="btn-gold">Add Division</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Scroll to Top Button -->
<button id="scrollTopBtn" title="Go to top">↑</button>


<script>
  const scrollBtn = document.getElementById("scrollTopBtn");

  // Show/hide button on scroll
  window.onscroll = function() {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
      scrollBtn.style.display = "block";
    } else {
      scrollBtn.style.display = "none";
    }
  };

  // Scroll to top smoothly
  scrollBtn.addEventListener("click", function() {
    window.scrollTo({
      top: 0,
      behavior: "smooth"
    });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
