<?php
// classes/Database.php

class Database {
    private $host = "localhost";
    private $db_name = "php_oop_project";  // Change this to your DB name
    private $username = "root";            // Change to your MySQL username
    private $password = "";                // Change to your MySQL password
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            // Set error mode to exceptions
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Database connection error: " . $exception->getMessage();
            exit();
        }

        return $this->conn;
    }
}
?>
