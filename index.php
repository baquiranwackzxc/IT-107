<!DOCTYPE html>
<html>
<head>
    <title>Student Management</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7fb; color: #333; }
        .page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border: 1px solid #e6e8ee; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.06); padding: 28px; width: 100%; max-width: 420px; }
        h1 { margin: 0 0 6px; font-size: 22px; }
        p { margin: 0 0 18px; color: #666; }
        form { display: grid; gap: 10px; margin-top: 6px; }
        button { appearance: none; border: 1px solid #3b7ddd; background: #3b7ddd; color: #fff; padding: 10px 14px; border-radius: 8px; cursor: pointer; font-size: 14px; transition: background .15s ease, box-shadow .15s ease; }
        button:hover { background: #2f69bf; }
        button:active { background: #285aa6; }
        .alt { background: #eef3ff; color: #2f69bf; border-color: #c7d7ff; }
        .alt:hover { background: #e3ecff; }
        .roles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .footer { margin-top: 14px; font-size: 12px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <h1>Student Management System</h1>
            <p>Please choose your role to sign in.</p>
            <form action="auth/login.php" method="get" class="roles">
                <button type="submit" name="role" value="student">Student</button>
                <button type="submit" name="role" value="teacher" class="alt">Teacher</button>
                <button type="submit" name="role" value="admin">Admin</button>
            </form>
            <div class="footer">Xanthone Plus 1.0</div>
        </div>
    </div>
</body>
</html>
