<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

include 'connect.php';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>BR IT SERVICE - Buyurtmalar Hisoboti (PDF)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, sans-serif; background: #fff; padding: 20px; }
        h2 { text-align: center; font-weight: bold; color: #0f172a; margin-bottom: 20px; }
        .table th, .table td { vertical-align: middle !important; font-size: 13px; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body onload="window.print()">
    <div class="container-fluid">
        <h2><strong>BR IT SERVICE - Kelib Tushgan Buyurtmalar Hisoboti</strong></h2>
        
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Mijoz F.I.O</th>
                    <th>Telefon</th>
                    <th>Xizmat Turi</th>
                    <th>Muammo / Izoh</th>
                    <th>Status</th>
                    <th>Sana</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM orders ORDER BY id DESC";
                $result = $connection->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td><strong>#{$row['id']}</strong></td>
                            <td><strong>{$row['client_name']}</strong></td>
                            <td><strong>{$row['phone']}</strong></td>
                            <td>{$row['service_type']}</td>
                            <td>{$row['description']}</td>
                            <td><strong>{$row['status']}</strong></td>
                            <td>{$row['created_at']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center py-3 text-muted'>Buyurtmalar topilmadi</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="text-center no-print mt-4">
            <button onclick="window.print()" class="btn btn-primary fw-bold px-4 py-2">🖨️ PDF Saqlash / Chop etish</button>
            <a href="index.php" class="btn btn-secondary fw-bold px-4 py-2">⬅️ Orqaga Qaytish</a>
        </div>
    </div>
</body>
</html>