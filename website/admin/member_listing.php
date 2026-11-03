<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }


require_once("../linkdb.php"); 


if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php"); 
    exit();
}


if(isset($_GET['delete'])){
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    $sql = "DELETE FROM member WHERE IDmember = '$id'";
    if(mysqli_query($conn, $sql)){
        header("Location: member_listing.php?msg=deleted");
        exit();
    }
}


if(isset($_POST['update_member'])){
    $id = mysqli_real_escape_string($conn, $_POST['IDmember']);
    $name = mysqli_real_escape_string($conn, $_POST['Member_name']);
    $email = mysqli_real_escape_string($conn, $_POST['Email']);
    $phone = mysqli_real_escape_string($conn, $_POST['Phone_number']);

    
    $check_sql = "SELECT IDmember FROM member WHERE (Member_name='$name' OR Email='$email') AND IDmember != '$id'";
    $check_res = mysqli_query($conn, $check_sql);

    if(mysqli_num_rows($check_res) > 0) {
        
        echo "<script>alert('❌ Error: Member Name or Email already exists! Please use a different one.'); window.location.href='member_listing.php';</script>";
        exit();
    }

   
    $sql = "UPDATE member SET Member_name='$name', Email='$email', Phone_number='$phone' WHERE IDmember='$id'";
    
    if(mysqli_query($conn, $sql)){
        header("Location: member_listing.php?msg=updated"); 
        exit();
    } else {
        echo "<script>alert('❌ Database Error: Could not update record.'); window.location.href='member_listing.php';</script>";
        exit();
    }
}


$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query = "SELECT * FROM member WHERE 1=1";
if($search_query != '') {
    $query .= " AND (Member_name LIKE '%$search_query%' OR Email LIKE '%$search_query%' OR IDmember LIKE '%$search_query%')";
}
$members = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Member Listing</title>
    <link rel="stylesheet" href="../admin_css/admin_crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include("admin_header.php"); ?>

    <div class="container">
        <div class="container-header">
            <h2><i class="fas fa-users"></i> Member Listing</h2>
        </div>

        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search Member ID, Name..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <button type="submit" class="btn-apply">Search</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Member ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($members) > 0): ?>
                    <?php while($m = mysqli_fetch_assoc($members)): ?>
                    <tr>
                        <td><strong><?php echo $m['IDmember']; ?></strong></td>
                        <td><?php echo htmlspecialchars($m['Member_name']); ?></td>
                        <td><?php echo htmlspecialchars($m['Email']); ?></td>
                        <td><?php echo htmlspecialchars($m['Phone_number'] ?: 'N/A'); ?></td>
                        <td class="text-center">
                            <button class="btn-edit-action" onclick='editMember(<?php echo json_encode($m); ?>)'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="member_listing.php?delete=<?php echo $m['IDmember']; ?>" class="btn-del" onclick="return confirm('Are you sure you want to delete this member?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-state">No members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="editMemberModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editMemberModal')">&times;</span>
            <h3><i class="fas fa-user-edit"></i> Edit Member</h3>
            <hr style="margin-bottom: 20px; border: 0; border-top: 1px solid #eee;">
            <form method="POST">
                <input type="hidden" name="IDmember" id="edit_id">
                
                <label>Name</label>
                <input type="text" name="Member_name" id="edit_name" required>
                
                <label>Email</label>
                <input type="email" name="Email" id="edit_email" required>
                
                <label>Phone</label>
                <input type="text" name="Phone_number" id="edit_phone">
                
                <button type="submit" name="update_member" class="btn-edit-action update-btn-full" style="width: 100%; margin-top: 10px;">
                    Update Member Info
                </button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = "block"; }
        function closeModal(id) { document.getElementById(id).style.display = "none"; }
        
        function editMember(data) {
            document.getElementById('edit_id').value = data.IDmember;
            document.getElementById('edit_name').value = data.Member_name;
            document.getElementById('edit_email').value = data.Email;
            document.getElementById('edit_phone').value = data.Phone_number;
            openModal('editMemberModal');
        }

        // 成功操作后的弹窗
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('msg') === 'updated') alert("✅ Member updated successfully!");
            if (urlParams.get('msg') === 'deleted') alert("🗑️ Member deleted successfully!");
        }

        // 点击空白处关闭弹窗
        window.onclick = function(e) { if (e.target.className === 'modal') { e.target.style.display = "none"; } }
    </script>
</body>
</html>