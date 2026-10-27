<?php
require_once 'includes/db.php';
@include_once 'includes/telegram.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. TILLAR TIZIMI
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'uz');
$_SESSION['lang'] = $lang;

$translations = [
    'uz' => [
        'services' => 'Xizmatlar & Prays',
        'order' => 'Buyurtma berish',
        'portfolio' => 'Direktor Portfoliosi',
        'contact' => 'Kontaktlar',
        'comments' => 'Sharhlar',
        'search_ph' => 'Qidiruv...',
        'hero_title' => 'IT XIZMATLAR (Ishonchli va Tezkor)',
        'hero_sub' => 'Windows operatsion tizimi, drayverlar, masofaviy yordam va dasturiy ta\'minot bo\'yicha tezkor xizmat.',
        'login' => 'Kirish',
        'register' => 'Ro\'yxatdan o\'tish',
        'logout' => 'Chiqish'
    ],
    'kr' => [
        'services' => 'Хизматлар & Прайс',
        'order' => 'Буюртма бериш',
        'portfolio' => 'Директор Портфолиоси',
        'contact' => 'Контактлар',
        'comments' => 'Шарҳлар',
        'search_ph' => 'Қидирув...',
        'hero_title' => 'Касбий ва Ишончли IT Хизматлар',
        'hero_sub' => 'Windows операцион тизими, драйверлар, масофавий ёрдам ва дастурий таъминот бўйича тезкор хизмат.',
        'login' => 'Кириш',
        'register' => 'Рўйхатдан ўтиш',
        'logout' => 'Чиқиш'
    ],
    'ru' => [
        'services' => 'Услуги и Прайс',
        'order' => 'Заказать',
        'portfolio' => 'Портфолио',
        'contact' => 'Контакты',
        'comments' => 'Отзывы',
        'search_ph' => 'Поиск...',
        'hero_title' => 'Профессиональные IT Услуги',
        'hero_sub' => 'Быстрая настройка Windows, драйверов, программ и удаленная поддержка.',
        'login' => 'Войти',
        'register' => 'Регистрация',
        'logout' => 'Выйти'
    ],
    'en' => [
        'services' => 'Services & Pricing',
        'order' => 'Order Now',
        'portfolio' => 'Portfolio',
        'contact' => 'Contacts',
        'comments' => 'Reviews',
        'search_ph' => 'Search...',
        'hero_title' => 'Professional IT Services',
        'hero_sub' => 'Fast setup of Windows, drivers, software, and remote support.',
        'login' => 'Login',
        'register' => 'Register',
        'logout' => 'Logout'
    ]
];

$t = $translations[$lang];

// 2. STATISTIKA VA BOTGA TASHRIF XABARI
try {
    $pdo->query("UPDATE site_stats SET page_views = page_views + 1 WHERE id = 1");
    $stmt = $pdo->query("SELECT page_views FROM site_stats WHERE id = 1");
    $stats = $stmt->fetch();
    $page_views = $stats ? $stats['page_views'] : 1;

    if (!isset($_SESSION['visited'])) {
        $_SESSION['visited'] = true;
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Mehmon (Anonim)';
        $time = date('H:i');
        $date = date('d/m/Y');
        
        $tg_msg = "🌐 <b>YANGI TASHRIF!</b>\n\n";
        $tg_msg .= "👤 <b>Foydalanuvchi:</b> {$user_name}\n";
        $tg_msg .= "🖥 <b>IP:</b> {$user_ip}\n";
        $tg_msg .= "📊 <b>Jami tashriflar:</b> {$page_views}\n";
        $tg_msg .= "📅 <b>Sana:</b> {$date} - {$time}";
        
        if (function_exists('sendTelegramNotification')) {
            sendTelegramNotification($tg_msg);
        }
    }
} catch (Exception $e) {
    $page_views = 1;
}

