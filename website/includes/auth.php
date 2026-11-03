<?php
require_once __DIR__ . '/../linkdb.php'; 


function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function isMember() {
    return isLoggedIn() && $_SESSION['user_type'] === 'member';
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

// ===== REDIRECT =====
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login_register.php');
        exit();
    }
}

function requireMember() {
    requireLogin();
    if (!isMember()) {
        header('Location: login_register.php');
        exit();
    }
}


function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCartCount() {
    global $conn;

    if (!isMember()) return 0;

    $memberId = getCurrentUserId();

    $sql = "SELECT SUM(Quantity) as total FROM cart WHERE IDmember = '$memberId'";
    $result = mysqli_query($conn, $sql);

    $row = mysqli_fetch_assoc($result);

    return $row['total'] ?? 0;
}
?>