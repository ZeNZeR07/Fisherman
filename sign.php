<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: home_page.php");
        exit;
    } else {
        $error = "Invalid credentials. Use admin/admin.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Page</title>

<style>
*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", sans-serif;
}

body{
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #f4f6f8;
}

.container{
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.card{
    width: 380px;
    background: #ffffff;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.card h3{
    text-align: center;
    color: #111827;
    font-size: 28px;
    margin-bottom: 10px;
    letter-spacing: 1px;
}

.card h4{
    text-align: center;
    color: #111827;
    font-size: 17px;
    margin-bottom: 35px;
    letter-spacing: 8px;
}

.box{
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.box p{
    color: #4b5563;
    font-size: 14px;
    font-weight: 500;
}

.box input{
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-size: 15px;
    outline: none;
    transition: all 0.3s ease;
    background: #fafafa;
}

.box input:focus{
    border-color: #1e3a8a;
    box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.12);
    background: #ffffff;
}

.box button{
    margin-top: 15px;
    padding: 14px;
    border: none;
    border-radius: 12px;
    background: #111827;
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.box button:hover{
    background: #1e3a8a;
    transform: translateY(-2px);
}

.box button:active{
    transform: translateY(0);
}

.error {
    color: red;
    text-align: center;
    margin-bottom: 10px;
}
</style>
</head>

<body>
    <div class="container">
        <div class="card">
            <h3>LOGIN 🎣</h3>
            <h4>TO FISHER MAN</h4>
            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="sign.php">
                <div class="box">
                    <p>Username</p>
                    <input type="text" name="username" id="username" placeholder="Enter your username" required>

                    <p>Password</p>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>

                    <button type="submit">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>