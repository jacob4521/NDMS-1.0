<?php
// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF']);

// Get user info for sidebar
$username = '';
$role = $_SESSION['Role'] ?? '';

if (isset($_SESSION['UserID'])) {
    $userResult = $conn->query("SEL                <div class="nav-item">
                    <a href="citizen_vaccination.php" class="nav-link <?= ($currentPage == 'citizen_vaccination.php') ? 'active' : '' ?>">
                        <span class="nav-icon">💉</span>
                        <span class="nav-text">Vaccinations</span>
                    </a>
                    <div class="nav-tooltip">Vaccinations</div>
                </div>
                <div class="nav-item">
                    <a href="career_guidance_form.php" class="nav-link <?= ($currentPage == 'career_guidance_form.php') ? 'active' : '' ?>">
                        <span class="nav-icon">🎯</span>
                        <span class="nav-text">Career Guidance</span>
                    </a>
                    <div class="nav-tooltip">Career Guidance Assessment</div>
                </div>
                <div class="nav-item">
                    <a href="change_password.php" class="nav-link <?= ($currentPage == 'change_password.php') ? 'active' : '' ?>">
                        <span class="nav-icon">🔐</span>
                        <span class="nav-text">Change Password</span>
                    </a>
                    <div class="nav-tooltip">Change Password</div>
                </div>ROM Users WHERE UserID = " . $_SESSION['UserID']);
    if ($userResult && $user = $userResult->fetch_assoc()) {
        $username = $user['Username'];
    }
}
?>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleMobileSidebar()">☰</button>

<!-- Mobile Overlay -->
<div class="mobile-overlay" onclick="toggleMobileSidebar()"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🇱🇰</div>
        <div class="sidebar-title">NDMS Portal</div>
        <div class="sidebar-toggle" onclick="toggleSidebar()">◀</div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Main Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <div class="nav-tooltip">Dashboard</div>
            </div>
        </div>
        
        <!-- Role-based Navigation -->
        <?php if ($role == "Admin"): ?>
            <div class="nav-section">
                <div class="nav-section-title">Administration</div>
                <div class="nav-item">
                    <a href="register.php" class="nav-link <?= ($currentPage == 'register.php') ? 'active' : '' ?>">
                        <span class="nav-icon">👤</span>
                        <span class="nav-text">Register Citizen</span>
                    </a>
                    <div class="nav-tooltip">Register New Citizen</div>
                </div>
                <div class="nav-item">
                    <a href="search_citizens.php" class="nav-link <?= ($currentPage == 'search_citizens.php') ? 'active' : '' ?>">
                        <span class="nav-icon">🔍</span>
                        <span class="nav-text">Citizen Directory</span>
                    </a>
                    <div class="nav-tooltip">Citizen Directory</div>
                </div>
                <div class="nav-item">
                    <a href="manage_users.php" class="nav-link <?= ($currentPage == 'manage_users.php') ? 'active' : '' ?>">
                        <span class="nav-icon">👥</span>
                        <span class="nav-text">Manage Users</span>
                    </a>
                    <div class="nav-tooltip">Manage Users</div>
                </div>
                <div class="nav-item">
                    <a href="manage_citizen_accounts.php" class="nav-link <?= ($currentPage == 'manage_citizen_accounts.php') ? 'active' : '' ?>">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-text">Citizen Accounts</span>
                    </a>
                    <div class="nav-tooltip">Manage Citizen Accounts</div>
                </div>
                <div class="nav-item">
                    <a href="admin_notifications.php" class="nav-link <?= ($currentPage == 'admin_notifications.php') ? 'active' : '' ?>">
                        <span class="nav-icon">🔔</span>
                        <span class="nav-text">Notifications</span>
                    </a>
                    <div class="nav-tooltip">Admin Notifications</div>
                </div>
                <div class="nav-item">
                    <a href="manage_subjects.php" class="nav-link <?= ($currentPage == 'manage_subjects.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📖</span>
                        <span class="nav-text">Manage Subjects</span>
                    </a>
                    <div class="nav-tooltip">Manage Academic Subjects</div>
                </div>
                <div class="nav-item">
                    <a href="admin_subscribers.php" class="nav-link <?= ($currentPage == 'admin_subscribers.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📧</span>
                        <span class="nav-text">Newsletter Subscribers</span>
                    </a>
                    <div class="nav-tooltip">Manage Newsletter Subscribers</div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($role == "MedicalOfficer"): ?>
            <div class="nav-section">
                <div class="nav-section-title">Medical Services</div>
                <div class="nav-item">
                    <a href="birth_certificate.php" class="nav-link <?= ($currentPage == 'birth_certificate.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📄</span>
                        <span class="nav-text">Birth Certificates</span>
                    </a>
                    <div class="nav-tooltip">Birth Certificates</div>
                </div>
                <div class="nav-item">
                    <a href="search_citizens.php" class="nav-link <?= ($currentPage == 'search_citizens.php') ? 'active' : '' ?>">
                        <span class="nav-icon">👥</span>
                        <span class="nav-text">Citizens</span>
                    </a>
                    <div class="nav-tooltip">Search Citizens</div>
                </div>
                <div class="nav-item">
                    <a href="manage_vaccines.php" class="nav-link <?= ($currentPage == 'manage_vaccines.php') ? 'active' : '' ?>">
                        <span class="nav-icon">💉</span>
                        <span class="nav-text">Vaccines</span>
                    </a>
                    <div class="nav-tooltip">Manage Vaccines</div>
                </div>
                <div class="nav-item">
                    <a href="manage_vaccination_schedule.php" class="nav-link <?= ($currentPage == 'manage_vaccination_schedule.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📅</span>
                        <span class="nav-text">Vaccination Schedule</span>
                    </a>
                    <div class="nav-tooltip">Vaccination Schedule</div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($role == "EducationOfficer"): ?>
            <div class="nav-section">
                <div class="nav-section-title">Education Services</div>
                <div class="nav-item">
                    <a href="add_education.php" class="nav-link <?= ($currentPage == 'add_education.php') ? 'active' : '' ?>">
                        <span class="nav-icon">🎓</span>
                        <span class="nav-text">Add Education</span>
                    </a>
                    <div class="nav-tooltip">Add Education Record</div>
                </div>
                <div class="nav-item">
                    <a href="view_education.php" class="nav-link <?= ($currentPage == 'view_education.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📚</span>
                        <span class="nav-text">View Education</span>
                    </a>
                    <div class="nav-tooltip">View Education Records</div>
                </div>
                <div class="nav-item">
                    <a href="manage_subjects.php" class="nav-link <?= ($currentPage == 'manage_subjects.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📖</span>
                        <span class="nav-text">Manage Subjects</span>
                    </a>
                    <div class="nav-tooltip">Manage Subjects</div>
                </div>
                <div class="nav-item">
                    <a href="search_citizens.php" class="nav-link <?= ($currentPage == 'search_citizens.php') ? 'active' : '' ?>">
                        <span class="nav-icon">👥</span>
                        <span class="nav-text">Citizens</span>
                    </a>
                    <div class="nav-tooltip">Search Citizens</div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Career Guidance Section -->
        <?php if (in_array($role, ['Admin', 'EducationOfficer'])): ?>
            <div class="nav-section">
                <div class="nav-section-title">Career Guidance</div>
                <div class="nav-item">
                    <a href="career_guidance_dashboard.php" class="nav-link <?= in_array($currentPage, ['career_guidance_dashboard.php']) ? 'active' : '' ?>">
                        <span class="nav-icon">🎯</span>
                        <span class="nav-text">Guidance Dashboard</span>
                    </a>
                    <div class="nav-tooltip">Career Guidance Dashboard</div>
                </div>
                <div class="nav-item">
                    <a href="career_guidance_form.php" class="nav-link <?= ($currentPage == 'career_guidance_form.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📝</span>
                        <span class="nav-text">Student Assessment</span>
                    </a>
                    <div class="nav-tooltip">New Student Assessment</div>
                </div>
                <div class="nav-item">
                    <a href="admin_career_guidance.php" class="nav-link <?= in_array($currentPage, ['admin_career_guidance.php', 'career_suggestions.php', 'generate_suggestions.php']) ? 'active' : '' ?>">
                        <span class="nav-icon">👥</span>
                        <span class="nav-text">Manage Students</span>
                    </a>
                    <div class="nav-tooltip">Manage Career Guidance Students</div>
                </div>
                <div class="nav-item">
                    <a href="career_guidance_testing.php" class="nav-link <?= ($currentPage == 'career_guidance_testing.php') ? 'active' : '' ?>">
                        <span class="nav-icon">🧪</span>
                        <span class="nav-text">Testing Lab</span>
                    </a>
                    <div class="nav-tooltip">Career Guidance Testing Lab</div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($role == "Employer"): ?>
            <div class="nav-section">
                <div class="nav-section-title">Employment Services</div>
                <div class="nav-item">
                    <a href="add_employment.php" class="nav-link <?= ($currentPage == 'add_employment.php') ? 'active' : '' ?>">
                        <span class="nav-icon">💼</span>
                        <span class="nav-text">Add Employment</span>
                    </a>
                    <div class="nav-tooltip">Add Employment Record</div>
                </div>
                <div class="nav-item">
                    <a href="view_employment.php" class="nav-link <?= ($currentPage == 'view_employment.php') ? 'active' : '' ?>">
                        <span class="nav-icon">👔</span>
                        <span class="nav-text">View Employment</span>
                    </a>
                    <div class="nav-tooltip">View Employment Records</div>
                </div>
                <div class="nav-item">
                    <a href="verify_employee.php" class="nav-link <?= ($currentPage == 'verify_employee.php') ? 'active' : '' ?>">
                        <span class="nav-icon">✅</span>
                        <span class="nav-text">Verify Employee</span>
                    </a>
                    <div class="nav-tooltip">Verify Employee</div>
                </div>
                <div class="nav-item">
                    <a href="search_citizens.php" class="nav-link <?= ($currentPage == 'search_citizens.php') ? 'active' : '' ?>">
                        <span class="nav-icon">👥</span>
                        <span class="nav-text">Citizens</span>
                    </a>
                    <div class="nav-tooltip">Search Citizens</div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($role == "Citizen"): ?>
            <div class="nav-section">
                <div class="nav-section-title">Citizen Services</div>
                <div class="nav-item">
                    <a href="citizen_dashboard.php" class="nav-link <?= ($currentPage == 'citizen_dashboard.php') ? 'active' : '' ?>">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <div class="nav-tooltip">Dashboard</div>
                </div>
                <div class="nav-item">
                    <a href="citizen_activities_citizen.php" class="nav-link <?= ($currentPage == 'citizen_activities_citizen.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📋</span>
                        <span class="nav-text">My Activities</span>
                    </a>
                    <div class="nav-tooltip">My Activities</div>
                </div>
                <div class="nav-item">
                    <a href="citizen_vaccination.php" class="nav-link <?= ($currentPage == 'citizen_vaccination.php') ? 'active' : '' ?>">
                        <span class="nav-icon">�</span>
                        <span class="nav-text">Vaccinations</span>
                    </a>
                    <div class="nav-tooltip">Vaccinations</div>
                </div>
                <div class="nav-item">
                    <a href="change_password.php" class="nav-link <?= ($currentPage == 'change_password.php') ? 'active' : '' ?>">
                        <span class="nav-icon">🔐</span>
                        <span class="nav-text">Change Password</span>
                    </a>
                    <div class="nav-tooltip">Change Password</div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Common Services -->
        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <div class="nav-item">
                <a href="login.php?logout=1" class="nav-link">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
                <div class="nav-tooltip">Logout</div>
            </div>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($username, 0, 1)) ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($username) ?></div>
                <div class="user-role"><?= $role ?></div>
            </div>
        </div>
    </div>
</div>
