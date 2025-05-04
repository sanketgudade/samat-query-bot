<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'rajkumar@123');
define('DB_NAME', 'hackathon');

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create database connection
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    
    // Set charset to utf8
    if (!$db->set_charset("utf8")) {
        throw new Exception("Error setting charset: " . $db->error);
    }
    
    // Display success message (only if this file is accessed directly)
    if (basename($_SERVER['PHP_SELF']) == 'db_connection.php') {
        echo "<h2 style='color:green;'>Database Connection Successful!</h2>";
        echo "<p>Host: " . DB_HOST . "</p>";
        echo "<p>Database: " . DB_NAME . "</p>";
        
        // Test a simple query
        $result = $db->query("SELECT 1+1 AS test_result");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>Query Test Result: " . $row['test_result'] . " (Expected: 2)</p>";
        } else {
            echo "<p style='color:orange;'>Query test failed: " . $db->error . "</p>";
        }
    }
    
} catch (Exception $e) {
    // Display error message
    if (basename($_SERVER['PHP_SELF']) == 'db_connection.php') {
        echo "<h2 style='color:red;'>Database Connection Failed</h2>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "<p>Check your credentials in db_connection.php</p>";
    }
    die(); // Stop execution if connection fails
}

// Function to safely escape strings
function db_escape($string) {
    global $db;
    return $db->real_escape_string($string);
}

// Timezone setting
date_default_timezone_set('Asia/Kolkata');
?>