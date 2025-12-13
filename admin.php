<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

header("Content-Type: application/json");
include 'db.php';
session_start();

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['username']) || !isset($data['password'])) {
    echo json_encode(["status"=>"error","message"=>"Username and password required"]);
    exit;
}

$username = $data['username'];
$password = $data['password'];

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION["admin_logged_in"] = true;
        echo json_encode(["status"=>"success","message"=>"Login successful"]);
    } else {
        echo json_encode(["status"=>"error","message"=>"Invalid credentials"]);
    }
} catch(PDOException $e) {
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
?>
