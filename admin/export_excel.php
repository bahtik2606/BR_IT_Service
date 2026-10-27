<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

include 'connect.php';

$filename = "buyurtmalar_royxati_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>
<html lang="uz">
<head>
    <meta charset="UTF-8">
</head>
<body>
    <table border="1">
        <tr style="background-color: #1e293b; color: #ffffff; font-weight: bold;">
            <th>ID</th>
            <th>Mijoz F.I.O</th>
            <th>Telefon</th>
            <th>Xizmat Turi</th>
            <th>Muammo / Izoh</th>
            <th>Status</th>
            <th>Kelib Tushgan Sana</th>
        </tr>
        <?php
        $sql = "SELECT * FROM orders ORDER BY id DESC";
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>#{$row['id']}</td>
                <td>{$row['client_name']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['service_type']}</td>
                <td>{$row['description']}</td>
                <td>{$row['status']}</td>
                <td>{$row['created_at']}</td>
            </tr>";
        }
        ?>
    </table>
</body>
</html>