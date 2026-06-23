<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

$match_id = $_GET['match_id'] ?? 0;
if (!$match_id) {
    die("Match ID not provided.");
}

$stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();
if (!$match) {
    die("Match not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'start_race') {
        $pdo->prepare("UPDATE matches SET status = 'live' WHERE id = ?")->execute([$match_id]);
    } elseif ($action === 'stop_race') {
        $pdo->prepare("UPDATE matches SET status = 'stopped' WHERE id = ?")->execute([$match_id]);
    } elseif ($action === 'add_category' && $match['status'] !== 'stopped') {
        $name = $_POST['name'];
        $min_weight = $_POST['min_weight'];
        $prize_quota = $_POST['prize_quota'];
        $pdo->prepare("INSERT INTO categories (match_id, name, min_weight, prize_quota) VALUES (?, ?, ?, ?)")
            ->execute([$match_id, $name, $min_weight, $prize_quota]);
    } elseif ($action === 'add_team' && $match['status'] !== 'stopped') {
        $team_name = $_POST['team_name'];
        $seq = $_POST['sequence_number'];
        $pdo->prepare("INSERT INTO teams (match_id, sequence_number, team_name) VALUES (?, ?, ?)")
            ->execute([$match_id, $seq, $team_name]);
    } elseif ($action === 'add_catch' && $match['status'] !== 'stopped') {
        $cat_id = $_POST['category_id'];
        $team_id = $_POST['team_id'];
        $weight = $_POST['weight'];
        $pdo->prepare("INSERT INTO catch_logs (match_id, category_id, team_id, weight) VALUES (?, ?, ?, ?)")
            ->execute([$match_id, $cat_id, $team_id, $weight]);
    }
    header("Location: race_page.php?match_id=$match_id&tab=" . urlencode($_GET['tab'] ?? 'dashboard'));
    exit;
}

$tab = $_GET['tab'] ?? 'dashboard';

$categories = $pdo->prepare("SELECT * FROM categories WHERE match_id = ?");
$categories->execute([$match_id]);
$categories = $categories->fetchAll();

