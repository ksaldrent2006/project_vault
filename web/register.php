<?php

/**
 * register.php lets a new user create an account (saves a row in the
 * person table)
 */
 
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/PVDatabase.php';
 
$error = '';
$success = '';

$first='';
$last='';
$email='';

// only run this if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // check the input before touching the database
    if ($first === '' || $last === '' || $email === '' || $pass === '') {
        $error = 'Please fill in every field.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "That email address doesn't look right.";
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $confirm) {
        $error = "Passwords don't match.";
    } else {
        try {
            // password_hash() scrambles the password one-way
            PVDatabase::executeSQL(
                "INSERT INTO person (first_name, last_name, email_address, password)
                 VALUES (?, ?, ?, ?)",
                [$first, $last, $email, password_hash($pass, PASSWORD_DEFAULT)]
            );

            $success = 'Account created! You can login now.';
            $first = $last = $email = ''; // clear form
        } catch (PDOException $e) {
            // 1062 is a duplicate entry error
            if ($e -> errorInfo[1] == 1062) {
                $error = 'An account with that email already exists.';
            } else {
                $error ='Something went wrong saving your account.';
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
    <title>Create Account</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card auth-card">
        <p class="project">Project Vault</p>
        <h1>Create Account</h1>

        <?php if ($error): ?>
            <div class="message error"><php echo htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="auth-form" method="post">
            <div class="row">
                <label>First Name
                    <input type="text" name="first_name" value="<?= htmlspecialchars($first) ?>" required>                
                </label>
                <label>Last Name
                    <input type="text" name="last_name" value="<?= htmlspecialchars($last) ?>" required>
                </label>

                <label>Email
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>                
                </label>

            <label>Password
                <input type="password" name="password" minlength="8" required>
            </label>
 
            <label>Confirm Password
                <input type="password" name="confirm" minlength="8" required>
            </label>   
            
            <br>
            <button type="submit">Create Account</button>
        </form>

        <p class="note">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>