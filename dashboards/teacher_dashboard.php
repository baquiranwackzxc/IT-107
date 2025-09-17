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

            case 'add_student':
                $student_number = $_POST['student_number'] ?? '';
                $first_name = $_POST['first_name'] ?? '';
                $last_name = $_POST['last_name'] ?? '';
				$course = $_POST['course'] ?? '';
    			$year_level = $_POST['year_level'] ?? '';

                if ($student_number && $first_name && $last_name && $course && $year_level) {
                    $stmt = $conn->prepare("INSERT INTO students (student_number, first_name, last_name, course, year_level, created_at) VALUES (?, ?, ?, ?,?, NOW())");
                    $stmt->bind_param("sssss", $student_number, $first_name, $last_name, $course, $year_level);

                    if ($stmt->execute()) {
                        $message = "Student added successfully!";
                    } else {
                        $error = "Error adding student: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Please fill in all fields.";
                }
                break;

            case 'add_grade':
                $student_id = $_POST['student_id'] ?? '';
                $subject_id = $_POST['subject_id'] ?? '';
                $grade = $_POST['grade'] ?? '';

                if ($student_id && $subject_id && $grade !== '') {
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
$students_result = $conn->query("SELECT * FROM students ORDER BY created_at DESC");

// Get subjects assigned to this teacher
$subjects_result = $conn->query("SELECT * FROM subjects WHERE teacher_id = $teacher_id");
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

    <h2>Add Student</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_student">
        <label>Student Number:</label><br>
        <input type="text" name="student_number" required><br><br>
        <label>First Name:</label><br>
        <input type="text" name="first_name" required><br><br>
        <label>Last Name:</label><br>
        <input type="text" name="last_name" required><br><br>
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

    <h2>Input Grades</h2>
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
        <input type="number" step="0.01" name="grade" required><br><br>

        <button type="submit">Save Grade</button>
    </form>
</body>
</html>

<?php $conn->close(); ?>
