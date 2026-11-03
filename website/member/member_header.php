<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/linkdb.php'; 


$cartCount = 0;
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'member') {
    $memberId = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conn, "SELECT SUM(Quantity) AS total FROM cart WHERE IDmember = ?");
    mysqli_stmt_bind_param($stmt, "s", $memberId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    $cartCount = $row['total'] ?? 0;
    mysqli_stmt_close($stmt);
}
?>
<link rel="stylesheet" href="../member_css/member_header.css">

<header class="header">
    <a href="products.php" class="logo">
        <span>🛒 Online Shop</span>
    </a>

    <nav class="nav">
        <a href="products.php">Products</a>
        <a href="order_history.php">My Orders</a>
        <a href="profile.php">Profile</a>
        <a href="wishlist.php">Wishlist</a>
        
        <a href="cart.php" class="cart">
            Cart (<span id="cart-count"><?php echo $cartCount; ?></span>)
        </a>

        <a href="logout.php" class="logout">Logout</a>
    </nav>
</header>