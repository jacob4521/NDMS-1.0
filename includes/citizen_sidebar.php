<?php
// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF']);

// Get user info for sidebar
$username = '';
$role = $_SESSION['Role'] ?? '';

if (isset($_SESSION['UserID'])) {
    $userResult = $conn->query("SELECT Username FROM Users WHERE UserID = " . $_SESSION['UserID']);
    if ($userResult && $user = $userResult->fetch_assoc()) {
        $username = $user['Username'];
    }
}

// Get citizen information for personalized experience
$citizenName = '';
$citizenID = null;
if ($role == 'Citizen' && isset($_SESSION['UserID'])) {
    $citizenQuery = $conn->prepare("
        SELECT c.*, u.Username as LoginUsername
        FROM Citizens c 
        JOIN Users u ON c.Citizen_eID = u.Username
        WHERE u.UserID = ?
    ");
    $citizenQuery->bind_param("i", $_SESSION['UserID']);
    $citizenQuery->execute();
    $citizenResult = $citizenQuery->get_result();
    
    if ($citizenResult->num_rows > 0) {
        $citizen = $citizenResult->fetch_assoc();
        $citizenName = $citizen['FirstName'] . ' ' . $citizen['LastName'];
        $citizenID = $citizen['CitizenID'];
    }
}
?>

<style>
.citizen-sidebar {
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    position: fixed;
    left: 0;
    top: 0;
    transition: transform 0.3s ease;
    z-index: 1000;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.citizen-sidebar.collapsed {
    transform: translateX(-220px);
}

.citizen-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
}

.citizen-logo {
    font-size: 2.5rem;
    text-align: center;
    margin-bottom: 10px;
}

.citizen-title {
    color: white;
    font-size: 1.2rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 5px;
}

.citizen-subtitle {
    color: rgba(255,255,255,0.8);
    font-size: 0.9rem;
    text-align: center;
}

.citizen-toggle {
    position: absolute;
    right: 15px;
    top: 20px;
    background: rgba(255,255,255,0.2);
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.citizen-toggle:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.1);
}

.citizen-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.citizen-nav-section {
    margin-bottom: 25px;
}

.citizen-section-title {
    color: rgba(255,255,255,0.7);
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
    padding: 0 20px;
}

.citizen-nav-item {
    position: relative;
    margin: 5px 15px;
}

.citizen-nav-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.citizen-nav-link:hover {
    background: rgba(255,255,255,0.15);
    color: white;
    transform: translateX(5px);
    text-decoration: none;
}

.citizen-nav-link.active {
    background: rgba(255,255,255,0.2);
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.citizen-nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: #ffd700;
    border-radius: 2px;
}

.citizen-nav-icon {
    font-size: 1.2rem;
    margin-right: 12px;
    width: 24px;
    text-align: center;
}

.citizen-nav-text {
    font-size: 0.95rem;
    font-weight: 500;
}

.citizen-nav-badge {
    background: #ff4757;
    color: white;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: auto;
}

.citizen-sidebar-footer {
    padding: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.1);
}

.citizen-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.citizen-user-avatar {
    width: 45px;
    height: 45px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
}

.citizen-user-details {
    flex: 1;
}

.citizen-user-name {
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 2px;
}

.citizen-user-role {
    color: rgba(255,255,255,0.7);
    font-size: 0.8rem;
}

.citizen-quick-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.citizen-quick-btn {
    flex: 1;
    background: rgba(255,255,255,0.1);
    color: white;
    border: none;
    padding: 8px;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.citizen-quick-btn:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .citizen-sidebar {
        width: 100%;
        transform: translateX(-100%);
    }
    
    .citizen-sidebar.mobile-open {
        transform: translateX(0);
    }
}

