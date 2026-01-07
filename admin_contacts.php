<?php
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_messages':
            $page = (int)($_GET['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? 'all';
            $search = $_GET['search'] ?? '';
            
            $whereClause = "1=1";
            $params = [];
            $types = "";
            
            if ($status !== 'all') {
                $whereClause .= " AND status = ?";
                $params[] = $status;
                $types .= "s";
            }
            
            if ($search) {
                $whereClause .= " AND (name LIKE ? OR email LIKE ? OR message LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                $types .= "sss";
            }
            
            // Get total count
            $countQuery = $conn->prepare("SELECT COUNT(*) as total FROM contact_messages WHERE $whereClause");
            if ($params) {
                $countQuery->bind_param($types, ...$params);
            }
            $countQuery->execute();
            $totalCount = $countQuery->get_result()->fetch_assoc()['total'];
            
            // Get messages
            $query = $conn->prepare("
                SELECT contact_id, name, email, message, status, created_at, ip_address,
                       CASE 
                           WHEN created_at > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'Today'
                           WHEN created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'This Week'
                           WHEN created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'This Month'
                           ELSE 'Older'
                       END as time_category
                FROM contact_messages 
                WHERE $whereClause 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            $query->bind_param($types, ...$params);
            $query->execute();
            $result = $query->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'messages' => $messages,
                'total' => $totalCount,
                'page' => $page,
                'totalPages' => ceil($totalCount / $limit)
            ]);
            exit;
            
        case 'get_message':
            $contactId = (int)$_GET['id'];
            
            $query = $conn->prepare("
                SELECT contact_id, name, email, message, status, created_at, updated_at, 
                       ip_address, user_agent, admin_notes, replied_at, replied_by
                FROM contact_messages 
                WHERE contact_id = ?
            ");
            $query->bind_param("i", $contactId);
            $query->execute();
            $result = $query->get_result();
            
            if ($result->num_rows > 0) {
                $message = $result->fetch_assoc();
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Message not found']);
            }
            exit;
            
        case 'update_status':
            $contactId = (int)$_POST['contact_id'];
            $newStatus = $_POST['status'];
            $adminNotes = $_POST['admin_notes'] ?? '';
            
            $validStatuses = ['new', 'read', 'replied', 'closed'];
            if (!in_array($newStatus, $validStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            $updateQuery = $conn->prepare("
                UPDATE contact_messages 
                SET status = ?, admin_notes = ?, updated_at = NOW()
                WHERE contact_id = ?
            ");
            $updateQuery->bind_param("ssi", $newStatus, $adminNotes, $contactId);
            
            if ($updateQuery->execute()) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
            exit;
            
        case 'delete_message':
            $contactId = (int)$_POST['contact_id'];
            
            $deleteQuery = $conn->prepare("DELETE FROM contact_messages WHERE contact_id = ?");
            $deleteQuery->bind_param("i", $contactId);
            
            if ($deleteQuery->execute()) {
                echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
            }
            exit;
            
        case 'get_stats':
            $statsQuery = $conn->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
                    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
                    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
                    SUM(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as today_count,
                    SUM(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_count
                FROM contact_messages
            ");
            $stats = $statsQuery->fetch_assoc();
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            exit;
    }
}

// Get basic stats for display
$statsQuery = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count
    FROM contact_messages
");
$stats = $statsQuery->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - NDMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1f2937;
        }

        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #6b7280;
            font-weight: 500;
        }

        .stat-card.new { border-left: 4px solid #f59e0b; }
        .stat-card.read { border-left: 4px solid #3b82f6; }
        .stat-card.replied { border-left: 4px solid #10b981; }
        .stat-card.closed { border-left: 4px solid #6b7280; }

        .controls {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .filter-select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            background: white;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .messages-table {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .table tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-new {
            background: #fef3c7;
            color: #92400e;
        }

        .status-read {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-replied {
            background: #d1fae5;
            color: #065f46;
        }

        .status-closed {
            background: #f3f4f6;
            color: #374151;
        }

        .message-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.75rem;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .pagination button.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .pagination button:hover:not(.active) {
            background: #f3f4f6;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }

        .no-messages {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: auto;
            }

            .table-container {
                overflow-x: auto;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-envelope"></i> Contact Messages</h1>
        <p>Manage and respond to contact form submissions</p>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card new">
                <h3><?= $stats['new_count'] ?></h3>
                <p>New Messages</p>
            </div>
            <div class="stat-card read">
                <h3><?= $stats['read_count'] ?></h3>
                <p>Read Messages</p>
            </div>
            <div class="stat-card replied">
                <h3><?= $stats['replied_count'] ?></h3>
                <p>Replied Messages</p>
            </div>
            <div class="stat-card closed">
                <h3><?= $stats['closed_count'] ?></h3>
                <p>Closed Messages</p>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search messages by name, email, or content...">
            </div>
            <select id="statusFilter" class="filter-select">
                <option value="all">All Status</option>
                <option value="new">New</option>
                <option value="read">Read</option>
                <option value="replied">Replied</option>
                <option value="closed">Closed</option>
            </select>
            <button class="btn btn-primary" onclick="refreshMessages()">
                <i class="fas fa-sync-alt"></i>
                Refresh
            </button>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Messages Table -->
        <div class="messages-table">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="messagesTable">
                        <tr>
                            <td colspan="6" class="loading">
                                <i class="fas fa-spinner fa-spin"></i> Loading messages...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
    </div>

    <!-- Modal for viewing/editing message -->
    <div class="modal" id="messageModal">
        <div class="modal-content">
            <h3 id="modalTitle">Message Details</h3>
            <div id="modalContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button class="btn btn-primary" onclick="closeModal()">Close</button>
                <button class="btn btn-success" id="saveChangesBtn" onclick="saveChanges()" style="display: none;">
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let currentStatus = 'all';
        let currentSearch = '';

        // Load messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadMessages();
            
            // Set up event listeners
            document.getElementById('searchInput').addEventListener('input', debounce(function() {
                currentSearch = this.value;
                currentPage = 1;
                loadMessages();
            }, 500));
            
            document.getElementById('statusFilter').addEventListener('change', function() {
                currentStatus = this.value;
                currentPage = 1;
                loadMessages();
            });
        });

        // Debounce function for search
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Load messages from server
        async function loadMessages() {
            try {
                const response = await fetch(`?action=get_messages&page=${currentPage}&status=${currentStatus}&search=${encodeURIComponent(currentSearch)}`);
                const data = await response.json();
                
                if (data.success) {
                    displayMessages(data.messages);
                    displayPagination(data.page, data.totalPages);
                } else {
                    document.getElementById('messagesTable').innerHTML = '<tr><td colspan="6" class="no-messages">Error loading messages</td></tr>';
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                document.getElementById('messagesTable').innerHTML = '<tr><td colspan="6" class="no-messages">Error loading messages</td></tr>';
            }
        }

        // Display messages in table
        function displayMessages(messages) {
            const tableBody = document.getElementById('messagesTable');
            
            if (messages.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="no-messages">No messages found</td></tr>';
                return;
            }
            
            tableBody.innerHTML = messages.map(message => `
                <tr>
                    <td>${escapeHtml(message.name)}</td>
                    <td>${escapeHtml(message.email)}</td>
                    <td class="message-preview" title="${escapeHtml(message.message)}">${escapeHtml(message.message.substring(0, 50))}${message.message.length > 50 ? '...' : ''}</td>
                    <td><span class="status-badge status-${message.status}">${message.status}</span></td>
                    <td>${new Date(message.created_at).toLocaleDateString()}</td>
                    <td class="actions">
                        <button class="btn btn-primary btn-sm" onclick="viewMessage(${message.contact_id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-success btn-sm" onclick="editMessage(${message.contact_id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteMessage(${message.contact_id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        // Display pagination
        function displayPagination(currentPage, totalPages) {
            const pagination = document.getElementById('pagination');
            
            if (totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }
            
            let buttons = [];
            
            // Previous button
            if (currentPage > 1) {
                buttons.push(`<button onclick="changePage(${currentPage - 1})">Previous</button>`);
            }
            
            // Page numbers
            for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
                buttons.push(`<button class="${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`);
            }
            
            // Next button
            if (currentPage < totalPages) {
                buttons.push(`<button onclick="changePage(${currentPage + 1})">Next</button>`);
            }
            
            pagination.innerHTML = buttons.join('');
        }

        // Change page
        function changePage(page) {
            currentPage = page;
            loadMessages();
        }

        // Refresh messages
        function refreshMessages() {
            loadMessages();
        }

        // View message details
        async function viewMessage(contactId) {
            try {
                showModal('View Message', '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading message details...</div>');
                
                const response = await fetch(`?action=get_message&id=${contactId}`);
                const data = await response.json();
                
                if (data.success) {
                    const message = data.message;
                    const modalContent = `
                        <div class="message-details">
                            <div class="form-group">
                                <label><strong>From:</strong></label>
                                <p>${escapeHtml(message.name)} (${escapeHtml(message.email)})</p>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Status:</strong></label>
                                <span class="status-badge status-${message.status}">${message.status}</span>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Submitted:</strong></label>
                                <p>${new Date(message.created_at).toLocaleString()}</p>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Message:</strong></label>
                                <div style="background: #f9fafb; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; white-space: pre-wrap;">${escapeHtml(message.message)}</div>
                            </div>
                            
                            ${message.admin_notes ? `
                                <div class="form-group">
                                    <label><strong>Admin Notes:</strong></label>
                                    <div style="background: #fef3c7; padding: 1rem; border-radius: 0.5rem; border: 1px solid #f59e0b; white-space: pre-wrap;">${escapeHtml(message.admin_notes)}</div>
                                </div>
                            ` : ''}
                            
                            <div class="form-group">
                                <label><strong>Technical Info:</strong></label>
                                <div style="font-size: 0.875rem; color: #6b7280;">
                                    <p><strong>IP Address:</strong> ${escapeHtml(message.ip_address)}</p>
                                    <p><strong>User Agent:</strong> ${escapeHtml(message.user_agent)}</p>
                                    ${message.updated_at !== message.created_at ? `<p><strong>Last Updated:</strong> ${new Date(message.updated_at).toLocaleString()}</p>` : ''}
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                            <button class="btn btn-primary" onclick="editMessage(${contactId})">
                                <i class="fas fa-edit"></i> Edit Message
                            </button>
                            <button class="btn btn-success" onclick="markAsReplied(${contactId})">
                                <i class="fas fa-reply"></i> Mark as Replied
                            </button>
                            <button class="btn btn-danger" onclick="deleteMessage(${contactId}); closeModal();">
                                <i class="fas fa-trash"></i> Delete Message
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('modalContent').innerHTML = modalContent;
                    
                    // Mark as read if it's new
                    if (message.status === 'new') {
                        await updateMessageStatus(contactId, 'read', '');
                    }
                } else {
                    document.getElementById('modalContent').innerHTML = '<div class="no-messages">Error: ' + data.message + '</div>';
                }
            } catch (error) {
                console.error('Error viewing message:', error);
                document.getElementById('modalContent').innerHTML = '<div class="no-messages">Error loading message details</div>';
            }
        }

        // Edit message
        async function editMessage(contactId) {
            try {
                showModal('Edit Message', '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading message details...</div>');
                
                const response = await fetch(`?action=get_message&id=${contactId}`);
                const data = await response.json();
                
                if (data.success) {
                    const message = data.message;
                    const modalContent = `
                        <form id="editMessageForm" onsubmit="saveMessageChanges(event, ${contactId})">
                            <div class="form-group">
                                <label><strong>From:</strong></label>
                                <p>${escapeHtml(message.name)} (${escapeHtml(message.email)})</p>
                                <small style="color: #6b7280;">Submitted: ${new Date(message.created_at).toLocaleString()}</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="status"><strong>Status:</strong></label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="new" ${message.status === 'new' ? 'selected' : ''}>New</option>
                                    <option value="read" ${message.status === 'read' ? 'selected' : ''}>Read</option>
                                    <option value="replied" ${message.status === 'replied' ? 'selected' : ''}>Replied</option>
                                    <option value="closed" ${message.status === 'closed' ? 'selected' : ''}>Closed</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Original Message:</strong></label>
                                <div style="background: #f9fafb; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; white-space: pre-wrap; max-height: 150px; overflow-y: auto;">${escapeHtml(message.message)}</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_notes"><strong>Admin Notes:</strong></label>
                                <textarea id="admin_notes" name="admin_notes" rows="4" placeholder="Add internal notes about this message..." style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; resize: vertical;">${message.admin_notes || ''}</textarea>
                                <small style="color: #6b7280;">These notes are for internal use only and will not be visible to the user.</small>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Quick Actions:</strong></label>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                    <button type="button" class="btn btn-sm" style="background: #e5e7eb; color: #374151;" onclick="addQuickNote('Contacted user via phone')">
                                        <i class="fas fa-phone"></i> Phone Contact
                                    </button>
                                    <button type="button" class="btn btn-sm" style="background: #e5e7eb; color: #374151;" onclick="addQuickNote('Sent email response')">
                                        <i class="fas fa-envelope"></i> Email Sent
                                    </button>
                                    <button type="button" class="btn btn-sm" style="background: #e5e7eb; color: #374151;" onclick="addQuickNote('Issue resolved')">
                                        <i class="fas fa-check"></i> Resolved
                                    </button>
                                    <button type="button" class="btn btn-sm" style="background: #e5e7eb; color: #374151;" onclick="addQuickNote('Requires follow-up')">
                                        <i class="fas fa-clock"></i> Follow-up
                                    </button>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-primary" onclick="composeReply('${escapeHtml(message.email)}', '${escapeHtml(message.name)}')">
                                    <i class="fas fa-reply"></i> Compose Reply
                                </button>
                            </div>
                        </form>
                    `;
                    
                    document.getElementById('modalContent').innerHTML = modalContent;
                    document.getElementById('saveChangesBtn').style.display = 'none'; // Hide default save button
                } else {
                    document.getElementById('modalContent').innerHTML = '<div class="no-messages">Error: ' + data.message + '</div>';
                }
            } catch (error) {
                console.error('Error editing message:', error);
                document.getElementById('modalContent').innerHTML = '<div class="no-messages">Error loading message details</div>';
            }
        }

        // Save message changes
        async function saveMessageChanges(event, contactId) {
            event.preventDefault();
            
            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(form);
                formData.append('contact_id', contactId);
                
                const response = await fetch('?action=update_status', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Message updated successfully!');
                    closeModal();
                    loadMessages(); // Refresh the table
                } else {
                    alert('Error updating message: ' + data.message);
                }
            } catch (error) {
                console.error('Error saving changes:', error);
                alert('Error saving changes');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }

        // Add quick note helper
        function addQuickNote(note) {
            const notesField = document.getElementById('admin_notes');
            const currentValue = notesField.value.trim();
            const timestamp = new Date().toLocaleString();
            const newNote = `[${timestamp}] ${note}`;
            
            if (currentValue) {
                notesField.value = currentValue + '\n' + newNote;
            } else {
                notesField.value = newNote;
            }
        }

        // Compose reply (opens email client)
        function composeReply(email, name) {
            const subject = encodeURIComponent('Re: Your NDMS Contact Form Inquiry');
            const body = encodeURIComponent(`Dear ${name},\n\nThank you for contacting the National Digital Management System.\n\n\n\nBest regards,\nNDMS Support Team`);
            
            window.open(`mailto:${email}?subject=${subject}&body=${body}`);
        }

        // Mark as replied helper
        async function markAsReplied(contactId) {
            try {
                const formData = new FormData();
                formData.append('contact_id', contactId);
                formData.append('status', 'replied');
                formData.append('admin_notes', `[${new Date().toLocaleString()}] Marked as replied`);
                
                const response = await fetch('?action=update_status', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Message marked as replied!');
                    closeModal();
                    loadMessages();
                } else {
                    alert('Error updating status: ' + data.message);
                }
            } catch (error) {
                console.error('Error marking as replied:', error);
                alert('Error updating status');
            }
        }

        // Delete message
        async function deleteMessage(contactId) {
            if (!confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('contact_id', contactId);
                
                const response = await fetch('?action=delete_message', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Message deleted successfully!');
                    loadMessages(); // Refresh the table
                } else {
                    alert('Error deleting message: ' + data.message);
                }
            } catch (error) {
                console.error('Error deleting message:', error);
                alert('Error deleting message');
            }
        }

        // Update message status helper
        async function updateMessageStatus(contactId, status, notes) {
            try {
                const formData = new FormData();
                formData.append('contact_id', contactId);
                formData.append('status', status);
                formData.append('admin_notes', notes);
                
                await fetch('?action=update_status', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Error updating status:', error);
            }
        }

        // Delete message
        async function deleteMessage(contactId) {
            if (!confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('contact_id', contactId);
                
                const response = await fetch('?action=delete_message', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Message deleted successfully');
                    loadMessages();
                } else {
                    alert('Error deleting message: ' + data.message);
                }
            } catch (error) {
                console.error('Error deleting message:', error);
                alert('Error deleting message');
            }
        }

        // Show modal
        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showModal(title, content) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('messageModal').classList.add('show');
        }

        // Close modal
        function closeModal() {
            document.getElementById('messageModal').classList.remove('show');
        }

        // Save changes
        function saveChanges() {
            // Implementation for saving changes
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal when clicking outside
        document.getElementById('messageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
