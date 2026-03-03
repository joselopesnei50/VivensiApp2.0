<?php
$conn = new mysqli("127.0.0.1", "root", "", "finmanage_pro");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully\n";
$result = $conn->query("SELECT COUNT(*) as count FROM subscription_plans");
$row = $result->fetch_assoc();
echo "Plans: " . $row['count'] . "\n";
$conn->close();
