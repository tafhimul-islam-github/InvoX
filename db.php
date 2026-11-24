<?php
$host = 'localhost';
$db   = 'invox_db';
$user = 'root';
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function hasAnyRole($roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}
function isAdmin() {
    return hasRole('Admin');
}
function isAccountant() {
    return hasRole('Accountant');
}
function isCustomer() {
    return hasRole('Customer');
}

// Helper to require specific role (redirect if not authorized)
function requireRole($role) {
    checkLogin();
    if (!hasRole($role)) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}
function requireAnyRole($roles) {
    checkLogin();
    if (!hasAnyRole($roles)) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}

// Helper to get customer ID for logged-in customer user
function getCustomerIdForUser($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}
?>