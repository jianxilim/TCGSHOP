<?php
session_start();
require_once("../linkdb.php");


if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $orderId = mysqli_real_escape_string($conn, $_GET['id']);
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    
    $allowed_statuses = ['shipped', 'completed', 'cancelled'];
    
    if (in_array($status, $allowed_statuses)) {
        $sql = "UPDATE orders SET Order_status = ? WHERE IDorder = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $status, $orderId);
        
        if (mysqli_stmt_execute($stmt)) {
          
            echo "<script>alert('Order #$orderId updated to $status'); window.location='admin_orders.php';</script>";
        } else {
            echo "Error updating record: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Invalid status.";
    }
} else {
    header("Location: admin_order_detail.php");
}
?>