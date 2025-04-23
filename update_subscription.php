<?php
session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Database connection
$db = new mysqli('localhost', 'username', 'password', 'hackathon');
if ($db->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get input data
$id = intval($_POST['id']);
$status = in_array($_POST['status'], ['approved', 'rejected']) ? $_POST['status'] : 'pending';

// Update subscription status
$stmt = $db->prepare("UPDATE subscriptions SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $db->error]);
}

$stmt->close();
$db->close();
?>