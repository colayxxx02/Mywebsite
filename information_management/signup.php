<?php
include 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $check = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        $result = $conn->query("SELECT MAX(user_id) as max_id FROM users");
        $row = $result->fetch_assoc();
        $user_id = ($row['max_id'] ?? 0) + 1;

        $sql = "INSERT INTO users (user_id, fullname, email, password, role) 
                VALUES ('$user_id', '$fullname', '$email', '$password', '$role')";

        if ($conn->query($sql)) {
            $success = "Account created successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BookShare - Sign Up</title>
</head>
<body>
    <h2>BookShare - Sign Up</h2>

    <?php if ($error): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color:green;"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Full Name:</label><br>
        <input type="text" name="fullname" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Role:</label><br>
        <select name="role">
            <option value="member">Member</option>
            <option value="librarian">Librarian</option>
        </select><br><br>

        <button type="submit">Sign Up</button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
</body>
</html>