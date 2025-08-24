<?php
// update_review_status.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

$assignment_id = $data['assignment_id'] ?? null;
$status = $data['status'] ?? null;

if ($assignment_id && $status) {
    $stmt = $conn->prepare("UPDATE review_assignments SET status = ? WHERE assignment_id = ?");
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("si", $status, $assignment_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Cập nhật trạng thái thành công.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi khi cập nhật trạng thái: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu assignment_id hoặc status.']);
}

$conn->close();
?>