<?php
require_once '../includes/linkdb.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isMember()) {
    echo json_encode(['count' => 0]);
    exit();
}

$memberId = getCurrentUserId();


$sql = "SELECT SUM(Quantity) as total FROM cart WHERE IDmember='$memberId'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

echo json_encode([
    'count' => $row['total'] ?? 0
]);
?>