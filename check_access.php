<?php
session_start();

if (!isset($_SESSION['username'])) {
    die(json_encode(['success' => false]));
}

$db = new mysqli('localhost', 'root', 'rajkumar@123', 'hackathon');
if ($db->connect_error) {
    die(json_encode(['success' => false]));
}

// Check for active subscription
$stmt = $db->prepare("SELECT status, access_expires_at FROM payments 
                      WHERE username = ? AND (status = 'verified' OR status = 'pending')
                      ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$stmt->bind_result($status, $expiresAt);
$stmt->fetch();
$stmt->close();
$db->close();

if ($status === 'verified') {
    echo json_encode([
        'hasAccess' => true,
        'expiresAt' => date('M d, Y', strtotime($expiresAt))
    ]);
} elseif ($status === 'pending') {
    echo json_encode([
        'hasAccess' => false,
        'pending' => true
    ]);
} else {
    echo json_encode([
        'hasAccess' => false,
        'pending' => false
    ]);
}
?>



