<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Log received data
error_log("Received payment update request: " . print_r($_POST, true));

require 'db_connection.php'; // Ensure this path is correct

$paymentId = $_POST['payment_id'] ?? null;
$status = $_POST['status'] ?? null;
$adminNotes = $_POST['admin_notes'] ?? '';

// More detailed validation
if (!$paymentId) {
    die(json_encode(['success' => false, 'message' => 'Missing payment ID']));
}
if (!in_array($status, ['verified', 'rejected'])) {
    die(json_encode(['success' => false, 'message' => 'Invalid status']));
}

try {
    $db->begin_transaction();
    
    // Update payment status
    $stmt = $db->prepare("UPDATE payments SET status = ?, admin_notes = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    $stmt->bind_param("ssi", $status, $adminNotes, $paymentId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // If verifying, set expiration date (1 month from now)
    if ($status === 'verified') {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 month'));
        $stmt = $db->prepare("UPDATE payments SET access_expires_at = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        $stmt->bind_param("si", $expiresAt, $paymentId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'new_status' => $status,
        'expires_at' => $status === 'verified' ? $expiresAt : null
    ]);
} catch (Exception $e) {
    $db->rollback();
    error_log("Error updating payment: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>