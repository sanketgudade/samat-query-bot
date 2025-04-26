<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'message' => 'Not authorized']));
}

$userId = $_POST['user_id'] ?? null;

if (!$userId) {
    die(json_encode(['success' => false, 'message' => 'User ID required']));
}

// Database connection
$db = new mysqli('localhost', 'root', 'rajkumar@123', 'hackathon');
if ($db->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Delete user and their payments
$db->begin_transaction();

try {
    // Delete payments first (foreign key constraint)
    $stmt = $db->prepare("DELETE FROM payments WHERE username IN (SELECT username FROM users WHERE id = ?)");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    
    // Then delete the user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $db->commit();
        echo json_encode(['success' => true]);
    } else {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$db->close();
?>