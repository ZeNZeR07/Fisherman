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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_match') {
    $edit_id = $_POST['match_id'] ?? 0;
    $match_name = trim($_POST['match_name'] ?? '');
    if ($edit_id && $match_name !== '') {
        $stmt = $pdo->prepare("UPDATE matches SET name = ? WHERE id = ?");
        $stmt->execute([$match_name, $edit_id]);
    }
    header("Location: home_page.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_match') {
    $delete_id = $_POST['match_id'] ?? 0;
    if ($delete_id) {
        $stmt = $pdo->prepare("DELETE FROM matches WHERE id = ?");
        $stmt->execute([$delete_id]);
    }
    header("Location: home_page.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM matches ORDER BY id DESC");
$matches = $stmt->fetchAll();

$status_labels = [
    'pending' => 'รอลงเบ็ด',
    'live'    => 'เปิดบ่อแล้ว',
    'stopped' => 'เก็บเบ็ดแล้ว',
];
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
                <table style="width: 100%; table-layout: fixed; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: center; padding: 12px 15px;">RACE NAME</th>
                        <th style="text-align: center; padding: 12px 15px;">DATE</th>
                        <th style="text-align: center; padding: 12px 15px;">STATUS</th>
                        <th style="text-align: center; padding: 12px 15px;">ACTION</th>
                    </tr>
                    <?php if (count($matches) > 0): ?>
                        <?php foreach ($matches as $match): ?>
                        <tr>
                            <td style="text-align: center; padding: 12px 15px;">
                                <a href="race_page.php?match_id=<?= htmlspecialchars($match['id']) ?>" style="text-decoration: none; color: inherit;">
                                    <?= htmlspecialchars($match['name']) ?>
                                </a>
                            </td>
                            <td style="text-align: center; padding: 12px 15px;">
                                <?= htmlspecialchars(date('d/m/Y', strtotime($match['created_at']))) ?>
                            </td>
                            <td style="text-align: center; padding: 12px 15px;">
                                <?= htmlspecialchars($status_labels[$match['status']] ?? ucfirst($match['status'])) ?>
                            </td>
                            <td style="text-align: center; padding: 12px 15px;">
                                <div class="row-actions">
                                    <button type="button" class="edit-btn" data-id="<?= htmlspecialchars($match['id']) ?>" data-name="<?= htmlspecialchars($match['name'], ENT_QUOTES) ?>" onclick="openEditMatchModal(this)">แก้ไข</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('ลบการแข่งขัน &quot;<?= htmlspecialchars(addslashes($match['name'])) ?>&quot; ใช่หรือไม่? ข้อมูลทีม/ชนิดปลา/บันทึกน้ำหนักทั้งหมดจะถูกลบด้วย');">
                                        <input type="hidden" name="action" value="delete_match">
                                        <input type="hidden" name="match_id" value="<?= htmlspecialchars($match['id']) ?>">
                                        <button type="submit" class="confirm-btn">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #6b7280;">No matches found</td>
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

    <!-- edit match modal -->
    <div class="modal" id="editMatchModal">
        <div class="modal-card">
            <h2>แก้ไขการแข่ง</h2>

            <p>ชื่อการแข่ง</p>
            <input type="text" id="editMatchName" placeholder="กรอกชื่อการแข่ง">

            <div class="btn-group">
                <button class="cancel-btn" onclick="closeEditMatchModal()">ยกเลิก</button>
                <button class="create-btn" onclick="saveEditMatch()">บันทึก</button>
            </div>
        </div>
    </div>

    <form id="editMatchForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="edit_match">
        <input type="hidden" name="match_id" id="editMatchIdInput">
        <input type="hidden" name="match_name" id="editMatchNameInput">
    </form>

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

    let editMatchId = null;

    function openEditMatchModal(btn) {
        editMatchId = btn.dataset.id;
        document.getElementById("editMatchName").value = btn.dataset.name;
        document.getElementById("editMatchModal").style.display = "flex";
    }

    function closeEditMatchModal() {
        document.getElementById("editMatchModal").style.display = "none";
    }

    function saveEditMatch() {
        let name = document.getElementById("editMatchName").value.trim();

        if (name === "") {
            alert("Please enter match name");
            return;
        }

        document.getElementById("editMatchIdInput").value = editMatchId;
        document.getElementById("editMatchNameInput").value = name;
        document.getElementById("editMatchForm").submit();
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