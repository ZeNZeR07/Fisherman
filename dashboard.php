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

if (!$match) {
    die("Match not found.");
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE match_id = ?");
$stmt->execute([$match_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rankingsByCategory = [];
foreach ($categories as $cat) {
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
    $stmt->bindValue(1, $match_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $cat['id'], PDO::PARAM_INT);
    $stmt->bindValue(3, $cat['min_weight'], PDO::PARAM_STR);
    $stmt->bindValue(4, 5, PDO::PARAM_INT);
    $stmt->execute();
    $rankingsByCategory[$cat['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style/dashboard.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <a href="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>" style="text-decoration:none;"><button type="button" class="back-btn">&larr; กลับ</button></a>
                <h2><?= htmlspecialchars($match['name']) ?></h2>
                <div class="spacer"></div>
            </div>

            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $cat): ?>
                <h3 style="margin: 20px 0 10px;"><?= htmlspecialchars($cat['name']) ?> (Top 5)</h3>
                <table>
                    <tr>
                        <th>อันดับ</th>
                        <th>ทีม</th>
                        <th>น้ำหนัก</th>
                        <th>เวลา</th>
                    </tr>
                    <?php if (count($rankingsByCategory[$cat['id']]) > 0): ?>
                        <?php $rank = 1; foreach ($rankingsByCategory[$cat['id']] as $row): ?>
                        <tr>
                            <td><?= $rank++ ?></td>
                            <td><?= htmlspecialchars($row['team_name']) ?></td>
                            <td><?= htmlspecialchars($row['max_weight']) ?></td>
                            <td><?= htmlspecialchars(date('H:i:s', strtotime($row['first_caught']))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No catches yet</td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; margin-top: 20px;">No categories found</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
