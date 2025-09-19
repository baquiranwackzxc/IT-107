<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher' || !isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';
$error = '';
$teacher_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            
            case 'change_password':
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
                    // Get current teacher data
                    $stmt = $conn->prepare("SELECT password FROM teachers WHERE id = ?");
                    $stmt->bind_param("i", $teacher_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $teacher_data = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($teacher_data) {
                        // Verify current password
                        if (password_verify($current_password, $teacher_data['password']) || $current_password === $teacher_data['password']) {
                            // Hash new password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            
                            // Update password
                            $stmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
                            $stmt->bind_param("si", $hashed_password, $teacher_id);
                            
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
                        $error = "Teacher not found.";
                    }
                }
                break;

            case 'add_student':
                $student_number = $_POST['student_number'] ?? '';
                $first_name = $_POST['first_name'] ?? '';
                $last_name = $_POST['last_name'] ?? '';
                $email = $_POST['email'] ?? '';
                $course = $_POST['course'] ?? '';
                $year_level = $_POST['year_level'] ?? '';

                if ($student_number && $first_name && $last_name && $course && $year_level) {
                    // Check if student number already exists
                    $stmt = $conn->prepare("SELECT id FROM students WHERE student_number = ?");
                    $stmt->bind_param("s", $student_number);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = "Student number already exists.";
                    } else {
                        // Default password is the student's last name (hashed)
                        $password = password_hash($last_name, PASSWORD_DEFAULT);

                        $stmt = $conn->prepare("INSERT INTO students (student_number, first_name, last_name, email, password, course, year_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("sssssss", $student_number, $first_name, $last_name, $email, $password, $course, $year_level);

                        if ($stmt->execute()) {
                            $message = "Student added successfully!";
                        } else {
                            $error = "Error adding student: " . $stmt->error;
                        }
                    }
                    $stmt->close();
                } else {
                    $error = "Please fill in all fields.";
                }
                break;

            case 'add_subject':
                $subject_name = $_POST['subject_name'] ?? '';
                
                if ($subject_name) {
                    // Check if subject name already exists for this teacher
                    $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_name = ? AND teacher_id = ?");
                    $stmt->bind_param("si", $subject_name, $teacher_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = "Subject name already exists for you.";
                    } else {
                        // Add the subject
                        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, teacher_id) VALUES (?, ?)");
                        $stmt->bind_param("si", $subject_name, $teacher_id);
                        
                        if ($stmt->execute()) {
                            $message = "Subject added successfully!";
                        } else {
                            $error = "Error adding subject: " . $stmt->error;
                        }
                    }
                    $stmt->close();
                } else {
                    $error = "Subject name is required.";
                }
                break;

            case 'add_grade':
                $student_id = $_POST['student_id'] ?? '';
                $subject_id = $_POST['subject_id'] ?? '';
                $grade = $_POST['grade'] ?? '';

                if ($student_id && $subject_id && $grade !== '' && is_numeric($grade)) {
                    // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both insert and update
                    $stmt = $conn->prepare("INSERT INTO grades (student_id, subject_id, grade) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE grade = VALUES(grade)");
                    $stmt->bind_param("iid", $student_id, $subject_id, $grade);

                    if ($stmt->execute()) {
                        $message = "Grade saved successfully!";
                    } else {
                        $error = "Error saving grade: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Please fill in all fields.";
                }
                break;
        }
    }
}

// Get all students
$students_result = $conn->query("SELECT * FROM students ORDER BY last_name, first_name");

// Get subjects assigned to this teacher
$subjects_result = $conn->query("SELECT * FROM subjects WHERE teacher_id = $teacher_id ORDER BY subject_name");

// Get existing grades for this teacher's subjects
$existing_grades_result = $conn->query("
    SELECT g.id, g.grade, g.student_id, g.subject_id, s.student_number, s.first_name, s.last_name, sub.subject_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN subjects sub ON g.subject_id = sub.id
    WHERE sub.teacher_id = $teacher_id
    ORDER BY sub.subject_name, s.last_name, s.first_name
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
</head>
<body>
    <h1>Teacher Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['teacher_name']); ?>!</p>
    <a href="../logout.php">Logout</a>

    <?php if ($message): ?>
        <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <h2>Change Password</h2>
    <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <label for="current_password">Current Password:</label><br>
        <input type="password" id="current_password" name="current_password" required><br><br>
        
        <label for="new_password">New Password:</label><br>
        <input type="password" id="new_password" name="new_password" required minlength="6"><br><br>
        
        <label for="confirm_password">Confirm New Password:</label><br>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"><br><br>
        
        <button type="submit">Change Password</button>
    </form>

    <h2>Add New Student</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_student">
        <label>Student Number:</label><br>
        <input type="text" name="student_number" required><br><br>
        <label>First Name:</label><br>
        <input type="text" name="first_name" required><br><br>
        <label>Last Name:</label><br>
        <input type="text" name="last_name" required><br><br>
        <label>Email:</label><br>
        <input type="text" name="email" required><br><br>
        <label>Course:</label><br>
        <input type="text" name="course" required><br><br>
        <label>Year Level:</label><br>
        <select name="year_level" required>
            <option value="">-- Select Year Level --</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
        </select><br><br>
        <button type="submit">Add Student</button>
    </form>

    <h2>Add Subject</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_subject">
        <label>Subject Name:</label><br>
        <input type="text" name="subject_name" required placeholder="e.g., Computer Programming"><br><br>
        <button type="submit">Add Subject</button>
    </form>

    <h2>Add/Edit Grade</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_grade">
        
        <label>Student:</label><br>
        <select name="student_id" required>
            <option value="">-- Select Student --</option>
            <?php while ($student = $students_result->fetch_assoc()): ?>
                <option value="<?php echo $student['id']; ?>">
                    <?php echo htmlspecialchars($student['student_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Subject:</label><br>
        <select name="subject_id" required>
            <option value="">-- Select Subject --</option>
            <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                <option value="<?php echo $subject['id']; ?>">
                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Grade:</label><br>
        <input type="number" step="0.01" name="grade" required min="0" max="100"><br><br>

        <button type="submit">Save Grade</button>
    </form>

    <h2>Current Grades</h2>
    <?php if ($existing_grades_result->num_rows > 0): ?>
        <table border="1">
            <tr>
                <th>Student</th>
                <th>Subject</th>
                <th>Grade</th>
            </tr>
            <?php while ($grade_row = $existing_grades_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($grade_row['student_number'] . ' - ' . $grade_row['first_name'] . ' ' . $grade_row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($grade_row['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($grade_row['grade']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No grades recorded yet.</p>
    <?php endif; ?>

    <h2>My Subjects</h2>
    <?php if ($subjects_result->num_rows > 0): ?>
        <table border="1">
            <tr>
                <th>Subject Name</th>
                <th>Created Date</th>
            </tr>
            <?php 
            // Reset the subjects result pointer
            $subjects_result->data_seek(0);
            while ($subject = $subjects_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($subject['created_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No subjects created yet.</p>
    <?php endif; ?>
</body>
</html>

<?php $conn->close(); ?>