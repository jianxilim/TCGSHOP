<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../includes/linkdb.php");

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    die("Invalid request. No security token provided.");
}

$sql = "SELECT IDmember, Member_name FROM member WHERE reset_token = ? AND reset_token_expire > NOW()";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $error = "This reset link is invalid or has expired. Please request a new one.";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password']) && $user) {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
       
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        
        $update_sql = "UPDATE member SET Member_password = ?, reset_token = NULL, reset_token_expire = NULL WHERE IDmember = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $user['IDmember']);

        if (mysqli_stmt_execute($update_stmt)) {
            $success = "Success! Your password has been updated.";
        } else {
            $error = "Database error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Card Shop</title>
    <link rel="stylesheet" href="../member_css/reset_password.css">
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h2>New Password</h2>
            <p>Hi <?php echo $user ? htmlspecialchars($user['Member_name']) : 'User'; ?>, enter your new password below.</p>
        </div>

        <div class="reset-content">
            <?php if($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="success-msg"><?php echo $success; ?></div>
                <a href="login_register.php" class="login-link">Click here to Login</a>
            <?php elseif($user): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" placeholder="At least 8 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Repeat password" required>
                    </div>
                    <button type="submit" name="update_password">Update Password</button>
                </form>
            <?php else: ?>
                <a href="forgot_password.php" class="login-link">Request a new link</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>