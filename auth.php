<?php
// Prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Check if functions are already declared
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('login')) {
    function login($username, $password) {
        global $conn;
        
        try {
            $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
}
// includes/auth.php
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to login page
    header("Location: ../login.php");
    exit();
}
?>