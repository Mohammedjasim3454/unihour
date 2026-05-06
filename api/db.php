<?php
// Simple Database Connection Script

$host = 'localhost';
$dbname = 'unihour';
$username = 'root'; // default XAMPP username
$password = ''; // default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Return early if connection fails for API responses
    die(json_encode(["status" => "error", "message" => "Database Connection failed: " . $e->getMessage()]));
}

// Utility function to send JSON response
function sendJsonResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    $response = ['status' => $status, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}
?>
