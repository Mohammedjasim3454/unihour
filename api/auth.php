<?php
require 'db.php';
session_start();

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    $company_name = $_POST['company_name'] ?? null;
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Basic file upload handling for college ID (mocked path for now)
    $college_id_path = null;
    if (isset($_FILES['college_id']) && $_FILES['college_id']['error'] == 0) {
        $target_dir = "../images/uploads/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = basename($_FILES["college_id"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name;
        if(move_uploaded_file($_FILES["college_id"]["tmp_name"], $target_file)) {
            $college_id_path = "images/uploads/" . time() . "_" . $file_name;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, user_type, college_id_path, company_name) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password_hash, $user_type, $college_id_path, $company_name]);
        sendJsonResponse('success', 'User registered successfully');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Unique constraint violation (duplicate email)
             sendJsonResponse('error', 'Email already exists');
        }
        sendJsonResponse('error', 'Registration failed: ' . $e->getMessage());
    }
} 
elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['name'] = $user['name'];
            
            // Unset hash before sending back to frontend
            unset($user['password_hash']);
            sendJsonResponse('success', 'Login successful', $user);
        } else {
            sendJsonResponse('error', 'Invalid email or password');
        }
    } catch (PDOException $e) {
        sendJsonResponse('error', 'Login failed: ' . $e->getMessage());
    }
}
elseif ($action === 'logout') {
    session_destroy();
    sendJsonResponse('success', 'Logged out successfully');
}
else {
    sendJsonResponse('error', 'Invalid action');
}
?>
