<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "card_assignment"; 


$conn = mysqli_connect($servername, $username, $password, $database);


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


mysqli_set_charset($conn, "utf8mb4");


date_default_timezone_set("Asia/Kuala_Lumpur");

