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

// Handle goal creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $subject_id = $_POST['subject_id'];
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $target_date = $_POST['target_date'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO study_goals (user_id, subject_id, goal_description, target_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $subject_id, $description, $target_date]);
        $success = "Goal added successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle goal completion toggle
if (isset($_GET['toggle'])) {
    $goal_id = filter_input(INPUT_GET, 'toggle', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("UPDATE study_goals SET completed = NOT completed WHERE goal_id = ? AND user_id = ?");
    $stmt->execute([$goal_id, $user_id]);
    header("Location: goals.php");
    exit();
}

// Fetch all goals
$goals = $conn->prepare("
    SELECT g.*, s.subject_name 
    FROM study_goals g
    JOIN subjects s ON g.subject_id = s.subject_id
    WHERE g.user_id = ?
    ORDER BY g.target_date ASC
");
$goals->execute([$user_id]);

// Fetch subjects for dropdown
$subjects = $conn->prepare("SELECT subject_id, subject_name FROM subjects WHERE user_id = ?");
$subjects->execute([$user_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Goals - Study Scheduler</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .goal-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }
        
        .goal-card.completed {
            border-left-color: var(--success);
            opacity: 0.8;
        }
        
        .goal-card.completed .goal-title {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .goal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .goal-subject {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .goal-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .goal-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .toggle-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .toggle-btn.completed {
            background: var(--success);
            color: white;
        }
        
        .toggle-btn.incomplete {
            background: #e9ecef;
            color: var(--dark);
        }
        
        .delete-btn {
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        
        .goal-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Study Goals</h1>
            <a href="dashboard.php" class="btn">
                <i>←</i> Back to Dashboard
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="goal-form">
            <h2>Create New Goal</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="subject_id">Subject</label>
                        <select id="subject_id" name="subject_id" class="form-control" required>
                            <?php while ($subject = $subjects->fetch()): ?>
                                <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="target_date">Target Date</label>
                        <input type="date" id="target_date" name="target_date" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Goal Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                </div>
                
                <button type="submit" name="add_goal" class="btn">Add Goal</button>
            </form>
        </div>
        
        <h2>Your Goals</h2>
        
        <?php if ($goals->rowCount() === 0): ?>
            <div class="empty-state">
                <p>No goals set yet. Create your first goal above!</p>
            </div>
        <?php else: ?>
            <?php while ($goal = $goals->fetch()): 
                $is_completed = $goal['completed'];
                $due_date = new DateTime($goal['target_date']);
                $today = new DateTime();
                $days_remaining = $today->diff($due_date)->format('%r%a');
            ?>
                <div class="goal-card <?= $is_completed ? 'completed' : '' ?>">
                    <div class="goal-header">
                        <h3 class="goal-title"><?= htmlspecialchars($goal['goal_description']) ?></h3>
                        <span class="goal-subject"><?= htmlspecialchars($goal['subject_name']) ?></span>
                    </div>
                    
                    <p class="goal-date">
                        Target: <?= $due_date->format('M j, Y') ?> 
                        (<?= $days_remaining >= 0 ? "$days_remaining days left" : "Overdue by ".abs($days_remaining)." days" ?>)
                    </p>
                    
                    <div class="goal-actions">
                        <a href="?toggle=<?= $goal['goal_id'] ?>" class="toggle-btn <?= $is_completed ? 'completed' : 'incomplete' ?>">
                            <?= $is_completed ? '✓ Completed' : 'Mark Complete' ?>
                        </a>
                        <a href="?delete=<?= $goal['goal_id'] ?>" class="delete-btn">Delete</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</body>
</html>