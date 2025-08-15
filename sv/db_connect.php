<?php
// Thông tin kết nối database từ biến môi trường
$servername = getenv("DB_HOST");
$port       = getenv("DB_PORT");
$username   = getenv("DB_USER");
$password   = getenv("DB_PASS");
$dbname     = getenv("DB_NAME");

// Khởi tạo kết nối MySQLi với SSL
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Kết nối tới MySQL (Aiven yêu cầu SSL)
if (!mysqli_real_connect($conn, $servername, $username, $password, $dbname, (int)$port, NULL, MYSQLI_CLIENT_SSL)) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array(
        "status" => "error",
        "message" => "Lỗi kết nối database: " . mysqli_connect_error()
    ));
    die();
}

// Thiết lập charset để hỗ trợ tiếng Việt
mysqli_set_charset($conn, "utf8mb4");

// Thiết lập múi giờ MySQL về giờ Việt Nam
mysqli_query($conn, "SET time_zone = '+07:00'");
?>