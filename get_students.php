<?php
// get_students.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db_connect.php';

// Lấy danh sách học sinh (không bao gồm admin)
$sql = "SELECT student_id, student_name, username FROM students WHERE username != 'admin' ORDER BY student_name ASC";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        $students = array();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $students]);
    } else {
        echo json_encode(['status' => 'success', 'data' => []]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi truy vấn database: ' . $conn->error]);
}

$conn->close();
?>