<?php
// notification_component.php
// Reusable notification bell component for any NDMS page

// This file should be included after config.php and after user authentication

if (!isset($_SESSION['UserID'])) {
    return; // Don't show notifications if not logged in
}

$userRole = $_SESSION['Role'];
?>

<!-- Notification Bell Component -->
<div id="notification-container" style="position: relative; display: inline-block;">
    <div id="notification-bell" style="cursor: pointer; position: relative;">
        🔔
        <span id="notification-badge" class="notification-badge" style="
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            display: none;
            min-width: 18px;
            text-align: center;
        ">0</span>
    </div>
</div>

<!-- Notification Panel - Separate from container -->
<div id="notification-backdrop" class="notification-backdrop" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(2px);
    z-index: 99999998;
    display: none;
"></div>

<div id="notification-panel" style="
    position: fixed;
    top: 80px;
    right: 20px;
    background: white;
    border: 2px solid rgba(16, 185, 129, 0.2);
    border-radius: 15px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3), 0 8px 10px -6px rgba(0,0,0,0.2);
    width: 380px;
    max-height: 450px;
    overflow-y: auto;
    z-index: 99999999;
    display: none;
">
        <div style="padding: 15px; border-bottom: 1px solid #eee; background: #f8f9fa; border-radius: 8px 8px 0 0;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h6 style="margin: 0; color: #495057;">Notifications</h6>
                <button id="mark-all-seen" style="
                    background: none;
                    border: none;
                    color: #007cba;
                    font-size: 12px;
                    cursor: pointer;
                    text-decoration: underline;
                ">Mark all as read</button>
            </div>
        </div>
        
        <div id="notification-list" style="max-height: 300px; overflow-y: auto;">
            <!-- Notifications will be loaded here -->
        </div>
        
        <div style="padding: 10px; text-align: center; border-top: 1px solid #eee;">
            <small style="color: #6c757d;">Click notifications to mark as read</small>
        </div>
    </div>
</div>

<style>
.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-message {
    font-size: 14px;
    color: #495057;
    margin-bottom: 4px;
    line-height: 1.4;
}

.notification-date {
    font-size: 11px;
    color: #6c757d;
}

.notification-unread {
    background-color: #e3f2fd;
    border-left: 3px solid #007cba;
}

.no-notifications {
    padding: 20px;
    text-align: center;
    color: #6c757d;
    font-style: italic;
}

/* Ensure notification panel always appears on top */
#notification-container {
    position: relative !important;
    z-index: 999999 !important;
}

#notification-panel {
    z-index: 99999999 !important;
    position: fixed !important;
    top: 80px !important;
    right: 20px !important;
    width: 380px !important;
    max-height: 450px !important;
    border-radius: 15px !important;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3), 0 8px 10px -6px rgba(0,0,0,0.2) !important;
}

