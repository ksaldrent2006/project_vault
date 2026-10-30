<?php
/**
 * login.php checks email and password against the person table
 * and logs the user in
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/PVDatabase.php';

// skip login page if already logged in
if (isset($_SESSION['person_id'])) {
    header('Location: projects.php');
    exit;
}

$error = '';
$email = '';

// check request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    // find account by email
    $rows = PVDatabase::getDataFromSQL(
        "SELECT person_id, first_name, password, role, currently_active
         FROM person
         WHERE email_address = ?",
        [$email]
    );

    // password_verify() in php checks the typed password against the saved hash
    if (count($rows) === 1 && password_verify($pass, $rows[0]['password'])) {
        $user = $rows[0];

        if ($user['currently_active'] == 0) {
            $error = 'This account has been deactivated.';
        } else {
            session_regenerate_id(true);

            // save who they are in the session
            $_SESSION['person_id'] = $user['person_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['role'] = $user['role'];

            header('Location: projects.php');
            exit;
        }
    } else {
        $error = 'Wrong email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card auth-card">
        <p class="project">Project Vault</p>
        <h1>Login</h1>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error)?></div>
        <?php endif; ?>

        <form class="auth-form" method="post">
            <label>Email
                <input type="email" name="email" value="<?php echo htmlspecialchars($email) ?>" required autofocus>            
            </label>

            <label>Password
                <input type="password" name="password" required>
            </label>

            <button type="submit">Login</button>
        </form>

        <p class="note">New here? <a href="register.php">Create an account!</a></p>
        </div>
</body>
</html>