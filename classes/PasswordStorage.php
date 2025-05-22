<?php
// classes/PasswordStorage.php

class PasswordStorage {
    private $conn;
    private $aes_key;
    private $user_id;
    private $table_name = "passwords";

    public function __construct($conn, $aes_key, $user_id) {
        $this->conn = $conn;
        $this->aes_key = $aes_key;
        $this->user_id = $user_id;
    }

    // Encrypt password with AES key
    private function encryptPassword($password) {
        $method = "AES-256-CBC";
        // Use fixed IV for simplicity (ideally use random IV and store it alongside password)
        $iv = substr(hash('sha256', $this->aes_key), 0, 16);
        return openssl_encrypt($password, $method, $this->aes_key, OPENSSL_RAW_DATA, $iv);
    }

    // Decrypt password with AES key
    private function decryptPassword($encrypted_password) {
        $method = "AES-256-CBC";
        $iv = substr(hash('sha256', $this->aes_key), 0, 16);
        return openssl_decrypt($encrypted_password, $method, $this->aes_key, OPENSSL_RAW_DATA, $iv);
    }

    // Save password record (website, password)
    public function savePassword($website, $password) {
        $encrypted_password = $this->encryptPassword($password);
        $encrypted_password_base64 = base64_encode($encrypted_password);

        $query = "INSERT INTO {$this->table_name} (user_id, website_name, password_encrypted) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->user_id, $website, $encrypted_password_base64]);
    }

    // Get all passwords for user (decrypt before returning)
    public function getPasswords() {
        $query = "SELECT website_name, password_encrypted, created_at FROM {$this->table_name} WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->user_id]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decrypt passwords
        foreach ($rows as &$row) {
            $decoded = base64_decode($row['password_encrypted']);
            $row['password'] = $this->decryptPassword($decoded);
            unset($row['password_encrypted']);  // remove encrypted field for clean output
        }

        return $rows;
    }
}
?>