#notification-backdrop {
    z-index: 99999998 !important;
    position: fixed !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.getElementById('notification-bell');
    const notificationBadge = document.getElementById('notification-badge');
    let notificationPanel = document.getElementById('notification-panel');
    let notificationBackdrop = document.getElementById('notification-backdrop');
    let notificationList, markAllSeenBtn;
    
    // Move panel and backdrop to body to escape any container constraints
    if (notificationPanel && notificationBackdrop) {
        document.body.appendChild(notificationPanel);
        document.body.appendChild(notificationBackdrop);
        
        // Get elements after moving
        notificationList = document.getElementById('notification-list');
        markAllSeenBtn = document.getElementById('mark-all-seen');
    }
    
    let panelOpen = false;
    
    // Toggle notification panel
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        if (panelOpen) {
            closePanel();
        } else {
            openPanel();
        }
    });
    
    // Close panel when clicking backdrop
    notificationBackdrop.addEventListener('click', function() {
        closePanel();
    });
    
    // Close panel when clicking outside (fallback)
    document.addEventListener('click', function(e) {
        if (panelOpen && !notificationPanel.contains(e.target) && !notificationBell.contains(e.target)) {
            closePanel();
        }
    });
    
    // Prevent panel from closing when clicking inside it
    notificationPanel.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Close panel on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && panelOpen) {
            closePanel();
        }
    });
    
    // Mark all as seen
    if (markAllSeenBtn) {
        markAllSeenBtn.addEventListener('click', function() {
            fetch('notifications_api.php?action=mark_all_seen', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    updateNotificationCount();
                }
            });
        });
    }
    
    function openPanel() {
        if (!notificationPanel || !notificationBackdrop) return;
        
        // Position panel dynamically based on bell position
        const bellRect = notificationBell.getBoundingClientRect();
        const panel = notificationPanel;
        
        // Calculate optimal position
        let top = bellRect.bottom + 10;
        let right = window.innerWidth - bellRect.right;
        
        // Adjust if panel would go off screen
        if (top + 450 > window.innerHeight) {
            top = Math.max(10, bellRect.top - 450);
        }
        if (right + 380 > window.innerWidth) {
            right = 10;
        }
        
        panel.style.top = top + 'px';
        panel.style.right = right + 'px';
        panel.style.display = 'block';
        notificationBackdrop.style.display = 'block';
        
        panelOpen = true;
        loadNotifications();
        
        // Add opening animation
        panel.style.opacity = '0';
        panel.style.transform = 'translateY(-10px) scale(0.95)';
        panel.style.transition = 'all 0.2s ease-out';
        
        setTimeout(() => {
            panel.style.opacity = '1';
            panel.style.transform = 'translateY(0) scale(1)';
        }, 10);
    }
    
    function closePanel() {
        if (!notificationPanel || !notificationBackdrop) return;
        
        const panel = notificationPanel;
        
        // Add closing animation
        panel.style.opacity = '0';
        panel.style.transform = 'translateY(-10px) scale(0.95)';
        
        setTimeout(() => {
            panel.style.display = 'none';
            notificationBackdrop.style.display = 'none';
            panelOpen = false;
        }, 200);
    }
    
    function loadNotifications() {
        fetch('notifications_api.php?action=get_notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
            }
        });
    }
    
    function displayNotifications(notifications) {
        if (!notificationList) return;
        
        if (notifications.length === 0) {
            notificationList.innerHTML = '<div class="no-notifications">No new notifications</div>';
            return;
        }
        
        let html = '';
        notifications.forEach(notification => {
            html += `
                <div class="notification-item notification-unread" data-id="${notification.NotificationID}">
                    <div class="notification-message">${notification.Message}</div>
                    <div class="notification-date">${formatDate(notification.CreatedAt)}</div>
                </div>
            `;
        });
        
        notificationList.innerHTML = html;
        
        // Add click handlers to mark as seen
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                markAsSeen(notificationId, this);
            });
        });
    }
    
    function markAsSeen(notificationId, element) {
        const formData = new FormData();
        formData.append('notification_id', notificationId);
        
        fetch('notifications_api.php?action=mark_seen', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.classList.remove('notification-unread');
                element.style.opacity = '0.7';
                updateNotificationCount();
            }
        });
    }
    
    function updateNotificationCount() {
        fetch('notifications_api.php?action=get_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.count;
                if (count > 0) {
                    notificationBadge.textContent = count;
                    notificationBadge.style.display = 'block';
                } else {
                    notificationBadge.style.display = 'none';
                }
            }
        });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        if (diffMins < 60) {
            return diffMins <= 1 ? 'Just now' : `${diffMins} minutes ago`;
        } else if (diffHours < 24) {
            return diffHours === 1 ? '1 hour ago' : `${diffHours} hours ago`;
        } else if (diffDays === 1) {
            return 'Yesterday';
        } else if (diffDays < 7) {
            return `${diffDays} days ago`;
        } else {
            return date.toLocaleDateString();
        }
    }
    
    // Update notification count on page load
    updateNotificationCount();
    
    // Auto-refresh notifications every 5 minutes
    setInterval(updateNotificationCount, 5 * 60 * 1000);
});
</script>
