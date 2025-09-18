<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$student_username = $_SESSION['username'];

// Get student info (match via users.username -> students.student_number or email)
$stmt = $conn->prepare("SELECT s.* 
                        FROM students s 
                        JOIN users u ON u.username = s.student_number OR u.username = s.email
                        WHERE u.username = ?");
$stmt->bind_param("s", $student_username);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

if (!$student) {
    echo "No student record found.";
    exit;
}

$student_id = $student['id'];

// Get grades for this student
$stmt = $conn->prepare("SELECT g.grade, sub.subject_name 
                        FROM grades g
                        JOIN subjects sub ON g.subject_id = sub.id
                        WHERE g.student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades_result = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        table { border-collapse: collapse; width: 70%; margin-top: 15px; }
        table, th, td { border: 1px solid #aaa; }
        th, td { padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
        .profile-box { border: 1px solid #ddd; padding: 15px; width: 50%; margin-bottom: 20px; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>
    <h1>Student Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?>!</p>
    <a href="../logout.php">Logout</a>

    <h2>My Profile</h2>
    <div class="profile-box">
        <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
        <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></p>
        <p><strong>Year Level:</strong> <?php echo htmlspecialchars($student['year_level']); ?></p>
    </div>

    <h2>My Grades</h2>
    <table>
        <tr>
            <th>Subject</th>
            <th>Grade</th>
        </tr>
        <?php if ($grades_result->num_rows > 0): ?>
            <?php while ($row = $grades_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['grade']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="2">No grades recorded yet.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>

<?php $conn->close(); ?>
