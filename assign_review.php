<?php
// assign_review.php (FINAL - Calls Node.js Microservice)

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include 'db_connect.php';

// This URL points to your new Node.js sender service on Render
define('FCM_SENDER_URL', 'https://serversender.onrender.com/send-fcm'); 

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input.');
    }

    $admin_id = $data['admin_id'] ?? null;
    $student_id = $data['student_id'] ?? null;
    $unit_id = $data['unit_id'] ?? null;
    $number_of_words = $data['number_of_words'] ?? null;
    $time_per_word = $data['time_per_word'] ?? null;
    $review_sessions = $data['review_sessions'] ?? null;
    $review_mode = $data['review_mode'] ?? null;

    if (!$admin_id || !$student_id || !$unit_id || !$number_of_words || !$time_per_word || !$review_sessions || !$review_mode) {
        throw new Exception('Dữ liệu đầu vào không hợp lệ.');
    }

    // Step 1: Save the assignment to the database
    $stmt = $conn->prepare("INSERT INTO review_assignments (admin_id, student_id, unit_id, number_of_words, time_per_word, review_sessions, review_mode) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiiss", $admin_id, $student_id, $unit_id, $number_of_words, $time_per_word, $review_sessions, $review_mode);
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi tạo bài ôn tập: ' . $stmt->error);
    }
    $assignment_id = $stmt->insert_id;
    $stmt->close();

    // Step 2: Get the student's FCM token
    $stmt = $conn->prepare("SELECT fcm_token FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Không tìm thấy học sinh.');
    }
    $fcm_token = $result->fetch_assoc()['fcm_token'];
    $stmt->close();

    if (empty($fcm_token)) {
        throw new Exception('Học sinh này chưa có FCM token để nhận thông báo.');
    }

    // Step 3: Call the Node.js microservice to send the notification
    // Ensure all data values are strings, as required by FCM
    $fcm_data_payload = [
        'type' => 'review_assignment',
        'assignment_id' => strval($assignment_id),
        'target_student_id' => strval($student_id) // Ensure this is a string
    ];

    $post_data = json_encode([
        'token' => $fcm_token,
        'title' => 'Bài ôn tập mới!',
        'body' => 'Bạn vừa được giao một bài ôn tập từ vựng mới. Nhấn để bắt đầu ngay!',
        'data' => $fcm_data_payload
    ]);

    $ch = curl_init(FCM_SENDER_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($post_data)]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        echo json_encode(['status' => 'success', 'message' => 'Bài ôn tập đã được giao và thông báo đã được gửi.']);
    } else {
        throw new Exception('Đã tạo bài ôn tập nhưng có lỗi khi gửi thông báo. FCM Service Response: ' . $response);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>