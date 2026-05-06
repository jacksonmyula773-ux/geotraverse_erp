<?php
// test_password.php

$password = 'admin1234';
$hashed = password_hash($password, PASSWORD_BCRYPT);

echo "Password: " . $password . "<br>";
echo "Hashed: " . $hashed . "<br><br>";

// Test verification
$storedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
if (password_verify($password, $storedHash)) {
    echo "✅ Password verification SUCCESS!";
} else {
    echo "❌ Password verification FAILED!";
}
?>