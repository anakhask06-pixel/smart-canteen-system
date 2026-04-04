<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

// Auto-detect schema
$cols = $conn->query("SHOW COLUMNS FROM orders")->fetch_all(MYSQLI_ASSOC);
$colNames = array_column($cols, 'Field');
$useNewSchema = in_array('user_id', $colNames);

$rows = [];

if ($useNewSchema && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare(
        "SELECT order_group,
         CASE WHEN SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END)=0 THEN 'Ready' ELSE 'Pending' END AS status
         FROM orders WHERE user_id=? GROUP BY order_group ORDER BY order_group DESC LIMIT 20"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} elseif (!empty($_GET['admission_no'])) {
    $admission_no = trim($_GET['admission_no']);

    if ($useNewSchema) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE admission_no=?");
        $stmt->bind_param("s", $admission_no);
        $stmt->execute();
        $userRow = $stmt->get_result()->fetch_assoc();
        if ($userRow) {
            $uid = $userRow['user_id'];
            $stmt2 = $conn->prepare(
                "SELECT order_group,
                 CASE WHEN SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END)=0 THEN 'Ready' ELSE 'Pending' END AS status
                 FROM orders WHERE user_id=? GROUP BY order_group ORDER BY order_group DESC LIMIT 20"
            );
            $stmt2->bind_param("i", $uid);
            $stmt2->execute();
            $rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE admission_no=?");
        $stmt->bind_param("s", $admission_no);
        $stmt->execute();
        $studentRow = $stmt->get_result()->fetch_assoc();
        if ($studentRow) {
            $sid = $studentRow['student_id'];
            $stmt2 = $conn->prepare(
                "SELECT order_group,
                 CASE WHEN SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END)=0 THEN 'Ready' ELSE 'Pending' END AS status
                 FROM orders WHERE student_id=? GROUP BY order_group ORDER BY order_group DESC LIMIT 20"
            );
            $stmt2->bind_param("i", $sid);
            $stmt2->execute();
            $rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}

echo json_encode($rows);
?>