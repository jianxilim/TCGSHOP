<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("../includes/linkdb.php"); 

if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

include("admin_header.php");

$orderId = $_GET['id'] ?? '';
if (empty($orderId)) { header("Location: admin_order.php"); exit(); }

$sqlOrder = "SELECT o.*, m.Member_name, m.Email 
             FROM orders o 
             JOIN member m ON o.IDmember = m.IDmember 
             WHERE o.IDorder = '$orderId'";
$resOrder = mysqli_query($conn, $sqlOrder);
$order = mysqli_fetch_assoc($resOrder);

if (!$order) {
    header("Location: admin_order.php");
    exit();
}

$sqlItems = "SELECT oi.*, p.Product_name 
             FROM order_items oi 
             JOIN products p ON oi.IDproduct = p.IDproduct 
             WHERE oi.IDorder = '$orderId'";
$resItems = mysqli_query($conn, $sqlItems);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Order Detail - #<?php echo $orderId; ?></title>
    <link rel="stylesheet" href="../admin_css/admin_order_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="detail-container">
    <div class="detail-header">
        <h2><i class="fas fa-file-invoice"></i> Order #<?php echo $orderId; ?></h2>
        <span class="status-badge status-<?php echo $order['Order_status']; ?>">
            <?php echo str_replace('_', ' ', ucfirst($order['Order_status'])); ?>
        </span>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <strong>Customer Name</strong>
            <span><?php echo htmlspecialchars($order['Member_name']); ?></span>
        </div>
        <div class="info-item">
            <strong>Email Address</strong>
            <span><?php echo htmlspecialchars($order['Email']); ?></span>
        </div>
        
        <div class="info-item shipping-span">
            <strong>Shipping Address</strong>
            <div class="address-box">
                <i class="fas fa-truck"></i>
                <?php echo nl2br(htmlspecialchars($order['Shipping_address'])); ?>
            </div>
        </div>
    </div>

    <?php if($order['Order_status'] == 'cancel_pending'): ?>
    <div class="reason-box">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Cancellation Request Reason:</strong>
        <p class="reason-text">
            <?php echo nl2br(htmlspecialchars($order['Cancel_reason'])); ?>
        </p>
    </div>
    <?php endif; ?>

    <h3 class="section-title"><i class="fas fa-box-open"></i> Order Content</h3>
    
    <table class="item-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            while($item = mysqli_fetch_assoc($resItems)): 
                $subtotal = $item['Price'] * $item['Quantity'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['Product_name']); ?></td>
                <td>RM <?php echo number_format($item['Price'], 2); ?></td>
                <td><?php echo $item['Quantity']; ?></td>
                <td class="text-right"><strong>RM <?php echo number_format($subtotal, 2); ?></strong></td>
            </tr>
            <?php endwhile; ?>
            
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                <td class="text-right">
                    <strong class="grand-total-price">RM <?php echo number_format($order['Total_amount'], 2); ?></strong>
                </td>
            </tr>
        </tbody>
    </table>
    
    <div class="footer-actions">
        <a href="admin_order.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Orders List
        </a>
    </div>
</div>

</body>
</html>