<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

/* ==========================
   เลือกการแข่งขันเริ่มต้น
========================== */
if (!isset($_GET['match_id'])) {

    // ดึงการแข่งขันล่าสุด
    $stmt = $pdo->query("SELECT id FROM matches ORDER BY id DESC LIMIT 1");
    $defaultMatch = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($defaultMatch) {
        header("Location: race_page.php?match_id=" . $defaultMatch['id']);
        exit;
    } else {
        die("No matches found.");
    }
}

$match_id = (int)$_GET['match_id'];

/* ==========================
   ดึงข้อมูลการแข่งขัน
========================== */
$stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("Match not found.");
}

/* ==========================
   POST Actions
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'start_race':
            $stmt = $pdo->prepare("
                UPDATE matches
                SET status='live'
                WHERE id=?
            ");
            $stmt->execute([$match_id]);
            break;

        case 'stop_race':
            $stmt = $pdo->prepare("
                UPDATE matches
                SET status='stopped'
                WHERE id=?
            ");
            $stmt->execute([$match_id]);
            break;

        case 'add_category':
            if ($match['status'] !== 'stopped') {
                $stmt = $pdo->prepare("
                    INSERT INTO categories
                    (match_id, name, min_weight, prize_quota)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $match_id,
                    trim($_POST['name']),
                    $_POST['min_weight'],
                    $_POST['prize_quota']
                ]);
            }
            break;

        case 'edit_category':
            if ($match['status'] !== 'stopped') {
                $stmt = $pdo->prepare("
                    UPDATE categories
                    SET name = ?, min_weight = ?, prize_quota = ?
                    WHERE id = ? AND match_id = ?
                ");

                $stmt->execute([
                    trim($_POST['name']),
                    $_POST['min_weight'],
                    $_POST['prize_quota'],
                    $_POST['category_id'],
                    $match_id
                ]);
            }
            break;

        case 'delete_category':
            if ($match['status'] !== 'stopped') {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND match_id = ?");
                $stmt->execute([$_POST['category_id'], $match_id]);
            }
            break;

        case 'add_team':
            if ($match['status'] !== 'stopped') {
                $stmt = $pdo->prepare("
                    INSERT INTO teams
                    (match_id, sequence_number, team_name)
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $match_id,
                    $_POST['sequence_number'],
                    trim($_POST['team_name'])
                ]);
            }
            break;

        case 'edit_team':
            if ($match['status'] !== 'stopped') {
                $stmt = $pdo->prepare("
                    UPDATE teams
                    SET sequence_number = ?, team_name = ?
                    WHERE id = ? AND match_id = ?
                ");

                $stmt->execute([
                    $_POST['sequence_number'],
                    trim($_POST['team_name']),
                    $_POST['team_id'],
                    $match_id
                ]);
            }
            break;

        case 'delete_team':
            if ($match['status'] !== 'stopped') {
                $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ? AND match_id = ?");
                $stmt->execute([$_POST['team_id'], $match_id]);
            }
            break;

        case 'add_catch':
            if ($match['status'] !== 'stopped') {
                $stmt = $pdo->prepare("
                    INSERT INTO catch_logs
                    (match_id, category_id, team_id, weight)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $match_id,
                    $_POST['category_id'],
                    $_POST['team_id'],
                    $_POST['weight']
                ]);
            }
            break;

        case 'edit_catch':
            if ($match['status'] !== 'stopped') {
                $stmt = $pdo->prepare("
                    UPDATE catch_logs
                    SET category_id = ?, team_id = ?, weight = ?
                    WHERE id = ? AND match_id = ?
                ");

                $stmt->execute([
                    $_POST['category_id'],
                    $_POST['team_id'],
                    $_POST['weight'],
                    $_POST['log_id'],
                    $match_id
                ]);
            }
            break;

        case 'delete_catch':
            if ($match['status'] !== 'stopped') {
                $stmt = $pdo->prepare("DELETE FROM catch_logs WHERE id = ? AND match_id = ?");
                $stmt->execute([$_POST['log_id'], $match_id]);
            }
            break;
    }

    header("Location: race_page.php?match_id={$match_id}&tab=" . urlencode($_GET['tab'] ?? 'dashboard'));
    exit;
}

/* ==========================
   Tab
========================== */
$tab = $_GET['tab'] ?? 'dashboard';

