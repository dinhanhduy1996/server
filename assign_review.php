<?php
// assign_review.php (FINAL VERSION)

ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include 'db_connect.php';

function send_json_response($data) {
    $stray_output = ob_get_clean();
    if (!isset($data['status'])) {
        $data['status'] = 'error';
    }
    if (!empty($stray_output) && !isset($data['stray_output'])) {
        $data['stray_output'] = $stray_output;
    }
    if (!headers_sent()) {
        header("Content-Type: application/json; charset=UTF-8");
    }
    echo json_encode($data);
    exit();
}

function get_access_token($credentials_path) {
    if (!file_exists($credentials_path)) {
        throw new Exception('Firebase credentials file not found.');
    }
    $credentials = json_decode(file_get_contents($credentials_path), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in credentials file: ' . json_last_error_msg());
    }

    // --- Robust Private Key Formatting ---
    $key_string_from_json = $credentials['private_key'];
    // Remove header, footer, and all newlines/whitespace
    $key_body = str_replace('-----BEGIN PRIVATE KEY-----', '', $key_string_from_json);
    $key_body = str_replace('-----END PRIVATE KEY-----', '', $key_body);
    $key_body = preg_replace('/
+/', '', $key_body);
    // Rebuild the key in valid PEM format with 64-character line breaks
    $pem = "-----BEGIN PRIVATE KEY-----\\n"
         . chunk_split($key_body, 64, "\\n")
         . "-----END PRIVATE KEY-----\\n";

    $private_key = openssl_pkey_get_private($pem);
    if ($private_key === false) {
        throw new Exception('Could not get private key. OpenSSL Error: ' . openssl_error_string());
    }

    $jwt_header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $jwt_claim_set = [
        'iss' => $credentials['client_email'],
        'sub' => $credentials['client_email'],
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => time(),
        'exp' => time() + 3500,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
    ];

    $header_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($jwt_header)));
    $payload_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($jwt_claim_set)));
    $signature_input = $header_encoded . '.' . $payload_encoded;
    
    $signature = '';
    if (!openssl_sign($signature_input, $signature, $private_key, 'sha256')) {
        throw new Exception('Failed to sign the JWT: ' . openssl_error_string());
    }
    openssl_free_key($private_key);

    $jwt = $signature_input . '.' . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('cURL error when getting access token: ' . curl_error($ch));
    }
    curl_close($ch);

    $token_data = json_decode($result, true);
    if (!isset($token_data['access_token'])) {
        throw new Exception('Failed to get access token from Google. Response: ' . $result);
    }
    return $token_data['access_token'];
}

// --- Main Logic ---
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

    $stmt = $conn->prepare("INSERT INTO review_assignments (admin_id, student_id, unit_id, number_of_words, time_per_word, review_sessions, review_mode) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiiss", $admin_id, $student_id, $unit_id, $number_of_words, $time_per_word, $review_sessions, $review_mode);
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi tạo bài ôn tập: ' . $stmt->error);
    }
    $assignment_id = $stmt->insert_id;
    $stmt->close();

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

    $credentials_path = __DIR__ . '/firebase_credentials.json';
    $access_token = get_access_token($credentials_path);

    $credentials = json_decode(file_get_contents($credentials_path), true);
    $project_id = $credentials['project_id'];
    $fcm_url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";

    $notification_payload = [
        'message' => [
            'token' => $fcm_token,
            'notification' => ['title' => 'Bài ôn tập mới!', 'body' => 'Bạn vừa được giao một bài ôn tập từ vựng mới. Nhấn để bắt đầu ngay!'],
            'data' => ['type' => 'review_assignment', 'assignment_id' => strval($assignment_id)]
        ]
    ];

    $headers = ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fcm_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification_payload));

    $fcm_result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        throw new Exception('Đã tạo bài ôn tập nhưng không thể gửi thông báo (cURL error): ' . curl_error($ch));
    }
    curl_close($ch);

    if ($http_code == 200) {
        send_json_response(['status' => 'success', 'message' => 'Bài ôn tập đã được giao và thông báo đã được gửi.']);
    } else {
        throw new Exception('Đã tạo bài ôn tập nhưng có lỗi khi gửi thông báo. FCM Response: ' . $fcm_result);
    }

} catch (Exception $e) {
    send_json_response(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>