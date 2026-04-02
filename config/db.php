<?php
$conn = new mysqli("localhost", "root", "@Harika$$1305", "voting_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>