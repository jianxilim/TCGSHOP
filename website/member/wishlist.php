<?php
include("member_header.php"); 
require_once '../includes/linkdb.php'; 
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireMember();

// Initialize wishlist in session
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $productId = $_POST['product_id'];
    
    if (!in_array($productId, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $productId;
        setMessage('Added to wishlist');
    } else {
        setMessage('Product already in wishlist', 'error');
    }
    redirect('wishlist.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist'])) {
    $productId = $_POST['product_id'];
    $key = array_search($productId, $_SESSION['wishlist']);
    if ($key !== false) {
        unset($_SESSION['wishlist'][$key]);
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
        setMessage('Removed from wishlist');
    }
    redirect('wishlist.php');
}

  
if (isset($_GET['clear'])) {
    $_SESSION['wishlist'] = [];
    setMessage('Wishlist cleared');
    redirect('wishlist.php');
}


$wishlistItems = [];
if (!empty($_SESSION['wishlist'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['wishlist']), '?'));
    
    $sql = "SELECT IDproduct, Product_name, Price, Image, Stock 
            FROM products 
            WHERE IDproduct IN ($placeholders) AND Status = 'active'";
    
    $stmt = mysqli_prepare($conn, $sql);
    $types = str_repeat('s', count($_SESSION['wishlist']));
    mysqli_stmt_bind_param($stmt, $types, ...$_SESSION['wishlist']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $results = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    $productMap = [];
    foreach ($results as $item) {
        $productMap[$item['IDproduct']] = $item;
    }
    
    foreach ($_SESSION['wishlist'] as $productId) {
        if (isset($productMap[$productId])) {
            $wishlistItems[] = $productMap[$productId];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist - Online Shop</title>
    <link rel="stylesheet" href="../member_css/style.css">
    <link rel="stylesheet" href="../member_css/wishlist.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

</head>
<body>
    <div class="container">
        <header>
            <h1>My Wishlist</h1>
        </header>

        <?= displayMessage() ?>

        <?php if (empty($wishlistItems)): ?>
            <div class="empty-wishlist">
                <p>Your wishlist is empty.</p>
                <a href="products.php" class="btn">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="wishlist-header">
                <p>Total items: <strong><?= count($wishlistItems) ?></strong></p>
                <a href="?clear=1" class="btn-remove" onclick="return confirm('Clear entire wishlist?')">Clear Wishlist</a>
            </div>
            
            <div class="wishlist-grid">
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="wishlist-card">
                        <img src="../images/<?= $item['Image'] ?: 'default.jpg' ?>" alt="<?= htmlspecialchars($item['Product_name']) ?>">
                        <h3><?= htmlspecialchars($item['Product_name']) ?></h3>
                        <p class="price"><?= formatPrice($item['Price']) ?></p>
                        <p class="stock">Stock: <?= $item['Stock'] ?></p>
                        <div class="wishlist-actions">
                            <a href="product_detail.php?id=<?= $item['IDproduct'] ?>" class="btn">View Details</a>
                            <button class="btn-add-cart" data-id="<?= $item['IDproduct'] ?>">Add to Cart</button>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="product_id" value="<?= $item['IDproduct'] ?>">
                                <button type="submit" name="remove_from_wishlist" class="btn-remove">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<script>
    
$(document).ready(function() {
    
    $(document).on('click', '.btn-add-cart', function(e) {
        e.preventDefault();
        
        var productId = $(this).data('id');
        
        if (!productId) {
            alert('Product ID not found');
            return;
        }
        
        $.ajax({
            url: 'ajax_add_to_cart.php',
            type: 'POST',
            data: { 
                product_id: productId, 
                quantity: 1 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Product added to cart!');
                    // Update cart count if exists
                    if ($('#cart-count').length) {
                        $('#cart-count').text(response.cart_count);
                    }
                } else {
                    alert(response.message || 'Error adding to cart');
                }
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error + '\nPlease try again.');
            }
        });
    });
});
</script>