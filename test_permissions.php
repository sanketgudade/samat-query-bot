<?php
header('Content-Type: text/plain');
$uploadDir = 'C:/xampp/htdocs/Hack/receipts/';

// 1. Check if directory exists
if (!file_exists($uploadDir)) {
    die("[ERROR] Directory doesn't exist: $uploadDir");
}

// 2. Check if it's a directory
if (!is_dir($uploadDir)) {
    die("[ERROR] Path is not a directory: $uploadDir");
}

// 3. Check readability
if (!is_readable($uploadDir)) {
    die("[ERROR] Directory not readable: $uploadDir");
}

// 4. Check writability
if (!is_writable($uploadDir)) {
    die("[ERROR] Directory not writable: $uploadDir");
}

// 5. Try creating a test file
$testFile = $uploadDir . 'permission_test_' . time() . '.txt';
if (!file_put_contents($testFile, "This is a test file created by PHP")) {
    die("[ERROR] Failed to create test file in: $uploadDir");
}

// 6. Try deleting the test file
if (!unlink($testFile)) {
    die("[ERROR] Created test file but couldn't delete it in: $uploadDir");
}

// 7. Check effective permissions (Windows only)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "=== Windows Permission Details ===\n";
    echo shell_exec('icacls "' . str_replace('/', '\\', $uploadDir) . '"');
}

echo "\n[SUCCESS] Directory has proper permissions:\n$uploadDir";
?>