<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['student_number'])) {
	header('Location: ../index.php');
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Student Dashboard</title>
</head>
<body>
	<h2>Welcome <?php echo htmlspecialchars($_SESSION['student_number']); ?></h2>
	<a href="../logout.php">Logout</a>
</body>
</html>

