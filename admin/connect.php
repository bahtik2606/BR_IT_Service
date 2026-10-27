<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "brit_service";

$connection = new mysqli($servername, $username, $password, $database);

if ($connection->connect_error) {
    die("Ulanishda xatolik: " . $connection->connect_error);
}
$connection->set_charset("utf8mb4");
?>