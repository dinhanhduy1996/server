<?php
// get_student_review_status.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include 'db_connect.php';

$admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;

if ($admin_id > 0) {
    // Lấy tất cả các bài tập được giao bởi admin này, cùng với tên học sinh
    $stmt = $conn->prepare("
        SELECT 
            ra.assignment_id, 
            ra.student_id, 
            s.student_name, 
            u.unit_name,
            ra.status,
            ra.created_at
        FROM review_assignments ra
        JOIN students s ON ra.student_id = s.student_id
        JOIN units u ON ra.unit_id = u.unit_id
        WHERE ra.admin_id = ?
        ORDER BY ra.created_at DESC
    ");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $assignments = array();
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $assignments]);
    } else {
        echo json_encode(['status' => 'success', 'data' => []]); // Trả về mảng rỗng nếu không có bài tập nào
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu admin_id.']);
}

$conn->close();
?>