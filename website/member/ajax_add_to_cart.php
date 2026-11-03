<?php
require_once '../includes/linkdb.php';
require_once '../includes/auth.php'; 

header('Content-Type: application/json');


if (!isMember()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$productId = $_POST['product_id'] ?? '';
$quantity = intval($_POST['quantity'] ?? 1);
$memberId = getCurrentUserId();

if (empty($productId) || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit();
}


$stmt = mysqli_prepare($conn, "SELECT Stock FROM products WHERE IDproduct = ? AND Status = 'active'");
mysqli_stmt_bind_param($stmt, "s", $productId);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

if ($product['Stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    exit();
}


$stmt = mysqli_prepare($conn, "SELECT IDcart, Quantity FROM cart WHERE IDmember = ? AND IDproduct = ?");
mysqli_stmt_bind_param($stmt, "ss", $memberId, $productId);
mysqli_stmt_execute($stmt);
$cartItem = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($cartItem) {
    $newQuantity = $cartItem['Quantity'] + $quantity;
    if ($newQuantity > $product['Stock']) {
        echo json_encode(['success' => false, 'message' => 'Total quantity exceeds stock']);
        exit();
    }
  
    $updateStmt = mysqli_prepare($conn, "UPDATE cart SET Quantity = ? WHERE IDcart = ?");
    mysqli_stmt_bind_param($updateStmt, "ii", $newQuantity, $cartItem['IDcart']);
    mysqli_stmt_execute($updateStmt);
} else {
    
    $insertStmt = mysqli_prepare($conn, "INSERT INTO cart (IDmember, IDproduct, Quantity) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insertStmt, "ssi", $memberId, $productId, $quantity);
    mysqli_stmt_execute($insertStmt);
}


echo json_encode([
    'success' => true,
    'cart_count' => getCartCount() 
]);
?>