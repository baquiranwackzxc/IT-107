<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['student_number'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$student_number = $_SESSION['student_number'];
$message = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Get current student data
        $stmt = $conn->prepare("SELECT password FROM students WHERE student_number = ?");
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $student_data = $result->fetch_assoc();
        $stmt->close();
        
        if ($student_data) {
 
            if (password_verify($current_password, $student_data['password']) || $current_password === $student_data['password']) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_number = ?");
                $stmt->bind_param("ss", $hashed_password, $student_number);
                
                if ($stmt->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Error updating password: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Current password is incorrect.";
            }
        } else {
            $error = "Student not found.";
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM students WHERE student_number = ?");
$stmt->bind_param("s", $student_number);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

if (!$student) {
    echo "No student record found.";
    exit;
}

$student_id = $student['id'];

$stmt = $conn->prepare("SELECT g.grade, sub.subject_name 
                        FROM grades g
                        JOIN subjects sub ON g.subject_id = sub.id
                        WHERE g.student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades_result = $stmt->get_result();
$stmt->close();

// Get all subjects with their teachers
$subjects_with_teachers_result = $conn->query("
    SELECT s.subject_name, s.created_at, t.first_name, t.last_name, t.department
    FROM subjects s
    JOIN teachers t ON s.teacher_id = t.id
    ORDER BY s.created_at DESC
");

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
        .change-password-btn { 
            background: #28a745; 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .change-password-btn:hover { background: #218838; }
        .password-form { 
            border: 1px solid #ddd; 
            padding: 20px; 
            width: 50%; 
            margin: 20px 0; 
            background: #f9f9f9;
            display: none;
        }
        .password-form input[type="password"] { 
            width: 100%; 
            padding: 8px; 
            margin: 5px 0 15px 0; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
        }
        .password-form button { 
            background: #007bff; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-right: 10px;
        }
        .password-form button:hover { background: #0056b3; }
        .cancel-btn { background: #6c757d; }
        .cancel-btn:hover { background: #545b62; }
        .message { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Student Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?>!</p>
    <a href="../logout.php">Logout</a>

    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <h2>My Profile</h2>
    <div class="profile-box">
        <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
        <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></p>
        <p><strong>Year Level:</strong> <?php echo htmlspecialchars($student['year_level']); ?></p>
        <button class="change-password-btn" onclick="togglePasswordForm()">Change Password</button>
    </div>

    <div id="passwordForm" class="password-form">
        <h3>Change Password</h3>
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>
            
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required minlength="6">
            
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            
            <button type="submit">Change Password</button>
            <button type="button" class="cancel-btn" onclick="togglePasswordForm()">Cancel</button>
        </form>
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

    <h2>Available Subjects</h2>
    <p>Here are all the subjects available in the system:</p>
    <?php if ($subjects_with_teachers_result->num_rows > 0): ?>
        <table border="1">
            <tr>
                <th>Subject Name</th>
                <th>Teacher</th>
                <th>Department</th>
                <th>Created Date</th>
            </tr>
            <?php while ($subject_teacher = $subjects_with_teachers_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($subject_teacher['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($subject_teacher['first_name'] . ' ' . $subject_teacher['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($subject_teacher['department'] ?? 'N/A'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($subject_teacher['created_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No subjects available yet.</p>
    <?php endif; ?>

    <script>
        function togglePasswordForm() {
            var form = document.getElementById('passwordForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
                // Clear form when hiding
                document.getElementById('current_password').value = '';
                document.getElementById('new_password').value = '';
                document.getElementById('confirm_password').value = '';
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
