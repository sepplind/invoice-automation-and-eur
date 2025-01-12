<?php
$servername = "localhost";
$username = "abcdef";
$password = "123abc456";
$dbname = "123abc456";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
