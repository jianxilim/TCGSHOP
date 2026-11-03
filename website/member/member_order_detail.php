<?php
include("member_header.php"); 
require_once dirname(__DIR__) . '/includes/linkdb.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireMember();

$orderId = $_GET['id'] ?? '';
$memberId = $_SESSION['user_id'];

if (empty($orderId)) { 
    header("Location: order_history.php"); 
    exit(); 
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_cancel_request'])) {
    $reason = mysqli_real_escape_string($conn, $_POST['cancel_reason']);
    
    $updateSql = "UPDATE orders SET Order_status = 'cancel_pending', Cancel_reason = ? 
                  WHERE IDorder = ? AND IDmember = ? AND Order_status IN ('pending', 'paid')";
    $stmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($stmt, "sss", $reason, $orderId, $memberId);
    
    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        $msg = "<div class='msg success-msg'>✅ Cancellation request submitted. Waiting for Admin approval.</div>";
    } else {
        $msg = "<div class='msg error-msg'>❌ Failed to submit request or order status not eligible.</div>";
    }
}

$sql = "SELECT * FROM orders WHERE IDorder = ? AND IDmember = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $orderId, $memberId);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header("Location: order_history.php");
    exit();
}

$sql2 = "SELECT oi.*, p.Product_name FROM order_items oi 
         JOIN products p ON oi.IDproduct = p.IDproduct 
         WHERE oi.IDorder = ?";
$stmt2 = mysqli_prepare($conn, $sql2);
mysqli_stmt_bind_param($stmt2, "s", $orderId);
mysqli_stmt_execute($stmt2);
$items = mysqli_fetch_all(mysqli_stmt_get_result($stmt2), MYSQLI_ASSOC);

$canRequestCancel = in_array($order['Order_status'], ['pending', 'paid']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Detail #<?php echo $orderId; ?></title>
    <link rel="stylesheet" href="../member_css/member_order_detail.css">
</head>
<body>
<div class="container">
    <a href="order_history.php" class="back-link">⬅ Back to My Orders</a>
    
    <h1>Order #<?php echo htmlspecialchars($orderId); ?></h1>
    
    <?php echo $msg; ?>

    <div class="status-banner status-<?php echo $order['Order_status']; ?>">
        <span class="status-label">Status: 
            <?php echo ($order['Order_status'] == 'cancel_pending') ? "⚠️ Waiting for Approval" : ucfirst($order['Order_status']); ?>
        </span>
        <span class="date-label">Date: <?php echo date('d M Y, H:i', strtotime($order['Order_date'])); ?></span>
    </div>

    <div class="address-container">
        <h4>Shipping Address:</h4>
        <p class="address-text">
            <?php echo nl2br(htmlspecialchars($order['Shipping_address'])); ?>
        </p>
    </div>

    <table class="detail-table">
        <thead>
            <tr>
                <th>Product Name</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): 
                $subtotal = $item['Price'] * $item['Quantity'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['Product_name']); ?></td>
                <td class="text-center"><?php echo $item['Quantity']; ?></td>
                <td class="text-right">RM <?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-right label-cell">Total Amount:</td>
                <td class="total-row">RM <?php echo number_format($order['Total_amount'], 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if ($canRequestCancel): ?>
    <div class="cancel-box">
        <h3>🚨 Need to cancel this order?</h3>
        <p class="helper-text">Please provide a reason for the Admin to review.</p>
        <form method="POST">
            <textarea name="cancel_reason" required placeholder="Reason for cancellation..."></textarea>
            <button type="submit" name="submit_cancel_request" class="btn-submit-cancel">Submit Cancellation Request</button>
        </form>
    </div>
    <?php elseif ($order['Order_status'] == 'cancel_pending'): ?>
        <div class="cancel-box status-pending-approval">
            <h3 class="pending-title">Cancellation Request Pending</h3>
            <p><strong>Your Reason:</strong> <?php echo htmlspecialchars($order['Cancel_reason']); ?></p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>