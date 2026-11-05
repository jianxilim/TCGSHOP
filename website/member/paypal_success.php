<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/includes/linkdb.php';
require_once dirname(__DIR__) . '/includes/paypal_config.php';
require_once dirname(__DIR__) . '/includes/helpers.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}

$memberId = $_SESSION['user_id'];
$paypalOrderId = $_GET['token'] ?? $_SESSION['paypal_order_id'] ?? '';

if (empty($paypalOrderId)) {
    die("No PayPal order ID found");
}


function getAccessToken() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $result = curl_exec($ch);
    $data = json_decode($result, true);
    return $data['access_token'] ?? null;
}


function capturePayment($orderId) {
    $accessToken = getAccessToken();
    if (!$accessToken) return ['status' => 'AUTH_ERROR'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v2/checkout/orders/' . $orderId . '/capture');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    $result = curl_exec($ch);
    $response = json_decode($result, true);
    return $response;
}

try {
    $capture = capturePayment($paypalOrderId);
    
    
    if (isset($capture['status']) && $capture['status'] === 'COMPLETED') {
        
        if (!isset($_SESSION['temp_order'])) {
            throw new Exception('Order data missing in session');
        }
        
        $tempOrder = $_SESSION['temp_order'];
        $orderId = 'O' . date('Ymd') . rand(1000, 9999);
        
        mysqli_begin_transaction($conn);
        
        
        $sql = "INSERT INTO orders (IDorder, IDmember, Total_amount, Shipping_address, Order_status, Order_date) 
                VALUES (?, ?, ?, ?, 'paid', NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssds", $orderId, $memberId, $tempOrder['total'], $tempOrder['shipping_address']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        $lowStockItems = [];

      
        foreach ($tempOrder['items'] as $item) {
            
            $sql2 = "INSERT INTO order_items (IDorder, IDproduct, Quantity, Price) VALUES (?, ?, ?, ?)";
            $stmt2 = mysqli_prepare($conn, $sql2);
            mysqli_stmt_bind_param($stmt2, "ssid", $orderId, $item['IDproduct'], $item['Quantity'], $item['Price']);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
            
            
            $sql3 = "UPDATE products SET Stock = Stock - ? WHERE IDproduct = ?";
            $stmt3 = mysqli_prepare($conn, $sql3);
            mysqli_stmt_bind_param($stmt3, "is", $item['Quantity'], $item['IDproduct']);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);

            
            $checkSql = "SELECT Product_name, Stock FROM products WHERE IDproduct = ?";
            $c_stmt = mysqli_prepare($conn, $checkSql);
            mysqli_stmt_bind_param($c_stmt, "s", $item['IDproduct']);
            mysqli_stmt_execute($c_stmt);
            $pData = mysqli_fetch_assoc(mysqli_stmt_get_result($c_stmt));
            
            if ($pData['Stock'] < 5) {
                $lowStockItems[] = $pData['Product_name'] . " (Remaining: " . $pData['Stock'] . ")";
            }
        }
        
       
        mysqli_query($conn, "DELETE FROM cart WHERE IDmember = '$memberId'");
        mysqli_commit($conn);

        
        $userQuery = mysqli_query($conn, "SELECT Member_name, Email FROM member WHERE IDmember = '$memberId'");
        $userData = mysqli_fetch_assoc($userQuery);
        $customerName = $userData['Member_name'] ?? 'Valued Customer';
        $customerEmail = $userData['Email'] ?? '';

        
        $itemsForEmail = [];
        foreach ($tempOrder['items'] as $item) {
            $itemsForEmail[] = [
                'name'  => $item['Product_name'],
                'qty'   => $item['Quantity'],
                'price' => $item['Price']
            ];
        }

       
        $adminSubject = !empty($lowStockItems) ? "⚠️ STOCK ALERT & New Order #$orderId" : "New Order Notice #$orderId";
        sendAdminOrderEmail($orderId, $tempOrder['total'], $customerName, $itemsForEmail);

        
        if (!empty($customerEmail)) {
            sendCustomerOrderEmail(
                $orderId, 
                $tempOrder['total'], 
                $customerEmail, 
                $customerName, 
                $tempOrder['shipping_address'], 
                $itemsForEmail
            );
        }

        $finalTotal = $tempOrder['total'];
        $finalItems = $tempOrder['items'];

        unset($_SESSION['temp_order'], $_SESSION['paypal_order_id']);
?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Order Confirmed</title>
            <link rel="stylesheet" href="../member_css/paypal_success.css">
        </head>
        <body>
            <div class="container">
                <div class="success-box">
                    <h1>✅ Payment Successful!</h1>
                    <p>Thank you for your purchase. Your Order ID is <strong><?php echo $orderId; ?></strong>.</p>
                    <p>A confirmation email has been sent to <strong><?php echo htmlspecialchars($customerEmail); ?></strong></p>
                </div>
                
                <h3>Order Summary</h3>
                <table class="order-summary-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($finalItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['Product_name']); ?></td>
                            <td class="text-center"><?php echo $item['Quantity']; ?></td>
                            <td class="text-right">RM <?php echo number_format($item['Price'], 2); ?></td>
                            <td class="text-right">RM <?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="grand-total-row">
                            <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                            <td class="grand-total"><strong>RM <?php echo number_format($finalTotal, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="actions">
                    <a href="order_history.php" class="btn btn-history">View Order History</a>
                    <a href="products.php" class="btn btn-shop">Return to Shop</a>
                </div>
            </div>
        </body>
        </html>
<?php
    } else {
        echo "<h3>Capture Failed</h3>";
        echo "<p>PayPal was unable to process the payment capture. This might be due to a duplicate request or currency settings.</p>";
        echo "<pre>"; print_r($capture); echo "</pre>";
        exit;
    }
} catch (Exception $e) {
    if (isset($conn)) mysqli_rollback($conn);
    die("Error: " . $e->getMessage());
}
?>