/* ==========================
   Categories
========================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM categories
    WHERE match_id = ?
");
$stmt->execute([$match_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
   Teams
========================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM teams
    WHERE match_id = ?
    ORDER BY sequence_number
");
$stmt->execute([$match_id]);
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Page</title>
    <link rel="stylesheet" href="style/race_pageas.css">
    <style>
        /* ---------- Card-style input form ---------- */

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
                <a href="dashboard.php?match_id=<?= htmlspecialchars($match_id) ?>" style="text-decoration: none;"><button type="button">dashboard</button></a>
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
                    <button type="button" class="add-toggle-btn" onclick="toggleCard('overlay-add-category')">
                        <span class="plus-icon">+</span>
                    </button>
                    <div class="modal-overlay" id="overlay-add-category" onclick="closeOnOverlay(event, 'overlay-add-category')">
                    <div class="form-card" id="card-add-category">
                        <button type="button" class="modal-close" onclick="toggleCard('overlay-add-category')">&times;</button>
                        <h4>เพิ่มประเภทการแข่งขัน</h4>
                        <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=categories">
                            <input type="hidden" name="action" value="add_category">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label for="cat_name">ชื่อประเภท</label>
                                    <input type="text" id="cat_name" name="name" placeholder="เช่น ปลานิล" required>
                                </div>
                                <div class="form-field">
                                    <label for="cat_min_weight">น้ำหนักขั้นต่ำ (กก.)</label>
                                    <input type="number" step="0.01" id="cat_min_weight" name="min_weight" placeholder="0.00" required>
                                </div>
                                <div class="form-field">
                                    <label for="cat_prize_quota">จำนวนรางวัล</label>
                                    <input type="number" id="cat_prize_quota" name="prize_quota" placeholder="0" required>
                                </div>
                            </div>
                            <div class="btn-row">
                                <button type="submit">เพิ่มประเภท</button>
                            </div>
                        </form>
                    </div>
                    </div>
                    <?php endif; ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Min Wgt</th>
                            <th>Quota</th>
                            <?php if ($match['status'] !== 'stopped'): ?>
                            <th>Action</th>
                            <?php endif; ?>
                        </tr>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['id']) ?></td>
                            <td><?= htmlspecialchars($cat['name']) ?></td>
                            <td><?= htmlspecialchars($cat['min_weight']) ?></td>
                            <td><?= htmlspecialchars($cat['prize_quota']) ?></td>
                            <?php if ($match['status'] !== 'stopped'): ?>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="edit-btn" onclick="toggleCard('overlay-edit-category-<?= htmlspecialchars($cat['id']) ?>')">แก้ไข</button>
                                    <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=categories" style="display:inline;" onsubmit="return confirm('ลบประเภท &quot;<?= htmlspecialchars(addslashes($cat['name'])) ?>&quot; ใช่หรือไม่? บันทึกการจับปลาในประเภทนี้จะถูกลบด้วย');">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?= htmlspecialchars($cat['id']) ?>">
                                        <button type="submit" class="delete-btn">ลบ</button>
                                    </form>
                                </div>
                            </td>
                            <div class="modal-overlay" id="overlay-edit-category-<?= htmlspecialchars($cat['id']) ?>" onclick="closeOnOverlay(event, 'overlay-edit-category-<?= htmlspecialchars($cat['id']) ?>')">
                            <div class="form-card">
                                <button type="button" class="modal-close" onclick="toggleCard('overlay-edit-category-<?= htmlspecialchars($cat['id']) ?>')">&times;</button>
                                <h4>แก้ไขประเภทการแข่งขัน</h4>
                                <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=categories">
                                    <input type="hidden" name="action" value="edit_category">
                                    <input type="hidden" name="category_id" value="<?= htmlspecialchars($cat['id']) ?>">
                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label>ชื่อประเภท</label>
                                            <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                                        </div>
                                        <div class="form-field">
                                            <label>น้ำหนักขั้นต่ำ (กก.)</label>
                                            <input type="number" step="0.01" name="min_weight" value="<?= htmlspecialchars($cat['min_weight']) ?>" required>
                                        </div>
                                        <div class="form-field">
                                            <label>จำนวนรางวัล</label>
                                            <input type="number" name="prize_quota" value="<?= htmlspecialchars($cat['prize_quota']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="btn-row">
                                        <button type="submit">บันทึกการแก้ไข</button>
                                    </div>
                                </form>
                            </div>
                            </div>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                <?php elseif ($tab === 'teams'): ?>
                    <?php if ($match['status'] !== 'stopped'): ?>
                    <button type="button" class="add-toggle-btn" onclick="toggleCard('overlay-add-team')">
                        <span class="plus-icon">+</span>
                    </button>
                    <div class="modal-overlay" id="overlay-add-team" onclick="closeOnOverlay(event, 'overlay-add-team')">
                    <div class="form-card" id="card-add-team">
                        <button type="button" class="modal-close" onclick="toggleCard('overlay-add-team')">&times;</button>
                        <h4>เพิ่มทีม / นักตกปลา</h4>
                        <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=teams">
                            <input type="hidden" name="action" value="add_team">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label for="team_seq">หมายเลข (No)</label>
                                    <input type="number" id="team_seq" name="sequence_number" placeholder="เช่น 1" required>
                                </div>
                                <div class="form-field">
                                    <label for="team_name">ชื่อทีม/นักตกปลา</label>
                                    <input type="text" id="team_name" name="team_name" placeholder="ชื่อทีม" required>
                                </div>
                            </div>
                            <div class="btn-row">
                                <button type="submit">เพิ่มทีม</button>
                            </div>
                        </form>
                    </div>
                    </div>
                    <?php endif; ?>
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Name</th>
                            <?php if ($match['status'] !== 'stopped'): ?>
                            <th>Action</th>
                            <?php endif; ?>
                        </tr>
                        <?php foreach ($teams as $team): ?>
                        <tr>
                            <td><?= htmlspecialchars($team['sequence_number']) ?></td>
                            <td><?= htmlspecialchars($team['team_name']) ?></td>
                            <?php if ($match['status'] !== 'stopped'): ?>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="edit-btn" onclick="toggleCard('overlay-edit-team-<?= htmlspecialchars($team['id']) ?>')">แก้ไข</button>
                                    <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=teams" style="display:inline;" onsubmit="return confirm('ลบทีม &quot;<?= htmlspecialchars(addslashes($team['team_name'])) ?>&quot; ใช่หรือไม่? บันทึกการจับปลาของทีมนี้จะถูกลบด้วย');">
                                        <input type="hidden" name="action" value="delete_team">
                                        <input type="hidden" name="team_id" value="<?= htmlspecialchars($team['id']) ?>">
                                        <button type="submit" class="delete-btn">ลบ</button>
                                    </form>
                                </div>
                            </td>
                            <div class="modal-overlay" id="overlay-edit-team-<?= htmlspecialchars($team['id']) ?>" onclick="closeOnOverlay(event, 'overlay-edit-team-<?= htmlspecialchars($team['id']) ?>')">
                            <div class="form-card">
                                <button type="button" class="modal-close" onclick="toggleCard('overlay-edit-team-<?= htmlspecialchars($team['id']) ?>')">&times;</button>
                                <h4>แก้ไขทีม / นักตกปลา</h4>
                                <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=teams">
                                    <input type="hidden" name="action" value="edit_team">
                                    <input type="hidden" name="team_id" value="<?= htmlspecialchars($team['id']) ?>">
                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label>หมายเลข (No)</label>
                                            <input type="number" name="sequence_number" value="<?= htmlspecialchars($team['sequence_number']) ?>" required>
                                        </div>
                                        <div class="form-field">
                                            <label>ชื่อทีม/นักตกปลา</label>
                                            <input type="text" name="team_name" value="<?= htmlspecialchars($team['team_name']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="btn-row">
                                        <button type="submit">บันทึกการแก้ไข</button>
                                    </div>
                                </form>
                            </div>
                            </div>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                <?php elseif ($tab === 'logs'): ?>
                    <?php if ($match['status'] !== 'stopped'): ?>
                    <button type="button" class="add-toggle-btn" onclick="toggleCard('overlay-add-log')">
                        <span class="plus-icon">+</span>
                    </button>
                    <div class="modal-overlay" id="overlay-add-log" onclick="closeOnOverlay(event, 'overlay-add-log')">
                    <div class="form-card" id="card-add-log">
                        <button type="button" class="modal-close" onclick="toggleCard('overlay-add-log')">&times;</button>
                        <h4>บันทึกการจับปลา</h4>
                        <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=logs">
                            <input type="hidden" name="action" value="add_catch">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label for="log_team">ทีม</label>
                                    <select id="log_team" name="team_id" required>
                                        <option value="">เลือกทีม</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?= htmlspecialchars($team['id']) ?>"><?= htmlspecialchars($team['sequence_number'] . ' - ' . $team['team_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="log_category">ประเภท</label>
                                    <select id="log_category" name="category_id" required>
                                        <option value="">เลือกประเภท</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="log_weight">น้ำหนัก (กก.)</label>
                                    <input type="number" step="0.01" id="log_weight" name="weight" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="btn-row">
                                <button type="submit">บันทึก</button>
                            </div>
                        </form>
                    </div>
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
                            <?php if ($match['status'] !== 'stopped'): ?>
                            <th>Action</th>
                            <?php endif; ?>
                        </tr>
                        <?php foreach ($logs->fetchAll() as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['team_name']) ?></td>
                            <td><?= htmlspecialchars($log['cat_name']) ?></td>
                            <td><?= htmlspecialchars($log['weight']) ?></td>
                            <td><?= htmlspecialchars(date('H:i:s', strtotime($log['caught_at']))) ?></td>
                            <?php if ($match['status'] !== 'stopped'): ?>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="edit-btn" onclick="toggleCard('overlay-edit-log-<?= htmlspecialchars($log['id']) ?>')">แก้ไข</button>
                                    <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=logs" style="display:inline;" onsubmit="return confirm('ลบบันทึกการจับปลาของทีม &quot;<?= htmlspecialchars(addslashes($log['team_name'])) ?>&quot; ใช่หรือไม่?');">
                                        <input type="hidden" name="action" value="delete_catch">
                                        <input type="hidden" name="log_id" value="<?= htmlspecialchars($log['id']) ?>">
                                        <button type="submit" class="delete-btn">ลบ</button>
                                    </form>
                                </div>
                            </td>
                            <div class="modal-overlay" id="overlay-edit-log-<?= htmlspecialchars($log['id']) ?>" onclick="closeOnOverlay(event, 'overlay-edit-log-<?= htmlspecialchars($log['id']) ?>')">
                            <div class="form-card">
                                <button type="button" class="modal-close" onclick="toggleCard('overlay-edit-log-<?= htmlspecialchars($log['id']) ?>')">&times;</button>
                                <h4>แก้ไขบันทึกการจับปลา</h4>
                                <form method="POST" action="race_page.php?match_id=<?= htmlspecialchars($match_id) ?>&tab=logs">
                                    <input type="hidden" name="action" value="edit_catch">
                                    <input type="hidden" name="log_id" value="<?= htmlspecialchars($log['id']) ?>">
                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label>ทีม</label>
                                            <select name="team_id" required>
                                                <?php foreach ($teams as $team): ?>
                                                    <option value="<?= htmlspecialchars($team['id']) ?>" <?= $team['id'] == $log['team_id'] ? 'selected' : '' ?>><?= htmlspecialchars($team['sequence_number'] . ' - ' . $team['team_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label>ประเภท</label>
                                            <select name="category_id" required>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= htmlspecialchars($cat['id']) ?>" <?= $cat['id'] == $log['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label>น้ำหนัก (กก.)</label>
                                            <input type="number" step="0.01" name="weight" value="<?= htmlspecialchars($log['weight']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="btn-row">
                                        <button type="submit">บันทึกการแก้ไข</button>
                                    </div>
                                </form>
                            </div>
                            </div>
                            <?php endif; ?>
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

    <script>
        function toggleCard(overlayId) {
            const overlay = document.getElementById(overlayId);
            if (!overlay) return;
            const isShowing = overlay.classList.toggle('show');
            if (isShowing) {
                document.body.style.overflow = 'hidden';
                const firstField = overlay.querySelector('input, select');
                if (firstField) firstField.focus();
            } else {
                document.body.style.overflow = '';
            }
        }

        function closeOnOverlay(event, overlayId) {
            // Only close if the click was on the overlay itself, not inside the card
            if (event.target.id === overlayId) {
                toggleCard(overlayId);
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.show').forEach(function (overlay) {
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                });
            }
        });
    </script>
</body>
</html>