<?php

include("member_header.php"); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/includes/linkdb.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'member') {
    header("Location: login_register.php");
    exit();
}

$memberId = $_SESSION['user_id'];
$errors = [];


$sql = "SELECT c.IDcart, c.Quantity, p.IDproduct, p.Product_name, p.Price, p.Stock 
        FROM cart c 
        JOIN products p ON c.IDproduct = p.IDproduct 
        WHERE c.IDmember = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $memberId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$cartItems = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cartItems[] = $row;
}

if (empty($cartItems) && !isset($_GET['success'])) {
    echo "<script>alert('Your cart is empty'); window.location='products.php';</script>";
    exit();
}


$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['Price'] * $item['Quantity'];
}
$tax = $subtotal * 0.06;
$total = $subtotal + $tax;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    
    if (empty($shippingAddress)) {
        $errors['address'] = 'Please enter a shipping address.';
    }

    if (empty($errors)) {
    
        $_SESSION['temp_order'] = [
            'total' => $total,
            'shipping_address' => $shippingAddress,
            'items' => $cartItems,
            'user_id' => $memberId
        ];

        header("Location: paypal_checkout.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Card Shop</title>
    <link rel="stylesheet" href="../member_css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="checkout-container">
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            <h1><i class="fas fa-check-circle"></i> Payment Successful!</h1>
            <p>Thank you! Your Order <strong><?php echo htmlspecialchars($_GET['oid']); ?></strong> is being processed.</p>
            <a href="order_history.php" class="btn-paypal">View My Orders</a>
        </div>
    <?php else: ?>
        <h1>Secure Checkout</h1>
        
        <h2>1. Order Summary</h2>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['Product_name']); ?></td>
                    <td><?php echo $item['Quantity']; ?></td>
                    <td>RM <?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Tax (6%)</td>
                    <td>RM <?php echo number_format($tax, 2); ?></td>
                </tr>
                <tr class="total-section">
                    <td colspan="2"><strong>Grand Total</strong></td>
                    <td class="grand-total">RM <?php echo number_format($total, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <h2>2. Shipping Information</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-banner"><?php echo reset($errors); ?></div>
        <?php endif; ?>

        <form method="POST" class="shipping-info">
            <div class="form-group">
                <label for="shipping_address">Delivery Address</label>
                <textarea 
                    name="shipping_address" 
                    rows="4" 
                    placeholder="Enter your full shipping address here..." 
                    required><?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?></textarea>
            </div>

            <button type="submit" name="place_order" class="btn-paypal">
                <i class="fab fa-paypal"></i> Proceed to PayPal RM <?php echo number_format($total, 2); ?>
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>