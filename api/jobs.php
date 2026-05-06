<?php
require 'db.php';
session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Check if user is logged in for most endpoints
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse('error', 'Unauthorized access');
    }
}

if ($action === 'post_job') {
    requireLogin();
    if ($_SESSION['user_type'] !== 'employer') {
        sendJsonResponse('error', 'Only employers can post jobs');
    }

    $employer_id = $_SESSION['user_id'];
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $hourly_rate = $_POST['hourly_rate'] ?? 0;
    $location = $_POST['location'] ?? ''; // e.g. "1.2 km away" string or lat/long in future
    $date = $_POST['date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO jobs (employer_id, title, description, hourly_rate, location, date, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$employer_id, $title, $description, $hourly_rate, $location, $date, $start_time, $end_time]);
        sendJsonResponse('success', 'Job posted successfully');
    } catch (PDOException $e) {
        sendJsonResponse('error', 'Failed to post job: ' . $e->getMessage());
    }
}
elseif ($action === 'list_jobs') { // For students to see available jobs
    try {
        $stmt = $pdo->query("
            SELECT j.*, u.company_name, u.name as employer_name 
            FROM jobs j 
            JOIN users u ON j.employer_id = u.id 
            WHERE j.status = 'open' 
            ORDER BY j.created_at DESC
        ");
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse('success', 'Jobs retrieved successfully', $jobs);
    } catch (PDOException $e) {
        sendJsonResponse('error', 'Failed to fetch jobs');
    }
}
elseif ($action === 'my_jobs') { // For employers to see their posted jobs
    requireLogin();
    try {
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse('success', 'Your postings retrieved', $jobs);
    } catch (PDOException $e) {
        sendJsonResponse('error', 'Failed to fetch your jobs');
    }
}
elseif ($action === 'apply_job') {
    requireLogin();
    if ($_SESSION['user_type'] !== 'student') {
        sendJsonResponse('error', 'Only students can apply for jobs');
    }

    $job_id = $_POST['job_id'] ?? 0;
    $student_id = $_SESSION['user_id'];

    // Check if already applied
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND student_id = ?");
    $stmt->execute([$job_id, $student_id]);
    if ($stmt->fetch()) {
        sendJsonResponse('error', 'You have already applied for this shift');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO applications (job_id, student_id) VALUES (?, ?)");
        $stmt->execute([$job_id, $student_id]);
        sendJsonResponse('success', 'Application submitted successfully');
    } catch (PDOException $e) {
        sendJsonResponse('error', 'Failed to apply');
    }
}
else {
    sendJsonResponse('error', 'Invalid action');
}
?>
