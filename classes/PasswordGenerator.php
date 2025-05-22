<?php
// classes/PasswordGenerator.php

class PasswordGenerator {
    private $length;
    private $use_upper;
    private $use_lower;
    private $use_numbers;
    private $use_special;

    public function __construct($length = 8, $use_upper = true, $use_lower = true, $use_numbers = true, $use_special = true) {
        $this->length = $length;
        $this->use_upper = $use_upper;
        $this->use_lower = $use_lower;
        $this->use_numbers = $use_numbers;
        $this->use_special = $use_special;
    }

    public function generate() {
        $chars = '';
        if ($this->use_upper) {
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        if ($this->use_lower) {
            $chars .= 'abcdefghijklmnopqrstuvwxyz';
        }
        if ($this->use_numbers) {
            $chars .= '0123456789';
        }
        if ($this->use_special) {
            $chars .= '!@#$%^&*()-_=+[]{}|;:,.<>?';
        }

        if (empty($chars)) {
            return '';  // no chars selected
        }

        $password = '';
        $max_index = strlen($chars) - 1;

        for ($i = 0; $i < $this->length; $i++) {
            $index = random_int(0, $max_index);
            $password .= $chars[$index];
        }

        return $password;
    }
}
?>
