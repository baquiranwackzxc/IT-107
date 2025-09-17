<?php
$role = $_GET['role'] ?? 'guest'; // default if no role
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo ucfirst($role); ?> Login</title>
</head>
<body>
	<h2><?php echo ucfirst($role); ?> Login</h2>
	<?php if (isset($_GET['error'])) { ?>
		<div style="color: red; margin-bottom: 10px;">
			<?php echo htmlspecialchars($_GET['error']); ?>
		</div>
	<?php } ?>
    <form action="authenticate.php" method="post">
		<input type="hidden" name="role" value="<?php echo $role; ?>">
		<?php if ($role === 'teacher') { ?>
		<label>Username:</label>
		<input type="text" name="username" required><br><br>
		<?php } elseif ($role === 'student') { ?>
		<label>Student Number:</label>
		<input type="text" name="student_number" required><br><br>
		<?php } elseif ($role === 'admin') { ?>
		<label>Admin Username:</label>
		<input type="text" name="username" required><br><br>
		<?php } else { ?>
		<label>Username:</label>
		<input type="text" name="username" required><br><br>
		<?php } ?>
		<label>Password:</label>
		<input type="password" name="password" required><br><br>
		<button type="submit">Login</button>
	</form>
</body>
</html>

