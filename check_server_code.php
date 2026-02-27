<?php
$file = 'app/Services/EvolutionApiService.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $start = strpos($content, 'public function normalizeToNumeric');
    if ($start !== false) {
        echo "--- SERVER CODE CHECK ---\n";
        echo substr($content, $start, 300);
        echo "\n--- END CHECK ---\n";
    } else {
        echo "Method not found.\n";
    }
} else {
    echo "File not found.\n";
}
