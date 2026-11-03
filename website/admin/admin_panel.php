<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("../linkdb.php");

$page = isset($_GET['page']) ? $_GET['page'] : 'listing';
$message = "";
$error = "";


if ($page == "logout") {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}


if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Control Panel</title>
    <link rel="stylesheet" href="../admin_css/admin_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include("admin_header.php"); ?>

<div class="container">
    <div class="card">

    <?php if ($page == "profile"): 
        $adminId = $_SESSION['admin_id'];
        $stmt = mysqli_prepare($conn, "SELECT * FROM admins WHERE IDadmin = ?");
        mysqli_stmt_bind_param($stmt, "s", $adminId);
        mysqli_stmt_execute($stmt);
        $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    ?>
        <h2><i class="fas fa-id-card"></i> My Account</h2>
        <p class="subtitle">View and manage your administrator details</p>

        <div class="profile-section">
            <img class="profile-photo" src="../images/<?php echo h($admin['Profile_image'] ?: 'default.png'); ?>" onerror="this.src='https://via.placeholder.com/130'">
        </div>

        <div class="info-grid">
            <div class="info-label">Admin ID</div>
            <div class="info-value"><?php echo h($admin['IDadmin']); ?></div>
            <div class="info-label">Full Name</div>
            <div class="info-value"><?php echo h($admin['Admin_name']); ?></div>
            <div class="info-label">Email Address</div>
            <div class="info-value"><?php echo h($admin['Email'] ?: 'Not set'); ?></div>
            <div class="info-label">Phone Number</div>
            <div class="info-value"><?php echo h($admin['Phone_number'] ?: 'Not set'); ?></div>
        </div>

        <div class="actions">
            <a href="?page=profile_update" class="btn-base btn-primary">Edit Profile</a>
            <a href="?page=change_password" class="btn-base btn-primary" style="background:#27ae60"><i class="fas fa-key"></i> Password</a>
            <a href="?page=photo_upload" class="btn-base btn-primary" style="background:#8e44ad">Change Photo</a>
            <a href="admin_crud.php" class="btn-base btn-secondary">Manage Products</a>
        </div>

    <?php elseif ($page == "change_password"): 
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $new_pass = $_POST['new_password'];
            $confirm_pass = $_POST['confirm_password'];

            if (strlen($new_pass) < 6) {
                $error = "Password must be at least 6 characters.";
            } elseif ($new_pass !== $confirm_pass) {
                $error = "New passwords do not match.";
            } else {
                
                $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
                $adminId = $_SESSION['admin_id'];
                
                $upd = mysqli_prepare($conn, "UPDATE admins SET Admin_password = ? WHERE IDadmin = ?");
                mysqli_stmt_bind_param($upd, "ss", $hashed_password, $adminId);
                
                if (mysqli_stmt_execute($upd)) {
                    $message = "Password updated successfully!";
                } else {
                    $error = "Error updating password.";
                }
            }
        }
    ?>
        <h2><i class="fas fa-lock"></i> Change Password</h2>
        <?php if ($message): ?><div class="msg msg-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg msg-error" style="background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:15px;"><?php echo $error; ?></div><?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required placeholder="Enter new password">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
            </div>
            <div class="actions">
                <button type="submit" class="btn-base btn-primary">Update Password</button>
                <a href="?page=profile" class="btn-base btn-secondary">Back to Profile</a>
            </div>
        </form>

    <?php elseif ($page == "profile_update"): 
        $adminId = $_SESSION['admin_id'];
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $upd = mysqli_prepare($conn, "UPDATE admins SET Admin_name=?, Email=?, Phone_number=? WHERE IDadmin=?");
            mysqli_stmt_bind_param($upd, "ssss", $name, $email, $phone, $adminId);
            if(mysqli_stmt_execute($upd)) {
                $_SESSION['admin_name'] = $name;
                $message = "Profile updated successfully!";
            }
        }
        $stmt = mysqli_prepare($conn, "SELECT * FROM admins WHERE IDadmin = ?");
        mysqli_stmt_bind_param($stmt, "s", $adminId);
        mysqli_stmt_execute($stmt);
        $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    ?>
        <h2>Update Information</h2>
        <?php if ($message): ?><div class="msg msg-success"><?php echo $message; ?></div><?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="name" class="form-control" value="<?php echo h($admin['Admin_name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo h($admin['Email']); ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?php echo h($admin['Phone_number']); ?>">
            </div>
            <div class="actions">
                <button type="submit" class="btn-base btn-primary">Save Changes</button>
                <a href="?page=profile" class="btn-base btn-secondary">Cancel</a>
            </div>
        </form>

    <?php elseif ($page == "photo_upload"): ?>
        <h2>Update Avatar</h2>
        <?php 
        if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['photo'])) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $newName = "adm_" . time() . "." . $ext;
            if(move_uploaded_file($_FILES['photo']['tmp_name'], "../images/" . $newName)) {
                mysqli_query($conn, "UPDATE admins SET Profile_image='$newName' WHERE IDadmin='".$_SESSION['admin_id']."'");
                echo "<div class='msg msg-success'>Photo uploaded successfully!</div>";
            }
        }
        ?>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="photo" class="form-control" required style="margin: 20px 0;">
            <div class="actions">
                <button type="submit" class="btn-base btn-primary">Upload Now</button>
                <a href="?page=profile" class="btn-base btn-secondary">Back</a>
            </div>
        </form>

    <?php else: 
        $res = mysqli_query($conn, "SELECT * FROM admins ORDER BY IDadmin ASC");
    ?>
        <div style="display:flex; justify-content:space-between; align-items:center">
            <h2><i class="fas fa-users"></i> Admin Team</h2>
            <a href="?page=profile" class="btn-base btn-primary">My Account</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($res)) : ?>
                <tr>
                    <td><img class="avatar-sm" src="../images/<?php echo h($row['Profile_image'] ?: 'default.png'); ?>" onerror="this.src='https://via.placeholder.com/45'"></td>
                    <td><b><?php echo h($row['Admin_name']); ?></b></td>
                    <td><?php echo h($row['Email'] ?: '-'); ?></td>
                    <td><span class="badge">Active Admin</span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="actions" style="justify-content: flex-end;">
            <a href="?page=logout" class="btn-base btn-danger">Log Out System</a>
        </div>
    <?php endif; ?>

    </div>
</div>

</body>
</html>