// 3. BUYURTMA BERISH (BOT + EMAIL: bahtik2606@gmail.com)
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'order') {
    $name = trim($_POST['client_name']);
    $phone = trim($_POST['phone']);
    $service = trim($_POST['service_type']);
    $desc = trim($_POST['description']);

    if (!empty($name) && !empty($phone) && !empty($service)) {
        $stmt = $pdo->prepare("INSERT INTO orders (client_name, phone, service_type, description, status) VALUES (?, ?, ?, ?, 'Yangi')");
        if ($stmt->execute([$name, $phone, $service, $desc])) {
            $msg = "<div class='alert alert-success fw-bold text-center mb-4 shadow-sm'>✅ Buyurtmangiz qabul qilindi va mutaxassisga yuborildi!</div>";
            
            // Telegram Bot Xabari
            $tg_order = "🚨 <b>YANGI ONLAYN BUYURTMA!</b>\n\n";
            $tg_order .= "👤 <b>Mijoz F.I.O:</b> {$name}\n";
            $tg_order .= "📞 <b>Tel:</b> {$phone}\n";
            $tg_order .= "🔧 <b>Xizmat:</b> {$service}\n";
            $tg_order .= "📝 <b>Izoh:</b> {$desc}\n";
            $tg_order .= "⏰ <b>Vaqt:</b> " . date('d/m/Y H:i');
            
            if (function_exists('sendTelegramNotification')) {
                sendTelegramNotification($tg_order);
            }

            // Gmail Pochtasiga Yuborish
            $to_email = "bahtik2606@gmail.com";
            $subject = "Yangi Buyurtma - BR IT SERVICE (#" . rand(100,999) . ")";
            $email_content = "Yangi Buyurtma Tafsilotlari:\n\nMijoz Ismi: {$name}\nTelefon: {$phone}\nXizmat Tur: {$service}\nIzoh: {$desc}\nSana: " . date('d/m/Y H:i');
            $headers = "From: no-reply@brit-service.uz\r\nReply-To: {$to_email}\r\nX-Mailer: PHP/" . phpversion();

            @mail($to_email, $subject, $email_content, $headers);
        } else {
            $msg = "<div class='alert alert-danger fw-bold text-center mb-4 shadow-sm'>❌ Xatolik yuz berdi. Qayta urinib ko'ring.</div>";
        }
    }
}

// 4. SHARHLAR TIZIMI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    $c_name = trim($_POST['comment_name']);
    $c_text = trim($_POST['comment_text']);
    if (!empty($c_name) && !empty($c_text)) {
        $stmt = $pdo->prepare("INSERT INTO comments (name, comment, rating) VALUES (?, ?, 5)");
        $stmt->execute([$c_name, $c_text]);
        $msg = "<div class='alert alert-info fw-bold text-center mb-4 shadow-sm'>✨ Fikringiz saqlandi!</div>";
    }
}

