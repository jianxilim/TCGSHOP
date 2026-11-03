<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("../linkdb.php"); 

if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

include("admin_header.php");


if (isset($_GET['update_id']) && isset($_GET['new_status'])) {
    $oid = mysqli_real_escape_string($conn, $_GET['update_id']);
    $status = mysqli_real_escape_string($conn, $_GET['new_status']);
    
    mysqli_query($conn, "UPDATE orders SET Order_status = '$status' WHERE IDorder = '$oid'");
    echo "<script>alert('Order #$oid updated to $status'); window.location='admin_order.php';</script>";
}


$sql = "SELECT o.*, m.Member_name, m.Email 
        FROM orders o 
        JOIN member m ON o.IDmember = m.IDmember 
        ORDER BY o.Order_date DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Orders - Admin Portal</title>
    <link rel="stylesheet" href="../admin_css/admin_order.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="admin-container">
    <h2><i class="fas fa-shopping-basket"></i> Order Management</h2>
    
    <table class="order-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Member Name</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): 
                $s = $row['Order_status']; 
            ?>
            <tr>
                <td><strong>#<?php echo $row['IDorder']; ?></strong></td>
                <td><?php echo date('d M Y, H:i', strtotime($row['Order_date'])); ?></td>
                <td><?php echo htmlspecialchars($row['Member_name']); ?></td>
                <td class="price-text">RM <?php echo number_format($row['Total_amount'], 2); ?></td>
                <td>
                    <span class="status-pill status-<?php echo $s; ?>">
                        <?php echo str_replace('_', ' ', ucfirst($s)); ?>
                    </span>
                </td>
                <td>
                    <a href="admin_order_detail.php?id=<?php echo $row['IDorder']; ?>" class="btn btn-view">
                        <i class="fas fa-eye"></i> View
                    </a>

                    <?php if($s == 'pending'): ?>
                        <a href="?update_id=<?php echo $row['IDorder']; ?>&new_status=paid" class="btn btn-done">
                            <i class="fas fa-check-circle"></i> Mark Paid
                        </a>

                    <?php elseif($s == 'paid'): ?>
                        <a href="?update_id=<?php echo $row['IDorder']; ?>&new_status=shipped" class="btn btn-ship">
                            <i class="fas fa-truck"></i> Ship Now
                        </a>

                    <?php elseif($s == 'shipped'): ?>
                        <a href="?update_id=<?php echo $row['IDorder']; ?>&new_status=completed" class="btn btn-done">
                            <i class="fas fa-flag-checkered"></i> Complete
                        </a>

                    <?php elseif($s == 'cancel_pending'): ?>
                        <a href="?update_id=<?php echo $row['IDorder']; ?>&new_status=cancelled" 
                           class="btn btn-approve" 
                           onclick="return confirm('Confirm Cancellation for Order #<?php echo $row['IDorder']; ?>?')">
                            <i class="fas fa-times-circle"></i> Approve Cancel
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>