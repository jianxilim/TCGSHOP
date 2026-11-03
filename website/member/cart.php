<?php
include("member_header.php"); 
require_once '../includes/linkdb.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}
$memberId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $cartId => $quantity) {
            $cartId = mysqli_real_escape_string($conn, $cartId);
            $quantity = max(1, intval($quantity)); 
            mysqli_query($conn, "UPDATE cart SET Quantity='$quantity' WHERE IDcart='$cartId' AND IDmember='$memberId'");
        }
        header("Location: cart.php"); exit();
    }
    if (isset($_POST['remove_item'])) {
        $cartId = mysqli_real_escape_string($conn, $_POST['remove_item']);
        mysqli_query($conn, "DELETE FROM cart WHERE IDcart='$cartId' AND IDmember='$memberId'");
        header("Location: cart.php"); exit();
    }
    if (isset($_POST['clear_cart'])) {
        mysqli_query($conn, "DELETE FROM cart WHERE IDmember='$memberId'");
        header("Location: cart.php"); exit();
    }
}

$sql = "SELECT c.IDcart, c.Quantity, p.IDproduct, p.Product_name, p.Price, p.Image, p.Stock 
        FROM cart c JOIN products p ON c.IDproduct = p.IDproduct WHERE c.IDmember = '$memberId'";
$result = mysqli_query($conn, $sql);
$cartItems = mysqli_fetch_all($result, MYSQLI_ASSOC);

$subtotal = 0;
foreach ($cartItems as $item) { $subtotal += $item['Price'] * $item['Quantity']; }
$tax = $subtotal * 0.06;
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Shopping Cart</title>
    <link rel="stylesheet" href="../member_css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="cart-container">
    <h2><i class="fas fa-shopping-basket"></i> Your Shopping Cart</h2>

    <?php if (empty($cartItems)): ?>
       <div class="empty-cart">
            <i class="fas fa-cart-arrow-down fa-4x empty-icon"></i>
            <p class="empty-msg">Your cart is feeling a bit light...</p>
            <a href="products.php" class="btn btn-update empty-btn">Go Shopping Now</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <img src="../images/<?php echo $item['Image'] ?: 'default_product.png'; ?>" onerror="this.src='../images/default_product.png'">
                                <span><?php echo htmlspecialchars($item['Product_name']); ?></span>
                            </div>
                        </td>
                        <td>RM <?php echo number_format($item['Price'], 2); ?></td>
                        <td>
                            <input type="number" name="quantity[<?php echo $item['IDcart']; ?>]" class="qty-input" value="<?php echo $item['Quantity']; ?>" min="1" max="<?php echo $item['Stock']; ?>">
                        </td>
                        <td><strong>RM <?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></strong></td>
                        <td>
                            <button type="submit" name="remove_item" value="<?php echo $item['IDcart']; ?>" class="btn btn-remove" onclick="return confirm('Remove this item?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-footer">
                <div class="footer-btns">
                    <button type="submit" name="update_cart" class="btn btn-update">
                        <i class="fas fa-sync-alt"></i> Update Quantities
                    </button>
                    <button type="submit" name="clear_cart" class="btn btn-clear" onclick="return confirm('Empty your cart?')">
                        <i class="fas fa-eraser"></i> Empty Cart
                    </button>
                </div>

                <div class="summary-card">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>RM <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (6%):</span>
                        <span>RM <?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row total-row">
                        <span>Total:</span>
                        <span>RM <?php echo number_format($total, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">
                        Checkout <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>