$teams = $pdo->prepare("SELECT * FROM teams WHERE match_id = ? ORDER BY sequence_number");
$teams->execute([$match_id]);
$teams = $teams->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Page</title>
    <link rel="stylesheet" href="style/race_page.css">
    <style>
        .form-container { margin-bottom: 20px; padding: 10px; background: #f9f9f9; border-radius: 5px;}
        .form-container input, .form-container select { margin: 5px 0; padding: 5px; }
        .form-container button { padding: 5px 10px; cursor: pointer; }
        .nav-link { text-decoration: none; color: inherit; display: inline-block; padding: 5px 10px; }
        .active-tab { font-weight: bold; background-color: #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="col-1">
            <div class="text-box">
                <h1><?= htmlspecialchars($match['name']) ?></h1>
                <h3>Status: <?= htmlspecialchars(ucfirst($match['status'])) ?></h3>
            </div>

            <div class="btu-box">
                <a href="home_page.php" style="text-decoration: none;"><button type="button">Home</button></a>
                <form method="GET" action="race_page.php" style="display:inline;">
                    <input type="hidden" name="match_id" value="<?= htmlspecialchars($match_id) ?>">
                    <input type="hidden" name="tab" value="dashboard">
                    <button type="submit" class="<?= $tab === 'dashboard' ? 'active-tab' : '' ?>">dashboard</button>
                </form>
                
                <?php if ($match['status'] === 'pending'): ?>
                <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=<?= htmlspecialchars($tab) ?>" style="display:inline;">
                    <input type="hidden" name="action" value="start_race">
                    <button type="submit">start race</button>
                </form>
                <?php elseif ($match['status'] === 'live'): ?>
                <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=<?= htmlspecialchars($tab) ?>" style="display:inline;">
                    <input type="hidden" name="action" value="stop_race">
                    <button type="submit">stop match</button>
                </form>
                <?php else: ?>
                <button type="button" disabled>stopped</button>
                <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=<?= htmlspecialchars($tab) ?>" style="display:inline;">
                    <input type="hidden" name="action" value="start_race">
                    <button type="submit">re-start match</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-2">
            <div class="menu">
                <a href="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=categories" class="nav-link <?= $tab === 'categories' ? 'active-tab' : '' ?>"><button type="button" style="pointer-events: none;">การแข่ง</button></a>
                <a href="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=teams" class="nav-link <?= $tab === 'teams' ? 'active-tab' : '' ?>"><button type="button" style="pointer-events: none;">ทีม</button></a>
                <a href="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=logs" class="nav-link <?= $tab === 'logs' ? 'active-tab' : '' ?>"><button type="button" style="pointer-events: none;">บันทึก</button></a>
            </div>

            <div class="show-detail">
                <?php if ($tab === 'categories'): ?>
                    <?php if ($match['status'] !== 'stopped'): ?>
                    <div class="form-container">
                        <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=categories">
                            <input type="hidden" name="action" value="add_category">
                            <input type="text" name="name" placeholder="Category Name (e.g. ปลานิล)" required>
                            <input type="number" step="0.01" name="min_weight" placeholder="Min Weight" required>
                            <input type="number" name="prize_quota" placeholder="Prize Quota" required>
                            <button type="submit">Add Category</button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Min Wgt</th>
                            <th>Quota</th>
                        </tr>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['id']) ?></td>
                            <td><?= htmlspecialchars($cat['name']) ?></td>
                            <td><?= htmlspecialchars($cat['min_weight']) ?></td>
                            <td><?= htmlspecialchars($cat['prize_quota']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                <?php elseif ($tab === 'teams'): ?>
                    <?php if ($match['status'] !== 'stopped'): ?>
                    <div class="form-container">
                        <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=teams">
                            <input type="hidden" name="action" value="add_team">
                            <input type="number" name="sequence_number" placeholder="No (Seq)" required>
                            <input type="text" name="team_name" placeholder="Team/Angler Name" required>
                            <button type="submit">Add Team</button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Name</th>
                        </tr>
                        <?php foreach ($teams as $team): ?>
                        <tr>
                            <td><?= htmlspecialchars($team['sequence_number']) ?></td>
                            <td><?= htmlspecialchars($team['team_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                <?php elseif ($tab === 'logs'): ?>
                    <?php if ($match['status'] !== 'stopped'): ?>
                    <div class="form-container">
                        <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=logs">
                            <input type="hidden" name="action" value="add_catch">
                            <select name="team_id" required>
                                <option value="">Select Team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= htmlspecialchars($team['id']) ?>"><?= htmlspecialchars($team['sequence_number'] . ' - ' . $team['team_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" step="0.01" name="weight" placeholder="Weight (kg)" required>
                            <button type="submit">Add Log</button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <?php
                    $logs = $pdo->prepare("SELECT cl.*, t.team_name, c.name as cat_name FROM catch_logs cl 
                                           JOIN teams t ON cl.team_id = t.id 
                                           JOIN categories c ON cl.category_id = c.id 
                                           WHERE cl.match_id = ? ORDER BY cl.caught_at DESC");
                    $logs->execute([$match_id]);
                    ?>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>Category</th>
                            <th>Weight</th>
                            <th>Time</th>
                        </tr>
                        <?php foreach ($logs->fetchAll() as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['team_name']) ?></td>
                            <td><?= htmlspecialchars($log['cat_name']) ?></td>
                            <td><?= htmlspecialchars($log['weight']) ?></td>
                            <td><?= htmlspecialchars(date('H:i:s', strtotime($log['caught_at']))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                <?php elseif ($tab === 'dashboard'): ?>
                    <!-- Live Dashboard: Calculate rankings per category -->
                    <?php foreach ($categories as $cat): ?>
                        <h3 style="margin-top: 15px;"><?= htmlspecialchars($cat['name']) ?> Leaderboard (Top <?= htmlspecialchars($cat['prize_quota']) ?>)</h3>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT t.sequence_number, t.team_name, MAX(cl.weight) as max_weight, MIN(cl.caught_at) as first_caught
                            FROM catch_logs cl
                            JOIN teams t ON cl.team_id = t.id
                            WHERE cl.match_id = ? 
                              AND cl.category_id = ? 
                              AND cl.weight >= ?
                            GROUP BY t.id
                            ORDER BY max_weight DESC, first_caught ASC
                            LIMIT ?
                        ");
                        // We must cast prize_quota to int for LIMIT clause with emulate_prepares=false
                        $stmt->bindValue(1, $match_id, PDO::PARAM_INT);
                        $stmt->bindValue(2, $cat['id'], PDO::PARAM_INT);
                        $stmt->bindValue(3, $cat['min_weight'], PDO::PARAM_STR);
                        $stmt->bindValue(4, (int)$cat['prize_quota'], PDO::PARAM_INT);
                        $stmt->execute();
                        $rankings = $stmt->fetchAll();
                        ?>
                        <table>
                            <tr>
                                <th>Rank</th>
                                <th>No</th>
                                <th>Name</th>
                                <th>Weight (kg)</th>
                            </tr>
                            <?php $rank = 1; foreach ($rankings as $row): ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= htmlspecialchars($row['sequence_number']) ?></td>
                                <td><?= htmlspecialchars($row['team_name']) ?></td>
                                <td><?= htmlspecialchars($row['max_weight']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>