<?php
require_once __DIR__ . "/includes/helpers.php";

// Test cases
$testNumbers = [
    50,
    500,
    999,
    1000,
    1500,
    5000,
    10000,
    50000,
    100000,
    500000,
    1000000,
    1500000,
    5000000,
    10000000,
    100000000,
    1000000000,
    5000000000
];

echo "<h1>Number Formatting Test</h1>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Original Number</th><th>English Format</th><th>Arabic Format</th></tr>";

foreach ($testNumbers as $num) {
    echo "<tr>";
    echo "<td>" . number_format($num) . "</td>";
    echo "<td>" . formatNumber($num) . "</td>";
    echo "<td>" . formatNumberAr($num) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Function Tests</h2>";
echo "<p>formatCoins(5000): " . formatCoins(5000) . "</p>";
echo "<p>formatCount(1500000): " . formatCount(1500000) . "</p>";
?>
