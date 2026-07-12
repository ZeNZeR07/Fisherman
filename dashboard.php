<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (!isset($_GET['match_id'])) {

    $stmt = $pdo->query("SELECT id FROM matches ORDER BY id DESC LIMIT 1");
    $defaultMatch = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($defaultMatch) {
        header("Location: dashboard.php?match_id=" . $defaultMatch['id']);
        exit;
    } else {
        die("No matches found.");
    }
}

$match_id = (int)$_GET['match_id'];

$stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$match) {
    die("Match not found.");
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE match_id = ?");
$stmt->execute([$match_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$rankingsByCategory = [];

// Prepare the ranking query ONCE outside the loop, then re-execute it
// for each category. Closing the cursor after every fetchAll() prevents
// "previous statement still active" issues that stop later iterations
// from returning data (a common cause of "first table works, next ones don't").
// Ranking rule: each team's best catch is the one closest to the category's
// target weight (min_weight); rank 1 = smallest difference, and the list is
// capped at the category's prize_quota.
$rankStmt = $pdo->prepare("
    SELECT sequence_number, team_name, weight AS best_weight, caught_at AS first_caught, diff
    FROM (
        SELECT t.sequence_number, t.team_name, t.id AS team_id, cl.weight, cl.caught_at,
               ABS(cl.weight - ?) AS diff,
               ROW_NUMBER() OVER (PARTITION BY t.id ORDER BY ABS(cl.weight - ?) ASC, cl.caught_at ASC) AS rn
        FROM catch_logs cl
        JOIN teams t ON cl.team_id = t.id
        WHERE cl.match_id = ? AND cl.category_id = ?
    ) best_catches
    WHERE rn = 1
    ORDER BY diff ASC, first_caught ASC
    LIMIT ?
");

foreach ($categories as $cat) {
    $rankStmt->bindValue(1, $cat['min_weight'], PDO::PARAM_STR);
    $rankStmt->bindValue(2, $cat['min_weight'], PDO::PARAM_STR);
    $rankStmt->bindValue(3, $match_id, PDO::PARAM_INT);
    $rankStmt->bindValue(4, $cat['id'], PDO::PARAM_INT);
    $rankStmt->bindValue(5, (int)$cat['prize_quota'], PDO::PARAM_INT);
    $rankStmt->execute();
    $rankingsByCategory[$cat['id']] = $rankStmt->fetchAll(PDO::FETCH_ASSOC);
    $rankStmt->closeCursor(); // release the result set before the next iteration reuses the statement
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/dashboardedS.css">
</head>
<body>
    
    <nav class="navbar">
        <div class="logo">
            <h2>FISHER MAN</h2>
        </div>

        <ul class="nav-menu">
            <li><a href="home_page.php">Home</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <a href="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>" style="text-decoration:none;"><button type="button" class="back-btn">← กลับ</button></a>
                <h2><?= htmlspecialchars($match['name']) ?></h2>
                <div class="spacer"></div>
            </div>

            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $cat): ?>
                <h3 style="margin: 20px 0 20px;"><?= htmlspecialchars($cat['name']) ?> -  <?= htmlspecialchars($cat['min_weight']) ?> กก.</h3>
                <div class="table-scroll">
                <table>
                    <tr>
                        <th>อันดับ</th>
                        <th>ทีม</th>
                        <th>น้ำหนัก</th>
                        <th>ห่างจากเป้าหมาย</th>
                        <th>เวลา</th>
                    </tr>
                        <?php if (count($rankingsByCategory[$cat['id']]) > 0): ?>
                            <?php $rank = 1; foreach ($rankingsByCategory[$cat['id']] as $row): ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= htmlspecialchars($row['team_name']) ?></td>
                                <td><?= htmlspecialchars($row['best_weight']) ?></td>
                                <td><?= htmlspecialchars(number_format((float)$row['diff'], 2)) ?></td>
                                <td><?= htmlspecialchars(date('H:i:s', strtotime($row['first_caught']))) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No catches yet</td>
                            </tr>
                        <?php endif; ?>
                </table>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; margin-top: 20px;">No categories found</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>