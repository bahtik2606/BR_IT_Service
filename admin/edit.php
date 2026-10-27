<?php
session_start();
date_default_timezone_set('Asia/Tashkent');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

include 'connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("location: index.php");
    exit;
}

// EDIT FORM UPDATE LOGIC
if (isset($_POST['save_edit'])) {
    $c_name  = $connection->real_escape_string($_POST['client_name']);
    $phone   = $connection->real_escape_string($_POST['phone']);
    $service = $connection->real_escape_string($_POST['service_type']);
    $desc    = $connection->real_escape_string($_POST['description']);

    $stmt = $connection->prepare("UPDATE orders SET client_name=?, phone=?, service_type=?, description=? WHERE id=?");
    $stmt->bind_param("ssssi", $c_name, $phone, $service, $desc, $id);
    $stmt->execute();

    header("location: index.php");
    exit;
}

// MA'LUMOTNI OLISH
$result = $connection->query("SELECT * FROM orders WHERE id=$id");
if (!$result || $result->num_rows == 0) {
    header("location: index.php");
    exit;
}

$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Buyurtmani Tahrirlash — BR IT SERVICE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { background-color: #0B132B; color: #F8FAFC; font-family: 'Inter', sans-serif; }
        
        select option {
            background-color: #ffffff !important;
            color: #0f172a !important;
            font-weight: 700 !important;
            padding: 12px !important;
        }

        /* TUGMALAR UCHUN SHaffoflik va NUR ANIMATSIYASI */
        @keyframes btnGlow {
            0%, 100% { box-shadow: 0 0 10px rgba(255, 215, 0, 0.4); }
            50% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.8); }
        }
        .btn-save-glow {
            animation: btnGlow 2.5s infinite ease-in-out;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="bg-navy-800 border-2 border-gold-500/50 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
        
        <!-- HEADER (TAHRIRLASH RAQAMI KATTALASHTIRILDI VA ALOHIDA RANG/NUR BERILDI) -->
        <div class="p-5 border-b border-navy-500 bg-navy-900/90 flex items-center justify-between">
            <h2 class="text-lg font-black text-white flex items-center gap-3">
                <i class="fa-solid fa-pen-to-square text-gold-500 text-2xl"></i> 
                <span>Buyurtmani Tahrirlash</span>
                <span class="bg-gradient-to-r from-gold-500 to-amber-500 text-navy-900 font-black text-sm px-3.5 py-1.5 rounded-xl shadow-lg border border-gold-400">#<?php echo $row['id']; ?></span>
            </h2>
            <a href="index.php" class="text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark text-2xl"></i></a>
        </div>

        <form method="POST" class="p-6 space-y-5">
            <input type="hidden" name="save_edit" value="1">
            
            <div>
                <label class="block text-xs font-black text-gold-400 uppercase mb-1.5">Mijoz F.I.O:</label>
                <input type="text" name="client_name" value="<?php echo htmlspecialchars($row['client_name']); ?>" required class="w-full bg-slate-900 border border-navy-500 focus:border-gold-500 rounded-xl px-4 py-3 text-sm text-white font-bold focus:outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-black text-gold-400 uppercase mb-1.5">Aloqa (Telefon / TG Nick / Email):</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars(strip_tags($row['phone'])); ?>" required class="w-full bg-slate-900 border border-navy-500 focus:border-gold-500 rounded-xl px-4 py-3 text-sm text-white font-bold focus:outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-black text-gold-400 uppercase mb-1.5">Xizmat Turi (12 tur):</label>
                <select name="service_type" required class="w-full bg-slate-900 border border-navy-500 focus:border-gold-500 rounded-xl px-4 py-3 text-xs text-white font-extrabold focus:outline-none cursor-pointer transition">
                    <?php
                    $services = [
                        "🎓 Kompyuter savodxonligi" => "🎓 Kompyuter savodxonligi",
                        "🏛 MyGov.uz va Davlat Xizmatlari" => "🏛 MyGov.uz va Davlat Xizmatlari (Portal yo'riqnomalari)",
                        "🌐 Web-saytlar Yaratish" => "🌐 Web-saytlar va Portallar Yaratish (VS Code, XAMPP)",
                        "🤖 Mikrokontroller (Arduino/IoT) va Elektronika" => "🤖 Mikrokontroller (Arduino/IoT) va Elektronika",
                        "🗄 Ma'lumotlar Bazasi" => "🗄 Ma'lumotlar Bazasi (Borland C++, MySQL)",
                        "📄 Ofis va Excel/Word Hujjatlari" => "📄 Ofis va Excel/Word Hujjatlari Xizmatlari",
                        "🎨 AI, Dizayn & Montaj" => "🎨 AI Rasm, Musiqa, Photoshop va Montaj",
                        "📱 Mobil Ilovalar" => "📱 Mobil Ilovalar Yaratish (Android Studio)",
                        "🪟 Windows & Drayverlar" => "🪟 Windows & Drayverlar O'rnatish",
                        "🔧 IT Tez Yordam" => "🔧 IT Tez Yordam / Masofaviy Yordam",
                        "🏠 Uyga borib xizmat" => "🏠 Uyga borib xizmat ko'rsatish",
                        "📡 Wi-Fi va Printer" => "📡 Wi-Fi, Internet va Printer Sozlash"
                    ];

                    foreach ($services as $key => $val) {
                        $selected = ($row['service_type'] == $key || strpos($row['service_type'], $key) !== false) ? 'selected' : '';
                        echo "<option value='{$key}' {$selected}>{$val}</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-black text-gold-400 uppercase mb-1.5">Izoh / Muammo:</label>
                <textarea name="description" rows="3" class="w-full bg-slate-900 border border-navy-500 focus:border-gold-500 rounded-xl px-4 py-3 text-sm text-white font-medium focus:outline-none transition"><?php echo htmlspecialchars($row['description']); ?></textarea>
            </div>

            <!-- TUGMALAR (BEKOR QILISH VA SAQLASH TUGMASIGA HAM BIRXIL OLTIN NUR VA HOSHIYA ANIMATSIYASI BERILDI) -->
            <div class="pt-4 flex items-center justify-end space-x-4 border-t border-navy-600">
                <a href="index.php" class="px-5 py-2.5 rounded-xl bg-red-500/20 hover:bg-red-500 text-red-400 hover:text-white border border-red-500/40 text-xs font-bold transition hover:scale-105">Bekor qilish</a>
                
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-gold-500 to-amber-600 hover:from-gold-400 hover:to-amber-500 text-navy-900 text-xs font-black shadow-lg transition-all duration-300 hover:scale-105 btn-save-glow">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Saqlash
                </button>
            </div>
        </form>
    </div>

</body>
</html>