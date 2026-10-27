<?php
session_start();
date_default_timezone_set('Asia/Tashkent');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

include 'connect.php';
@include '../includes/telegram.php';

// TELEFON RAQAMINI CHIROYLI FORMATGA KELTIRISH FUNKSIYASI
function formatPhoneNumber($phone) {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean) == 9) {
        $clean = '998' . $clean;
    }
    if (strlen($clean) == 12) {
        return '+' . substr($clean, 0, 3) . ' ' . substr($clean, 3, 2) . ' ' . substr($clean, 5, 3) . ' ' . substr($clean, 8, 2) . ' ' . substr($clean, 10, 2);
    }
    return $phone;
}

// ==================== BUYURTMALAR LOGIKASI ====================
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $connection->real_escape_string($_POST['status']);

    $stmt = $connection->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute()) {
        $tg_msg  = "🔄 <b>BUYURTMA STATUSI O'ZGARDI!</b>\n\n";
        $tg_msg .= "🆔 <b>Buyurtma ID:</b> #{$order_id}\n";
        $tg_msg .= "📌 <b>Yangi Status:</b> <b>{$new_status}</b>\n";
        $tg_msg .= "📅 <b>Sana:</b> " . date('Y-m-d') . "\n";
        $tg_msg .= "⏰ <b>Vaqt:</b> " . date('H:i');
        
        if (function_exists('sendTelegramNotification')) {
            sendTelegramNotification($tg_msg);
        }
    }
    header("location: index.php?tab=orders");
    exit;
}

if (isset($_POST['add_order'])) {
    $c_name  = $connection->real_escape_string($_POST['client_name']);
    $phone   = $connection->real_escape_string($_POST['phone']);
    $tg_nick = $connection->real_escape_string($_POST['tg_nick']);
    $email   = $connection->real_escape_string($_POST['email']);
    $service = $connection->real_escape_string($_POST['service_type']);
    $desc    = $connection->real_escape_string($_POST['description']);

    $contact_info = $phone;
    if (!empty($tg_nick)) { $contact_info .= "<br><span class='text-sky-400 text-xs font-bold'><i class='fa-brands fa-telegram me-1'></i>" . $tg_nick . "</span>"; }
    if (!empty($email)) { $contact_info .= "<br><span class='text-amber-300 text-xs font-bold'><i class='fa-solid fa-envelope me-1'></i>" . $email . "</span>"; }

    $stmt = $connection->prepare("INSERT INTO orders (client_name, phone, service_type, description, status) VALUES (?, ?, ?, ?, 'Yangi')");
    $stmt->bind_param("ssss", $c_name, $contact_info, $service, $desc);
    if ($stmt->execute()) {
        $tg_msg  = "🆕 <b>YANGI BUYURTMA QABUL QILINDI!</b>\n\n";
        $tg_msg .= "👤 <b>Mijoz:</b> {$c_name}\n";
        $tg_msg .= "📞 <b>Telefon:</b> {$phone}\n";
        $tg_msg .= "🛠 <b>Xizmat:</b> {$service}\n";
        $tg_msg .= "💬 <b>Izoh:</b> {$desc}\n";
        $tg_msg .= "📅 <b>Sana:</b> " . date('Y-m-d H:i');
        if (function_exists('sendTelegramNotification')) {
            sendTelegramNotification($tg_msg);
        }
    }
    header("location: index.php?tab=orders");
    exit;
}

