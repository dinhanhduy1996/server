<?php
header('Content-Type: application/json');
require 'db_connect.php';

// Lấy tất cả học sinh, loại trừ tài khoản admin
$query = "SELECT student_id, student_name, username FROM students WHERE username != 'admin' ORDER BY student_name ASC";

$result = mysqli_query($conn, $query);

if ($result) {
    $students = array();
    while($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $students]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch students.']);
}

mysqli_close($conn);
?>
