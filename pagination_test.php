<?php
/**
 * Quick Pagination Test - Shows pagination with just 3 records per page
 * This will definitely show page numbers if you have more than 3 subscribers
 */

$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db   = "ndms"; 
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// TESTING: Use only 3 records per page to ensure pagination shows
$records_per_page = 3;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM subscribers");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get subscribers with pagination
$result = $conn->query("SELECT * FROM subscribers ORDER BY SubscribedAt DESC LIMIT $records_per_page OFFSET $offset");
$subscribers = [];
while ($row = $result->fetch_assoc()) {
    $subscribers[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pagination Test - NDMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug { background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .pagination { display: flex; gap: 10px; align-items: center; margin: 20px 0; }
        .page-btn { 
            padding: 8px 12px; 
            border: 2px solid #ccc; 
            background: white; 
            text-decoration: none; 
            border-radius: 5px; 
            color: #333;
        }
        .page-btn.active { background: #007bff; color: white; border-color: #007bff; }
        .page-btn:hover { background: #f8f9fa; }
        .page-btn.disabled { color: #ccc; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>

<h1>🧪 Pagination Test (3 per page)</h1>

<div class="debug">
    <h3>📊 Debug Information</h3>
    <strong>Total Records:</strong> <?= $total_records ?><br>
    <strong>Records Per Page:</strong> <?= $records_per_page ?><br>
    <strong>Total Pages:</strong> <?= $total_pages ?><br>
    <strong>Current Page:</strong> <?= $page ?><br>
    <strong>Offset:</strong> <?= $offset ?><br>
    <strong>Will Show Pagination?</strong> <?= ($total_pages > 1) ? '<span style="color: green; font-weight: bold;">YES</span>' : '<span style="color: red; font-weight: bold;">NO</span>' ?>
</div>

<?php if (empty($subscribers)): ?>
    <div style="text-align: center; padding: 40px; background: #fff3cd; border-radius: 8px;">
        <h3>⚠️ No Subscribers Found</h3>
        <p>Run the test data script first: <code>test_pagination_data.sql</code></p>
        <p>Or add some subscribers through the newsletter signup form.</p>
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Subscribed At</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subscribers as $subscriber): ?>
                <tr>
                    <td><?= $subscriber['SubscriberID'] ?></td>
                    <td><?= htmlspecialchars($subscriber['Email']) ?></td>
                    <td><?= date('M j, Y g:i A', strtotime($subscriber['SubscribedAt'])) ?></td>
                    <td><?= $subscriber['IsActive'] ? '✅ Active' : '❌ Inactive' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- THE PAGINATION SECTION -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <strong>📄 Pagination:</strong>
        
        <!-- Previous Button -->
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="page-btn">← Prev</a>
        <?php else: ?>
            <span class="page-btn disabled">← Prev</span>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <!-- Next Button -->
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>" class="page-btn">Next →</a>
        <?php else: ?>
            <span class="page-btn disabled">Next →</span>
        <?php endif; ?>
        
        <div style="margin-left: 20px; color: #666;">
            Page <?= $page ?> of <?= $total_pages ?> | 
            Showing <?= ($offset + 1) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
        </div>
    </div>
<?php else: ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px;">
        <strong>❌ Pagination Not Shown</strong><br>
        Reason: Only <?= $total_records ?> records (need more than <?= $records_per_page ?> for pagination)
    </div>
<?php endif; ?>

<hr style="margin: 30px 0;">

<div style="display: flex; gap: 15px;">
    <a href="admin_subscribers.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        ← Back to Admin Page
    </a>
    
    <a href="test_subscribers_data.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        📊 Check Database
    </a>
    
    <a href="homepage.php" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🏠 Add Subscribers via Homepage
    </a>
</div>

</body>
</html>
