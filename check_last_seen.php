<?php
require_once __DIR__ . "/backend/config.php";

echo "<h2>Last Seen Status Check</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Last Seen</th><th>Minutes Ago</th><th>Status</th></tr>";

$result = $conn->query("SELECT id, name, last_seen, TIMESTAMPDIFF(MINUTE, last_seen, NOW()) as minutes_ago FROM users ORDER BY last_seen DESC LIMIT 10");

while ($row = $result->fetch_assoc()) {
    $minutes = intval($row['minutes_ago']);
    $status = $minutes < 5 ? '<span style="color:green">Active now</span>' : '<span style="color:gray">Offline</span>';
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . $row['last_seen'] . "</td>";
    echo "<td>" . $minutes . " min</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<br><p><a href='chat.php?user=2'>Test Chat</a></p>";
?>
