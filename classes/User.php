<?php
// classes/User.php

require_once __DIR__ . '/Database.php';

class User {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Signup user with username, password
    // Stores password hash and AES key encrypted with password
    public function signup($username, $password) {
        // Check if username exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            return ["success" => false, "message" => "Username already taken"];
        }

        // Hash the password for login
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Generate AES key (random 32 bytes)
        $key = openssl_random_pseudo_bytes(32);

        // Encrypt AES key with user's plain password
        $key_encrypted = $this->encryptKeyWithPassword($key, $password);

        // Insert user into DB
        $insert = "INSERT INTO " . $this->table_name . " (username, password_hash, key_encrypted) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($insert);
        if ($stmt->execute([$username, $password_hash, base64_encode($key_encrypted)])) {
            return ["success" => true];
        } else {
            return ["success" => false, "message" => "Signup failed"];
        }
    }

    // Encrypt AES key with user's plain password using AES-256-CBC
    private function encryptKeyWithPassword($key, $password) {
        $method = "AES-256-CBC";
        $iv = substr(hash('sha256', $password), 0, 16);
        return openssl_encrypt($key, $method, $password, OPENSSL_RAW_DATA, $iv);
    }
}
?>
