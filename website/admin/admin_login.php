<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }


require_once("../linkdb.php"); 

if (isset($_SESSION['admin_id'])) {
    header("Location: admin_crud.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = trim($_POST['admin_id']);
    $password = trim($_POST['password']);

    if ($admin_id == "" || $password == "") {
        $error = "Please fill in all fields.";
    } else {
        $sql = "SELECT * FROM admins WHERE IDadmin = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $admin_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);

            if ($admin) {
                $is_hash_match = password_verify($password, $admin['Admin_password']);
                $is_plain_match = ($password === $admin['Admin_password']);

                if ($is_hash_match || $is_plain_match) {
                    $_SESSION['admin_id'] = $admin['IDadmin'];
                    $_SESSION['admin_name'] = $admin['Admin_name'];
                    $_SESSION['user_type'] = 'admin';

                    header("Location: admin_crud.php"); 
                    exit;
                } else {
                    $error = "Invalid Password.";
                }
            } else {
                $error = "Admin ID not found.";
            }
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Portal</title>
    <link rel="stylesheet" href="../admin_css/admin_login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="card">
        <h2><i class="fas fa-user-shield"></i> Admin Login</h2>
        
        <?php if($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Admin ID</label>
                <input type="text" name="admin_id" placeholder="Enter Admin ID" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit">Login to Dashboard</button>
            
            <a href="../index.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Member Login
            </a>
        </form>
    </div>

</body>
</html>