<?php
include 'db.php';

$error = "";
$success = "";
$fullname = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'];

    // Validation
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check = $conn->query("SELECT user_id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Escape strings to prevent SQL injection
            $fullname = $conn->real_escape_string($fullname);
            $email = $conn->real_escape_string($email);
            $role = $conn->real_escape_string($role);

            // Get next user_id
            $result = $conn->query("SELECT MAX(user_id) as max_id FROM users");
            $row = $result->fetch_assoc();
            $user_id = ($row['max_id'] ?? 0) + 1;

            // Insert new user
            $sql = "INSERT INTO users (user_id, fullname, email, password, role, is_on_hold, hold_until) 
                    VALUES ('$user_id', '$fullname', '$email', '$hashed_password', '$role', 0, NULL)";

            if ($conn->query($sql)) {
                $success = "Account created successfully! Redirecting to login...";
                $fullname = "";
                $email = "";
                // Redirect after 2 seconds
                header("refresh:2;url=index.php");
            } else {
                $error = "Error creating account: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - BookShare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="signup-page">

    <div class="signup-container">
        <!-- HEADER -->
        <div class="signup-header">
            <div class="signup-logo">📚</div>
            <h1>Create Account</h1>
            <p>Join BookShare and start borrowing books</p>
        </div>

        <!-- ALERTS -->
        <?php if ($error): ?>
            <div class="error">
                <span>❌</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <span>✅</span>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <form method="POST" action="">
            <!-- FULL NAME -->
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input 
                    type="text" 
                    id="fullname"
                    name="fullname" 
                    placeholder="Juan Dela Cruz"
                    value="<?= htmlspecialchars($fullname ?? '') ?>"
                    required
                    autocomplete="name">
            </div>

            <!-- EMAIL -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email"
                    name="email" 
                    placeholder="your@email.com"
                    value="<?= htmlspecialchars($email ?? '') ?>"
                    required
                    autocomplete="email">
            </div>

            <!-- PASSWORD -->
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    placeholder="Enter a strong password"
                    required
                    autocomplete="new-password"
                    minlength="6">
                <div class="password-info">Minimum 6 characters required</div>
            </div>

            <!-- CONFIRM PASSWORD -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password"
                    name="confirm_password" 
                    placeholder="Confirm your password"
                    required
                    autocomplete="new-password"
                    minlength="6">
            </div>

            <!-- ROLE -->
            <div class="form-group">
                <label for="role">Account Type</label>
                <select id="role" name="role" required>
                    <option value="member" selected>📖 Member</option>
                    <option value="librarian">👨‍💼 Librarian</option>
                </select>
            </div>

            <!-- SUBMIT BUTTON -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">Create My Account</button>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer">
                Already have an account? <a href="index.php">Login here</a>
            </div>
        </form>
    </div>

</body>
</html>