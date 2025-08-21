


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