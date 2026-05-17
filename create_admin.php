<?php
$host = 'localhost';
$dbname = 'webb6_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Удаляем старого
    $pdo->exec("DELETE FROM admins WHERE login = 'admin'");
    
    // Создаём нового
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO admins (login, password_hash) VALUES (?, ?)");
    $stmt->execute(['admin', $hash]);
    
    echo "✅ Администратор создан!<br>";
    echo "Логин: <strong>admin</strong><br>";
    echo "Пароль: <strong>admin123</strong><br>";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>