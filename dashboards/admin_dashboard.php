<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
	header('Location: ../index.php');
	exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'add_teacher':
				$teacher_username = $_POST['teacher_username'] ?? '';
				$first_name = $_POST['first_name'] ?? '';
				$last_name = $_POST['last_name'] ?? '';
				$email = $_POST['email'] ?? '';
				$department = $_POST['department'] ?? '';
				
					if ($teacher_username && $first_name && $last_name && $email) {
						$teacher_password = password_hash($last_name, PASSWORD_DEFAULT);
						$stmt = $conn->prepare("INSERT INTO teachers (username, password, first_name, last_name, email, department) VALUES (?, ?, ?, ?, ?, ?)");
						$stmt->bind_param("ssssss", $teacher_username, $teacher_password, $first_name, $last_name, $email, $department);
					
					if ($stmt->execute()) {
						$message = "Teacher added successfully!";
					} else {
						$error = "Error adding teacher: " . $stmt->error;
					}
					$stmt->close();
				} else {
						$error = "Please fill in all required fields";
				}
				break;
				
			case 'edit_teacher':
				$teacher_id = $_POST['teacher_id'] ?? '';
				$teacher_username = $_POST['teacher_username'] ?? '';
				$first_name = $_POST['first_name'] ?? '';
				$last_name = $_POST['last_name'] ?? '';
				$email = $_POST['email'] ?? '';
				$department = $_POST['department'] ?? '';
				
				if ($teacher_id && $teacher_username && $first_name && $last_name && $email) {
					$stmt = $conn->prepare("UPDATE teachers SET username = ?, first_name = ?, last_name = ?, email = ?, department = ? WHERE id = ?");
					$stmt->bind_param("sssssi", $teacher_username, $first_name, $last_name, $email, $department, $teacher_id);
					
					if ($stmt->execute()) {
						$message = "Teacher updated successfully!";
					} else {
						$error = "Error updating teacher: " . $stmt->error;
					}
					$stmt->close();
				} else {
					$error = "Please fill in all required fields";
				}
				break;
				
			case 'delete_teacher':
				$teacher_id = $_POST['teacher_id'] ?? '';
				
				if ($teacher_id) {
					$stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
					$stmt->bind_param("i", $teacher_id);
					
					if ($stmt->execute()) {
						$message = "Teacher deleted successfully!";
					} else {
						$error = "Error deleting teacher: " . $stmt->error;
					}
					$stmt->close();
				}
				break;
		}
	}
}

// Get all teachers
$teachers_result = $conn->query("SELECT * FROM teachers ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
	<title>Admin</title>
</head>
<body>
	<h1>Admin</h1>
	<p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
	<a href="../logout.php">Logout</a>

	<?php if ($message): ?>
		<p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
	<?php endif; ?>

	<?php if ($error): ?>
		<p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
	<?php endif; ?>

	<h2>Add Teacher</h2>
	<form method="POST">
		<input type="hidden" name="action" value="add_teacher">
		<label>Username:</label><br>
		<input type="text" name="teacher_username" required><br><br>
		
		<label>First Name:</label><br>
		<input type="text" name="first_name" required><br><br>
		
		<label>Last Name:</label><br>
		<input type="text" name="last_name" required><br><br>
		
		<label>Email:</label><br>
		<input type="email" name="email" required><br><br>
		
		<label>Department:</label><br>
		<input type="text" name="department"><br><br>
		
		<button type="submit">Add Teacher</button>
	</form>

	<h2>Teachers Management</h2>
	<table border="1">
		<tr>
			
			<th>Username</th>
			<th>Name</th>
			<th>Email</th>
			<th>Department</th>
			<th>Created</th>
			<th>Option</th>
		</tr>
		<?php while ($teacher = $teachers_result->fetch_assoc()): ?>
		<tr>
			<td><?php echo htmlspecialchars($teacher['username']); ?></td>
			<td><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
			<td><?php echo htmlspecialchars($teacher['email']); ?></td>
			<td><?php echo htmlspecialchars($teacher['department']); ?></td>
			<td><?php echo date('Y-m-d H:i', strtotime($teacher['created_at'])); ?></td>
			<td>
				<button onclick="editTeacher(<?php echo htmlspecialchars(json_encode($teacher)); ?>)">Edit</button>
				<form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this teacher?')">
					<input type="hidden" name="action" value="delete_teacher">
					<input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
					<button type="submit">Delete</button>
				</form>
			</td>
		</tr>
		<?php endwhile; ?>
	</table>

	<!-- Edit Teacher Modal -->
	<div id="editModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div style="background-color: white; margin: 5% auto; padding: 20px; width: 50%; max-width: 500px;">
			<h2>Edit Teacher</h2>
			<form method="POST" id="editForm">
				<input type="hidden" name="action" value="edit_teacher">
				<input type="hidden" name="teacher_id" id="edit_teacher_id">
				
				<label>Username:</label><br>
				<input type="text" name="teacher_username" id="edit_username" required><br><br>
				
				<label>First Name:</label><br>
				<input type="text" name="first_name" id="edit_first_name" required><br><br>
				
				<label>Last Name:</label><br>
				<input type="text" name="last_name" id="edit_last_name" required><br><br>
				
				<label>Email:</label><br>
				<input type="text" name="email" id="edit_email" required><br><br>
				
				<label>Department:</label><br>
				<input type="text" name="department" id="edit_department"><br><br>
				
				<button type="submit">Update Teacher</button>
				<button type="button" onclick="closeEditModal()">Cancel</button>
			</form>
		</div>
	</div>

	<script>
		function editTeacher(teacher) {
			document.getElementById('edit_teacher_id').value = teacher.id;
			document.getElementById('edit_username').value = teacher.username;
			document.getElementById('edit_first_name').value = teacher.first_name;
			document.getElementById('edit_last_name').value = teacher.last_name;
			document.getElementById('edit_email').value = teacher.email;
			document.getElementById('edit_department').value = teacher.department || '';
			document.getElementById('editModal').style.display = 'block';
		}

		function closeEditModal() {
			document.getElementById('editModal').style.display = 'none';
		}

		// Close modal when clicking outside
		window.onclick = function(event) {
			var modal = document.getElementById('editModal');
			if (event.target == modal) {
				modal.style.display = 'none';
			}
		}
	</script>
</body>
</html>

<?php
$conn->close();
?>

