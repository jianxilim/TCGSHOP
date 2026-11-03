<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("../linkdb.php");
include("admin_header.php");

if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

if(isset($_POST['add'])){
    $id = mysqli_real_escape_string($conn, $_POST['IDcategory']);
    $name = mysqli_real_escape_string($conn, $_POST['Category_name']);
    $desc = mysqli_real_escape_string($conn, $_POST['Description']);

    if(!preg_match('/^CAT[0-9]+$/', $id)){
        echo "<script>alert('Error: IDcategory must start with CAT and contain numbers only!'); window.history.back();</script>";
        exit();
    }

    
    $check_id = mysqli_query($conn, "SELECT * FROM categories WHERE IDcategory = '$id'");
    if(mysqli_num_rows($check_id) > 0){
        echo "<script>alert('Error: Category ID \'$id\' already exists!'); window.history.back();</script>";
        exit();
    }

    
    $check_name = mysqli_query($conn, "SELECT * FROM categories WHERE Category_name = '$name'");
    if(mysqli_num_rows($check_name) > 0){
        echo "<script>alert('Error: Category Name \'$name\' already exists!'); window.history.back();</script>";
        exit();
    }

    $sql = "INSERT INTO categories (IDcategory, Category_name, Description) VALUES ('$id','$name','$desc')";
    if(mysqli_query($conn, $sql)){
        header("Location: category.php");
        exit();
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}

if(isset($_GET['delete'])){
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
  
    $check = mysqli_query($conn,"SELECT * FROM products WHERE IDcategory='$id'");
    if(mysqli_num_rows($check) > 0){
        echo "<script>alert('Cannot delete: This category is still used by products!'); window.location='category.php';</script>";
    } else {
        mysqli_query($conn,"DELETE FROM categories WHERE IDcategory='$id'");
        header("Location: category.php");
    }
    exit();
}


$edit_mode = false;
if(isset($_GET['edit'])){
    $edit_mode = true;
    $id = mysqli_real_escape_string($conn, $_GET['edit']);
    $res = mysqli_query($conn,"SELECT * FROM categories WHERE IDcategory='$id'");
    $edit = mysqli_fetch_assoc($res);
}

if(isset($_POST['update'])){
    $id = mysqli_real_escape_string($conn, $_POST['IDcategory']);
    $name = mysqli_real_escape_string($conn, $_POST['Category_name']);
    $desc = mysqli_real_escape_string($conn, $_POST['Description']);

    $check_name = mysqli_query($conn, "SELECT * FROM categories WHERE Category_name = '$name' AND IDcategory != '$id'");
    if(mysqli_num_rows($check_name) > 0){
        echo "<script>alert('Error: Another category is already using the name \'$name\'!'); window.history.back();</script>";
        exit();
    }

    $sql = "UPDATE categories SET Category_name='$name', Description='$desc' WHERE IDcategory='$id'";
    mysqli_query($conn,$sql);
    header("Location: category.php");
    exit();
}


$categories = mysqli_query($conn,"SELECT * FROM categories ORDER BY IDcategory ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Category Management - Admin Portal</title>
    <link rel="stylesheet" href="../admin_css/category.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="category-container">
    <h2><i class="fas fa-tags"></i> Category Management</h2>

    <div class="form-card">
        <h3 style="margin-bottom:15px; color:#34495e; font-size:16px;">
            <?php echo $edit_mode ? "Edit Existing Category" : "Create New Category"; ?>
        </h3>
        <form method="POST" class="form-inline">
            <div class="input-group">
                <label>Category ID</label>
                <input type="text" name="IDcategory" placeholder="e.g. CAT001" required
                       value="<?php echo $edit_mode ? $edit['IDcategory'] : ''; ?>"
                       <?php echo $edit_mode ? 'readonly style="background:#eee;"' : ''; ?>>
            </div>

            <div class="input-group">
                <label>Category Name</label>
                <input type="text" name="Category_name" placeholder="e.g. CARD" required 
                       value="<?php echo $edit_mode ? $edit['Category_name'] : ''; ?>">
            </div>

            <div class="input-group">
                <label>Description</label>
                <input type="text" name="Description" placeholder="Brief details..." style="width: 350px;"
                       value="<?php echo $edit_mode ? $edit['Description'] : ''; ?>">
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" name="<?php echo $edit_mode ? 'update' : 'add'; ?>" class="btn btn-add">
                    <i class="fas <?php echo $edit_mode ? 'fa-save' : 'fa-plus'; ?>"></i>
                    <?php echo $edit_mode ? 'Save Changes' : 'Add Category'; ?>
                </button>
                
                <?php if($edit_mode): ?>
                    <a href="category.php" class="btn btn-cancel">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <table class="category-table">
        <thead>
            <tr>
                <th width="150">ID</th>
                <th width="250">Category Name</th>
                <th>Description</th>
                <th width="200" style="text-align:center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($categories) > 0): ?>
                <?php while($c = mysqli_fetch_assoc($categories)){ ?>
                <tr>
                    <td><strong><?php echo $c['IDcategory']; ?></strong></td>
                    <td><?php echo htmlspecialchars($c['Category_name']); ?></td>
                    <td><?php echo htmlspecialchars($c['Description']); ?></td>
                    <td style="text-align:center;">
                        <a href="?edit=<?php echo $c['IDcategory']; ?>" class="btn btn-edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?delete=<?php echo $c['IDcategory']; ?>" 
                           class="btn btn-delete" 
                           onclick="return confirm('Delete this category? This cannot be undone.')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php } ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:20px; color:#999;">No categories found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>