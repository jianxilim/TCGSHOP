<?php
include("member_header.php"); 
require_once("../includes/linkdb.php");

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$error = ''; 
$success = ''; 
$user = null;
$is_logged_in = isset($_SESSION['user_id']);


$upload_dir = '../images/';
if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);


if (!$is_logged_in && !isset($_POST['login']) && !isset($_POST['register'])) {
    header("Location: login_register.php");
    exit();
}

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM member WHERE IDmember = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}


if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $sql = "SELECT * FROM member WHERE Email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($user_data && password_verify($password, $user_data['Member_password'])) {
        $_SESSION['user_id'] = $user_data['IDmember'];
        $_SESSION['username'] = $user_data['Member_name'];
        header("Location: profile.php");
        exit();
    } else {
        $error = 'Invalid email or password';
    }
}


if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    
    
    $check_sql = "SELECT IDmember FROM member WHERE Member_name = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $username);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $error = 'Username already exists. Please choose another name.';
    } elseif ($password !== $_POST['confirm_password']) {
        $error = 'Passwords do not match';
    } else {
        
        $res = mysqli_query($conn, "SELECT IDmember FROM member ORDER BY CAST(SUBSTRING(IDmember, 2) AS UNSIGNED) DESC LIMIT 1");
        $last_id_row = mysqli_fetch_assoc($res);
        $new_id = "M" . ($last_id_row ? ((int)substr($last_id_row['IDmember'], 1) + 1) : 1);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = "INSERT INTO member (IDmember, Member_name, Email, Member_password, Phone_number, Profile_image) VALUES (?, ?, ?, ?, ?, 'default.png')";
        $stmt = mysqli_prepare($conn, $ins);
        mysqli_stmt_bind_param($stmt, "sssss", $new_id, $username, $email, $hash, $phone);
        
        if (mysqli_stmt_execute($stmt)) { 
            $success = 'Registered! Please login.'; 
        } else { 
            $error = 'Registration failed. Email may already exist.'; 
        }
    }
}


if ($is_logged_in) {
   
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['member_name']);
        $ph = trim($_POST['phone']);
        
        // Check if another user already has this name
        $check_sql = "SELECT IDmember FROM member WHERE Member_name = ? AND IDmember != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ss", $name, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = 'This username is already taken by another member. Please choose a different name.';
        } else {
            
            $update_sql = "UPDATE member SET Member_name = ?, Phone_number = ? WHERE IDmember = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "sss", $name, $ph, $user_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success = "Profile Updated!";
                
                $_SESSION['username'] = $name;
            } else {
                $error = "Failed to update profile.";
            }
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
       
        $check_sql = "SELECT Member_password FROM member WHERE IDmember = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $user_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $user_data = mysqli_fetch_assoc($result);
        
        if (!$user_data || !password_verify($current_password, $user_data['Member_password'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE member SET Member_password = ? WHERE IDmember = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ss", $new_hash, $user_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password. Please try again.';
            }
        }
    }
    

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = 'Only JPG, PNG, and GIF files are allowed';
        } else {
            $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_dir . $filename)) {
               
                if ($user['Profile_image'] && $user['Profile_image'] != 'default.png') {
                    $old_path = $upload_dir . $user['Profile_image'];
                    if (file_exists($old_path)) unlink($old_path);
                }
                
                $update_img_sql = "UPDATE member SET Profile_image = ? WHERE IDmember = ?";
                $update_img_stmt = mysqli_prepare($conn, $update_img_sql);
                mysqli_stmt_bind_param($update_img_stmt, "ss", $filename, $user_id);
                mysqli_stmt_execute($update_img_stmt);
                $success = "Photo Updated!";
            } else {
                $error = "Failed to upload image";
            }
        }
    }
    
    $refresh_sql = "SELECT * FROM member WHERE IDmember = ?";
    $refresh_stmt = mysqli_prepare($conn, $refresh_sql);
    mysqli_stmt_bind_param($refresh_stmt, "s", $user_id);
    mysqli_stmt_execute($refresh_stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($refresh_stmt));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="../member_css/profile.css">
    <style>
       
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="profile-container">
    <?php if (!$is_logged_in): ?>
        <div class="tabs">
            <button class="tab-btn active" data-tab="login">Login</button>
            <button class="tab-btn" data-tab="register">Register</button>
        </div>

        <?php if($error): ?><div class="msg error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if($success): ?><div class="msg success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <div id="login-panel" class="form-panel active">
            <form method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>

        <div id="register-panel" class="form-panel">
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="tel" name="phone" placeholder="Phone">
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" name="register">Register</button>
            </form>
        </div>

    <?php else: ?>
        <div class="profile-info">
            <?php 
                $img = (!empty($user['Profile_image'])) ? $user['Profile_image'] : 'default.png';
            ?>
            <img src="../images/<?php echo htmlspecialchars($img); ?>" width="130" height="130" onerror="this.src='../images/default.png'">
            <h2><?php echo htmlspecialchars($user['Member_name']); ?></h2>
            <p>Member ID: <?php echo htmlspecialchars($user['IDmember']); ?></p>
            <p>Email: <?php echo htmlspecialchars($user['Email']); ?></p>
        </div>

        <?php if($error): ?><div class="msg error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if($success): ?><div class="msg success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <h3>📝 Edit Profile</h3>
        <form method="POST">
            <input type="text" name="member_name" value="<?php echo htmlspecialchars($user['Member_name']); ?>" required>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['Phone_number']); ?>" placeholder="Phone Number">
            <button type="submit" name="update_profile">Save Changes</button>
        </form>

        <h3>🖼️ Change Photo</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/gif">
            <button type="submit">Upload Photo</button>
        </form>

        <!-- Password Reset Section -->
        <div class="password-section">
            <h3>🔒 Change Password</h3>
            <form method="POST" class="reset-password-form">
                <input type="password" name="current_password" placeholder="Current Password" required>
                <input type="password" name="new_password" placeholder="New Password (min. 6 characters)" required>
                <div class="password-requirement">Password must be at least 6 characters</div>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                <button type="submit" name="reset_password" class="btn-reset">Reset Password</button>
            </form>
        </div>

    <?php endif; ?>
</div>

<script>
    $('.tab-btn').click(function() {
        $('.tab-btn').removeClass('active'); $(this).addClass('active');
        $('.form-panel').removeClass('active'); $('#' + $(this).data('tab') + '-panel').addClass('active');
    });
</script>

</body>
</html>