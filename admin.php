<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

$host = 'localhost';
$dbname = 'webb6_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Простая форма входа вместо HTTP-авторизации
$isAdmin = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;

if (!$isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // ВРЕМЕННО: любой логин и пароль
    if ($login === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged'] = true;
        $isAdmin = true;
    } else {
        $error = "Неверный логин или пароль";
    }
}

if (!$isAdmin):
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Вход для админа</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        form { background: white; padding: 30px; border-radius: 16px; width: 300px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 8px; }
        button { width: 100%; padding: 10px; background: #3498db; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .error { color: red; }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Вход в админ-панель</h2>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        <input type="text" name="login" placeholder="Логин" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>
</body>
</html>
<?php
    exit;
endif;

// Админ авторизован — показываем панель
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель — Задание 6</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #3498db; color: white; }
        .btn { display: inline-block; padding: 5px 10px; margin: 2px; border-radius: 5px; text-decoration: none; font-size: 12px; }
        .btn-edit { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-save { background: #2ecc71; color: white; padding: 10px 20px; }
        .btn-cancel { background: #95a5a6; color: white; padding: 10px 20px; }
        .stats { background: #e8f4fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .stats ul { columns: 3; list-style: none; padding: 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: inline-block; width: 150px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 300px; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
        select[multiple] { height: 100px; }
        .edit-form { background: #f0f2f5; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .logout { float: right; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔐 Админ-панель — Задание 6</h1>
    <p>Вы вошли как <strong>admin</strong> <a href="?logout=1" class="btn btn-delete" style="float:right;">Выйти</a></p>
    
    <?php
    // Обработка выхода
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: admin.php');
        exit;
    }
    
    // Обработка удаления
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
        header('Location: admin.php');
        exit;
    }
    
    // Обработка редактирования
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $fio = trim($_POST['fio']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $birth_date = $_POST['birth_date'];
        $gender = $_POST['gender'];
        $biography = trim($_POST['biography']);
        $contract = isset($_POST['contract']) ? 1 : 0;
        $languages = $_POST['languages'] ?? [];
        
        $pdo->prepare("UPDATE applications SET fio=?, phone=?, email=?, birth_date=?, gender=?, biography=?, contract_agreed=? WHERE id=?")
            ->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract, $id]);
        
        $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
        
        $stmtLang = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang_id) {
            $stmtLang->execute([$id, $lang_id]);
        }
        
        header('Location: admin.php');
        exit;
    }
    
    // Статистика
    $stats = $pdo->query("
        SELECT pl.name, COUNT(al.language_id) as count 
        FROM programming_languages pl
        LEFT JOIN application_languages al ON pl.id = al.language_id
        GROUP BY pl.id
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Все заявки
    $applications = $pdo->query("
        SELECT a.*, u.login as user_login 
        FROM applications a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $languagesList = $pdo->query("SELECT * FROM programming_languages ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    // Данные для редактирования
    $editData = null;
    $editLanguages = [];
    if (isset($_GET['edit'])) {
        $id = (int)$_GET['edit'];
        $editData = $pdo->prepare("SELECT * FROM applications WHERE id = ?")->execute([$id]) ? null : $pdo->prepare("SELECT * FROM applications WHERE id = ?")->fetch(PDO::FETCH_ASSOC);
        $editData = $pdo->query("SELECT * FROM applications WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
        if ($editData) {
            $editLanguages = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = ?")->execute([$id]) ? [] : $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = ?")->fetchAll(PDO::FETCH_COLUMN);
            $editLanguages = $pdo->query("SELECT language_id FROM application_languages WHERE application_id = $id")->fetchAll(PDO::FETCH_COLUMN);
        }
    }
    ?>
    
    <h2>📊 Статистика по языкам</h2>
    <div class="stats">
        <ul>
            <?php foreach ($stats as $stat): ?>
                <li><strong><?php echo htmlspecialchars($stat['name']); ?></strong>: <?php echo $stat['count']; ?> пользователей</li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <?php if ($editData): ?>
        <div class="edit-form">
            <h3>✏️ Редактирование заявки #<?php echo $editData['id']; ?></h3>
            <form method="POST">
                <input type="hidden" name="edit_id" value="<?php echo $editData['id']; ?>">
                <div class="form-group"><label>ФИО:</label><input type="text" name="fio" value="<?php echo htmlspecialchars($editData['fio']); ?>" required></div>
                <div class="form-group"><label>Телефон:</label><input type="text" name="phone" value="<?php echo htmlspecialchars($editData['phone']); ?>" required></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" value="<?php echo htmlspecialchars($editData['email']); ?>" required></div>
                <div class="form-group"><label>Дата рождения:</label><input type="date" name="birth_date" value="<?php echo htmlspecialchars($editData['birth_date']); ?>" required></div>
                <div class="form-group">
                    <label>Пол:</label>
                    <select name="gender">
                        <option value="male" <?php echo $editData['gender'] == 'male' ? 'selected' : ''; ?>>Мужской</option>
                        <option value="female" <?php echo $editData['gender'] == 'female' ? 'selected' : ''; ?>>Женский</option>
                        <option value="other" <?php echo $editData['gender'] == 'other' ? 'selected' : ''; ?>>Другой</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Языки:</label>
                    <select name="languages[]" multiple>
                        <?php foreach ($languagesList as $lang): ?>
                            <option value="<?php echo $lang['id']; ?>" <?php echo in_array($lang['id'], $editLanguages) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lang['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Биография:</label><textarea name="biography" rows="4"><?php echo htmlspecialchars($editData['biography']); ?></textarea></div>
                <div class="form-group"><label>Контракт:</label><input type="checkbox" name="contract" value="1" <?php echo $editData['contract_agreed'] ? 'checked' : ''; ?>></div>
                <button type="submit" class="btn btn-save">Сохранить</button>
                <a href="admin.php" class="btn btn-cancel">Отмена</a>
            </form>
        </div>
    <?php endif; ?>
    
    <h2>📋 Все заявки</h2>
    <table>
        <thead><tr><th>ID</th><th>Пользователь</th><th>ФИО</th><th>Телефон</th><th>Email</th><th>Дата</th><th>Пол</th><th>Языки</th><th>Действия</th></tr></thead>
        <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?php echo $app['id']; ?></td>
                    <td><?php echo htmlspecialchars($app['user_login'] ?? 'Неизвестно'); ?></td>
                    <td><?php echo htmlspecialchars($app['fio']); ?></td>
                    <td><?php echo htmlspecialchars($app['phone']); ?></td>
                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                    <td><?php echo $app['birth_date']; ?></td>
                    <td><?php echo ['male' => 'Мужской', 'female' => 'Женский', 'other' => 'Другой'][$app['gender']] ?? $app['gender']; ?></td>
                    <td>
                        <?php
                            $langs = $pdo->query("SELECT pl.name FROM application_languages al JOIN programming_languages pl ON al.language_id = pl.id WHERE al.application_id = {$app['id']}")->fetchAll(PDO::FETCH_COLUMN);
                            echo implode(', ', array_map('htmlspecialchars', $langs));
                        ?>
                    </td>
                    <td>
                        <a href="admin.php?edit=<?php echo $app['id']; ?>" class="btn btn-edit">Ред.</a>
                        <a href="admin.php?delete=<?php echo $app['id']; ?>" class="btn btn-delete" onclick="return confirm('Удалить?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    慵
</div>
</body>
</html>