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

// Fetch sessions for calendar
$sessions = $conn->prepare("
    SELECT s.session_id, s.start_time, s.end_time, s.completed, sub.subject_name
    FROM study_sessions s
    JOIN subjects sub ON s.subject_id = sub.subject_id
    WHERE s.user_id = ?
    ORDER BY s.start_time
");
$sessions->execute([$user_id]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Study Calendar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <style>
        #calendar {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .fc-event-completed {
            opacity: 0.7;
            text-decoration: line-through;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Study Calendar</h1>
            <a href="dashboard.php" class="btn">← Back to Dashboard</a>
        </header>

        <div id="calendar"></div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    <?php while ($session = $sessions->fetch()): ?>
                    {
                        id: '<?= $session['session_id'] ?>',
                        title: '<?= addslashes($session['subject_name']) ?>',
                        start: '<?= $session['start_time'] ?>',
                        end: '<?= $session['end_time'] ?>',
                        className: '<?= $session['completed'] ? 'fc-event-completed' : '' ?>',
                        color: '<?= $session['completed'] ? '#6c757d' : '#28a745' ?>'
                    },
                    <?php endwhile; ?>
                ],
                eventClick: function(info) {
                    window.location.href = 'schedule.php?session_id=' + info.event.id;
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>