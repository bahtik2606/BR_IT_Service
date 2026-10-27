<?php
session_start();

// Если уже авторизован — переходим в админ-панель
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: index.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Проверка логина и пароля
    if ($username === 'admin' && $password === '12345') {
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'Baxtiyor Rustamov';
        header("location: index.php");
        exit;
    } else {
        $message = "<div class='alert alert-danger py-2 fw-bold text-center'>Login yoki parol xato!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Tizimga Kirish - BR IT SERVICE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
        }
        .system-badge {
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 50px;
            display: inline-block;
            border: 1px solid #bae6fd;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="text-center mb-4">
        <i class="fa-solid fa-lock fa-3x text-primary mb-3"></i>
        <h3 class="fw-bold text-dark mb-2">Tizimga Kirish</h3>
        <div>
            <span class="system-badge shadow-sm">
                <i class="fa-solid fa-gear me-1"></i> Mijozlarni Boshqarish Paneli
            </span>
        </div>
    </div>

    <?= $message ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold">Foydalanuvchi nomi</label>
            <input type="text" name="username" class="form-control fw-semibold" required placeholder="login">
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Parol</label>
            <input type="password" name="password" class="form-control fw-semibold" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">Kirish</button>
    </form>
</div>

</body>
</html>