<?php
define('INCLUDED', true);
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Study Scheduler</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="card">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>What would you like to do today?</p>
            <div class="actions">
                <a href="subjects.php" class="btn">Manage Subjects</a>
                <a href="schedule.php" class="btn">Plan Sessions</a>
                <a href="calender.php" class="btn">View Calendar</a>
                <a href="goals.php" class="btn">goals</a>
                <a href="/study_schedule/includes/logout.php">Logout</a>

            </div>
        </div>
    </div>
</body>
</html>