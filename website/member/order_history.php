<?php
include("member_header.php"); 
require_once '../includes/linkdb.php'; 
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireMember();

$memberId = $_SESSION['user_id'];

$sql = "SELECT * FROM orders WHERE IDmember = ? ORDER BY Order_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $memberId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - Card Shop</title>
    <link rel="stylesheet" href="../member_css/order_history.css">
</head>
<body>
<div class="container">
    <h1>📋 My Orders</h1>
    
    <nav class="nav-links">
        <span class="order-count">Total Orders: <strong><?php echo count($orders); ?></strong></span>
    </nav>
    
    <hr class="divider">
    
    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <p>You haven't placed any orders yet.</p>
            <a href="products.php" class="btn-shop">Start Shopping Now →</a>
        </div>
    <?php else: ?>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td class="order-id">
                        #<?php echo htmlspecialchars($order['IDorder']); ?>
                    </td>
                    <td><?php echo date('d M Y, H:i', strtotime($order['Order_date'])); ?></td>
                    <td class="order-amount">
                        RM <?php echo number_format($order['Total_amount'], 2); ?>
                    </td>
                    <td>
                        <?php 
                            $status = $order['Order_status'];
                            $statusLabels = [
                                'pending' => '⏳ Pending',
                                'paid' => '✅ Paid',
                                'shipped' => '📦 Shipped',
                                'completed' => '✔️ Completed',
                                'cancelled' => '❌ Cancelled',
                                'cancel_pending' => '⚠️ Waiting for Approval'
                            ];
                            $label = $statusLabels[$status] ?? ucfirst($status);
                            echo "<span class='status-badge status-$status'>$label</span>";
                        ?>
                    </td>
                    <td>
                        <a href="member_order_detail.php?id=<?php echo $order['IDorder']; ?>" class="btn-view">Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>