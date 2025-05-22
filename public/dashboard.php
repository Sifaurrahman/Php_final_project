<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../classes/PasswordGenerator.php';
require_once __DIR__ . '/../classes/PasswordStorage.php';
require_once __DIR__ . '/../classes/Database.php';

$username = $_SESSION["user"];
$generated_password = "";
$error = "";
$success = "";

// Create DB connection for user info
$db = new Database();
$conn = $db->getConnection();

// Get user id from username
$stmt = $conn->prepare("SELECT id, key_encrypted FROM users WHERE username = ?");
$stmt->execute([$username]);
$user_row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user_row) {
    // User not found — log out
    session_destroy();
    header("Location: index.php");
    exit();
}

$user_id = $user_row['id'];
$key_encrypted = base64_decode($user_row['key_encrypted']);

// User password is needed to decrypt the key - for demo we skip this step here,
// but you must manage storing user plain password securely or ask for it to decrypt key.

// For now, we assume user password is stored in session (this is just for demo, **do NOT do this in real app**)
$user_password = $_SESSION["password_plain"] ?? null;
if (!$user_password) {
    // We need the plain password to decrypt key - redirect or ask user to re-enter password
    $error = "Password key unavailable. Please re-login.";
}

// Function to decrypt AES key using user password
function decryptKeyWithPassword($key_encrypted, $password) {
    $method = "AES-256-CBC";
    $iv = substr(hash('sha256', $password), 0, 16);
    return openssl_decrypt($key_encrypted, $method, $password, OPENSSL_RAW_DATA, $iv);
}

$aes_key = null;
if ($user_password) {
    $aes_key = decryptKeyWithPassword($key_encrypted, $user_password);
    if (!$aes_key) {
        $error = "Failed to decrypt encryption key.";
    }
}

// Handle Password Generation form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["generate_password"])) {
    $length = intval($_POST["length"] ?? 8);
    $use_upper = isset($_POST["use_upper"]);
    $use_lower = isset($_POST["use_lower"]);
    $use_numbers = isset($_POST["use_numbers"]);
    $use_special = isset($_POST["use_special"]);

    $generator = new PasswordGenerator($length, $use_upper, $use_lower, $use_numbers, $use_special);
    $generated_password = $generator->generate();
}

// Handle Save Password form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_password"])) {
    $website = trim($_POST["website"]);
    $password_to_save = $_POST["password_to_save"];

    if (empty($website) || empty($password_to_save)) {
        $error = "Website and password cannot be empty.";
    } elseif (!$aes_key) {
        $error = "Cannot save password: encryption key unavailable.";
    } else {
        $passwordStorage = new PasswordStorage($conn, $aes_key, $user_id);
        if ($passwordStorage->savePassword($website, $password_to_save)) {
            $success = "Password saved successfully!";
        } else {
            $error = "Failed to save password.";
        }
    }
}

// Fetch saved passwords for user
$saved_passwords = [];
if ($aes_key) {
    $passwordStorage = new PasswordStorage($conn, $aes_key, $user_id);
    $saved_passwords = $passwordStorage->getPasswords();
}

?>

<!DOCTYPE html>
<html>
<head><title>Dashboard</title></head>
<body>
<h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

<?php if ($error): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<h3>Generate a Password</h3>
<form method="POST">
    Length:
    <input type="number" name="length" value="8" min="4" max="64" required><br><br>

    <label><input type="checkbox" name="use_upper" checked> Uppercase Letters</label><br>
    <label><input type="checkbox" name="use_lower" checked> Lowercase Letters</label><br>
    <label><input type="checkbox" name="use_numbers" checked> Numbers</label><br>
    <label><input type="checkbox" name="use_special" checked> Special Characters</label><br><br>

    <button type="submit" name="generate_password">Generate Password</button>
</form>

<?php if ($generated_password): ?>
    <h4>Generated Password:</h4>
    <p><strong><?php echo htmlspecialchars($generated_password); ?></strong></p>
<?php endif; ?>

<h3>Save a Password</h3>
<form method="POST">
    Website/Program Name:<br>
    <input type="text" name="website" required><br><br>
    Password:<br>
    <input type="text" name="password_to_save" value="<?php echo htmlspecialchars($generated_password); ?>" required><br><br>
    <button type="submit" name="save_password">Save Password</button>
</form>

<h3>Saved Passwords</h3>
<?php if (empty($saved_passwords)): ?>
    <p>No saved passwords yet.</p>
<?php else: ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Website/Program</th>
                <th>Password</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($saved_passwords as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['website_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['password']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p><a href="logout.php">Logout</a></p>
</body>
</html>
