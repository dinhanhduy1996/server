<?php
// update_fcm_token.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

$student_id = $data['student_id'] ?? null;
$fcm_token = $data['fcm_token'] ?? null;

if ($student_id && $fcm_token) {
    $stmt = $conn->prepare("UPDATE students SET fcm_token = ? WHERE student_id = ?");
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("si", $fcm_token, $student_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Cập nhật FCM token thành công.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi khi cập nhật FCM token: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu student_id hoặc fcm_token.']);
}

$conn->close();
?>