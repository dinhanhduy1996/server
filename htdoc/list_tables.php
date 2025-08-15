<?php
include 'db_connect.php';

$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        echo "Các bảng trong database 'quiz_app_db':\n";
        while($row = $result->fetch_array()) {
            echo $row[0] . "\n";
        }
    } else {
        echo "Không tìm thấy bảng nào trong database 'quiz_app_db'.\n";
    }
} else {
    echo "Lỗi khi truy vấn database: " . $conn->error . "\n";
}

$conn->close();
?>