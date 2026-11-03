<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../includes/linkdb.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$login_error = '';
$register_error = '';
$register_success = '';


if (isset($_POST['login'])) {
    $email = filter_var(strtolower(trim($_POST['email'])), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $login_error = 'Please fill in all fields';
    } else {
        $sql = "SELECT * FROM member WHERE Email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['Member_password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['IDmember'];
            $_SESSION['username'] = $user['Member_name'];
            $_SESSION['user_type'] = 'member';
            header("Location: products.php");
            exit();
        } else {
            $login_error = 'Invalid email or password';
        }
    }
}

// --- Register Logic ---
if (isset($_POST['register'])) {
    $member_name = trim($_POST['username']);
    $email = strtolower(trim($_POST['email'])); 
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);

    // Basic Validation
    if (empty($member_name) || empty($email) || empty($password)) {
        $register_error = 'Please fill in all required fields';
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = 'Invalid email format.';
    } 
    elseif (strlen($password) < 8) {
        $register_error = 'Password must be at least 8 characters';
    } 
    elseif ($password !== $confirm_password) {
        $register_error = 'Passwords do not match';
    } 
    else {
        // --- DUPLICATION CHECK ---
        // Check if Email OR Username already exists in the 'member' table
        $check_sql = "SELECT Member_name, Email FROM member WHERE Email = ? OR Member_name = ? LIMIT 1";
        $c_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($c_stmt, "ss", $email, $member_name);
        mysqli_stmt_execute($c_stmt);
        $check_result = mysqli_stmt_get_result($c_stmt);
        
        if ($row = mysqli_fetch_assoc($check_result)) {
            if (strtolower($row['Email']) === $email) {
                $register_error = 'This Email is already registered.';
            } else {
                $register_error = 'The Username "' . htmlspecialchars($member_name) . '" is already taken.';
            }
        } 
        else {
            // --- GENERATE NEW ID ---
            $res = mysqli_query($conn, "SELECT IDmember FROM member ORDER BY CAST(SUBSTRING(IDmember, 2) AS UNSIGNED) DESC LIMIT 1");
            $new_id = "M1";
            if ($res && mysqli_num_rows($res) > 0) {
                $last_id_row = mysqli_fetch_assoc($res);
                $new_id = "M" . ((int)substr($last_id_row['IDmember'], 1) + 1);
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // --- INSERT DATA ---
            $sql = "INSERT INTO member (IDmember, Member_name, Email, Member_password, Phone_number, Profile_image) VALUES (?, ?, ?, ?, ?, 'default.png')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", $new_id, $member_name, $email, $hash, $phone);

            try {
                if (mysqli_stmt_execute($stmt)) {
                    $register_success = "Success! You can now login.";
                } else {
                    $register_error = "Registration failed. Please try again.";
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $register_error = "Username or Email already exists.";
                } else {
                    $register_error = "A database error occurred.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Your Account - Card Shop</title>
    <link rel="stylesheet" href="../member_css/login_register.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.tab-btn').click(function() {
                var tab = $(this).data('tab');
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.form-panel').removeClass('active');
                $('#' + tab + '-panel').addClass('active');
            });
            
            // Auto-switch to register tab if there is a registration error/success
            <?php if($register_success || $register_error): ?>
                $('.tab-btn[data-tab="register"]').trigger('click');
            <?php endif; ?>
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="welcome-section">
            <div class="logo-wrapper">
                <img src="../images/z_pokemon.PNG" alt="Logo" class="welcome-logo">
                <h1>CARD SHOP</h1>
            </div>
            <p>Start your journey with us!</p>
        </div>
        
        <div class="form-section">
            <div class="tab-buttons">
                <button class="tab-btn active" data-tab="login">🔐 LOGIN</button>
                <button class="tab-btn" data-tab="register">📝 REGISTER</button>
            </div>
            
            <div id="login-panel" class="form-panel active">
                <?php if($login_error): ?><div class="error"><?php echo $login_error; ?></div><?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="name@email.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    <div style="text-align: right; margin-bottom: 15px;">
                        <a href="forgot_password.php" style="font-size: 13px; color: #7f8c8d; text-decoration: none;">Forgot Password?</a>
                    </div>
                    <button type="submit" name="login">Enter Shop</button>
                </form>
                <a href="../admin/admin_login.php" class="admin-link">Administrator Portal</a>
            </div>
            
            <div id="register-panel" class="form-panel">
                <?php if($register_error): ?><div class="error"><?php echo $register_error; ?></div><?php endif; ?>
                <?php if($register_success): ?><div class="success"><?php echo $register_success; ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" placeholder="Display name">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="example@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" placeholder="+6012xxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>Password (Min. 8 chars)</label>
                        <input type="password" name="password" required minlength="8" placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required placeholder="••••••••">
                    </div>
                    <button type="submit" name="register">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>