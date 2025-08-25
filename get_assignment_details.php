<?php
// get_assignment_details.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include 'db_connect.php';

$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

if ($assignment_id > 0) {
    $stmt = $conn->prepare("
        SELECT ra.*, u.unit_name 
        FROM review_assignments ra
        JOIN units u ON ra.unit_id = u.unit_id
        WHERE ra.assignment_id = ?
    ");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $assignment_details = $result->fetch_assoc();
        $json_response = json_encode(['status' => 'success', 'data' => $assignment_details]);
        error_log("DEBUG PHP: get_assignment_details.php response: " . $json_response);
        echo $json_response;
    } else {
        $json_response = json_encode(['status' => 'error', 'message' => 'Không tìm thấy bài ôn tập.']);
        error_log("DEBUG PHP: get_assignment_details.php response: " . $json_response);
        echo $json_response;
    }
    $stmt->close();
} else {
    $json_response = json_encode(['status' => 'error', 'message' => 'Thiếu assignment_id.']);
    error_log("DEBUG PHP: get_assignment_details.php response: " . $json_response);
    echo $json_response;
}

$conn->close();
?>