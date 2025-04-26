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
$error = '';
$success = '';

// Add new subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_name = filter_input(INPUT_POST, 'subject_name', FILTER_SANITIZE_STRING);
    $difficulty = filter_input(INPUT_POST, 'difficulty', FILTER_SANITIZE_STRING);
    $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_NUMBER_INT);

    try {
        $stmt = $conn->prepare("INSERT INTO subjects (user_id, subject_name, difficulty_level, priority) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $subject_name, $difficulty, $priority]);
        $success = "Subject added successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Delete subject
if (isset($_GET['delete'])) {
    $subject_id = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ? AND user_id = ?");
        $stmt->execute([$subject_id, $user_id]);
        $success = "Subject deleted!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all subjects for the current user
$stmt = $conn->prepare("SELECT * FROM subjects WHERE user_id = ? ORDER BY priority DESC");
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Subjects - Study Scheduler</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>My Subjects</h1>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </header>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Add Subject Form -->
        <form method="POST" class="subject-form">
            <h2>Add New Subject</h2>
            <div class="form-group">
                <label>Subject Name</label>
                <input type="text" name="subject_name" required>
            </div>
            <div class="form-group">
                <label>Difficulty</label>
                <select name="difficulty" required>
                    <option value="easy">Easy</option>
                    <option value="medium" selected>Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>
            <div class="form-group">
                <label>Priority (1-10)</label>
                <input type="number" name="priority" min="1" max="10" value="5" required>
            </div>
            <button type="submit" name="add_subject" class="btn">Add Subject</button>
        </form>

        <!-- List of Subjects -->
        <div class="subject-list">
            <h2>Your Subjects</h2>
            <?php if (empty($subjects)): ?>
                <p>No subjects added yet.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Subject</th>
                        <th>Difficulty</th>
                        <th>Priority</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                            <td><?= ucfirst($subject['difficulty_level']) ?></td>
                            <td><?= $subject['priority'] ?></td>
                            <td>
                                <a href="?delete=<?= $subject['subject_id'] ?>" class="btn-delete">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>