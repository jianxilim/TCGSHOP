<?php
// 确保 Session 开启
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 使用 __DIR__ 锁定绝对路径，防止因为文件嵌套导致的路径失效
require_once __DIR__ . '/../includes/linkdb.php'; 
require_once __DIR__ . '/../includes/helpers.php'; 
require_once __DIR__ . '/../includes/auth.php';


requireMember();

include("member_header.php"); 

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';


$sql = "SELECT p.*, c.Category_name FROM products p 
        LEFT JOIN categories c ON p.IDcategory = c.IDcategory 
        WHERE p.Status = 'active'";

$types = "";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.Product_name LIKE ? OR p.Description LIKE ?)";
    $search_term = "%$search%";
    $types .= "ss"; 
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category)) {
    $sql .= " AND c.IDcategory = ?";
    $types .= "s"; 
    $params[] = $category;
}

$sql .= " ORDER BY p.Product_name";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);


$cat_query = "SELECT IDcategory, Category_name FROM categories ORDER BY Category_name";
$cat_result = mysqli_query($conn, $cat_query);
$categories = mysqli_fetch_all($cat_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products - Online Shop</title>
    <link rel="stylesheet" href="../member_css/style.css">
    <link rel="stylesheet" href="../member_css/products.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Our Products</h1>
        </header>

        <?php 
        if (function_exists('displayMessage')) {
            echo displayMessage(); 
        }
        ?>

        <div class="search-section">
            <form method="GET" id="searchForm">
                <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['IDcategory'] ?>" <?= $category == $cat['IDcategory'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['Category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="products-grid">
            <?php if (empty($products)): ?>
                <p>No products found.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="../images/<?= $product['Image'] ?: 'default.jpg' ?>" alt="<?= htmlspecialchars($product['Product_name']) ?>">
                        
                        <h3><?= htmlspecialchars($product['Product_name']) ?></h3>
                        <p class="price">
                            <?php 
                             
                            echo function_exists('formatPrice') ? formatPrice($product['Price']) : 'RM ' . number_format($product['Price'], 2); 
                            ?>
                        </p>
                        <p class="stock">Stock: <?= $product['Stock'] ?></p>
                        <div class="product-actions">
                            <a href="product_detail.php?id=<?= $product['IDproduct'] ?>" class="btn">View Details</a>
                            <button class="btn-add-cart" data-id="<?= $product['IDproduct'] ?>">Add to Cart</button>
                            <form method="POST" action="wishlist.php" style="display:inline">
                                <input type="hidden" name="product_id" value="<?= $product['IDproduct'] ?>">
                                <button type="submit" name="add_to_wishlist" class="btn-wishlist">❤️ Wishlist</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('.btn-add-cart').click(function() {
            var productId = $(this).data('id');
            $.ajax({
                url: 'ajax_add_to_cart.php',
                type: 'POST',
                data: { product_id: productId, quantity: 1 },
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
                    alert('An error occurred on the server.');
                }
            });
        });
    });
    </script>
</body>
</html>