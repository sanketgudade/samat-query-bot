<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode([]));
}

require 'db_connection.php';

$result = [
    'total_users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_contacts' => $db->query("SELECT COUNT(*) as count FROM contact_submissions")->fetch_assoc()['count'],
    'pending_payments' => $db->query("SELECT COUNT(*) as count FROM payments WHERE status='pending'")->fetch_assoc()['count'],
    'total_revenue' => number_format($db->query("SELECT SUM(amount) as total FROM payments WHERE status='verified'")->fetch_assoc()['total'] ?? 0, 2)
];

echo json_encode($result);
?>