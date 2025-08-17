<?php
// --- VERSION CHECK ---
// Phiên bản tối thiểu yêu cầu để truy cập server
$min_version_required = '1.0.5+5';

// Lấy phiên bản của client từ header
$client_version = isset($_SERVER['HTTP_X_APP_VERSION']) ? $_SERVER['HTTP_X_APP_VERSION'] : '0.0.0+0';

// So sánh phiên bản
// version_compare trả về -1 nếu phiên bản đầu tiên nhỏ hơn, 0 nếu bằng, 1 nếu lớn hơn.
if (version_compare($client_version, $min_version_required, '<')) {
    // Nếu phiên bản của client nhỏ hơn, trả về lỗi 426 Upgrade Required
    header('Content-Type: application/json');
    http_response_code(426); // Upgrade Required
    echo json_encode([
        'status' => 'error',
        'message' => 'Your app version is too old. Please update to the latest version to continue.',
        'required_version' => $min_version_required
    ]);
    die(); // Dừng thực thi tất cả các script khác
}
// --- END VERSION CHECK ---


// Thông tin kết nối database Aiven
$servername = "mysql-a2448e4-bachtuhoa20-0662.h.aivencloud.com";
$port       = 20704;
$username   = "avnadmin";
$password   = "AVNS_Vhe33AA3inpWDMgr1qO";
$dbname     = "defaultdb";

// Khởi tạo kết nối MySQLi với SSL
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
if (!mysqli_real_connect($conn, $servername, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array("status" => "error", "message" => "Lỗi kết nối database: " . mysqli_connect_error()));
    die();
}

// Thiết lập charset để hỗ trợ tiếng Việt
mysqli_set_charset($conn, "utf8mb4");

// Thiết lập múi giờ MySQL về giờ Việt Nam
mysqli_query($conn, "SET time_zone = '+07:00'");
?>