<?php
session_start();

require_once __DIR__ . '/../config/db.php';

$role = $_POST['role'] ?? '';
$login_success = false;
$error_message = '';

if ($role === 'admin') {
	$username = $_POST['username'] ?? '';
	$password = $_POST['password'] ?? '';
	
	if ($username && $password) {
		$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? AND role = 'admin'");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($result->num_rows > 0) {
			$user = $result->fetch_assoc();
			if ($password === $user['password']) {
				$_SESSION['user_id'] = $user['id'];
				$_SESSION['username'] = $user['username'];
				$_SESSION['role'] = 'admin';
				$login_success = true;
			} else {
				$error_message = "Invalid password";
			}
		} else {
			$error_message = "Admin user not found";
		}
		$stmt->close();
	} else {
		$error_message = "Please fill in all fields";
	}
	
} elseif ($role === 'teacher') {
	$username = $_POST['username'] ?? '';
	$password = $_POST['password'] ?? '';
	
	if ($username && $password) {
		$stmt = $conn->prepare("SELECT id, username, password, first_name, last_name FROM teachers WHERE username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($result->num_rows > 0) {
			$teacher = $result->fetch_assoc();
			if ($password === $teacher['password']) {
				$_SESSION['user_id'] = $teacher['id'];
				$_SESSION['username'] = $teacher['username'];
				$_SESSION['role'] = 'teacher';
				$_SESSION['teacher_name'] = $teacher['first_name'] . ' ' . $teacher['last_name'];
				$login_success = true;
			} else {
				$error_message = "Invalid password";
			}
		} else {
			$error_message = "Teacher not found";
		}
		$stmt->close();
	} else {
		$error_message = "Please fill in all fields";
	}
	
} elseif ($role === 'student') {
	$student_number = $_POST['student_number'] ?? '';
	$password = $_POST['password'] ?? '';
	
	if ($student_number && $password) {
		$stmt = $conn->prepare("SELECT id, student_number, first_name, last_name, password FROM students WHERE student_number = ?");
		$stmt->bind_param("s", $student_number);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($result->num_rows > 0) {
			$student = $result->fetch_assoc();
			// Verify password against hashed value in DB
			if (password_verify($password, $student['password'])) {
				$_SESSION['user_id'] = $student['id'];
				$_SESSION['student_number'] = $student['student_number'];
				// Ensure dashboards that expect `username` also work
				$_SESSION['username'] = $student['student_number'];
				$_SESSION['role'] = 'student';
				$_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
				$login_success = true;
			} else {
				$error_message = "Invalid password";
			}
		} else {
			$error_message = "Student not found";
		}
		$stmt->close();
	} else {
		$error_message = "Please fill in all fields";
	}
}

$conn->close();

if ($login_success) {
	if ($role === 'admin') {
		header('Location: ../dashboards/admin_dashboard.php');
	} elseif ($role === 'teacher') {
		header('Location: ../dashboards/teacher_dashboard.php');
	} elseif ($role === 'student') {
		header('Location: ../dashboards/student_dashboard.php');
	}
	exit;
} else {
	// Redirect back to login with error
	header('Location: login.php?role=' . $role . '&error=' . urlencode($error_message));
	exit;
}
?>

