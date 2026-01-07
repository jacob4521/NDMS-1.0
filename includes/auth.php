<?php
// Session authentication helper
function requireAuth() {
    if (!isset($_SESSION['UserID'])) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($requiredRole) {
    requireAuth();
    if ($_SESSION['Role'] !== $requiredRole) {
        die("Access denied. Required role: $requiredRole");
    }
}

function isLoggedIn() {
    return isset($_SESSION['UserID']);
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'UserID' => $_SESSION['UserID'],
            'Role' => $_SESSION['Role']
        ];
    }
    return null;
}
?>
