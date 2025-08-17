<?php
header('Content-Type: application/json');
include 'db_connect.php'; // Kết nối đến database

// Nhận dữ liệu từ Flutter
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu admin_id.']);
    exit;
}

$adminId = $data['admin_id'];

// Câu lệnh SQL để xóa tất cả thông báo của admin
$sql = "DELETE FROM notifications WHERE student_id = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi khi chuẩn bị câu lệnh: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $adminId);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Đã xóa tất cả thông báo.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi khi xóa thông báo: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