/* Mobile Menu Button */
.citizen-mobile-menu-btn {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    font-size: 1.2rem;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.citizen-mobile-menu-btn:hover {
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .citizen-mobile-menu-btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

/* Mobile Overlay */
.citizen-mobile-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}

.citizen-mobile-overlay.active {
    display: block;
}

/* Highlight animation for support section */
@keyframes highlight {
    0% { background-color: transparent; }
    25% { background-color: rgba(59, 130, 246, 0.1); }
    50% { background-color: rgba(59, 130, 246, 0.2); }
    75% { background-color: rgba(59, 130, 246, 0.1); }
    100% { background-color: transparent; }
}
</style>

<!-- Mobile Menu Button -->
<button class="citizen-mobile-menu-btn" onclick="toggleCitizenMobileSidebar()">☰</button>

<!-- Mobile Overlay -->
<div class="citizen-mobile-overlay" onclick="toggleCitizenMobileSidebar()"></div>

<!-- Citizen Sidebar -->
<div class="citizen-sidebar" id="citizenSidebar">
    <div class="citizen-sidebar-header">
        <div class="citizen-logo">🇱🇰</div>
        <div class="citizen-title">NDMS Portal</div>
        <div class="citizen-subtitle">Citizen Services</div>
        <div class="citizen-toggle" onclick="toggleCitizenSidebar()">◀</div>
    </div>
    
    <nav class="citizen-nav">
        <!-- Dashboard Section -->
        <div class="citizen-nav-section">
            <div class="citizen-section-title">Dashboard</div>
            <div class="citizen-nav-item">
                <a href="citizen_dashboard.php" class="citizen-nav-link <?= ($currentPage == 'citizen_dashboard.php') ? 'active' : '' ?>">
                    <span class="citizen-nav-icon">🏠</span>
                    <span class="citizen-nav-text">My Dashboard</span>
                </a>
            </div>
            <div class="citizen-nav-item">
                <a href="citizen_activities_citizen.php" class="citizen-nav-link <?= ($currentPage == 'citizen_activities_citizen.php') ? 'active' : '' ?>">
                    <span class="citizen-nav-icon">📋</span>
                    <span class="citizen-nav-text">My Activities</span>
                </a>
            </div>
        </div>
        
        <!-- Health Services -->
        <div class="citizen-nav-section">
            <div class="citizen-section-title">Health Services</div>
            <div class="citizen-nav-item">
                <a href="citizen_vaccination.php" class="citizen-nav-link <?= ($currentPage == 'citizen_vaccination.php') ? 'active' : '' ?>">
                    <span class="citizen-nav-icon">💉</span>
                    <span class="citizen-nav-text">My Vaccinations</span>
                </a>
            </div>
            <?php if ($citizenID): ?>
            <div class="citizen-nav-item">
                <a href="view_citizen.php?citizen_id=<?= $citizenID ?>" class="citizen-nav-link <?= ($currentPage == 'view_citizen.php') ? 'active' : '' ?>">
                    <span class="citizen-nav-icon">📄</span>
                    <span class="citizen-nav-text">My Records</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Education & Career -->
        <div class="citizen-nav-section">
            <div class="citizen-section-title">Education & Career</div>
            <div class="citizen-nav-item">
                <a href="career_guidance_form.php" class="citizen-nav-link <?= ($currentPage == 'career_guidance_form.php') ? 'active' : '' ?>">
                    <span class="citizen-nav-icon">🎯</span>
                    <span class="citizen-nav-text">Career Guidance</span>
                </a>
            </div>
        </div>
        
        <!-- Document Services -->
        <div class="citizen-nav-section">
            <div class="citizen-section-title">Documents</div>
            <div class="citizen-nav-item">
                <a href="citizen_dashboard.php#certificates" class="citizen-nav-link" onclick="openCertificatesTab()">
                    <span class="citizen-nav-icon">📜</span>
                    <span class="citizen-nav-text">Certificates</span>
                </a>
            </div>
            <div class="citizen-nav-item">
                <a href="citizen_dashboard.php#certificates" class="citizen-nav-link" onclick="openCertificatesTab()">
                    <span class="citizen-nav-icon">📊</span>
                    <span class="citizen-nav-text">Reports</span>
                </a>
            </div>
        </div>
        
        <!-- Account Settings -->
        <div class="citizen-nav-section">
            <div class="citizen-section-title">Account</div>
            <div class="citizen-nav-item">
                <a href="change_password.php" class="citizen-nav-link <?= ($currentPage == 'change_password.php') ? 'active' : '' ?>">
                    <span class="citizen-nav-icon">🔐</span>
                    <span class="citizen-nav-text">Change Password</span>
                </a>
            </div>
            <div class="citizen-nav-item">
                <a href="login.php?logout=1" class="citizen-nav-link">
                    <span class="citizen-nav-icon">🚪</span>
                    <span class="citizen-nav-text">Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Help & Support -->
        <div class="citizen-nav-section">
            <div class="citizen-section-title">Support</div>
            <div class="citizen-nav-item">
                <a href="citizen_help.php" class="citizen-nav-link <?= ($currentPage == 'citizen_help.php') ? 'active' : '' ?>">
                    <span class="citizen-nav-icon">❓</span>
                    <span class="citizen-nav-text">Help & Support</span>
                </a>
            </div>
        </div>
    </nav>
    
    <div class="citizen-sidebar-footer">
        <div class="citizen-user-info">
            <div class="citizen-user-avatar">
                <?= strtoupper(substr($citizenName ?: $username, 0, 1)) ?>
            </div>
            <div class="citizen-user-details">
                <div class="citizen-user-name"><?= htmlspecialchars($citizenName ?: $username) ?></div>
                <div class="citizen-user-role">Citizen</div>
            </div>
        </div>
        <div class="citizen-quick-actions">
            <a href="citizen_help.php" class="citizen-quick-btn" style="text-decoration: none; color: white;">❓ Help</a>
            <a href="citizen_help.php#contact-support" class="citizen-quick-btn" style="text-decoration: none; color: white;" onclick="return goToSupport()">📞 Support</a>
        </div>
    </div>
</div>

<script>
function toggleCitizenSidebar() {
    const sidebar = document.getElementById('citizenSidebar');
    sidebar.classList.toggle('collapsed');
    
    // Add/remove body class for CSS targeting
    document.body.classList.toggle('citizen-sidebar-collapsed', sidebar.classList.contains('collapsed'));
    
    // Save state to localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('citizenSidebarCollapsed', isCollapsed);
}

function toggleCitizenMobileSidebar() {
    const sidebar = document.getElementById('citizenSidebar');
    const overlay = document.querySelector('.citizen-mobile-overlay');
    
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('active');
}

function openCertificatesTab() {
    // Store the target tab in localStorage so the dashboard can read it
    localStorage.setItem('targetTab', 'certificates');
    
    // If we're already on the dashboard page, show the tab immediately
    if (window.location.pathname.includes('citizen_dashboard.php')) {
        // Small delay to ensure the page is ready
        setTimeout(function() {
            if (typeof showTab === 'function') {
                showTab('certificates');
                // Update URL hash
                window.history.replaceState(null, null, '#certificates');
            }
        }, 100);
        return false; // Prevent navigation
    }
    
    // Otherwise, let the navigation proceed normally
    return true;
}

function goToSupport() {
    // If we're already on the help page, scroll to contact support section
    if (window.location.pathname.includes('citizen_help.php')) {
        setTimeout(function() {
            const contactSection = document.getElementById('contact-support');
            if (contactSection) {
                contactSection.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
                // Add highlight effect
                contactSection.style.animation = 'highlight 2s ease-in-out';
            }
        }, 100);
        return false; // Prevent navigation
    }
    
    // Store the target section for when the help page loads
    localStorage.setItem('targetSection', 'contact-support');
    
    // Otherwise, let the navigation proceed normally
    return true;
}

// Restore sidebar state on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add initial body class for sidebar
    document.body.classList.add('has-citizen-sidebar');
    
    const isCollapsed = localStorage.getItem('citizenSidebarCollapsed') === 'true';
    if (isCollapsed) {
        document.getElementById('citizenSidebar').classList.add('collapsed');
        document.body.classList.add('citizen-sidebar-collapsed');
    }
    
    // Check if we should open a specific tab (for dashboard page)
    const targetTab = localStorage.getItem('targetTab');
    if (targetTab && window.location.pathname.includes('citizen_dashboard.php')) {
        // Small delay to ensure the dashboard JS is loaded
        setTimeout(function() {
            if (typeof showTab === 'function') {
                showTab(targetTab);
                // Clear the stored target
                localStorage.removeItem('targetTab');
            }
        }, 500);
    }
    
    // Check if we should scroll to a specific section (for help page)
    const targetSection = localStorage.getItem('targetSection');
    if (targetSection && window.location.pathname.includes('citizen_help.php')) {
        // Small delay to ensure the page is loaded
        setTimeout(function() {
            const section = document.getElementById(targetSection);
            if (section) {
                section.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
                // Add highlight effect
                section.style.animation = 'highlight 2s ease-in-out';
                // Clear the stored target
                localStorage.removeItem('targetSection');
            }
        }, 500);
    }
    
    // Close mobile sidebar when clicking outside
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('citizenSidebar');
        const mobileBtn = document.querySelector('.citizen-mobile-menu-btn');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !mobileBtn.contains(e.target) && 
            sidebar.classList.contains('mobile-open')) {
            toggleCitizenMobileSidebar();
        }
    });
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('citizenSidebar');
    const overlay = document.querySelector('.citizen-mobile-overlay');
    
    if (window.innerWidth > 768) {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
    }
});
</script>
