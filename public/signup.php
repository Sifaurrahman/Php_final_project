<?php
session_start();
require_once __DIR__ . '/../classes/User.php';

$user = new User();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $result = $user->signup($username, $password);
        if ($result["success"]) {
            $_SESSION["user"] = $username;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = $result["message"];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Signup</title></head>
<body>
<h2>Signup</h2>
<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="POST">
    Username:<br>
    <input type="text" name="username" required><br><br>
    Password:<br>
    <input type="password" name="password" required><br><br>
    <button type="submit">Sign Up</button>
</form>
<p>Already have an account? <a href="index.php">Login here</a></p>
</body>
</html>
