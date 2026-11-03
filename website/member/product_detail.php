<?php
include("member_header.php"); 
require_once '../includes/linkdb.php'; 
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireMember();

$productId = $_GET['id'] ?? '';
if (empty($productId)) {
    redirect('products.php');
}


$sql = "SELECT p.*, c.Category_name 
        FROM products p 
        LEFT JOIN categories c ON p.IDcategory = c.IDcategory 
        WHERE p.IDproduct = ? AND p.Status = 'active'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $productId); 
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$product) {
    setMessage('Product not found', 'error');
    redirect('products.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['Product_name']) ?> - Detail</title>
    <link rel="stylesheet" href="../member_css/style.css">
    <link rel="stylesheet" href="../member_css/product_detail.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Product Details</h1>
        </header>

        <?= displayMessage() ?>

        <div class="product-detail">
            <div class="product-image">
                <img src="../images/<?= $product['Image'] ?: 'default.jpg' ?>" 
                     alt="<?= htmlspecialchars($product['Product_name']) ?>"
                     onerror="this.src='../images/default.jpg'">
            </div>
            
            <div class="product-info">
                <h2><?= htmlspecialchars($product['Product_name']) ?></h2>
                <p class="category">Category: <?= htmlspecialchars($product['Category_name']) ?></p>
                <p class="price"><?= formatPrice($product['Price']) ?></p>
                <p class="stock">Stock Available: <strong><?= $product['Stock'] ?></strong></p>
                
                <div class="description">
                    <strong>Description:</strong>
                    <p><?= nl2br(htmlspecialchars($product['Description'])) ?></p>
                </div>
                
                <div class="quantity-section">
                    <label>Quantity:</label>
                    <input type="number" id="quantity" min="1" max="<?= $product['Stock'] ?>" value="1">
                    
                    <button id="addToCartBtn" data-id="<?= $product['IDproduct'] ?>" class="btn-primary">Add to Cart</button>
                    
                    <form method="POST" action="wishlist.php" style="display:inline">
                        <input type="hidden" name="product_id" value="<?= $product['IDproduct'] ?>">
                        <button type="submit" name="add_to_wishlist" class="btn-wishlist">❤️ Wishlist</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#addToCartBtn').click(function() {
            var productId = $(this).data('id');
            var quantity = $('#quantity').val();
            
            $.ajax({
                url: 'ajax_add_to_cart.php',
                type: 'POST',
                data: { product_id: productId, quantity: quantity },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#cart-count').text(response.cart_count);
                        alert('Product added to cart!');
                    } else {
                        alert(response.message || 'Error adding to cart');
                    }
                },
                error: function() {
                    alert('Server error, please try again.');
                }
            });
        });
    });
    </script>
</body>
</html>