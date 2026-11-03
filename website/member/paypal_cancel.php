<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


unset($_SESSION['paypal_order_id']);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Cancelled</title>
</head>
<body>
    <h1>Payment Cancelled</h1>
    <p>You have cancelled the payment. No charges have been made.</p>
    <p><a href="checkout.php">Return to Checkout</a> | <a href="products.php">Continue Shopping</a></p>
</body>
</html>
<?php
exit();
?>