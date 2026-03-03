<?php
$conn = new mysqli("127.0.0.1", "root", "", "finmanage_pro");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "--- JOBS TABLE ---\n";
$result = $conn->query("SELECT id, queue, payload, attempts, available_at, created_at FROM jobs ORDER BY id DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $payload = json_decode($row['payload'], true);
        $displayName = $payload['displayName'] ?? 'Unknown';
        echo "Job ID: {$row['id']} | Queue: {$row['queue']} | Name: {$displayName} | Attempts: {$row['attempts']}\n";
    }
} else {
    echo "Query error: " . $conn->error . "\n";
}

echo "\n--- FAILED JOBS TABLE ---\n";
$result2 = $conn->query("SELECT id, connection, queue, payload, exception, failed_at FROM failed_jobs ORDER BY id DESC LIMIT 5");
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
        $payload = json_decode($row['payload'], true);
        $displayName = $payload['displayName'] ?? 'Unknown';
        echo "Failed Job ID: {$row['id']} | Queue: {$row['queue']} | Name: {$displayName}\n";
        echo "Exception: " . substr($row['exception'], 0, 150) . "...\n\n";
    }
} else {
    echo "Query error: " . $conn->error . "\n";
}
$conn->close();
