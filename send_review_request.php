<?php
ob_start(); // Bắt đầu bộ đệm đầu ra
error_reporting(0); // Tắt hiển thị lỗi
ini_set('display_errors', 0); // Tắt hiển thị lỗi
header('Content-Type: application/json');
require 'db_connect.php'; // Sử dụng file kết nối CSDL của bạn

// Lấy dữ liệu JSON từ body của request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Lấy thông tin từ dữ liệu POST
$admin_id = $data['admin_id'] ?? null;
$student_username = $data['student_username'] ?? null;
$class_name = $data['class_name'] ?? null;
$unit_name = $data['unit_name'] ?? null;
$word_count = $data['word_count'] ?? null;
$review_mode = $data['review_mode'] ?? null;

if (!$admin_id || !$student_username) {
    http_response_code(400);
    ob_clean(); // Xóa bộ đệm trước khi xuất JSON
    echo json_encode(['success' => false, 'message' => 'Admin ID and Student Username are required']);
    exit();
}

// 1. Lấy thông tin student, bao gồm cả fcm_token
$stmt = $conn->prepare("SELECT id, fcm_token FROM users WHERE username = ?");
$stmt->bind_param("s", $student_username);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    http_response_code(404);
    ob_clean(); // Xóa bộ đệm trước khi xuất JSON
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}
$student_id = $student['id'];
$fcm_token = $student['fcm_token'];

// 2. Lưu yêu cầu vào CSDL
$stmt = $conn->prepare("INSERT INTO review_assignments (admin_id, student_id, class_name, unit_name, word_count, review_mode) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iisiss", $admin_id, $student_id, $class_name, $unit_name, $word_count, $review_mode);

if ($stmt->execute()) {
    $assignment_id = $stmt->insert_id; // Lấy ID của yêu cầu vừa tạo

    // 3. Gọi Node.js service để gửi FCM nếu có token
    if ($fcm_token) {
        $node_service_url = 'https://serversender.onrender.com/send-review-notification';

        $reviewData = [
            'assignment_id' => (string)$assignment_id,
            'class_name' => $class_name,
            'unit_name' => $unit_name,
            'word_count' => (string)$word_count,
            'review_mode' => $review_mode,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];

        $postData = [
            'fcmToken' => $fcm_token,
            'title' => 'Yêu cầu ôn tập mới',
            'body' => "Giáo viên yêu cầu bạn ôn tập Unit: $unit_name",
            'reviewData' => $reviewData
        ];

        $ch = curl_init($node_service_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_exec($ch);
        // Không kiểm tra lỗi curl ở đây để tránh block response cho client
        curl_close($ch);
    }

    ob_clean(); // Xóa bộ đệm trước khi xuất JSON
    echo json_encode(['success' => true, 'message' => 'Review request sent successfully.', 'assignment_id' => $assignment_id]);
} else {
    http_response_code(500);
    ob_clean(); // Xóa bộ đệm trước khi xuất JSON
    echo json_encode(['success' => false, 'message' => 'Failed to save review assignment.']);
}

$stmt->close();
$conn->close();
?>
