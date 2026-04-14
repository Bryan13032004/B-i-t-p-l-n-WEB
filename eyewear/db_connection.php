<?php
// Tên file: db_connect.php
$host = "localhost";
$username = "root";
$password = "";
$database = "eyewear_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Lỗi kết nối CSDL: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>