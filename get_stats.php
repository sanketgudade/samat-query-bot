<?php
session_start();
header('Content-Type: application/json');

$db = new mysqli('localhost', 'root', 'rajkumar@123', 'hackathon');
if ($db->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

$stats = [
    'total_users' => 0,
    'total_contacts' => 0,
    'pending_payments' => 0,
    'total_revenue' => 0
];

// Get total users
$result = $db->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Get total contacts
$result = $db->query("SELECT COUNT(*) as count FROM contact_submissions");
$stats['total_contacts'] = $result->fetch_assoc()['count'];

// Get pending payments
$result = $db->query("SELECT COUNT(*) as count FROM payments WHERE status='pending'");
$stats['pending_payments'] = $result->fetch_assoc()['count'];

// Get total revenue
$result = $db->query("SELECT SUM(amount) as total FROM payments WHERE status='verified'");
$stats['total_revenue'] = number_format($result->fetch_assoc()['total'] ?? 0, 2);

echo json_encode($stats);
$db->close();
?>