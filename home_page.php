<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_match') {
    $match_name = trim($_POST['match_name']);
    if (!empty($match_name)) {
        $stmt = $pdo->prepare("INSERT INTO matches (name, status) VALUES (?, 'pending')");
        $stmt->execute([$match_name]);
        header("Location: home_page.php");
        exit;
    }
}

$stmt = $pdo->query("SELECT * FROM matches ORDER BY id DESC");
$matches = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="style/home_pageoo.css">
</head>

<body>
    <nav class="navbar">
        <div class="logo">
            <h2>FISHER MAN</h2>
        </div>

        <ul class="nav-menu">
            <li><a href="home_page.php">Home</a></li>
            <li><a href="race_page.php">Race</a></li>
        </ul>

        <div class="nav-right">
            <button id="logoutBtn" class="logout-btn">Logout</button>
        </div>
    </nav>

    <div class="container">
    
        <div class="col-1">

            <h1>FISHER MAN</h1>
            
        </div>

        <!-- Logout Modal -->
        <div class="modal" id="logoutModal">
            <div class="logout-modal-card">
                <h2>ออกจากระบบ</h2>
                <p>คุณจะออกจากระบบหรือไม่</p>

                <div class="btn-group">
                    <button class="cancel-btn" onclick="closeLogoutModal()">
                        ยกเลิก
                    </button>

                    <button class="confirm-btn" onclick="logout()">
                        ยืนยัน
                    </button>
                </div>
            </div>
        </div>

    <script src="js/script.js"></script>
        <div class="col-2">
            <div class="menu">
                <form id="createMatchForm" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="create_match">
                    <input type="hidden" name="match_name" id="matchNameInput">
                    <button type="button" class="add-btn" onclick="openModal()">+</button>
                </form>
            </div>

            <div class="border">
                <table>
                    <tr>
                        <th>RACE NAME</th>
                        <Th>DATE</Th>
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

    <!-- card input -->
    <div class="modal" id="matchModal">
        <div class="modal-card">
            <h2>สร้างการแข่ง</h2>

            <p>ชี่อการแข่ง</p>
            <input type="text" id="matchName" placeholder="กรอกชื่อการแข่ง">

            <div class="btn-group">
                <button class="cancel-btn" onclick="closeModal()">ยกเลิก</button>
                <button class="create-btn" onclick="createMatch()">ยืนยัน</button>
            </div>
        </div>
    </div>
    
    <script>
    function openModal() {
        document.getElementById("matchModal").style.display = "flex";
    }

    function closeModal() {
        document.getElementById("matchModal").style.display = "none";
    }

    function createMatch() {

        let name = document.getElementById("matchName").value.trim();

        if(name === ""){
            alert("Please enter match name");
            return;
        }

        document.getElementById("matchNameInput").value = name;
        document.getElementById("createMatchForm").submit();
    }

    const logoutBtn = document.getElementById("logoutBtn");
    const logoutModal = document.getElementById("logoutModal");

    logoutBtn.addEventListener("click", function () {
        logoutModal.style.display = "flex";
    });

    function closeLogoutModal() {
        logoutModal.style.display = "none";
    }

    function logout() {
        window.location.href = "logout.php";
    }
    </script>
</body>
</html>