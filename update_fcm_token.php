<?php
header('Content-Type: application/json');
require 'db_connect.php';

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$user_id = $data['user_id'] ?? null;
$fcm_token = $data['fcm_token'] ?? null;

if (!$user_id || !$fcm_token) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and FCM Token are required']);
    exit();
}

$stmt = $conn->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
$stmt->bind_param("si", $fcm_token, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'FCM token updated successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update FCM token.']);
}

$stmt->close();
$conn->close();
?>
