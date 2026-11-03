<?php
// 1. Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once("../linkdb.php"); 
require_once '../PHPMailer/Exception.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';

if(!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin'){
    header("Location: admin_login.php"); 
    exit();
}

$upload_dir = "../images/";

function sendLowStockEmail($name, $stock) {
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
        $mail->isHTML(true);
        $mail->Subject = "=?UTF-8?B?".base64_encode("⚠️ Low Stock Alert: $name")."?=";
        $mail->Body    = "<h2>Inventory Alert</h2><p>Product: {$name}</p><p>Current Stock: {$stock}</p>";
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

// --- DELETE LOGIC ---
if(isset($_GET['delete'])){
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $img_res = mysqli_query($conn, "SELECT Image FROM products WHERE IDproduct = '$id'");
    $img_data = mysqli_fetch_assoc($img_res);
    $sql = "DELETE FROM products WHERE IDproduct = '$id'";
    if(mysqli_query($conn, $sql)){
        if($img_data && $img_data['Image'] != 'default.png') { @unlink($upload_dir . $img_data['Image']); }
        header("Location: admin_crud.php?msg=deleted");
        exit();
    }
}

// --- ADD LOGIC (FIXED FOR DUPLICATE ERROR) ---
if(isset($_POST['add'])){
    $id = mysqli_real_escape_string($conn, $_POST['IDproduct']);
    
    // 1. Pre-check: Check if ID already exists
    $check_query = mysqli_query($conn, "SELECT IDproduct FROM products WHERE IDproduct = '$id'");
    if(mysqli_num_rows($check_query) > 0) {
        echo "<script>alert('❌ Error: Product ID [$id] already exists! Please use a different ID.'); window.location.href='admin_crud.php';</script>";
        exit();
    }

    $name = mysqli_real_escape_string($conn, $_POST['Product_name']);
    $price = $_POST['Price'];
    $stock = $_POST['Stock'];
    $category = $_POST['IDcategory'];
    $description = mysqli_real_escape_string($conn, $_POST['Description']);
    $image = "default.png";
    if(!empty($_FILES['Image']['name'])){
        $image = time() . "_" . $_FILES['Image']['name'];
        move_uploaded_file($_FILES['Image']['tmp_name'], $upload_dir . $image);
    }

    // 2. Try-Catch block to safely handle database errors
    try {
        $sql = "INSERT INTO products (IDproduct, Product_name, Price, Image, Stock, IDcategory, Description) VALUES ('$id', '$name', '$price', '$image', '$stock', '$category', '$description')";
        if(mysqli_query($conn, $sql)){
            if($stock <= 5) sendLowStockEmail($name, $stock);
            header("Location: admin_crud.php?msg=added"); 
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        // Fallback for unexpected duplicate or other DB errors
        echo "<script>alert('❌ Database Error: Duplicate entry or invalid data.'); window.location.href='admin_crud.php';</script>";
        exit();
    }
}

// --- UPDATE LOGIC ---
if(isset($_POST['update'])){
    $id = mysqli_real_escape_string($conn, $_POST['IDproduct']);
    $name = mysqli_real_escape_string($conn, $_POST['Product_name']);
    $price = $_POST['Price'];
    $stock = $_POST['Stock'];
    $category = $_POST['IDcategory'];
    $description = mysqli_real_escape_string($conn, $_POST['Description']);
    $img_sql = "";
    if(!empty($_FILES['Image']['name'])){
        $image = time() . "_" . $_FILES['Image']['name'];
        move_uploaded_file($_FILES['Image']['tmp_name'], $upload_dir . $image);
        $img_sql = ", Image='$image'";
    }
    $sql = "UPDATE products SET Product_name='$name', Price='$price', Stock='$stock', IDcategory='$category', Description='$description' $img_sql WHERE IDproduct='$id'";
    if(mysqli_query($conn, $sql)){
        if($stock <= 5) sendLowStockEmail($name, $stock);
        header("Location: admin_crud.php?msg=updated"); exit();
    }
}

// Data Fetching
$categories_res = mysqli_query($conn, "SELECT * FROM categories");
$categories = mysqli_fetch_all($categories_res, MYSQLI_ASSOC);
$filter_category = isset($_GET['filter_cat']) ? mysqli_real_escape_string($conn, $_GET['filter_cat']) : '';
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query = "SELECT p.*, c.Category_name FROM products p LEFT JOIN categories c ON p.IDcategory = c.IDcategory WHERE 1=1";
if($filter_category != '') $query .= " AND p.IDcategory = '$filter_category'";
if($search_query != '') $query .= " AND (p.Product_name LIKE '%$search_query%' OR p.IDproduct LIKE '%$search_query%')";
$products = mysqli_query($conn, $query);
$low_stock_query = mysqli_query($conn, "SELECT * FROM products WHERE Stock <= 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Product Management</title>
    <link rel="stylesheet" href="../admin_css/admin_crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include("admin_header.php"); ?>

    <div class="container">
        <?php if(mysqli_num_rows($low_stock_query) > 0): ?>
        <div class="alert-box">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>Low Stock Alert:</strong> 
            <?php 
            $names = [];
            while($ls = mysqli_fetch_assoc($low_stock_query)) { 
                $names[] = htmlspecialchars($ls['Product_name']) . " (<span>".$ls['Stock']."</span>)"; 
            }
            echo implode(", ", $names);
            ?>
        </div>
        <?php endif; ?>

        <div class="container-header">
            <h2><i class="fas fa-boxes"></i> Product Maintenance</h2>
            <button class="btn-add" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Product</button>
        </div>

        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search ID or Name..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="filter-group">
                    <i class="fas fa-filter"></i>
                    <select name="filter_cat">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?php echo $c['IDcategory']; ?>" <?php echo ($filter_category == $c['IDcategory']) ? 'selected' : ''; ?>>
                                <?php echo $c['Category_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-apply">Apply</button>
                <?php if($filter_category != '' || $search_query != ''): ?>
                    <a href="admin_crud.php" class="reset-link">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($products) > 0): ?>
                    <?php while($p = mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td><strong><?php echo $p['IDproduct']; ?></strong></td>
                        <td><img src="../images/<?php echo $p['Image']; ?>" class="prod-img" onerror="this.src='https://via.placeholder.com/60'"></td>
                        <td><?php echo htmlspecialchars($p['Product_name']); ?></td>
                        <td><span class="cat-label"><?php echo $p['Category_name'] ?? 'N/A'; ?></span></td>
                        <td class="desc-cell" title="<?php echo htmlspecialchars($p['Description']); ?>">
                            <?php echo (mb_strlen($p['Description'] ?? '') > 30) ? mb_substr(htmlspecialchars($p['Description']), 0, 30).'...' : htmlspecialchars($p['Description'] ?? ''); ?>
                        </td>
                        <td>RM <?php echo number_format($p['Price'], 2); ?></td>
                        <td class="<?php echo ($p['Stock'] <= 5) ? 'stock-low' : ''; ?>">
                            <?php echo $p['Stock']; ?>
                            <?php if($p['Stock'] <= 0) echo '<span class="badge-red">OOS</span>'; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn-edit-action" onclick='editProduct(<?php echo json_encode($p); ?>)'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="admin_crud.php?delete=<?php echo $p['IDproduct']; ?>" class="btn-del" onclick="return confirm('Delete this product?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-state">No matching products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
            <form method="POST" enctype="multipart/form-data">
                <label>Product ID</label>
                <input type="text" name="IDproduct" required placeholder="e.g. P001" pattern="P[0-9]+">
                <label>Product Name</label>
                <input type="text" name="Product_name" required>
                <label>Price (RM)</label>
                <input type="number" step="0.01" name="Price" required>
                <label>Stock</label>
                <input type="number" name="Stock" required>
                <label>Category</label>
                <select name="IDcategory" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach($categories as $c) echo "<option value='{$c['IDcategory']}'>{$c['Category_name']}</option>"; ?>
                </select>
                <label>Description</label>
                <textarea name="Description" placeholder="Enter specifications..."></textarea>
                <label>Product Image</label>
                <input type="file" name="Image">
                <button type="submit" name="add" class="btn-add full-width-btn">Save Product</button>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h3><i class="fas fa-edit"></i> Edit Product</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="IDproduct" id="edit_id">
                <label>Product ID</label>
                <input type="text" id="display_id" disabled>
                <label>Product Name</label>
                <input type="text" name="Product_name" id="edit_name" required>
                <label>Price (RM)</label>
                <input type="number" step="0.01" name="Price" id="edit_price" required>
                <label>Stock</label>
                <input type="number" name="Stock" id="edit_stock" required>
                <label>Category</label>
                <select name="IDcategory" id="edit_category" required>
                    <?php foreach($categories as $c) echo "<option value='{$c['IDcategory']}'>{$c['Category_name']}</option>"; ?>
                </select>
                <label>Description</label>
                <textarea name="Description" id="edit_description"></textarea>
                <label>Change Image (Optional)</label>
                <input type="file" name="Image">
                <button type="submit" name="update" class="btn-edit-action update-btn-full">Update Product</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = "block"; }
        function closeModal(id) { document.getElementById(id).style.display = "none"; }
        function editProduct(data) {
            document.getElementById('edit_id').value = data.IDproduct;
            document.getElementById('display_id').value = data.IDproduct;
            document.getElementById('edit_name').value = data.Product_name;
            document.getElementById('edit_price').value = data.Price;
            document.getElementById('edit_stock').value = data.Stock;
            document.getElementById('edit_category').value = data.IDcategory;
            document.getElementById('edit_description').value = data.Description || '';
            openModal('editModal');
        }

        // Display alerts based on URL messages
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('msg') === 'added') alert("✅ Product added successfully!");
            if (urlParams.get('msg') === 'updated') alert("✅ Product updated successfully!");
            if (urlParams.get('msg') === 'deleted') alert("🗑️ Product deleted successfully!");
        }

        window.onclick = function(e) { if (e.target.className === 'modal') { e.target.style.display = "none"; } }
    </script>
</body>
</html>