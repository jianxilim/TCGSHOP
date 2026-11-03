<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/includes/linkdb.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/paypal_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 检查登录
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'member') {
    header("Location: login_register.php");
    exit();
}

if (!isset($_SESSION['temp_order'])) {
    echo "<script>alert('No order found'); window.location='checkout.php';</script>";
    exit();
}

$tempOrder = $_SESSION['temp_order'];


function getAccessToken() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200) {
        throw new Exception('Failed to get PayPal access token');
    }
    
    $data = json_decode($result, true);
    return $data['access_token'];
}

function createPayPalOrder($amount, $returnUrl, $cancelUrl) {
    $accessToken = getAccessToken();
    
    $data = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'amount' => [
                    'currency_code' => 'MYR',
                    'value' => number_format($amount, 2, '.', '')
                ]
            ]
        ],
        'application_context' => [
            'return_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
            'user_action' => 'PAY_NOW'
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 201) {
        throw new Exception('Failed to create PayPal order');
    }
    
    return json_decode($result, true);
}

try {
    
    $siteUrl = "http://localhost/website/";
    $returnUrl = $siteUrl . "member/paypal_success.php";
    $cancelUrl = $siteUrl . "member/paypal_cancel.php";
    
    
    $order = createPayPalOrder($tempOrder['total'], $returnUrl, $cancelUrl);
    
    
    $approveUrl = '';
    foreach ($order['links'] as $link) {
        if ($link['rel'] === 'approve') {
            $approveUrl = $link['href'];
            break;
        }
    }
    
    if ($approveUrl) {
        $_SESSION['paypal_order_id'] = $order['id'];
        header("Location: " . $approveUrl);
        exit();
    } else {
        throw new Exception('No approval URL found');
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<br><a href='checkout.php'>Back to Checkout</a>";
}
?>