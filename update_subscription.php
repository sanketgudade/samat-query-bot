<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$db = new mysqli('localhost', 'username', 'password', 'hackathon');
if ($db->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

$id = $db->real_escape_string($_POST['id']);
$status = $db->real_escape_string($_POST['status']);
$admin_id = $_SESSION['admin_id'];
$reason = isset($_POST['reason']) ? $db->real_escape_string($_POST['reason']) : null;

if ($status === 'approved') {
    $query = "UPDATE subscriptions SET 
              status = '$status', 
              verified_by = $admin_id, 
              verified_at = NOW(),
              expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH)
              WHERE id = $id";
} else {
    $query = "UPDATE subscriptions SET 
              status = '$status', 
              verified_by = $admin_id, 
              verified_at = NOW(),
              rejection_reason = " . ($reason ? "'$reason'" : "NULL") . "
              WHERE id = $id";
}

if ($db->query($query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $db->error]);
}

$db->close();
?>