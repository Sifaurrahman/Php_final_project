<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION["user"];
?>

<!DOCTYPE html>
<html>
<head><title>Dashboard</title></head>
<body>
<h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

<p>This is your dashboard.</p>

<p><a href="logout.php">Logout</a></p>
</body>
</html>
