<?php
/**
 * edit_profile.php lets a logged in user update their name, email,
 * and password
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/PVDatabase.php';

// only logged-in users can edit a profile
if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_SESSION['person_id'];
$error = '';
$success = '';

// load the current info so the form starts filled in
$rows = PVDatabase::getDataFromSQL(
    "SELECT first_name, last_name, email_address, password
    FROM person
    WHERE person_id = ?",
    [$id]
);

// if the account doesn't exist anymore, log them out
if (count($rows) !== 1) {
    header('Location: logout.php');
    exit;
}


$user = $rows[0];
$first = $user['first_name'];
$last = $user['last_name'];
$email = $user['email_address'];

// run this if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    // same checks as register.php
    if ($first === '' || $last === '' || $email === '') {
        $error = 'Please fill in your name and email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "That email address doesn't look right.";
    } elseif ($newPass !== '' && strlen($newPass) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($newPass !== $confirm) {
        $error = "New passwords don't match.";
    } elseif ($newPass !== '' && !password_verify($currentPass, $user['password'])) {
        // to change the password, they must type the old one correctly
        $error = 'Current password is wrong.';
    } else {   
        try {
            // update name and email
            PVDatabase::executeSQL(
                "UPDATE person
                SET first_name = ?, last_name = ?, email_address = ?
                WHERE person_id = ?",
                [$first, $last, $email, $id]
            );

            // only update password if they typed a new one
            if ($newPass !== '') {
                PVDatabase::executeSQL(
                    "UPDATE person SET password = ? WHERE person_id = ?",
                    [password_hash($newPass, PASSWORD_DEFAULT), $id]
                );
            }

            // keep the session in sync so the page shows the new name
            $_SESSION['first_name'] = $first;

            $success = 'Profile updated!';
        } catch (PDOException $e) {
            // duplicate entry error
            if ($e->errorInfo[1] == 1062) {
                $error = 'Another account already uses that email.';
            } else {
                $error = 'Something went wrong saving your changes.';
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
    <title>Edit Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card auth-card">
        <p class="project">Project Vault</p>
        <h1>Edit Profile</h1>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form class="auth-form" method="post">
            <div class="row">
                <label>First Name
                    <input type="text" name="first_name" value="<?= htmlspecialchars($first ?? '') ?>" required>
                </label>
                <label>Last Name
                    <input type="text" name="last_name" value="<?= htmlspecialchars($last ?? '') ?>" required>
                </label>     
            </div>  

            <label>Email
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>                
            </label>

            <p class="note">Only fill these in if you want to change your password.</p>

            <label>Current Password
                <input type="password" name="current_password">
            </label>
 
            <label>New Password
                <input type="password" name="new_password" minlength="8">
            </label>   

            <label>Confirm New Password
                <input type="password" name="confirm" minlength="8">
            </label>   


            <br>
            <button type="submit">Save Changes</button>
        </form>

        <p class="note"><a href="projects.php">Back to projects</a></p>
    </div>
</body>
</html>