// ==================== PRAYS-LIST (SERVICES) LOGIKASI ====================
if (isset($_POST['add_service'])) {
    $title = $connection->real_escape_string($_POST['title']);
    $desc = $connection->real_escape_string($_POST['description']);
    $price = $connection->real_escape_string($_POST['price']);
    $icon = $connection->real_escape_string($_POST['icon']);

    $stmt = $connection->prepare("INSERT INTO services (title, description, price, icon) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $desc, $price, $icon);
    if ($stmt->execute()) {
        $tg_msg  = "🏷️ <b>YANGI XIZMAT (PRAYS) QO'SHILDI!</b>\n\n";
        $tg_msg .= "📌 <b>Nomi:</b> {$title}\n";
        $tg_msg .= "💰 <b>Narxi:</b> {$price}\n";
        $tg_msg .= "📄 <b>Tavsif:</b> {$desc}";
        if (function_exists('sendTelegramNotification')) {
            sendTelegramNotification($tg_msg);
        }
    }
    header("location: index.php?tab=prices");
    exit;
}

if (isset($_POST['update_service'])) {
    $id = intval($_POST['service_id']);
    $title = $connection->real_escape_string($_POST['title']);
    $desc = $connection->real_escape_string($_POST['description']);
    $price = $connection->real_escape_string($_POST['price']);
    $icon = $connection->real_escape_string($_POST['icon']);

    $stmt = $connection->prepare("UPDATE services SET title = ?, description = ?, price = ?, icon = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $desc, $price, $icon, $id);
    $stmt->execute();
    header("location: index.php?tab=prices");
    exit;
}

if (isset($_GET['delete_service'])) {
    $del_id = intval($_GET['delete_service']);
    $connection->query("DELETE FROM services WHERE id = $del_id");
    header("location: index.php?tab=prices");
    exit;
}

// ==================== IZOHLAR (COMMENTS) LOGIKASI ====================
if (isset($_POST['update_comment_status'])) {
    $comment_id = intval($_POST['comment_id']);
    $new_status = $connection->real_escape_string($_POST['status']);

    $stmt = $connection->prepare("UPDATE comments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $comment_id);
    if ($stmt->execute()) {
        if ($new_status == 'Tasdiqlandi') {
            $res = $connection->query("SELECT * FROM comments WHERE id = $comment_id");
            if ($res && $row = $res->fetch_assoc()) {
                $c_name_val = $row['client_name'] ?? $row['name'] ?? 'Mijoz';
                $c_comm_val = $row['comment'] ?? $row['text'] ?? '';
                $c_rat_val  = $row['rating'] ?? '5';

                $tg_msg  = "💬 <b>MIJOZ IZOHI TASDIQLANDI!</b>\n\n";
                $tg_msg .= "👤 <b>Mijoz:</b> {$c_name_val}\n";
                $tg_msg .= "⭐ <b>Baho:</b> {$c_rat_val} yulduz\n";
                $tg_msg .= "📝 <b>Izoh:</b> {$c_comm_val}";
                if (function_exists('sendTelegramNotification')) {
                    sendTelegramNotification($tg_msg);
                }
            }
        }
    }
    header("location: index.php?tab=comments");
    exit;
}

if (isset($_GET['delete_comment'])) {
    $del_id = intval($_GET['delete_comment']);
    $connection->query("DELETE FROM comments WHERE id = $del_id");
    header("location: index.php?tab=comments");
    exit;
}

// ==================== MA'LUMOTLARNI OLISH ====================
$search = isset($_GET['search']) ? trim($connection->real_escape_string($_GET['search'])) : '';
$filter_status = isset($_GET['status_filter']) ? trim($connection->real_escape_string($_GET['status_filter'])) : '';

$where_clauses = array();
if (!empty($search)) {
    $where_clauses[] = "(client_name LIKE '%$search%' OR phone LIKE '%$search%' OR service_type LIKE '%$search%' OR description LIKE '%$search%')";
}
if (!empty($filter_status)) {
    if ($filter_status == 'Bajarildi') {
        $where_clauses[] = "(status = 'Bajarildi' OR status = 'Tugatilgan')";
    } else {
        $where_clauses[] = "status = '$filter_status'";
    }
}
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$count_result = $connection->query("SELECT COUNT(*) as total FROM orders $where_sql");
$total_orders = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_orders / $limit);

$orders_query = $connection->query("SELECT * FROM orders $where_sql ORDER BY id DESC LIMIT $offset, $limit");
$services_query = $connection->query("SELECT * FROM services ORDER BY id DESC");
$comments_query = $connection->query("SELECT * FROM comments ORDER BY id DESC");

// Statistika
$st_yangi = $connection->query("SELECT COUNT(*) as c FROM orders WHERE status='Yangi'")->fetch_assoc()['c'] ?? 0;
$st_jarayon = $connection->query("SELECT COUNT(*) as c FROM orders WHERE status='Jarayonda'")->fetch_assoc()['c'] ?? 0;
$st_tugatildi = $connection->query("SELECT COUNT(*) as c FROM orders WHERE status='Tugatilgan' OR status='Bajarildi'")->fetch_assoc()['c'] ?? 0;

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BR IT SERVICE — Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 900: '#0B132B', 800: '#10182F', 700: '#18223C', 600: '#1E2D4A', 500: '#2A3B5C' },
                        gold: { 400: '#FFE169', 500: '#FFD700', 600: '#D4AF37', 700: '#B38F23' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0B132B; color: #F8FAFC; font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(24, 34, 60, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(212, 175, 55, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .hero-banner {
            background: linear-gradient(135deg, rgba(16, 24, 47, 0.92) 0%, rgba(11, 19, 43, 0.88) 100%), 
                        url('../images/1.png');
            background-size: cover; background-position: center;
        }
        .status-select-custom { color: #ffffff !important; font-weight: 700 !important; }
        .status-select-custom option { background-color: #ffffff !important; color: #0f172a !important; font-weight: 700 !important; padding: 10px !important; }
        .gold-gradient-text {
            background: linear-gradient(135deg, #FFF099 0%, #FFD700 50%, #D4AF37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 15px rgba(255, 215, 0, 0.4); border-color: rgba(255, 215, 0, 0.6); }
            50% { box-shadow: 0 0 32px rgba(255, 215, 0, 0.95); border-color: #FFD700; }
        }
        .brand-logo-glow { animation: pulseGlow 3s infinite ease-in-out; transition: all 0.3s ease; }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased">

    <!-- TOP NAV HEADER -->
    <header class="bg-navy-800 border-b border-navy-500 sticky top-0 z-40 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-28 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center space-x-4 shrink-0">
                <div class="w-20 h-20 rounded-2xl bg-navy-900 border-2 p-1.5 flex items-center justify-center shadow-2xl cursor-pointer brand-logo-glow">
                    <img src="../images/brend.png" alt="BR IT Service" class="max-h-full max-w-full object-contain" onerror="this.src='../images/BR_IT_SERVICE.png'">
                </div>
                <div>
                    <span class="font-black text-2xl sm:text-3xl tracking-wider text-white block leading-none">BR IT SERVICE</span>
                    <span class="text-xs font-bold text-gold-400 flex items-center gap-1 mt-1.5">
                        <i class="fa-solid fa-star text-gold-500 text-xs animate-bounce"></i> AT-Servis Brand System
                    </span>
                </div>
            </div>

            <div class="hidden md:flex items-center space-x-3 bg-navy-900/90 px-6 py-3 rounded-2xl border border-gold-500/50 shadow-xl">
                <i class="fa-solid fa-shield-halved text-gold-500 text-2xl animate-pulse"></i>
                <span class="font-black text-xl sm:text-2xl tracking-wide uppercase gold-gradient-text whitespace-nowrap">ADMIN BOSHQARUV PANELI</span>
            </div>

            <div class="flex items-center space-x-3 shrink-0">
                <div class="hidden lg:flex flex-col text-right pr-2">
                    <span class="text-sm font-black text-slate-100 flex items-center justify-end">
                        <i class="fa-solid fa-user-graduate text-gold-500 mr-2"></i> RUSTAMOV BAHTIYOR
                    </span>
                    <span class="text-xs text-slate-400">Andijon Davlat Universiteti | AT-Servis</span>
                </div>
                <a href="../index.php" target="_blank" class="bg-navy-700 hover:bg-navy-600 text-slate-200 text-xs font-bold px-4 py-2.5 rounded-xl border border-navy-500 transition flex items-center gap-2">
                    <i class="fa-solid fa-globe text-gold-500"></i> Sayt
                </a>
                <a href="logout.php" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-bold px-4 py-2.5 rounded-xl border border-red-500/30 transition flex items-center gap-2">
                    <i class="fa-solid fa-right-from-bracket"></i> Chiqish
                </a>
            </div>
        </div>
    </header>

    <!-- ASOSIY TAB NAVIGATSIYA TUGMALARI -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-wrap items-center gap-3 border-b border-navy-600 pb-4">
            <button onclick="switchTab('orders')" id="tab-orders-btn" class="font-black px-6 py-3 rounded-xl text-xs sm:text-sm transition-all duration-300 flex items-center gap-2 shadow-lg">
                <i class="fa-solid fa-list-check text-base"></i> Buyurtmalar Boshqaruvi
            </button>
            <button onclick="switchTab('prices')" id="tab-prices-btn" class="font-black px-6 py-3 rounded-xl text-xs sm:text-sm transition-all duration-300 flex items-center gap-2 shadow-lg">
                <i class="fa-solid fa-tags text-base"></i> Prays-list & Xizmatlar
            </button>
            <button onclick="switchTab('comments')" id="tab-comments-btn" class="font-black px-6 py-3 rounded-xl text-xs sm:text-sm transition-all duration-300 flex items-center gap-2 shadow-lg">
                <i class="fa-solid fa-comments text-base"></i> Mijozlar Izohlari (Moderatsiya)
            </button>
        </div>
    </div>

    <!-- ASOSIY KONTENT -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full space-y-8">

        <!-- 1-TAB: BUYURTMALAR BOSHQARUVI -->
        <div id="tab-orders" class="space-y-8">
            <div class="hero-banner rounded-2xl p-6 sm:p-8 border border-navy-500/80 shadow-2xl space-y-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-white flex items-center gap-3">Mijozlar Buyurtmalari</h1>
                        <p class="text-slate-300 text-sm mt-1">Kelib tushgan buyurtmalarni nazorat qilish va status berish</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="export_excel.php" class="bg-emerald-600/90 hover:bg-emerald-600 text-white font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm border border-emerald-500/50 transition flex items-center gap-2 shadow-lg">
                            <i class="fa-solid fa-file-excel"></i> Excel
                        </a>
                        <a href="export_pdf.php" target="_blank" class="bg-rose-600/90 hover:bg-rose-600 text-white font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm border border-rose-500/50 transition flex items-center gap-2 shadow-lg">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>
                        <button onclick="document.getElementById('orderModal').classList.remove('hidden')" class="bg-gradient-to-r from-gold-500 to-amber-600 hover:from-gold-400 hover:to-amber-500 text-navy-900 font-black px-5 py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-2 shadow-lg">
                            <i class="fa-solid fa-circle-plus text-base"></i> + Yangi Buyurtma
                        </button>
                    </div>
                </div>

                <!-- Qidiruv va Filtr -->
                <form method="GET" action="index.php" class="pt-2">
                    <input type="hidden" name="tab" value="orders">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="md:col-span-3 relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass text-gold-500 text-base"></i>
                            </div>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Ism, telefon, TG nick, Email yoki xizmat bo'yicha..." 
                                class="w-full pl-11 pr-24 py-3.5 bg-navy-900/90 border border-navy-500 focus:border-gold-500 text-white placeholder-slate-400 rounded-xl text-sm focus:outline-none transition">
                            <?php if (!empty($search) || !empty($filter_status)): ?>
                                <a href="index.php?tab=orders" class="absolute right-16 top-2.5 text-slate-400 hover:text-white bg-navy-700 p-1.5 rounded-lg text-xs">Tozalash</a>
                            <?php endif; ?>
                            <button type="submit" class="absolute right-2 top-1.5 bottom-1.5 px-4 bg-gold-500 hover:bg-gold-400 text-navy-900 font-black text-xs rounded-lg transition">Qidirish</button>
                        </div>
                        <div>
                            <select name="status_filter" onchange="this.form.submit()" class="w-full py-3.5 px-3 bg-navy-900/90 border border-navy-500 text-white rounded-xl text-xs font-bold focus:outline-none cursor-pointer">
                                <option value="">🔍 Barcha Statuslar</option>
                                <option value="Yangi" <?php echo $filter_status == 'Yangi' ? 'selected' : ''; ?>>🔵 Yangi</option>
                                <option value="Jarayonda" <?php echo $filter_status == 'Jarayonda' ? 'selected' : ''; ?>>🟡 Jarayonda</option>
                                <option value="Bajarildi" <?php echo $filter_status == 'Bajarildi' ? 'selected' : ''; ?>>🟢 Bajarildi</option>
                                <option value="Bekor qilindi" <?php echo $filter_status == 'Bekor qilindi' ? 'selected' : ''; ?>>🔴 Bekor qilindi</option>
                            </select>
                        </div>
                    </div>
                </form>

                <!-- Statistika -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                    <div class="bg-navy-900/90 border border-navy-500 p-4 rounded-xl flex items-center justify-between">
                        <div><p class="text-xs font-bold text-slate-400 uppercase">JAMI</p><p class="text-2xl font-black text-white mt-1"><?php echo $total_orders; ?> ta</p></div>
                        <div class="w-10 h-10 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center"><i class="fa-solid fa-folder-open"></i></div>
                    </div>
                    <div class="bg-navy-900/90 border border-navy-500 p-4 rounded-xl flex items-center justify-between">
                        <div><p class="text-xs font-bold text-slate-400 uppercase">YANGI</p><p class="text-2xl font-black text-sky-400 mt-1"><?php echo $st_yangi; ?> ta</p></div>
                        <div class="w-10 h-10 rounded-lg bg-sky-500/10 text-sky-400 flex items-center justify-center"><i class="fa-solid fa-bell"></i></div>
                    </div>
                    <div class="bg-navy-900/90 border border-navy-500 p-4 rounded-xl flex items-center justify-between">
                        <div><p class="text-xs font-bold text-slate-400 uppercase">JARAYONDA</p><p class="text-2xl font-black text-amber-400 mt-1"><?php echo $st_jarayon; ?> ta</p></div>
                        <div class="w-10 h-10 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center"><i class="fa-solid fa-spinner animate-spin"></i></div>
                    </div>
                    <div class="bg-navy-900/90 border border-navy-500 p-4 rounded-xl flex items-center justify-between">
                        <div><p class="text-xs font-bold text-slate-400 uppercase">BAJARILDI</p><p class="text-2xl font-black text-emerald-400 mt-1"><?php echo $st_tugatildi; ?> ta</p></div>
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center"><i class="fa-solid fa-circle-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Buyurtmalar Jadvali -->
            <div class="glass-card rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-navy-500 bg-navy-800/50 flex items-center justify-between">
                    <h2 class="font-black text-base text-white flex items-center gap-2"><i class="fa-solid fa-table-list text-gold-500"></i> Barcha Buyurtmalar</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-navy-900/90 text-slate-300 text-xs font-bold uppercase border-b border-navy-500 text-center">
                                <th class="p-4 w-10">ID</th>
                                <th class="p-4 text-left">Mijoz F.I.O</th>
                                <th class="p-4 text-left">Aloqa Ma'lumoti</th>
                                <th class="p-4 text-left">Xizmat Turi</th>
                                <th class="p-4 text-left">Muammo / Izoh</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Sana va Vaqt</th>
                                <th class="p-4">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-600 text-sm font-medium">
                            <?php
                            if ($orders_query && $orders_query->num_rows > 0) {
                                while($row = $orders_query->fetch_assoc()) {
                                    $st = $row['status'] ?? 'Yangi';
                                    $st_style = ($st == 'Tugatilgan' || $st == 'Bajarildi') ? 'bg-emerald-500/30 text-emerald-300 border-emerald-500/50' : (($st == 'Jarayonda') ? 'bg-amber-500/30 text-amber-300 border-amber-500/50' : (($st == 'Bekor qilindi') ? 'bg-rose-500/30 text-rose-300 border-rose-500/50' : 'bg-sky-500/30 text-sky-300 border-sky-500/50'));
                                    $formatted_phone = formatPhoneNumber($row['phone']);
                                    echo "
                                    <tr class='hover:bg-navy-700/50 transition border-b border-navy-600/60'>
                                        <td class='p-4 text-center font-bold text-gold-400'>#{$row['id']}</td>
                                        <td class='p-4 font-bold text-white text-left'>{$row['client_name']}</td>
                                        <td class='p-4 text-emerald-400 font-black text-left whitespace-nowrap'>{$formatted_phone}</td>
                                        <td class='p-4 text-left'><span class='bg-navy-700 text-slate-100 text-xs px-3 py-1.5 rounded-lg border border-gold-500/30 font-bold'>{$row['service_type']}</span></td>
                                        <td class='p-4 text-slate-300 text-xs text-left max-w-xs truncate'>{$row['description']}</td>
                                        <td class='p-4 text-center'>
                                            <form method='POST'>
                                                <input type='hidden' name='order_id' value='{$row['id']}'>
                                                <input type='hidden' name='update_status' value='1'>
                                                <select name='status' onchange='this.form.submit()' class='status-select-custom text-xs font-bold px-3 py-1.5 rounded-lg border cursor-pointer bg-navy-900 {$st_style}'>
                                                    <option value='Yangi' " . ($st == 'Yangi' ? 'selected' : '') . ">🔵 Yangi</option>
                                                    <option value='Jarayonda' " . ($st == 'Jarayonda' ? 'selected' : '') . ">🟡 Jarayonda</option>
                                                    <option value='Bajarildi' " . ($st == 'Bajarildi' || $st == 'Tugatilgan' ? 'selected' : '') . ">🟢 Bajarildi</option>
                                                    <option value='Bekor qilindi' " . ($st == 'Bekor qilindi' ? 'selected' : '') . ">🔴 Bekor qilindi</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class='p-4 text-xs text-slate-400 text-center whitespace-nowrap font-bold'>" . date('Y-m-d H:i', strtotime($row['created_at'])) . "</td>
                                        <td class='p-4 text-center'>
                                            <div class='flex items-center justify-center space-x-2'>
                                                <a href='edit.php?id={$row['id']}' class='bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-500/40 text-xs font-bold px-2.5 py-1 rounded-lg transition'>Tahrir</a>
                                                <button onclick='confirmDeleteOrder({$row['id']})' class='bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/40 text-xs font-bold px-2.5 py-1 rounded-lg transition'>O'chirish</button>
                                            </div>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center py-8 text-slate-400 text-xs font-bold'>Hech qanday buyurtma topilmadi!</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2-TAB: PRAYS-LIST & XIZMATLAR BOSHQARUVI -->
        <div id="tab-prices" class="hidden space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-white flex items-center gap-2"><i class="fa-solid fa-tags text-gold-500"></i> Xizmatlar va Narxlar Katalogi</h2>
                    <p class="text-slate-400 text-xs mt-1">Saytdagi prays-list ma'lumotlarini boshqarish</p>
                </div>
                <button onclick="openAddServiceModal()" class="bg-gold-500 hover:bg-gold-400 text-navy-900 font-black px-5 py-2.5 rounded-xl text-xs transition shadow-lg">
                    + Yangi Xizmat Qo'shish
                </button>
            </div>

            <div class="glass-card rounded-2xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-navy-900 text-slate-300 text-xs font-extrabold uppercase border-b border-navy-500">
                                <th class="p-4 w-12 text-center">ID</th>
                                <th class="p-4 w-12 text-center">Ikonka</th>
                                <th class="p-4">Xizmat Nomi</th>
                                <th class="p-4">Tavsif / Izoh</th>
                                <th class="p-4">Belgilangan Narx</th>
                                <th class="p-4 text-center">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-600 text-xs font-semibold">
                            <?php
                            if ($services_query && $services_query->num_rows > 0) {
                                while($row = $services_query->fetch_assoc()) {
                                    $json_row = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    echo "
                                    <tr class='hover:bg-navy-700/50 transition border-b border-navy-600/60'>
                                        <td class='p-4 text-center font-bold text-gold-400'>#{$row['id']}</td>
                                        <td class='p-4 text-center text-lg text-gold-400'><i class='{$row['icon']}'></i></td>
                                        <td class='p-4 font-bold text-white text-sm'>{$row['title']}</td>
                                        <td class='p-4 text-slate-300 max-w-xs'>{$row['description']}</td>
                                        <td class='p-4 text-emerald-400 font-black text-sm'>{$row['price']}</td>
                                        <td class='p-4 text-center'>
                                            <div class='flex items-center justify-center space-x-2'>
                                                <button onclick='editService({$json_row})' class='bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-500/40 text-xs font-bold px-3 py-1.5 rounded-lg transition'>Tahrir</button>
                                                <button onclick='confirmDeleteService({$row['id']})' class='bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/40 text-xs font-bold px-3 py-1.5 rounded-lg transition'>O'chirish</button>
                                            </div>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center py-8 text-slate-400 font-bold'>Hech qanday xizmat topilmadi!</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3-TAB: MIJOZLAR IZOHLARI (MODERATSIYA) -->
        <div id="tab-comments" class="hidden space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-white flex items-center gap-2"><i class="fa-solid fa-comments text-gold-500"></i> Mijozlar Fikrlari va Baholari</h2>
                    <p class="text-slate-400 text-xs mt-1">Saytda qoldirilgan izohlarni tasdiqlash yoki rad etish</p>
                </div>
            </div>

            <div class="glass-card rounded-2xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-navy-900 text-slate-300 text-xs font-extrabold uppercase border-b border-navy-500">
                                <th class="p-4 w-12 text-center">ID</th>
                                <th class="p-4">Mijoz F.I.O</th>
                                <th class="p-4">Bahosi</th>
                                <th class="p-4">Izoh Matni</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-center">Sana</th>
                                <th class="p-4 text-center">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-600 text-xs font-semibold">
                            <?php
                            if ($comments_query && $comments_query->num_rows > 0) {
                                while($row = $comments_query->fetch_assoc()) {
                                    $st = $row['status'] ?? 'Kutilmoqda';
                                    $st_style = ($st == 'Tasdiqlandi') ? 'bg-emerald-500/30 text-emerald-300 border-emerald-500/50' : (($st == 'Rad etildi') ? 'bg-rose-500/30 text-rose-300 border-rose-500/50' : 'bg-amber-500/30 text-amber-300 border-amber-500/50');
                                    
                                    // Bazadagi ustun nomlari turlicha bo'lishi mumkinligi uchun tekshirib olamiz
                                    $c_name = $row['client_name'] ?? ($row['name'] ?? 'Mijoz');
                                    $c_text = $row['comment'] ?? ($row['text'] ?? '');
                                    $c_rat  = $row['rating'] ?? 5;

                                    $stars = str_repeat('<i class="fa-solid fa-star text-gold-400"></i>', intval($c_rat));
                                    echo "
                                    <tr class='hover:bg-navy-700/50 transition border-b border-navy-600/60'>
                                        <td class='p-4 text-center font-bold text-gold-400'>#{$row['id']}</td>
                                        <td class='p-4 font-bold text-white text-sm'>{$c_name}</td>
                                        <td class='p-4 whitespace-nowrap'>{$stars}</td>
                                        <td class='p-4 text-slate-300 max-w-md'>{$c_text}</td>
                                        <td class='p-4 text-center'>
                                            <form method='POST'>
                                                <input type='hidden' name='comment_id' value='{$row['id']}'>
                                                <input type='hidden' name='update_comment_status' value='1'>
                                                <select name='status' onchange='this.form.submit()' class='text-xs font-bold px-3 py-1.5 rounded-lg border cursor-pointer bg-navy-900 {$st_style}'>
                                                    <option value='Kutilmoqda' " . ($st == 'Kutilmoqda' ? 'selected' : '') . ">🟡 Kutilmoqda</option>
                                                    <option value='Tasdiqlandi' " . ($st == 'Tasdiqlandi' ? 'selected' : '') . ">🟢 Tasdiqlandi</option>
                                                    <option value='Rad etildi' " . ($st == 'Rad etildi' ? 'selected' : '') . ">🔴 Rad etildi</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class='p-4 text-center text-slate-400 whitespace-nowrap'>" . date('Y-m-d H:i', strtotime($row['created_at'])) . "</td>
                                        <td class='p-4 text-center'>
                                            <button onclick='confirmDeleteComment({$row['id']})' class='bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/40 text-xs font-bold px-3 py-1.5 rounded-lg transition'>O'chirish</button>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center py-8 text-slate-400 font-bold'>Hozircha izohlar mavjud emas!</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="bg-navy-800 border-t border-navy-500 mt-12 py-6 text-center text-xs text-slate-400">
        <p class="font-bold text-slate-300">© 2026 BR_IT_SERVICE — Barcha huquqlar himoyalangan.</p>
        <p class="text-slate-400 mt-1">Loyiha muallifi: <span class="text-gold-400 font-bold">AT-Servis talabasi Baxtiyor Rustamov</span></p>
    </footer>

    <!-- MODAL: YANGI BUYURTMA QO'SHISH -->
    <div id="orderModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
        <div class="glass-card bg-navy-800 border border-navy-500 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
            <div class="p-5 border-b border-navy-500 flex items-center justify-between bg-navy-900/60">
                <h3 class="text-base font-bold text-white"><i class="fa-solid fa-square-plus text-gold-500"></i> Yangi Buyurtma Qo'shish</h3>
                <button onclick="document.getElementById('orderModal').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="add_order" value="1">
                <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">Mijoz F.I.O</label><input type="text" name="client_name" required class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">Telefon Raqami</label><input type="text" name="phone" required class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">Telegram Nick</label><input type="text" name="tg_nick" class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none"></div>
                    <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">Email</label><input type="email" name="email" class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none"></div>
                </div>
                <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">Xizmat Turi</label><input type="text" name="service_type" required class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none" placeholder="Masalan: Windows o'rnatish"></div>
                <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">Izoh</label><textarea name="description" rows="2" class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none"></textarea></div>
                <div class="flex justify-end space-x-3"><button type="button" onclick="document.getElementById('orderModal').classList.add('hidden')" class="px-4 py-2 bg-navy-700 text-slate-300 text-xs font-semibold rounded-xl">Bekor qilish</button><button type="submit" class="px-5 py-2 bg-gold-500 text-navy-900 text-xs font-black rounded-xl shadow-lg">Saqlash</button></div>
            </form>
        </div>
    </div>

    <!-- MODAL: PRAYS-LIST QO'SHISH/TAHRIRLASH -->
    <div id="serviceModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
        <div class="glass-card bg-navy-800 border border-navy-500 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
            <div class="p-5 border-b border-navy-500 flex items-center justify-between bg-navy-900/60">
                <h3 id="serviceModalTitle" class="text-base font-bold text-white"><i class="fa-solid fa-square-plus text-gold-500"></i> Xizmatni Qo'shish / Tahrirlash</h3>
                <button onclick="document.getElementById('serviceModal').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" id="serviceForm" class="p-6 space-y-4">
                <input type="hidden" name="service_id" id="service_id">
                <input type="hidden" name="add_service" id="service_form_action" value="1">
                <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">Xizmat Nomi</label><input type="text" name="title" id="service_title" required class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">Xizmat Narxi</label><input type="text" name="price" id="service_price" required class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none" placeholder="100,000 so'm"></div>
                <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">FontAwesome Ikonka</label><input type="text" name="icon" id="service_icon" value="fa-solid fa-laptop-code" required class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-300 uppercase mb-1">Tavsif</label><textarea name="description" id="service_desc" rows="2" class="w-full bg-navy-900 border border-navy-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none"></textarea></div>
                <div class="flex justify-end space-x-3"><button type="button" onclick="document.getElementById('serviceModal').classList.add('hidden')" class="px-4 py-2 bg-navy-700 text-slate-300 text-xs font-semibold rounded-xl">Bekor qilish</button><button type="submit" class="px-5 py-2 bg-gold-500 text-navy-900 text-xs font-black rounded-xl shadow-lg">Saqlash</button></div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        function switchTab(tabName) {
            document.getElementById('tab-orders').classList.add('hidden');
            document.getElementById('tab-prices').classList.add('hidden');
            document.getElementById('tab-comments').classList.add('hidden');

            document.getElementById('tab-orders-btn').className = "bg-navy-800 hover:bg-navy-700 text-slate-300 font-bold px-6 py-3 rounded-xl text-xs sm:text-sm border border-navy-500 transition-all flex items-center gap-2";
            document.getElementById('tab-prices-btn').className = "bg-navy-800 hover:bg-navy-700 text-slate-300 font-bold px-6 py-3 rounded-xl text-xs sm:text-sm border border-navy-500 transition-all flex items-center gap-2";
            document.getElementById('tab-comments-btn').className = "bg-navy-800 hover:bg-navy-700 text-slate-300 font-bold px-6 py-3 rounded-xl text-xs sm:text-sm border border-navy-500 transition-all flex items-center gap-2";

            document.getElementById('tab-' + tabName).classList.remove('hidden');
            document.getElementById('tab-' + tabName + '-btn').className = "bg-gold-500 text-navy-900 font-black px-6 py-3 rounded-xl text-xs sm:text-sm transition-all flex items-center gap-2 shadow-lg shadow-gold-500/20";
        }

        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'orders';
        switchTab(currentTab);

        function openAddServiceModal() {
            document.getElementById('serviceModal').classList.remove('hidden');
            document.getElementById('serviceForm').reset();
            document.getElementById('service_id').value = '';
            document.getElementById('service_form_action').name = 'add_service';
            document.getElementById('serviceModalTitle').innerHTML = '<i class="fa-solid fa-square-plus text-gold-500"></i> Yangi Xizmat Qo\'shish';
        }

        function editService(data) {
            document.getElementById('serviceModal').classList.remove('hidden');
            document.getElementById('service_id').value = data.id;
            document.getElementById('service_title').value = data.title;
            document.getElementById('service_price').value = data.price;
            document.getElementById('service_icon').value = data.icon;
            document.getElementById('service_desc').value = data.description;
            document.getElementById('service_form_action').name = 'update_service';
            document.getElementById('serviceModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-gold-500"></i> Xizmatni Tahrirlash #' + data.id;
        }

        function confirmDeleteOrder(id) {
            Swal.fire({
                title: 'O\'chirilsinmi?', text: "Buyurtma #" + id + " o'chiriladi!", icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', cancelButtonColor: '#334155', confirmButtonText: 'Ha, o\'chirilsin', background: '#10182F', color: '#ffffff'
            }).then((result) => { if (result.isConfirmed) { window.location.href = 'delete.php?id=' + id; } });
        }

        function confirmDeleteService(id) {
            Swal.fire({
                title: 'O\'chirilsinmi?', text: "Ushbu xizmat o'chiriladi!", icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', cancelButtonColor: '#334155', confirmButtonText: 'Ha, o\'chirilsin', background: '#10182F', color: '#ffffff'
            }).then((result) => { if (result.isConfirmed) { window.location.href = 'index.php?tab=prices&delete_service=' + id; } });
        }

        function confirmDeleteComment(id) {
            Swal.fire({
                title: 'O\'chirilsinmi?', text: "Ushbu izoh o'chiriladi!", icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', cancelButtonColor: '#334155', confirmButtonText: 'Ha, o\'chirilsin', background: '#10182F', color: '#ffffff'
            }).then((result) => { if (result.isConfirmed) { window.location.href = 'index.php?tab=comments&delete_comment=' + id; } });
        }
    </script>
</body>
</html>