<?php
$conn = new mysqli("127.0.0.1", "root", "", "finmanage_pro");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully\n";
echo "--- PROCESSLIST ---\n";
$result = $conn->query("SHOW FULL PROCESSLIST");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
$conn->close();
