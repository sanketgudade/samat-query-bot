<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// Database connection
$db = new mysqli('localhost', 'root', 'rajkumar@123', 'hackathon');
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// Get export type
$type = isset($_GET['type']) ? $_GET['type'] : 'users';

// Set appropriate headers and filename
switch ($type) {
    case 'users':
        $filename = 'users_export_' . date('Y-m-d') . '.csv';
        $query = "SELECT * FROM users";
        break;
    case 'subscriptions':
        $filename = 'subscriptions_export_' . date('Y-m-d') . '.csv';
        $query = "SELECT s.*, a.username as verified_by_name 
                 FROM subscriptions s
                 LEFT JOIN admins a ON s.verified_by = a.id";
        break;
    case 'contacts':
        $filename = 'contacts_export_' . date('Y-m-d') . '.csv';
        $query = "SELECT * FROM contact_submissions";
        break;
    default:
        die("Invalid export type");
}

// Execute query
$result = $db->query($query);
if (!$result) {
    die("Error in query: " . $db->error);
}

// Create CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Write headers
if ($type === 'users') {
    fputcsv($output, array('ID', 'Username', 'Email', 'Created At'));
} elseif ($type === 'subscriptions') {
    fputcsv($output, array(
        'ID', 'User ID', 'Username', 'Email', 'Transaction ID', 
        'UPI Method', 'Receipt Filename', 'Amount', 'Status',
        'Joined At', 'Expires At', 'Verified At', 'Verified By ID',
        'Verified By Name', 'Rejection Reason'
    ));
} elseif ($type === 'contacts') {
    fputcsv($output, array('ID', 'Name', 'Email', 'Message', 'Submission Date'));
}

// Write data
while ($row = $result->fetch_assoc()) {
    if ($type === 'users') {
        fputcsv($output, array(
            $row['id'],
            $row['username'],
            $row['email'],
            $row['created_at']
        ));
    } elseif ($type === 'subscriptions') {
        fputcsv($output, array(
            $row['id'],
            $row['user_id'],
            $row['username'],
            $row['email'],
            $row['transaction_id'],
            $row['upi_method'],
            $row['receipt_filename'],
            $row['amount'],
            $row['status'],
            $row['joined_at'],
            $row['expires_at'],
            $row['verified_at'],
            $row['verified_by'],
            $row['verified_by_name'],
            $row['rejection_reason'] ?? ''
        ));
    } elseif ($type === 'contacts') {
        fputcsv($output, array(
            $row['id'],
            $row['name'],
            $row['email'],
            $row['message'],
            $row['submission_date']
        ));
    }
}

fclose($output);
exit;
?>