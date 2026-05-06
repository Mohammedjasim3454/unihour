<?php
require 'db.php';
session_start();

function requireEmployer() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
        sendJsonResponse('error', 'Only employers can perform this action');
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'view_applicants') {
    requireEmployer();
    $job_id = $_GET['job_id'] ?? 0;

    // Verify employer owns the job
    $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->execute([$job_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
         sendJsonResponse('error', 'Job not found or unauthorized');
    }

    try {
        $stmt = $pdo->prepare("
            SELECT a.id as application_id, a.status, u.id as student_id, u.name, u.college_id_path, a.applied_at 
            FROM applications a 
            JOIN users u ON a.student_id = u.id 
            WHERE a.job_id = ?
        ");
        $stmt->execute([$job_id]);
        $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse('success', 'Applicants retrieved', $applicants);
    } catch (PDOException $e) {
        sendJsonResponse('error', 'Failed to fetch applicants');
    }
}
elseif ($action === 'update_status') {
    requireEmployer();
    $application_id = $_POST['application_id'] ?? 0;
    $new_status = $_POST['status'] ?? '';

    if (!in_array($new_status, ['approved', 'rejected'])) {
        sendJsonResponse('error', 'Invalid status');
    }

    // Advanced logic: if approved, we might want to automatically reject other students for the same job limit.
    // Simplifying for now: just update the status.

    try {
        // Find job associated with this application to ensure ownership
        $stmt = $pdo->prepare("
            SELECT j.id, j.employer_id 
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$application_id]);
        $job = $stmt->fetch();

        if (!$job || $job['employer_id'] !== $_SESSION['user_id']) {
            sendJsonResponse('error', 'Unauthorized or application not found');
        }

        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $application_id]);
        
        // If approved, update job status to filled
        if ($new_status === 'approved') {
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'filled' WHERE id = ?");
            $stmt->execute([$job['id']]);
            
            // Optionally reject all other pending applications for this job
            $stmt = $pdo->prepare("UPDATE applications SET status = 'rejected' WHERE job_id = ? AND id != ?");
            $stmt->execute([$job['id'], $application_id]);
        }

        sendJsonResponse('success', "Application $new_status successfully");
    } catch (PDOException $e) {
        sendJsonResponse('error', 'Failed to update application status');
    }
}
else {
    sendJsonResponse('error', 'Invalid action');
}
?>
