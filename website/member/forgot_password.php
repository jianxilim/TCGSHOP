<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../includes/linkdb.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../PHPMailer/Exception.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';

$error = '';
$success = '';

function base($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . $host . dirname($_SERVER['SCRIPT_NAME'], 2) . '/';
    return $base_url . $path;
}

function getMailer() {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sengjun228@gmail.com'; 
        $mail->Password   = 'lyemlbnkekgmiyhu'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('sengjun228@gmail.com', 'Card Shop Support');
        $mail->CharSet = 'UTF-8';
        return $mail;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_request'])) {
    $email = filter_var(strtolower(trim($_POST['email'])), FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $sql = "SELECT IDmember, Member_name FROM member WHERE Email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            $update_sql = "UPDATE member SET reset_token = ?, reset_token_expire = ? WHERE IDmember = ?";
            $upd_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($upd_stmt, "sss", $token, $expires_at, $user['IDmember']);
            
            if (mysqli_stmt_execute($upd_stmt)) {
                $mail = getMailer();
                if ($mail) {
                    try {
                        $mail->addAddress($email, $user['Member_name']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset - Card Shop';
                        $link = base() . 'member/reset_password.php?token=' . $token;
                        $mail->Body = "Hello {$user['Member_name']},<br><br>Reset your password here: <a href='$link'>$link</a><br><br>Expires in 5 mins.";
                        $mail->send();
                        $success = 'Reset link sent! Please check your inbox.';
                    } catch (Exception $e) {
                        $error = 'Email failed to send.';
                    }
                }
            }
        } else {
            $success = 'If an account exists, you will receive a link.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Card Shop</title>
    <link rel="stylesheet" href="../member_css/forgot_password.css">
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h2>🔐 Reset Password</h2>
            <p>We'll send you a link to reset your password</p>
        </div>
        
        <div class="forgot-content">
            <?php if($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <div class="link-container">
                    <a href="login_register.php" class="back-link">← Back to Login</a>
                </div>
            <?php else: ?>
                <div class="info-text">
                    💡 The reset link will expire in <strong>5 minutes</strong>.
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="your@email.com">
                    </div>
                    <button type="submit" name="reset_request">Send Reset Link</button>
                </form>
                
                <div class="link-container">
                    <a href="login_register.php" class="back-link">← Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>