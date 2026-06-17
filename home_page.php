<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Handle creating a new match
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_match') {
    $match_name = trim($_POST['match_name']);
    if (!empty($match_name)) {
        $stmt = $pdo->prepare("INSERT INTO matches (name, status) VALUES (?, 'pending')");
        $stmt->execute([$match_name]);
        // Redirect to avoid form resubmission
        header("Location: home_page.php");
        exit;
    }
}

// Fetch all matches
$stmt = $pdo->query("SELECT * FROM matches ORDER BY id DESC");
$matches = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="style/home_page.css">
</head>

<body>
    <div class="container">
        <div class="col-1">

            <h1>FISHER MAN</h1>
            <a href="logout.php" style="color: #4b5563; text-decoration: none; font-size: 14px; margin-top: 10px; display: block;">Logout</a>
        </div>
        <div class="col-2">
            <div class="menu">
                <!-- Using a hidden form to submit without drastically changing the UI layout -->
                <form id="createMatchForm" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="create_match">
                    <input type="hidden" name="match_name" id="matchNameInput">
                    <button type="button" class="add-btn" onclick="promptCreateMatch()">+</button>
                </form>
            </div>

            <div class="border">
                <table>
                    <tr>
                        <th>RACE NAME</th>
                        <th>STATUS</th>
                    </tr>
                    <?php if (count($matches) > 0): ?>
                        <?php foreach ($matches as $match): ?>
                        <tr>
                            <td><a href="race_page.php?match_id=<?= htmlspecialchars($match['id']) ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($match['name']) ?></a></td>
                            <td><?= htmlspecialchars(ucfirst($match['status'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center;">No matches found</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    function promptCreateMatch() {
        const matchName = prompt("Enter the name of the new match:");
        if (matchName && matchName.trim() !== "") {
            document.getElementById('matchNameInput').value = matchName;
            document.getElementById('createMatchForm').submit();
        }
    }
    </script>
</body>
</html>