<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__. '/../PHPMailer/Exception.php';
require_once __DIR__. '/../PHPMailer/PHPMailer.php';
require_once __DIR__. '/../PHPMailer/SMTP.php';


function input($name, $value = '', $type = 'text', $attrs = []) {
    $attrStr = '';
    foreach ($attrs as $key => $val) {
        $attrStr .= " $key=\"$val\"";
    }
    return "<input type=\"$type\" name=\"$name\" id=\"$name\" value=\"" . htmlspecialchars($value) . "\"$attrStr>";
}

function textarea($name, $value = '', $attrs = []) {
    $attrStr = '';
    foreach ($attrs as $key => $val) {
        $attrStr .= " $key=\"$val\"";
    }
    return "<textarea name=\"$name\" id=\"$name\"$attrStr>" . htmlspecialchars($value) . "</textarea>";
}

function select($name, $options, $selected = '', $attrs = []) {
    $attrStr = '';
    foreach ($attrs as $key => $val) {
        $attrStr .= " $key=\"$val\"";
    }
    $html = "<select name=\"$name\" id=\"$name\"$attrStr>";
    foreach ($options as $value => $label) {
        $sel = ($value == $selected) ? ' selected' : '';
        $html .= "<option value=\"" . htmlspecialchars($value) . "\"$sel>" . htmlspecialchars($label) . "</option>";
    }
    $html .= "</select>";
    return $html;
}

function errorMessage($errors, $field) {
    if (isset($errors[$field])) {
        return "<span class=\"error\">" . htmlspecialchars($errors[$field]) . "</span>";
    }
    return '';
}

function formatPrice($price) {
    return 'RM ' . number_format($price, 2);
}

