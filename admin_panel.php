<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection
$db = new mysqli('localhost', 'root', 'rajkumar@123', 'hackathon');
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// Fetch all data
$payments = $db->query("SELECT * FROM payments ORDER BY created_at DESC");
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$contacts = $db->query("SELECT * FROM contact_submissions ORDER BY submission_date DESC LIMIT 10");

// Count totals for stats
$total_payments = $db->query("SELECT COUNT(*) as count FROM payments")->fetch_assoc()['count'];
$pending_payments = $db->query("SELECT COUNT(*) as count FROM payments WHERE status='pending'")->fetch_assoc()['count'];
$verified_payments = $db->query("SELECT COUNT(*) as count FROM payments WHERE status='verified'")->fetch_assoc()['count'];
$rejected_payments = $db->query("SELECT COUNT(*) as count FROM payments WHERE status='rejected'")->fetch_assoc()['count'];
$total_users = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_contacts = $db->query("SELECT COUNT(*) as count FROM contact_submissions")->fetch_assoc()['count'];
$total_revenue = $db->query("SELECT SUM(amount) as total FROM payments WHERE status='verified'")->fetch_assoc()['total'] ?? 0;
?>    
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --error-color: #ff3333;
            --warning-color: #ffcc00;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark-color);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
            color: white;
            position: fixed;
            height: 100vh;
            padding: 20px 0;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header img {
            width: 40px;
            margin-right: 10px;
        }
        
        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li {
            position: relative;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-menu .badge {
            position: absolute;
            right: 20px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50px;
            padding: 2px 8px;
            font-size: 12px;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
        }
        
        /* Top Navigation */
        .top-nav {
            background-color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .user-profile img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .user-profile .name {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Content Area */
        .content-wrapper {
            padding: 25px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .page-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stats-card .card-title {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .stats-card .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .stats-card .card-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        /* Tables */
        .data-table {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .data-table .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .data-table .table-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #555;
            border-bottom: 1px solid #eee;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        table tr:hover td {
            background-color: #f9f9f9;
        }
        
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-verified {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--error-color);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            animation: fadeIn 0.3s;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: hidden;
            }
            
            .sidebar-header h3, .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 15px 0;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .sidebar-menu .badge {
                display: none;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="logo.png" alt="Logo">
            <h3>Smart QueryBot</h3>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li>
                    <a href="#" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#users-section">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                        <span class="badge"><?= $total_users ?></span>
                    </a>
                </li>
                <li>
                    <a href="#payments-section">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                        <span class="badge"><?= $pending_payments ?></span>
                    </a>
                </li>
                <li>
                    <a href="#contacts-section">
                        <i class="fas fa-envelope"></i>
                        <span>Contacts</span>
                        <span class="badge"><?= $total_contacts ?></span>
                    </a>
                </li>
                <li>
                    <a href="admin_login.php?logout=true">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-nav">
            <div class="user-menu">
                <div class="user-profile">
                    <img src="admin.png" alt="Admin">
                    <div class="name">Admin</div>
                </div>
            </div>
        </div>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="page-header">
                <h2>Dashboard Overview</h2>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stats-card">
                    <div class="card-header">
                        <div class="card-title">Total Users</div>
                        <div class="card-icon" style="background-color: var(--primary-color);">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-value"><?= $total_users ?></div>
                </div>
                
                <div class="stats-card">
                    <div class="card-header">
                        <div class="card-title">Contact Messages</div>
                        <div class="card-icon" style="background-color: var(--accent-color);">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    <div class="card-value"><?= $total_contacts ?></div>
                </div>
                
                <div class="stats-card">
                    <div class="card-header">
                        <div class="card-title">Pending Payments</div>
                        <div class="card-icon" style="background-color: var(--warning-color);">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="card-value"><?= $pending_payments ?></div>
                </div>
                
                <div class="stats-card">
                    <div class="card-header">
                        <div class="card-title">Total Revenue</div>
                        <div class="card-icon" style="background-color: var(--secondary-color);">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                    </div>
                    <div class="card-value">₹<?= number_format($total_revenue, 2) ?></div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="data-table" id="users-section">
                <div class="table-header">
                    <div class="table-title">Recent Users</div>
                    <a href="export.php?type=users" class="btn btn-primary">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
                
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($user['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-danger btn-delete-user" data-id="<?= $user['id'] ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Payments Table -->
           <!-- Payments Table -->
<div class="data-table" id="payments-section">
    <div class="table-header">
        <div class="table-title">Payment Submissions</div>
        <a href="export.php?type=payments" class="btn btn-primary">
            <i class="fas fa-download"></i> Export
        </a>
    </div>
    
    <table id="paymentsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Transaction ID</th>
                <th>Amount</th>
                <th>Receipt</th>
                <th>Status</th>
                <th>Submitted At</th>
                <th>Expires At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($payment = $payments->fetch_assoc()): ?>
            <tr>
                <td><?= $payment['id'] ?></td>
                <td><?= htmlspecialchars($payment['username']) ?></td>
                <td><?= htmlspecialchars($payment['transaction_id']) ?></td>
                <td>₹<?= number_format($payment['amount'], 2) ?></td>
                <td>
                    <?php if(!empty($payment['receipt_filename'])): ?>
                        <a href="receipts/<?= htmlspecialchars($payment['receipt_filename']) ?>" target="_blank" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    <?php else: ?>
                        <span class="text-muted">No receipt</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="status status-<?= $payment['status'] ?>">
                        <?= ucfirst($payment['status']) ?>
                    </span>
                </td>
                <td><?= date('M d, Y H:i', strtotime($payment['created_at'])) ?></td>
                <td>
                    <?= !empty($payment['access_expires_at']) ? date('M d, Y H:i', strtotime($payment['access_expires_at'])) : '-' ?>
                </td>
                <td>
                    <?php if($payment['status'] == 'pending'): ?>
                        <button class="btn btn-success btn-approve" data-id="<?= $payment['id'] ?>">
                            <i class="fas fa-check"></i> Verify
                        </button>
                        <button class="btn btn-danger btn-reject" data-id="<?= $payment['id'] ?>">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    <?php elseif($payment['status'] == 'verified'): ?>
                        <button class="btn btn-warning btn-revoke" data-id="<?= $payment['id'] ?>">
                            <i class="fas fa-undo"></i> Revoke
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
            
            <!-- Contact Messages Table -->
            <div class="data-table" id="contacts-section">
                <div class="table-header">
                    <div class="table-title">Recent Contact Messages</div>
                    <a href="export.php?type=contacts" class="btn btn-primary">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
                
                <table id="contactsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($contact = $contacts->fetch_assoc()): ?>
                        <tr>
                            <td><?= $contact['id'] ?></td>
                            <td><?= htmlspecialchars($contact['name']) ?></td>
                            <td><?= htmlspecialchars($contact['email']) ?></td>
                            <td><?= htmlspecialchars(substr($contact['message'], 0, 50)) . (strlen($contact['message']) > 50 ? '...' : '') ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($contact['submission_date'])) ?></td>
                            <td>
                                <button class="btn btn-danger btn-delete-contact" data-id="<?= $contact['id'] ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Message View Modal -->
    <div id="messageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center;">
        <div style="background-color: white; padding: 20px; border-radius: 8px; max-width: 600px; width: 90%;">
            <h3 style="margin-bottom: 15px;">Message Details</h3>
            <p id="messageContent" style="margin-bottom: 20px;"></p>
            <button id="closeModal" style="padding: 8px 15px; background-color: var(--primary-color); color: white; border: none; border-radius: 4px; cursor: pointer;">Close</button>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#usersTable').DataTable({
                responsive: true,
                "pageLength": 5,
                "lengthChange": false
            });
            
            $('#paymentsTable').DataTable({
                responsive: true,
                "pageLength": 10,
                "lengthChange": false
            });
            
            $('#contactsTable').DataTable({
                responsive: true,
                "pageLength": 5,
                "lengthChange": false
            });
            
          // Handle payment approval
// Handle payment approval
// Handle payment approval
// Enhanced payment approval handler
// Handle payment approval
// Handle payment approval
// // Handle payment rejection
// Handle payment approval
// Handle payment approval with event delegation
$(document).on('click', '.btn-approve', function() {
    const paymentId = $(this).data('id');
    const row = $(this).closest('tr');
    
    console.log("Approve button clicked for payment ID:", paymentId);
    
    if (confirm('Approve this payment and grant access?')) {
        const button = $(this);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing');
        
        $.ajax({
            url: 'update_payment_status.php',
            method: 'POST',
            data: {
                payment_id: paymentId,
                status: 'verified',
                admin_notes: 'Approved by admin'
            },
            dataType: 'json',
            success: function(response) {
                console.log("Server response:", response);
                if (response.success) {
                    // Update the row immediately
                    row.find('.status')
                        .removeClass('status-pending')
                        .addClass('status-verified')
                        .text('Verified');
                    
                    // Update expiration date if provided
                    if (response.expires_at) {
                        const expiresAt = new Date(response.expires_at);
                        row.find('td:nth-child(8)').text(expiresAt.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }));
                    }
                    
                    // Update actions
                    row.find('td:last').html(`
                        <button class="btn btn-warning btn-revoke" data-id="${paymentId}">
                            <i class="fas fa-undo"></i> Revoke
                        </button>
                    `);
                    
                    showAlert('Payment approved successfully!', 'success');
                    
                    // Update stats counters
                    updateStatsCounters();
                } else {
                    const errorMsg = response.message || 'Failed to approve payment';
                    console.error("Error:", errorMsg);
                    showAlert('Error: ' + errorMsg, 'error');
                    button.prop('disabled', false).html('<i class="fas fa-check"></i> Verify');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr.responseText);
                showAlert('Error: ' + error, 'error');
                button.prop('disabled', false).html('<i class="fas fa-check"></i> Verify');
            }
        });
    }
});

// Handle payment rejection with event delegation
$(document).on('click', '.btn-reject', function() {
    const paymentId = $(this).data('id');
    const row = $(this).closest('tr');
    const reason = prompt('Enter rejection reason:');
    
    if (reason !== null && reason.trim() !== '') {
        const button = $(this);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing');
        
        $.ajax({
            url: 'update_payment_status.php',
            method: 'POST',
            data: {
                payment_id: paymentId,
                status: 'rejected',
                admin_notes: reason
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the row immediately
                    row.find('.status')
                        .removeClass('status-pending')
                        .addClass('status-rejected')
                        .text('Rejected');
                    
                    // Clear expiration date
                    row.find('td:nth-child(8)').text('-');
                    
                    // Remove action buttons
                    row.find('td:last').html('');
                    
                    showAlert('Payment rejected successfully!', 'warning');
                    
                    // Update stats counters
                    updateStatsCounters();
                } else {
                    showAlert('Error: ' + (response.message || 'Failed to reject payment'), 'error');
                    button.prop('disabled', false).html('<i class="fas fa-times"></i> Reject');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Error: ' + error, 'error');
                console.error('AJAX Error:', status, error, xhr.responseText);
                button.prop('disabled', false).html('<i class="fas fa-times"></i> Reject');
            }
        });
    } else if (reason !== null) {
        alert('Please provide a rejection reason');
    }
});
// Function to update stats counters
function updateStatsCounters() {
    $.get('get_stats.php', function(data) {
        $('.stats-card:nth-child(1) .card-value').text(data.total_users);
        $('.stats-card:nth-child(2) .card-value').text(data.total_contacts);
        $('.stats-card:nth-child(3) .card-value').text(data.pending_payments);
        $('.stats-card:nth-child(4) .card-value').text('₹' + data.total_revenue);
    }, 'json');
}// Handle payment revocation
$('.btn-revoke').click(function() {
    const paymentId = $(this).data('id');
    if (confirm('Revoke this payment and remove user access?')) {
        const button = $(this);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing');
        
        $.ajax({
            url: 'update_payment_status.php',
            method: 'POST',
            data: {
                payment_id: paymentId,
                status: 'rejected',
                admin_notes: 'Access revoked by admin'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('Payment revoked successfully!', 'warning');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Error: ' + (response.message || 'Unknown error'), 'error');
                    button.prop('disabled', false).html('<i class="fas fa-undo"></i> Revoke');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Error: ' + error, 'error');
                console.error('AJAX Error:', status, error);
                button.prop('disabled', false).html('<i class="fas fa-undo"></i> Revoke');
            }
        });
    }
});
            
            // Handle user deletion
            $('.btn-delete-user').click(function() {
                const userId = $(this).data('id');
                if (confirm('Are you sure you want to delete this user and all their data?')) {
                    $.ajax({
                        url: 'delete_user.php',
                        method: 'POST',
                        data: { user_id: userId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showAlert('User deleted successfully', 'success');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                showAlert('Error: ' + (response.message || 'Unknown error'), 'error');
                            }
                        },
                        error: function() {
                            showAlert('Network error - please try again', 'error');
                        }
                    });
                }
            });
            
            // Handle contact message deletion
            $('.btn-delete-contact').click(function() {
                const contactId = $(this).data('id');
                if (confirm('Are you sure you want to delete this contact message?')) {
                    $.ajax({
                        url: 'delete_contact.php',
                        method: 'POST',
                        data: { contact_id: contactId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showAlert('Contact message deleted successfully', 'success');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                showAlert('Error: ' + (response.message || 'Unknown error'), 'error');
                            }
                        },
                        error: function() {
                            showAlert('Network error - please try again', 'error');
                        }
                    });
                }
            });
            
            // Show alert message
            function showAlert(message, type) {
                const alert = $(`<div class="alert alert-${type}">${message}</div>`);
                $('.content-wrapper').prepend(alert);
                setTimeout(() => alert.fadeOut(), 3000);
            }
        });
    </script>
</body>
</html>