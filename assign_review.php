<?php
// assign_review.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include 'db_connect.php';

// --- Function to get Access Token --- //
function get_access_token($credentials_path) {
    if (!file_exists($credentials_path)) {
        return null;
    }
    $credentials = json_decode(file_get_contents($credentials_path), true);

    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

    $now = time();
    $expiry = $now + 3600;

    $payload = base64_encode(json_encode([
        'iss' => $credentials['client_email'],
        'sub' => $credentials['client_email'],
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $expiry,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
    ]));

    $signature_input = $header . '.' . $payload;
    $private_key = openssl_get_privatekey($credentials['private_key']);
    openssl_sign($signature_input, $signature, $private_key, 'sha256');
    $jwt = $signature_input . '.' . base64_encode($signature);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return null;
    }
    curl_close($ch);

    $token_data = json_decode($result, true);
    return $token_data['access_token'] ?? null;
}

// --- Main Logic --- //

$data = json_decode(file_get_contents("php://input"), true);

// Validate input
$admin_id = $data['admin_id'] ?? null;
$student_id = $data['student_id'] ?? null;
$unit_id = $data['unit_id'] ?? null;
$number_of_words = $data['number_of_words'] ?? null;
$time_per_word = $data['time_per_word'] ?? null;
$review_sessions = $data['review_sessions'] ?? null;
$review_mode = $data['review_mode'] ?? null;

if (!$admin_id || !$student_id || !$unit_id || !$number_of_words || !$time_per_word || !$review_sessions || !$review_mode) {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu đầu vào không hợp lệ.']);
    exit();
}

// 1. Insert into review_assignments
$stmt = $conn->prepare("INSERT INTO review_assignments (admin_id, student_id, unit_id, number_of_words, time_per_word, review_sessions, review_mode) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiiiss", $admin_id, $student_id, $unit_id, $number_of_words, $time_per_word, $review_sessions, $review_mode);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi khi tạo bài ôn tập: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit();
}
$assignment_id = $stmt->insert_id;
$stmt->close();

// 2. Get student's FCM token
$stmt = $conn->prepare("SELECT fcm_token FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy học sinh hoặc học sinh chưa có FCM token.']);
    $stmt->close();
    $conn->close();
    exit();
}
$fcm_token = $result->fetch_assoc()['fcm_token'];
$stmt->close();

if (!$fcm_token) {
     echo json_encode(['status' => 'error', 'message' => 'Học sinh này chưa có FCM token để nhận thông báo.']);
     $conn->close();
     exit();
}

// 3. Send Push Notification via FCM v1
$credentials_path = __DIR__ . '/firebase_credentials.json';
$access_token = get_access_token($credentials_path);

if (!$access_token) {
    echo json_encode(['status' => 'error', 'message' => 'Không thể lấy access token để gửi thông báo.']);
    $conn->close();
    exit();
}

$credentials = json_decode(file_get_contents($credentials_path), true);
$project_id = $credentials['project_id'];
$fcm_url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";

$notification_payload = [
    'message' => [
        'token' => $fcm_token,
        'notification' => [
            'title' => 'Bài ôn tập mới!',
            'body' => 'Bạn vừa được giao một bài ôn tập từ vựng mới. Nhấn để bắt đầu ngay!'
        ],
        'data' => [
            'type' => 'review_assignment',
            'assignment_id' => strval($assignment_id)
        ]
    ]
];

$headers = [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fcm_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification_payload));

$fcm_result = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['status' => 'warning', 'message' => 'Đã tạo bài ôn tập nhưng không thể gửi thông báo: ' . curl_error($ch)]);
} else {
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code == 200) {
        echo json_encode(['status' => 'success', 'message' => 'Bài ôn tập đã được giao và thông báo đã được gửi.']);
    } else {
         echo json_encode(['status' => 'warning', 'message' => 'Đã tạo bài ôn tập nhưng có lỗi khi gửi thông báo.', 'fcm_response' => json_decode($fcm_result)]);
    }
}
curl_close($ch);

$conn->close();
?>