function generateOrderId() {
    return 'O' . date('Ymd') . rand(1000, 9999);
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function displayMessage() {
    if (isset($_SESSION['message'])) {
        $msg = $_SESSION['message'];
        unset($_SESSION['message']);
        return "<div class=\"alert alert-{$msg['type']}\">{$msg['text']}</div>";
    }
    return '';
}

function setMessage($text, $type = 'success') {
    $_SESSION['message'] = ['text' => $text, 'type' => $type];
}


function sendAdminOrderEmail($orderId, $total, $customerName, $items = []) {
    $mail = new PHPMailer(true); 
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jiedsobig@gmail.com'; 
        $mail->Password   = 'qykw mmzp pwlr mssu';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('jiedsobig@gmail.com', 'Card Shop System');
        $mail->addAddress('jiedsobig@gmail.com'); 

        
        $order_list_html = '';
        if (!empty($items)) {
            foreach ($items as $item) {
                $order_list_html .= '
                <tr>
                    <td style="padding: 10px; border: 1px solid #eee; text-align: left;">' . htmlspecialchars($item['name']) . '</td>
                    <td style="padding: 10px; border: 1px solid #eee; text-align: center;">' . $item['qty'] . '</td>
                    <td style="padding: 10px; border: 1px solid #eee; text-align: right;">RM ' . number_format($item['price'], 2) . '</td>
                </tr>';
            }
        } else {
            $order_list_html = '<tr><td colspan="3" style="padding: 10px; text-align: center;">No items found</td>*</tr>';
        }

        $final_body = '
        <div style="font-family: Arial, sans-serif; border: 1px solid #ddd; padding: 20px; border-radius: 10px; max-width: 600px;">
            <h2 style="color: #27ae60; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">🛍️ New Order Received!</h2>
            <p><strong>Order ID:</strong> #' . $orderId . '</p>
            <p><strong>Customer:</strong> ' . htmlspecialchars($customerName) . '</p>
            <p><strong>Order Date:</strong> ' . date('d M Y, H:i:s') . '</p>
            
            <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="background-color: #f4f4f4;">
                        <th style="padding: 10px; text-align: left; border: 1px solid #eee;">Product</th>
                        <th style="padding: 10px; text-align: center; border: 1px solid #eee;">Qty</th>
                        <th style="padding: 10px; text-align: right; border: 1px solid #eee;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $order_list_html . '
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8f9fa;">
                        <td colspan="2" style="padding: 15px; text-align: right; font-weight: bold;">Total:</td>
                        <td style="padding: 15px; text-align: right; font-weight: bold; color: #27ae60;">RM ' . number_format($total, 2) . '</td>
                    </tr>
                </tfoot>
            </table>
            <hr style="margin: 20px 0;">
            <p style="color: #666; font-size: 12px;">This is an automated notification. Please do not reply to this email.</p>
        </div>';

        $mail->isHTML(true);
        $mail->Subject = "New Order Notification: #$orderId";
        $mail->Body    = $final_body;
        $mail->AltBody = "New Order #$orderId\nCustomer: $customerName\nTotal: RM " . number_format($total, 2);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Admin Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}


function sendCustomerOrderEmail($orderId, $total, $customerEmail, $customerName, $shippingAddress, $items = []) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ingt01138@gmail.com'; 
        $mail->Password   = 'fwsw glgk xwfx pywl';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('ingt01138@gmail.com', 'Card Shop');
        $mail->addAddress($customerEmail, $customerName);

        
        $itemsHtml = '';
        if (!empty($items)) {
            foreach ($items as $item) {
                $itemName = $item['name'] ?? $item['Product_name'] ?? 'Unknown Product';
                $itemQty = $item['qty'] ?? $item['Quantity'] ?? 1;
                $itemPrice = $item['price'] ?? $item['Price'] ?? 0;
                $subtotal = $itemPrice * $itemQty;
                
                $itemsHtml .= '
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #eee;">' . htmlspecialchars($itemName) . '</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eee; text-align: center;">' . $itemQty . '</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eee; text-align: right;">RM ' . number_format($itemPrice, 2) . '</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eee; text-align: right;">RM ' . number_format($subtotal, 2) . '</td>
                </tr>';
            }
        } else {
            $itemsHtml = '<tr><td colspan="4" style="padding: 12px; text-align: center;">No items found</td>*</tr>';
        }

        $tax = $total * 0.06;
        $subtotal = $total - $tax;

        $htmlBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Order Confirmation</title>
        </head>
        <body style="font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div style="background: linear-gradient(135deg, #2c5364, #203a43); padding: 25px 30px; text-align: center;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">🎉 Order Confirmed!</h1>
                    <p style="color: #bdc3c7; margin: 10px 0 0 0;">Thank you for shopping with us</p>
                </div>
                <div style="padding: 30px;">
                    <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #27ae60;">
                        <p style="margin: 0; color: #2e7d32;"><strong>✅ Payment Successful!</strong> Your order has been confirmed.</p>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <p style="margin: 5px 0;"><strong>Order ID:</strong> #' . htmlspecialchars($orderId) . '</p>
                        <p style="margin: 5px 0;"><strong>Order Date:</strong> ' . date('d M Y, H:i') . '</p>
                        <p style="margin: 5px 0;"><strong>Customer:</strong> ' . htmlspecialchars($customerName) . '</p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                        <h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 16px;">📦 Shipping Address</h3>
                        <p style="margin: 0; color: #555; line-height: 1.6;">' . nl2br(htmlspecialchars($shippingAddress)) . '</p>
                    </div>
                    
                    <h3 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 16px;">🛒 Order Summary</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Product</th>
                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Qty</th>
                                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #dee2e6;">Price</th>
                                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #dee2e6;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . $itemsHtml . '
                        </tbody>
                        <tfoot>
                            <tr><td colspan="3" style="padding: 12px; text-align: right;">Subtotal:</td><td style="padding: 12px; text-align: right;">RM ' . number_format($subtotal, 2) . '</td>*</tr>
                            <tr><td colspan="3" style="padding: 12px; text-align: right;">Tax (6%):</td><td style="padding: 12px; text-align: right;">RM ' . number_format($tax, 2) . '</td>*</tr>
                            <tr style="background: #f8f9fa;"><td colspan="3" style="padding: 12px; text-align: right; font-weight: bold; font-size: 16px;">Total:</td><td style="padding: 12px; text-align: right; font-weight: bold; font-size: 16px; color: #27ae60;">RM ' . number_format($total, 2) . '</td>*</tr>
                        </tfoot>
                    </table>
                    
                    <div style="background: #f0f7ff; padding: 15px; border-radius: 8px; margin-top: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 14px;">📋 What\'s Next?</h3>
                        <p style="margin: 5px 0; color: #555; font-size: 14px;">1. Our team will process your order within 1-2 business days.</p>
                        <p style="margin: 5px 0; color: #555; font-size: 14px;">2. You will receive another email when your order is shipped.</p>
                        <p style="margin: 5px 0; color: #555; font-size: 14px;">3. Track your order status in your account dashboard.</p>
                    </div>
                </div>
                <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;">
                    <p style="margin: 5px 0; color: #7f8c8d; font-size: 12px;">Card Shop - Your Trusted Trading Card Store</p>
                    <p style="margin: 5px 0; color: #7f8c8d; font-size: 12px;">Need help? Contact us at support@cardshop.com</p>
                </div>
            </div>
        </body>
        </html>';

        $textBody = "Order Confirmation #$orderId\n\nThank you for your purchase, $customerName!\n\nOrder ID: #$orderId\nOrder Date: " . date('d M Y, H:i') . "\nTotal: RM " . number_format($total, 2) . "\n\nShipping Address:\n$shippingAddress\n\nItems Ordered:\n";
        foreach ($items as $item) {
            $itemName = $item['name'] ?? $item['Product_name'] ?? 'Unknown';
            $itemQty = $item['qty'] ?? $item['Quantity'] ?? 1;
            $itemPrice = $item['price'] ?? $item['Price'] ?? 0;
            $textBody .= "- " . $itemName . " x" . $itemQty . " = RM " . number_format($itemPrice * $itemQty, 2) . "\n";
        }
        $textBody .= "\nThank you for shopping with Card Shop!";

        $mail->isHTML(true);
        $mail->Subject = "🎉 Order Confirmation #$orderId - Card Shop";
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Customer Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>