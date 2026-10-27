<?php
session_start();
date_default_timezone_set('Asia/Tashkent');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

include 'connect.php';

// 1. STATUSNI O'ZGARTIRISH (Tasdiqlash / Rad etish)
if (isset($_POST['update_comment_status'])) {
    $comment_id = intval($_POST['comment_id']);
    $new_status = $connection->real_escape_string($_POST['status']);

    $stmt = $connection->prepare("UPDATE comments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $comment_id);
    $stmt->execute();
    header("location: comments.php?status=updated");
    exit;
}

// 2. IZOHNI O'CHIRISH
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $connection->query("DELETE FROM comments WHERE id = $del_id");
    header("location: comments.php?status=deleted");
    exit;
}

// BARCHA IZOHLARNI OLISH
$comments = $connection->query("SELECT * FROM comments ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BR IT SERVICE — Mijozlar Izohlari (Moderatsiya)</title>
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
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased">

    <!-- HEADER -->
    <header class="bg-navy-800 border-b border-navy-500 sticky top-0 z-40 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-24 flex items-center justify-between gap-4">
            <div class="flex items-center space-x-4 shrink-0">
                <div class="w-16 h-16 rounded-2xl bg-navy-900 border-2 border-gold-500/50 p-1 flex items-center justify-center shadow-xl">
                    <img src="../images/brend.png" alt="BR IT Service" class="max-h-full max-w-full object-contain" onerror="this.src='../images/BR_IT_SERVICE.png'">
                </div>
                <div>
                    <span class="font-black text-xl sm:text-2xl tracking-wider text-white block leading-none">BR IT SERVICE</span>
                    <span class="text-xs font-bold text-gold-400 mt-1 block">Mijozlar Izohlarini Boshqarish</span>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <a href="index.php" class="bg-navy-700 hover:bg-navy-600 text-slate-200 text-xs font-bold px-4 py-2.5 rounded-xl border border-navy-500 transition flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-gold-500"></i> Buyurtmalar
                </a>
                <a href="prices.php" class="bg-navy-700 hover:bg-navy-600 text-slate-200 text-xs font-bold px-4 py-2.5 rounded-xl border border-navy-500 transition flex items-center gap-2">
                    <i class="fa-solid fa-tags text-gold-500"></i> Prays-list
                </a>
                <a href="logout.php" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-bold px-4 py-2.5 rounded-xl border border-red-500/30 transition">Chiqish</a>
            </div>
        </div>
    </header>

    <!-- ASOSIY BO'LIM -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-white flex items-center gap-2">
                    <i class="fa-solid fa-comments text-gold-500"></i> Mijozлар Фикрлари ва Баҳолари
                </h1>
                <p class="text-slate-400 text-xs mt-1">Saytda qoldirilgan izohlarni ko'rib chiqish va moderatsiya qilish</p>
            </div>
        </div>

        <!-- IZOHLAR JADVALI -->
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
                        if ($comments && $comments->num_rows > 0) {
                            while($row = $comments->fetch_assoc()) {
                                $st = $row['status'] ?? 'Kutilmoqda';
                                $st_style = ($st == 'Tasdiqlandi') ? 'bg-emerald-500/30 text-emerald-300 border-emerald-500/50' : (($st == 'Rad etildi') ? 'bg-rose-500/30 text-rose-300 border-rose-500/50' : 'bg-amber-500/30 text-amber-300 border-amber-500/50');

                                // Yulduzchalarni yasash
                                $stars = str_repeat('<i class="fa-solid fa-star text-gold-400"></i>', $row['rating']);

                                echo "
                                <tr class='hover:bg-navy-700/50 transition border-b border-navy-600/60'>
                                    <td class='p-4 text-center font-bold text-gold-400'>#{$row['id']}</td>
                                    <td class='p-4 font-bold text-white text-sm'>{$row['client_name']}</td>
                                    <td class='p-4 whitespace-nowrap'>{$stars}</td>
                                    <td class='p-4 text-slate-300 max-w-md'>{$row['comment']}</td>
                                    <td class='p-4 text-center'>
                                        <form method='POST'>
                                            <input type='hidden' name='comment_id' value='{$row['id']}'>
                                            <input type='hidden' name='update_comment_status' value='1'>
                                            <select name='status' onchange='this.form.submit()' class='text-xs font-bold px-3 py-1.5 rounded-lg border focus:outline-none cursor-pointer bg-navy-900 {$st_style}'>
                                                <option value='Kutilmoqda' " . ($st == 'Kutilmoqda' ? 'selected' : '') . ">🟡 Kutilmoqda</option>
                                                <option value='Tasdiqlandi' " . ($st == 'Tasdiqlandi' ? 'selected' : '') . ">🟢 Tasdiqlandi</option>
                                                <option value='Rad etildi' " . ($st == 'Rad etildi' ? 'selected' : '') . ">🔴 Rad etildi</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class='p-4 text-center text-slate-400 whitespace-nowrap'>" . date('Y-m-d H:i', strtotime($row['created_at'])) . "</td>
                                    <td class='p-4 text-center'>
                                        <button onclick='confirmDelete({$row['id']})' class='bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/40 text-xs font-bold px-3 py-1.5 rounded-lg transition'><i class='fa-solid fa-trash-can'></i> O'chirish</button>
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

    </main>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'O\'chirilsinmi?',
                text: "Ushbu izoh bazadan butunlay o'chiriladi!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Ha, o\'chirilsin',
                cancelButtonText: 'Bekor qilish',
                background: '#10182F',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'comments.php?delete_id=' + id;
                }
            });
        }
    </script>

</body>
</html>