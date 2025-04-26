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

// Fetch user's subjects for dropdown
$subjects = $conn->prepare("SELECT subject_id, subject_name FROM subjects WHERE user_id = ?");
$subjects->execute([$user_id]);

// Add new session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $notes = $_POST['notes'];

    try {
        $stmt = $conn->prepare("INSERT INTO study_sessions (user_id, subject_id, start_time, end_time, duration_minutes, notes) VALUES (?, ?, ?, ?, TIMESTAMPDIFF(MINUTE, ?, ?), ?)");
        $stmt->execute([$user_id, $subject_id, "$date $start_time", "$date $end_time", "$date $start_time", "$date $end_time", $notes]);
        $success = "Session added!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch upcoming sessions
$sessions = $conn->prepare("
    SELECT s.session_id, sub.subject_name, s.start_time, s.end_time, s.notes 
    FROM study_sessions s
    JOIN subjects sub ON s.subject_id = sub.subject_id
    WHERE s.user_id = ? AND s.start_time > NOW()
    ORDER BY s.start_time ASC
");
$sessions->execute([$user_id]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Study Schedule</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .schedule-form, .sessions-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Study Schedule</h1>
            <a href="dashboard.php" class="btn">← Back to Dashboard</a>
        </header>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Add Session Form -->
        <div class="schedule-form">
            <h2>Plan New Session</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject_id" required>
                        <?php while ($subject = $subjects->fetch()): ?>
                            <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" required>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" required>
                </div>
                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <textarea name="notes"></textarea>
                </div>
                <button type="submit" class="btn">Add Session</button>
            </form>
        </div>

        <!-- Upcoming Sessions -->
        <div class="sessions-list">
            <h2>Upcoming Sessions</h2>
            <?php if ($sessions->rowCount() === 0): ?>
                <p>No upcoming sessions. Plan one!</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Notes</th>
                    </tr>
                    <?php while ($session = $sessions->fetch()): 
                        $start = new DateTime($session['start_time']);
                        $end = new DateTime($session['end_time']);
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($session['subject_name']) ?></td>
                            <td><?= $start->format('M j, Y') ?></td>
                            <td><?= $start->format('g:i A') ?> - <?= $end->format('g:i A') ?></td>
                            <td><?= round((strtotime($session['end_time']) - strtotime($session['start_time'])) / 60) ?> mins</td>
                            <td><?= htmlspecialchars($session['notes']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>