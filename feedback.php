<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

session_start();
require_once "db.php";
header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

// ------------------------ POST: Submit Feedback ------------------------
if ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data["username"] ?? null;
    $rating   = $data["rating"] ?? null;
    $comments = $data["comments"] ?? "";

    if (!$rating || $rating < 1 || $rating > 5) {
        echo json_encode(["status" => "error", "message" => "Invalid rating"]);
        exit;
    }

    $sql = "INSERT INTO feedback (username, rating, comments)
            VALUES (:username, :rating, :comments)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":username" => $username,
        ":rating"   => $rating,
        ":comments" => $comments
    ]);

    echo json_encode(["status" => "success", "message" => "Feedback submitted"]);
    exit;
}

// ------------------------ GET: Admin Fetch Feedback ------------------------
if ($method === "GET" && !isset($_GET["stats"])) {
    if (!isset($_SESSION["admin_logged_in"])) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Unauthorized"]);
        exit;
    }

    $rating = $_GET["rating"] ?? null;
    $date   = $_GET["date"] ?? null;

    $query = "SELECT * FROM feedback WHERE 1=1";
    $params = [];
    if ($rating) { $query .= " AND rating = :rating"; $params[":rating"] = $rating; }
    if ($date)   { $query .= " AND DATE(submitted_at) = :date"; $params[":date"] = $date; }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status"=>"success","data"=>$feedbacks]);
    exit;
}

// ------------------------ GET: Admin Stats ------------------------
if ($method === "GET" && isset($_GET["stats"])) {
    if (!isset($_SESSION["admin_logged_in"])) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Unauthorized"]);
        exit;
    }

    $stmt = $conn->query("
        SELECT COUNT(*) as total, AVG(rating) as average,
        GROUP_CONCAT(rating ORDER BY rating) as ratings
        FROM feedback
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $ratings = array_map("intval", explode(",", $row["ratings"]));
    sort($ratings);
    $count = count($ratings);
    $median = $count % 2 === 0
        ? ($ratings[$count/2 - 1] + $ratings[$count/2]) / 2
        : $ratings[floor($count/2)];

    echo json_encode([
        "status"=>"success",
        "total" => (int)$row["total"],
        "average" => round($row["average"],2),
        "median" => $median
    ]);
    exit;
}

echo json_encode(["status"=>"error","message"=>"Invalid request"]);
?>
