<?php
session_start();
header('Content-Type: application/json');

// Database connection
$db = new mysqli('localhost', 'root', 'rajkumar@123', 'hackathon');
if ($db->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Check if user has active payment
$stmt = $db->prepare("SELECT status, access_expires_at FROM payments 
                     WHERE username = ? AND plan = 'pro' 
                     ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();

$response = [
    'has_access' => false,
    'pending' => false
];

if ($result->num_rows > 0) {
    $payment = $result->fetch_assoc();
    
    if ($payment['status'] === 'verified') {
        // Check if access hasn't expired
        $expires_at = strtotime($payment['access_expires_at']);
        if ($expires_at > time()) {
            $response['has_access'] = true;
        }
    } elseif ($payment['status'] === 'pending') {
        $response['pending'] = true;
    }
}

echo json_encode($response);
$stmt->close();
$db->close();
?>