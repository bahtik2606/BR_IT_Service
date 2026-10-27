<?php
session_start();
date_default_timezone_set('Asia/Tashkent');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

include 'connect.php';

// 1. YANGI XIZMAT QO'SHISH
if (isset($_POST['add_service'])) {
    $title = $connection->real_escape_string($_POST['title']);
    $desc = $connection->real_escape_string($_POST['description']);
    $price = $connection->real_escape_string($_POST['price']);
    $icon = $connection->real_escape_string($_POST['icon']);

    $stmt = $connection->prepare("INSERT INTO services (title, description, price, icon) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $desc, $price, $icon);
    $stmt->execute();
    header("location: prices.php?status=added");
    exit;
}

// 2. NARX/XIZMATNI TAHRIRLASH
if (isset($_POST['update_service'])) {
    $id = intval($_POST['service_id']);
    $title = $connection->real_escape_string($_POST['title']);
    $desc = $connection->real_escape_string($_POST['description']);
    $price = $connection->real_escape_string($_POST['price']);
    $icon = $connection->real_escape_string($_POST['icon']);

    $stmt = $connection->prepare("UPDATE services SET title = ?, description = ?, price = ?, icon = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $desc, $price, $icon, $id);
    $stmt->execute();
    header("location: prices.php?status=updated");
    exit;
}

// 3. XIZMATNI O'CHIRISH
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $connection->query("DELETE FROM services WHERE id = $del_id");
    header("location: prices.php?status=deleted");
    exit;
}

// XIZMATLAR RO'YXATINI OLISH
$services = $connection->query("SELECT * FROM services ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BR IT SERVICE — Xizmatlar va Prays-list</title>
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
                    <span class="text-xs font-bold text-gold-400 mt-1 block">Xizmatlar va Prays-list Boshqaruvi</span>
                </div>
            </div>

            <!-- ADMIN MENYU LINKLARI -->
            <div class="flex items-center space-x-3">
                <a href="index.php" class="bg-navy-700 hover:bg-navy-600 text-slate-200 text-xs font-bold px-4 py-2.5 rounded-xl border border-navy-500 transition flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-gold-500"></i> Buyurtmalar
                </a>
                <a href="prices.php" class="bg-gold-500 text-navy-900 text-xs font-black px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-lg">
                    <i class="fa-solid fa-tags text-navy-900"></i> Prays-list
                </a>
                <a href="comments.php" class="bg-navy-700 hover:bg-navy-600 text-slate-200 text-xs font-bold px-4 py-2.5 rounded-xl border border-navy-500 transition flex items-center gap-2">
                    <i class="fa-solid fa-comments text-gold-500"></i> Izohlar
                </a>
                <a href="logout.php" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-bold px-4 py-2.5 rounded-xl border border-red-500/30 transition">Chiqish</a>
            </div>
        </div>
    </header>

    <!-- ASOSIY BO'LIM -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white flex items-center gap-2">
                    <i class="fa-solid fa-tags text-gold-500"></i> Xizmatlar va Narxlar Katalogi
                </h1>
                <p class="text-slate-400 text-xs mt-1">Saytdagi prays-list va xizmatlar ma'lumotlarini tahrirlash</p>
            </div>
            <button onclick="openAddModal()" class="bg-gold-500 hover:bg-gold-400 text-navy-900 font-black px-5 py-2.5 rounded-xl text-xs transition shadow-lg">
                + Yangi Xizmat Qo'shish
            </button>
        </div>

        <!-- XIZMATLAR JADVALI -->
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
                        if ($services && $services->num_rows > 0) {
                            while($row = $services->fetch_assoc()) {
                                // HTML belgilar va kotirovkalarni to'g'ri o'tkazish uchun htmlspecialchars ishlatamiz
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
                                            <button onclick='editService({$json_row})' class='bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-500/40 text-xs font-bold px-3 py-1.5 rounded-lg transition'><i class='fa-solid fa-pen-to-square'></i> Tahrir</button>
                                            <button onclick='confirmDelete({$row['id']})' class='bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/40 text-xs font-bold px-3 py-1.5 rounded-lg transition'><i class='fa-solid fa-trash-can'></i> O'chirish</button>
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

    </main>

    <!-- YANGI XIZMAT QO'SHISH / TAHRIRLASH MODALI -->
    <div id="serviceModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
        <div class="glass-card bg-navy-800 border border-navy-500 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
            <div class="p-5 border-b border-navy-500 flex items-center justify-between bg-navy-900/60">
                <h3 id="modalTitle" class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-square-plus text-gold-500"></i> Xizmat Qo'shish / Tahrirlash
                </h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" id="serviceForm" class="p-6 space-y-4">
                <input type="hidden" name="service_id" id="service_id">
                <input type="hidden" name="add_service" id="form_action" value="1">

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Xizmat Nomi</label>
                    <input type="text" name="title" id="title" required placeholder="Masalan: Windows 11 o'rnatish" class="w-full bg-navy-900 border border-navy-500 focus:border-gold-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Xizmat Narxi</label>
                    <input type="text" name="price" id="price" required placeholder="Masalan: 100,000 so'm yoki Kelishuv asosida" class="w-full bg-navy-900 border border-navy-500 focus:border-gold-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">FontAwesome Ikonka Klassi</label>
                    <input type="text" name="icon" id="icon" value="fa-solid fa-laptop-code" required placeholder="fa-brands fa-windows" class="w-full bg-navy-900 border border-navy-500 focus:border-gold-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Xizmat Haqida Qisqacha Tavsif</label>
                    <textarea name="description" id="description" rows="3" placeholder="Xizmat tarkibiga nimalar kiradi..." class="w-full bg-navy-900 border border-navy-500 focus:border-gold-500 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none"></textarea>
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl bg-navy-700 text-slate-300 text-xs font-semibold">Bekor qilish</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-gold-500 to-amber-600 text-navy-900 text-xs font-black shadow-lg">Saqlash</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('serviceModal').classList.remove('hidden');
            document.getElementById('serviceForm').reset();
            document.getElementById('service_id').value = '';
            document.getElementById('form_action').name = 'add_service';
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-square-plus text-gold-500"></i> Yangi Xizmat Qo\'shish';
        }

        function closeModal() {
            document.getElementById('serviceModal').classList.add('hidden');
        }

        function editService(data) {
            document.getElementById('serviceModal').classList.remove('hidden');
            document.getElementById('service_id').value = data.id;
            document.getElementById('title').value = data.title;
            document.getElementById('price').value = data.price;
            document.getElementById('icon').value = data.icon;
            document.getElementById('description').value = data.description;
            
            document.getElementById('form_action').name = 'update_service';
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-gold-500"></i> Xizmatni Tahrirlash #' + data.id;
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'O\'chirilsinmi?',
                text: "Ushbu xizmat turi bazadan butunlay o'chiriladi!",
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
                    window.location.href = 'prices.php?delete_id=' + id;
                }
            });
        }
    </script>

</body>
</html>