<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Database connection
$db = new mysqli('localhost', 'root', 'rajkumar@123', 'hackathon');
if ($db->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get and validate input
$payment_id = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
$admin_notes = filter_input(INPUT_POST, 'admin_notes', FILTER_SANITIZE_STRING);

if (!$payment_id || !$status || !in_array($status, ['verified', 'rejected'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid parameters']));
}

try {
    $db->begin_transaction();
    
    // Update payment status
    $stmt = $db->prepare("UPDATE payments SET 
        status = ?, 
        admin_notes = ?,
        processed_at = NOW(),
        access_expires_at = CASE 
            WHEN ? = 'verified' THEN DATE_ADD(NOW(), INTERVAL 1 MONTH)
            ELSE NULL 
        END
        WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $db->error);
    }
    
    $stmt->bind_param("sssi", $status, $admin_notes, $status, $payment_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    // If verified, update user's plan and access
    if ($status === 'verified') {
        // Get payment details
        $payment_stmt = $db->prepare("SELECT username, plan FROM payments WHERE id = ?");
        $payment_stmt->bind_param("i", $payment_id);
        $payment_stmt->execute();
        $payment = $payment_stmt->get_result()->fetch_assoc();
        $payment_stmt->close();
        
        if ($payment) {
            // Update user's plan and access
            $update_user = $db->prepare("UPDATE users SET 
                plan = ?,
                access_expires = DATE_ADD(NOW(), INTERVAL 1 MONTH)
                WHERE username = ?");
            $update_user->bind_param("ss", $payment['plan'], $payment['username']);
            $update_user->execute();
            $update_user->close();
        }
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Payment status updated successfully']);
    
} catch (Exception $e) {
    $db->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $db->close();
}
?>