<?php
// Thông tin kết nối database
$servername = "127.0.0.1";
$username = getenv("db_user");
$password = getenv("db_pass");
$dbname   = getenv("db_name");

// Tạo kết nối bằng MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array("status" => "error", "message" => "Lỗi kết nối database: " . $conn->connect_error));
    die();
}

// Thiết lập charset để hỗ trợ tiếng Việt
$conn->set_charset("utf8mb4");

// Thiết lập múi giờ MySQL về giờ Việt Nam
$conn->query("SET time_zone = '+07:00'");
?>
