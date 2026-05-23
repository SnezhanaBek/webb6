<?php
require_once 'config.php';

session_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: text/html; charset=UTF-8');

$pdo = getPDO();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_login'] = $admin['login'];
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход для администратора</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-form { background: white; padding: 30px; border-radius: 16px; width: 300px; }
        h1 { text-align: center; color: #2c3e50; font-size: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 8px; }
        button { width: 100%; padding: 10px; background: #e74c3c; color: white; border: none; border-radius: 8px; cursor: pointer; }
        button:hover { background: #c0392b; }
        .error { color: red; text-align: center; margin-top: 10px; }
        .link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
<div class="login-form">
    <h1>🔐 Вход в админ-панель</h1>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="login" placeholder="Логин" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти как администратор</button>
    </form>
    <div class="link"><a href="index.php">← Вернуться к форме</a></div>
</div>
</body>
</html>