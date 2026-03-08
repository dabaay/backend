<?php
$dbFile = 'database/tenants/STORE001.sqlite';
if (!file_exists($dbFile)) {
    die("File not found: $dbFile\n");
}
$db = new PDO('sqlite:' . $dbFile);
$stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
if ($stmt) {
    echo "Tables in $dbFile:\n";
    while ($row = $stmt->fetch()) {
        echo "- " . $row['name'] . "\n";
    }
} else {
    echo "Query failed.\n";
}
