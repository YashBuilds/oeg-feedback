<?php
$host = "localhost";
$dbname = "oeg_feedback";
$username = "root"; // XAMPP default
$password = "";     // XAMPP default

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "DB Connected Successfully!";
} catch(PDOException $e) {
    die("DB Connection Failed: " . $e->getMessage());
}
?>
