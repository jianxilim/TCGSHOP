<?php

$current_page = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../admin_css/admin_header.css">

<header class="admin-navbar">
    <div class="admin-logo">
        <i class="fas fa-user-shield"></i> Admin Portal
    </div>

    <nav class="admin-nav-links">
        <a href="admin_crud.php" class="nav-item <?php echo ($current_page == 'admin_crud.php') ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="admin_order.php" class="nav-item <?php echo ($current_page == 'admin_order.php') ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> Orders
        </a>
        <a href="category.php" class="nav-item <?php echo ($current_page == 'category.php') ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i> Categories
        </a>
        <a href="member_listing.php" class="nav-item <?php echo ($current_page == 'member_listing.php') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Members
        </a>
    </nav>

    <div class="admin-right" style="display: flex; align-items: center; gap: 15px;">
        <a href="admin_panel.php?page=profile" class="nav-item <?php echo ($current_page == 'admin_panel.php') ? 'active' : ''; ?>" style="color: white; text-decoration: none; font-size: 14px;">
            <i class="fas fa-user-circle"></i> Profile
        </a>

        <a href="admin_panel.php?page=logout" class="admin-logout-btn" onclick="return confirm('Confirm logout?')">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<div class="header-spacer" style="height: 70px; width: 100%;"></div>