<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers for CORS and JSON response
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

// Database configuration
$host = 'localhost';
$dbname = 'hackathon';
$username = 'root';
$password = 'rajkumar@123';

// Create response array
$response = ['success' => false, 'message' => ''];

try {
    // Verify user is logged in
    if (!isset($_SESSION['username'])) {
        throw new Exception('User not logged in');
    }

    // Verify required fields
    if (empty($_POST['transaction_id'])) {
        throw new Exception('Transaction ID is required');
    }

    if (empty($_FILES['receipt'])) {
        throw new Exception('Receipt file is required');
    }

    // Process file upload
    $uploadDir = __DIR__ . '/receipts/';
    $file = $_FILES['receipt'];

    // Verify upload succeeded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    // Verify file size
    if ($file['size'] > 5242880) { // 5MB
        throw new Exception('File too large (max 5MB)');
    }

    // Create receipts directory if needed
    if (!file_exists($uploadDir) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Could not create upload directory');
        }
    }

    // Generate unique filename
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $receiptFilename = uniqid() . '.' . $fileExt;
    $uploadPath = $uploadDir . $receiptFilename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to save receipt file');
    }

    // Create database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare data for insertion
    $paymentData = [
        'username' => $_SESSION['username'],
        'transaction_id' => $_POST['transaction_id'],
        'amount' => $_POST['amount'] ?? '1.00',
        'upi_method' => $_POST['upi_method'] ?? 'Any UPI App',
        'receipt_filename' => $receiptFilename,
        'status' => 'pending',
        'payment_date' => date('Y-m-d H:i:s'),
        'access_expires_at' => date('Y-m-d H:i:s', strtotime('+1 month')),
        'plan' => 'basic'
    ];

    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (
        username, transaction_id, amount, upi_method, 
        receipt_filename, status, payment_date, 
        access_expires_at, plan
    ) VALUES (
        :username, :transaction_id, :amount, :upi_method,
        :receipt_filename, :status, :payment_date,
        :access_expires_at, :plan
    )");

    $stmt->execute($paymentData);

    // Success response
    $response = [
        'success' => true,
        'message' => 'Payment submitted successfully!',
        'data' => $paymentData
    ];

} catch (PDOException $e) {
    // Database error
    $response['message'] = 'Database error: ' . $e->getMessage();
    // Delete uploaded file if database operation failed
    if (!empty($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
} catch (Exception $e) {
    // General error
    $response['message'] = $e->getMessage();
}

// Send JSON response
echo json_encode($response);
?>