try {
    $comments_stmt = $pdo->query("SELECT * FROM comments ORDER BY id DESC");
    $comments_list = $comments_stmt->fetchAll();
} catch (Exception $e) {
    $comments_list = [];
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BR IT SERVICE - Professional IT Xizmatlar Markazi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #0f172a;
            --text-color: #f8fafc;
            --card-bg: #1e293b;
            --primary-color: #38bdf8;
            --border-color: #334155;
        }
        body.light-theme {
            --bg-color: #f8fafc;
            --text-color: #0f172a;
            --card-bg: #ffffff;
            --primary-color: #0284c7;
            --border-color: #e2e8f0;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Roboto, sans-serif;
            transition: all 0.3s ease;
        }
        .card {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .top-bar {
            background: #090d16;
            color: #cbd5e1;
            font-size: 14px;
        }
        
        /* HERO BANNER */
        .hero-section {
            background: linear-gradient(rgba(15, 23, 42, 0.65), rgba(15, 23, 42, 0.8)), url('images/1.png') center/cover no-repeat;
            color: white;
            padding: 80px 0 60px 0;
            border-radius: 0 0 40px 40px;
        }
        .gold-title {
            color: #ffd700;
            text-shadow: 0 0 25px rgba(255, 215, 0, 0.9);
            font-weight: 900;
        }

        /* PREMIUM DIREKTOR PORTFOLIOSI */
        .portfolio-premium-card {
            background: linear-gradient(rgba(10, 15, 30, 0.45), rgba(15, 23, 42, 0.65)), url('images/photo0.jpg') center/cover no-repeat;
            border: 2px solid #0284c7;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(2, 132, 199, 0.35);
            color: #ffffff;
        }
        .portfolio-logo-hover {
            max-width: 340px;
            width: 100%;
            border-radius: 50%;
            box-shadow: 0 0 30px rgba(56, 189, 248, 0.8);
            transition: transform 0.5s ease, box-shadow 0.5s ease;
        }
        .portfolio-logo-hover:hover {
            transform: scale(1.15) rotate(2deg);
            box-shadow: 0 0 50px rgba(56, 189, 248, 1);
        }

        /* RUSTAMOV BAXTIYOR CAPSLOCK VA JIRNIY TEXT */
        .director-name-bold {
            font-weight: 900 !important;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #ffd700;
            text-shadow: 3px 3px 10px rgba(0, 0, 0, 0.9), 0 0 15px rgba(255, 215, 0, 0.6);
        }

        /* MENYU ANIMATSIYASI VA RANGDOR IKONKALAR */
        .nav-link-custom {
            color: var(--text-color) !important;
            font-weight: 700;
            padding: 8px 14px !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nav-link-custom i {
            transition: transform 0.3s ease, color 0.3s ease;
        }
        .nav-link-custom:hover {
            background-color: var(--primary-color);
            color: #ffffff !important;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.3);
        }
        .nav-link-custom:hover i {
            transform: scale(1.3) rotate(5deg);
        }

        .scroll-btn {
            position: fixed;
            right: 20px;
            z-index: 999;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #btnUp { bottom: 80px; }
        #btnDown { bottom: 25px; }

        .search-box-compact {
            max-width: 200px;
        }
        .bg-text-overlay {
            background: rgba(0, 0, 0, 0.6);
            padding: 22px;
            border-radius: 18px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>

<!-- TOP BAR -->
<div class="top-bar py-2">
    <div class="container d-flex flex-wrap justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div><i class="fa-regular fa-clock me-1 text-info"></i> <span id="liveDateTime" class="fw-bold">--:--:--</span></div>
            <div class="small text-muted"><i class="fa-solid fa-user me-1 text-success"></i> <?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Mehmon' ?></div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Rejimlar -->
            <button class="btn btn-sm btn-outline-light" onclick="setTheme('light')" title="Kunduzgi rejim"><i class="fa-solid fa-sun text-warning"></i></button>
            <button class="btn btn-sm btn-outline-light" onclick="setTheme('dark')" title="Tungi rejim"><i class="fa-solid fa-moon text-info"></i></button>

            <!-- Tillar -->
            <div class="btn-group btn-group-sm ms-2 me-2">
                <a href="?lang=uz" class="btn btn-outline-light <?= $lang==='uz'?'active':'' ?>">O'zbek</a>
                <a href="?lang=kr" class="btn btn-outline-light <?= $lang==='kr'?'active':'' ?>">Ўзбек</a>
                <a href="?lang=ru" class="btn btn-outline-light <?= $lang==='ru'?'active':'' ?>">RU</a>
                <a href="?lang=en" class="btn btn-outline-light <?= $lang==='en'?'active':'' ?>">EN</a>
            </div>

            <!-- Kirish / Chiqish -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="btn btn-sm btn-outline-danger fw-bold"><i class="fa-solid fa-right-from-bracket me-1"></i><?= $t['logout'] ?></a>
            <?php else: ?>
                <a href="login.php" class="btn btn-sm btn-outline-info fw-bold"><i class="fa-solid fa-right-to-bracket me-1"></i><?= $t['login'] ?></a>
                <a href="register.php" class="btn btn-sm btn-success fw-bold"><i class="fa-solid fa-user-plus me-1"></i><?= $t['register'] ?></a>
            <?php endif; ?>

            <a href="admin/login.php" class="btn btn-sm btn-primary ms-2 fw-bold"><i class="fa-solid fa-user-shield me-1"></i> Admin</a>
        </div>
    </div>
</div>

<!-- NAVBAR & ANIMATSIYALI MENYU -->
<nav class="navbar navbar-expand-lg sticky-top shadow-sm" style="background-color: var(--card-bg);">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary fs-4" href="#">
            <img src="images/brend.png" alt="BR IT Service Logo" style="max-height: 45px;" onerror="this.src='images/BR_IT_SERVICE.png'">
            <span>BR IT SERVICE</span>
        </a>
        
        <!-- IXCHAM QIDIRUV -->
        <div class="ms-3 me-auto search-box-compact d-none d-md-block">
            <input type="text" id="liveSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="<?= $t['search_ph'] ?>" onkeyup="filterServices()">
        </div>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="fa-solid fa-bars text-primary fs-3"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto fw-bold align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="#services">
                        <i class="fa-solid fa-screwdriver-wrench text-primary"></i> <?= $t['services'] ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="#order">
                        <i class="fa-solid fa-cart-shopping text-warning"></i> <?= $t['order'] ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="#portfolio">
                        <i class="fa-solid fa-id-card text-success"></i> <?= $t['portfolio'] ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="#contact">
                        <i class="fa-solid fa-location-dot text-danger"></i> <?= $t['contact'] ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="#comments">
                        <i class="fa-solid fa-comments text-info"></i> <?= $t['comments'] ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO BANNER -->
<div class="hero-section text-center">
    <div class="container">
        <h1 class="display-3 gold-title mb-3"><?= $t['hero_title'] ?></h1>
        <p class="fs-4 fw-bold mb-4" style="background: rgba(0,0,0,0.5); display: inline-block; padding: 10px 24px; border-radius: 12px;"><?= $t['hero_sub'] ?></p>
        <div>
            <a href="#order" class="btn btn-warning btn-lg fw-bold px-5 py-3 shadow-lg fs-5"><i class="fa-solid fa-paper-plane me-2"></i><?= $t['order'] ?></a>
        </div>
    </div>
</div>

<div class="container my-5">
    <?= $msg ?>

    <!-- IT XIZMATLAR KATALOGI -->
    <section id="services" class="mb-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold fs-2"><i class="fa-solid fa-screwdriver-wrench text-primary me-2"></i><?= $t['services'] ?></h2>
        </div>

        <div class="row g-4" id="servicesList">
            <div class="col-md-4 service-item">
                <div class="card h-100 p-4 shadow-sm text-center">
                    <i class="fa-solid fa-kit-medical fa-3x text-danger mb-3"></i>
                    <h5 class="fw-bold text-danger">🔧 IT TEZ YORDAM</h5>
                    <p class="small text-muted">Kompyuter va tizimdagi favqulodda xatoliklarni zudlik bilan bartaraf etish.</p>
                    <h5 class="text-primary fw-bold mt-auto">30 000 - 60 000 so'm</h5>
                </div>
            </div>
            <div class="col-md-4 service-item">
                <div class="card h-100 p-4 shadow-sm text-center">
                    <i class="fa-solid fa-house-chimney-user fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold text-success">🏠 Uyga Borib Xizmat</h5>
                    <p class="small text-muted">Uyingizga yoki ofisingizga borib joyida sozlab berish.</p>
                    <h5 class="text-success fw-bold mt-auto">50 000 - 100 000 so'm</h5>
                </div>
            </div>
            <div class="col-md-4 service-item">
                <div class="card h-100 p-4 shadow-sm text-center">
                    <i class="fa-brands fa-windows fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">🪟 Windows • Dasturlar • Drayverlar</h5>
                    <p class="small text-muted">Windows 10/11 o'rnatish, optimal sozlash, drayverlar to'plami.</p>
                    <h5 class="text-primary fw-bold mt-auto">50 000 - 80 000 so'm</h5>
                </div>
            </div>
            <div class="col-md-4 service-item">
                <div class="card h-100 p-4 shadow-sm text-center">
                    <i class="fa-solid fa-wifi fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold">📡 Wi-Fi • Internet • Printer</h5>
                    <p class="small text-muted">Router/Wi-Fi sozlash, printer va skanerlarni ulash.</p>
                    <h5 class="text-warning fw-bold mt-auto">40 000 - 70 000 so'm</h5>
                </div>
            </div>
            <div class="col-md-4 service-item">
                <div class="card h-100 p-4 shadow-sm text-center">
                    <i class="fa-solid fa-file-excel fa-3x text-info mb-3"></i>
                    <h5 class="fw-bold">📊 Word • Excel • PowerPoint</h5>
                    <p class="small text-muted">Microsoft Office paketini o'rnatish hamda sozlash.</p>
                    <h5 class="text-info fw-bold mt-auto">30 000 - 50 000 so'm</h5>
                </div>
            </div>
            <div class="col-md-4 service-item">
                <div class="card h-100 p-4 shadow-sm text-center">
                    <i class="fa-solid fa-robot fa-3x text-purple mb-3" style="color:#8b5cf6;"></i>
                    <h5 class="fw-bold" style="color:#8b5cf6;">🤖 AI Rasm • Musiqa</h5>
                    <p class="small text-muted">Suno, Midjourney, D-ID kabi sun'iy intellektda kontentlar yaratish.</p>
                    <h5 class="fw-bold mt-auto" style="color:#8b5cf6;">40 000 - 90 000 so'm</h5>
                </div>
            </div>
        </div>
    </section>

    <!-- ONLAYN BUYURTMA FORMASI -->
    <section id="order" class="mb-5 p-4 p-md-5 rounded-4 shadow" style="background-color: var(--card-bg);">
        <h3 class="fw-bold mb-4 text-center text-primary"><i class="fa-solid fa-cart-plus me-2"></i><?= $t['order'] ?></h3>
        <form method="POST" action="#order">
            <input type="hidden" name="action" value="order">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">F.I.O (Ismingiz):</label>
                    <input type="text" name="client_name" class="form-control form-control-lg" required placeholder="Masalan: Jasur Rahimov">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Telefon Raqamingiz:</label>
                    <input type="text" name="phone" class="form-control form-control-lg" required placeholder="+998 90 621 78 21">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">Xizmat Turini Tanlang:</label>
                    <select name="service_type" class="form-select form-select-lg" required>
                        <option value="Windows & Drayverlar">🪟 Windows & Drayverlar o'rnatish</option>
                        <option value="IT Tez Yordam">🔧 IT Tez Yordam / Masofaviy Yordam (AnyDesk)</option>
                        <option value="Uyga borib xizmat">🏠 Uyga borib xizmat ko'rsatish</option>
                        <option value="Wi-Fi va Printer">📡 Wi-Fi, Internet va Printer sozlash</option>
                        <option value="AI va Kontent Yaratish">🤖 AI Rasm, Musiqa va Kontent Yaratish</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">Muammo yoki Buyurtma Izohi:</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Kompyuter yoki xizmat bo'yicha batafsilroq yozing..."></textarea>
                </div>
                <div class="col-md-12 text-center d-flex flex-wrap justify-content-center gap-3 mt-4">
                    <button type="submit" class="btn btn-warning btn-lg fw-bold px-5 shadow"><i class="fa-solid fa-paper-plane me-2"></i>Buyurtmani Yuborish</button>
                    <a href="https://t.me/at_servis_bot" target="_blank" class="btn btn-outline-primary btn-lg fw-bold px-4 shadow"><i class="fa-brands fa-telegram me-2"></i>Bot orqali Buyurtma (@at_servis_bot)</a>
                </div>
            </div>
        </form>
    </section>

    <!-- DIREKTOR PORTFOLIOSI (CAPSLOCK, JIRNIY ISM, TO'LIQ MO'TADIL FON) -->
    <section id="portfolio" class="mb-5 p-4 p-md-5 portfolio-premium-card">
        <div class="row align-items-center g-4">
            <div class="col-lg-5 text-center">
                <img src="images/BR_IT_SERVICE.png" alt="BR IT Service Logo" class="portfolio-logo-hover" onerror="this.src='images/brend.png'">
            </div>
            <div class="col-lg-7">
                <div class="bg-text-overlay">
                    <h1 class="display-5 director-name-bold mb-2">RUSTAMOV BAXTIYOR</h1>
                    <p class="fw-bold fs-5 mb-3 text-info"><i class="fa-solid fa-user-check me-2 text-success"></i>BR IT Service Markazi Rahbari / AT-Servis Mutaxassisi</p>
                    <p class="fs-5 fw-semibold leading-relaxed mb-4 text-light">
                        Andijon Davlat Universiteti Kompyuter injiniringi (AT-Servis) yo'nalishi bitiruvchisi. Kompyuter tizimlari, masofaviy IT qo'llab-quvvatlash, dasturiy ta'minot va avtomatlashtirilgan xizmatlar ko'rsatish bo'yicha ko'p yillik professional tajribaga ega.
                    </p>
                    
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                        <a href="https://www.youtube.com/channel/UCd7DakfdEiPr_3XUFAAihoA" target="_blank" class="btn btn-danger fw-bold shadow">
                            <i class="fa-brands fa-youtube me-1"></i> YouTube
                        </a>
                        <a href="https://t.me/KompyuterKayfiyati" target="_blank" class="btn btn-primary fw-bold shadow">
                            <i class="fa-brands fa-telegram me-1"></i> @KompyuterKayfiyati
                        </a>
                        <a href="https://t.me/rich_editor_Portfolio" target="_blank" class="btn btn-info text-white fw-bold shadow">
                            <i class="fa-solid fa-briefcase me-1"></i> Portfolio
                        </a>
                        <a href="https://instagram.com" target="_blank" class="btn btn-outline-light fw-bold shadow" style="background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);">
                            <i class="fa-brands fa-instagram me-1"></i> Instagram
                        </a>
                        <a href="https://facebook.com" target="_blank" class="btn btn-primary fw-bold shadow" style="background-color: #1877f2;">
                            <i class="fa-brands fa-facebook me-1"></i> Facebook
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- KONTAKTLAR VA REAL NAVIGATSIYA XARITASI -->
    <section id="contact" class="mb-5">
        <h3 class="fw-bold mb-4 text-center"><i class="fa-solid fa-location-dot text-danger me-2"></i>Aniq Manzil va Bog'lanish</h3>
        <div class="row g-4">
            <div class="col-md-5">
                <div class="card p-4 h-100 shadow-sm border-2 border-primary">
                    <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-address-book me-2"></i>Aloqa Ma'lumotlari:</h5>
                    <p class="fs-6">📞 <strong>Telefonlar:</strong> <br><a href="tel:+998906217821" class="text-decoration-none fw-bold text-success">+998 90 621 78 21</a> / <a href="tel:+998994306799" class="text-decoration-none fw-bold text-success">+998 99 430 67 99</a></p>
                    <p class="fs-6">💬 <strong>Telegram Admin:</strong> <a href="https://t.me/kayfiyatbekati" target="_blank" class="fw-bold text-info">@kayfiyatbekati</a></p>
                    <p class="fs-6">🤖 <strong>Telegram Bot:</strong> <a href="https://t.me/at_servis_bot" target="_blank" class="fw-bold text-primary">@at_servis_bot</a></p>
                    <p class="fs-6">📧 <strong>Email:</strong> <a href="mailto:bahtik2606@gmail.com" class="fw-bold text-warning text-decoration-none">bahtik2606@gmail.com (Bosib xat yozing)</a></p>
                    <p class="fs-6">📍 <strong>Aniq Manzil:</strong> <br><a href="https://maps.google.com/?q=40.758,72.345" target="_blank" class="fw-bold text-danger text-decoration-none"><i class="fa-solid fa-map-location-dot me-1"></i>Andijon shahri, Sakkokiy ko'chasi #24 (3-Poliklinika yaqinida)</a></p>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card p-2 h-100 shadow-sm">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3037.123!2d72.345!3d40.758!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDQ1JzI4LjgiTiA3MsKwMjAnNDIuMCJF!5e0!3m2!1suz!2s!4v1620000000000!5m2!1suz!2s" width="100%" height="320" style="border:0; border-radius: 12px;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- SHARHLAR BO'LIMI (AVVALGI SHARHLARNI HAM KO'RISH TIKLANDI) -->
    <section id="comments" class="mb-5">
        <h3 class="fw-bold mb-4 text-center"><i class="fa-solid fa-comments text-info me-2"></i>Tashrif Buyuruvchilar Sharhlari</h3>
        
        <!-- Dastlabki 3 ta sharh -->
        <div class="row g-4 mb-3">
            <?php if (!empty($comments_list)): ?>
                <?php 
                $first_three = array_slice($comments_list, 0, 3);
                $remaining_comments = array_slice($comments_list, 3);
                ?>
                <?php foreach ($first_three as $index => $c): ?>
                    <div class="col-md-4">
                        <div class="card p-3 h-100 shadow-sm border-start border-4 border-info">
                            <h6 class="fw-bold text-primary mb-1"><i class="fa-solid fa-user-circle me-1"></i><?= htmlspecialchars($c['name']) ?></h6>
                            <p class="small text-muted mb-2"><?= $c['created_at'] ?></p>
                            <p class="mb-0">"<?= htmlspecialchars($c['comment']) ?>"</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">Hozircha sharhlar mavjud emas.</p>
            <?php endif; ?>
        </div>

        <!-- Avvalgi Sig'magan Sharhlarni Ko'rish (Collapse) -->
        <?php if (!empty($remaining_comments)): ?>
            <div class="text-center mb-4">
                <button class="btn btn-outline-info fw-bold px-4" type="button" data-bs-toggle="collapse" data-bs-target="#moreComments" aria-expanded="false" aria-controls="moreComments">
                    <i class="fa-solid fa-comments me-2"></i>Barcha avvalgi sharhlarni ko'rish / Yopish
                </button>
            </div>

            <div class="collapse" id="moreComments">
                <div class="row g-4 mb-4">
                    <?php foreach ($remaining_comments as $rc): ?>
                        <div class="col-md-4">
                            <div class="card p-3 h-100 shadow-sm border-start border-4 border-secondary">
                                <h6 class="fw-bold text-primary mb-1"><i class="fa-solid fa-user-circle me-1"></i><?= htmlspecialchars($rc['name']) ?></h6>
                                <p class="small text-muted mb-2"><?= $rc['created_at'] ?></p>
                                <p class="mb-0">"<?= htmlspecialchars($rc['comment']) ?>"</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Sharh Qoldirish Formasi -->
        <div class="card p-4 shadow-sm border-2 border-primary">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Fikringizni qoldiring:</h5>
            <form method="POST" action="#comments">
                <input type="hidden" name="action" value="comment">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="comment_name" class="form-control" placeholder="Ismingiz" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="comment_text" class="form-control" placeholder="Fikringiz..." required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Yuborish</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<!-- FOOTER -->
<footer class="text-center py-4 border-top" style="background-color: var(--card-bg); color: var(--text-color);">
    <div class="container">
        <p class="fw-bold fs-6 mb-1">© 2026 @BR IT Service. (sayt yaratuvchi): <span class="text-primary">Rustamov Baxtiyor</span></p>
        <p class="fw-semibold">📊 Tashriflar soni: <span class="badge bg-primary px-3 py-2 rounded-pill"><?= $page_views ?></span></p>
    </div>
</footer>

<button id="btnUp" class="btn btn-primary scroll-btn" onclick="window.scrollTo({top: 0, behavior: 'smooth'})"><i class="fa-solid fa-arrow-up"></i></button>
<button id="btnDown" class="btn btn-primary scroll-btn" onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})"><i class="fa-solid fa-arrow-down"></i></button>

<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('liveDateTime').innerText = now.toLocaleString('uz-UZ');
    }
    setInterval(updateClock, 1000);
    updateClock();

    // KUNDUZGI VA TUNGI REJIM
    function setTheme(themeName) {
        document.body.className = '';
        if (themeName === 'light') {
            document.body.classList.add('light-theme');
        }
    }

    // LIVE SEARCH FUNKSIYASI
    function filterServices() {
        let input = document.getElementById('liveSearchInput').value.toLowerCase();
        let items = document.getElementsByClassName('service-item');
        for (let i = 0; i < items.length; i++) {
            let text = items[i].innerText.toLowerCase();
            if (text.includes(input)) {
                items[i].style.display = "";
            } else {
                items[i].style.display = "none";
            }
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>